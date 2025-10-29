<?php

namespace Drupal\admin\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReportBuilderService
{

    protected $entityTypeManager;

    protected $currentUser;
    protected $activityLogger;

    public function __construct(EntityTypeManagerInterface $entityTypeManager, UserActivityLogger $activityLogger, AccountProxyInterface $currentUser)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('admin.user_activity_logger'),
            $container->get('current_user')
        );
    }
    public function downloadReportTemplate(int $id): array
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $node = $storage->load($id);
        if (!$node) {
            throw new \InvalidArgumentException("Node $id not found.");
        }

        $config_json = $node->get('field_report_config')->value ?? '{}';
        $config = json_decode($config_json, TRUE);

        $fields = $config['selected_fields'] ?? [];
        $custom_fields = $config['custom_fields'] ?? [];

        $headers = [];
        $tooltips = [];

        foreach ($fields as $field_id) {
            [$type_id, $field_name] = explode(':', $field_id);
            $field_definitions = \Drupal::service('entity_field.manager')
                ->getFieldDefinitions('node', $type_id);

            $field_def = $field_definitions[$field_name] ?? NULL;
            $headers[] = preg_replace('/^field_/', '', $field_name);
            if ($field_def) {
                $desc = $field_def->getDescription();
                $tooltip = (!empty($desc) && strlen($desc) <= 20) ? $desc : $field_def->getLabel();
            } else {
                $tooltip = $field_name;
            }
            $tooltips[] = $tooltip;
        }

        foreach ($custom_fields as $cf) {
            if (!empty($cf['selected'])) {
                $headers[] = $cf['name'];
                $tooltips[] = $cf['tooltip'] ?? $cf['name'];
            }
        }

        $fh = fopen('php://temp', 'w');
        fputcsv($fh, $headers);
        fputcsv($fh, $tooltips);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $timestamp = date('Ymd_His');

        $filename = preg_replace(
            '/[^a-zA-Z0-9_\-]/',
            '_',
            $node->label()
        ) . "-$timestamp.csv";

        return [
            'csv' => $csv,
            'filename' => $filename,
        ];
    }
    public function getCustomFields(): array
    {
        $custom_field_storage = $this->entityTypeManager->getStorage('node');
        return $custom_field_storage->loadByProperties([
            'type' => 'custom_fields',
            'status' => 1,
        ]);
    }

    public function saveCustomFields(array $fields): void
    {
        $storage = $this->entityTypeManager->getStorage('node');

        foreach ($fields as $field) {
            if (empty($field['name']) || empty($field['type'])) {
                continue;
            }

            $existing = $storage->loadByProperties([
                'type' => 'custom_fields',
                'title' => $field['name'],
            ]);

            if (!empty($existing)) {
                continue;
            }

            $node = $storage->create([
                'type' => 'custom_fields',
                'title' => $field['name'],
                'field_field_name' => $field['name'],
                'field_tooltip' => $field['tooltip'] ?? '',
                'field_type' => $field['type'],
                'status' => 1,
            ]);

            $node->save();

            $data = [
                'changed_fields' => [],
                'performed_by_name' => $this->currentUser->getDisplayName(),
            ];
            $action = 'Created custom field ' . $field['name'] . ' in Report Builder';
            $this->activityLogger->log($action, 'node', $node->id(), $data, NULL, $this->currentUser);

        }
    }

    public function getTemplateById(int $id): Node
    {
        return Node::load($id);
    }

    public function getIdByTemplateName(string $name): ?int
    {
        $nids = $this->entityTypeManager
            ->getStorage('node')
            ->getQuery()
            ->condition('title', $name)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        return $nids ? reset($nids) : null;
    }

}
