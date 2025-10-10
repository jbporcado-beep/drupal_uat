<?php

namespace Drupal\admin\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Utility\Html;
use Drupal;

/**
 * Field handler to show cooperative name changes.
 *
 * @ViewsField("changed_summary")
 */
class ChangedSummary extends FieldPluginBase
{

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $this->ensureMyTable();
        $this->addAdditionalFields(['data', 'entity_type']);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ResultRow $values)
    {
        $data = $this->getValue($values, 'data');
        $entity_type = $this->getValue($values, 'entity_type');

        if (empty($data)) {
            return '';
        }

        $decoded = json_decode($data, TRUE);
        if (empty($decoded) || !is_array($decoded)) {
            return '';
        }

        // Focus only on the cooperative name field.
        if (empty($decoded['field_coop_name'])) {
            return '';
        }

        $changes = $decoded['field_coop_name'];

        $old_value = $this->extractReadableValue($changes['old'] ?? []);
        $new_value = $this->extractReadableValue($changes['new'] ?? []);

        // Skip identical values (no actual change).
        if ($old_value === $new_value) {
            return '';
        }

        // Get human-readable label (e.g., "Name" or "Cooperative Name")
        $label = $this->getFieldLabel($entity_type, 'field_coop_name');

        $markup = sprintf(
            '<strong>%s</strong><br>from: <em>%s</em><br>to: <em>%s</em>',
            Html::escape($label),
            Html::escape($old_value),
            Html::escape($new_value)
        );

        return ['#markup' => $markup];
    }

    /**
     * Extract readable values from Drupal field array.
     */
    protected function extractReadableValue($data)
    {
        if (is_array($data) && isset($data[0])) {
            $first = $data[0];
            if (isset($first['value'])) {
                return $first['value'];
            }
            return json_encode($first);
        }
        if (is_scalar($data)) {
            return (string) $data;
        }
        return json_encode($data);
    }

    /**
     * Get human-readable field label from Drupal field definitions.
     */
    protected function getFieldLabel($entity_type, $field_name)
    {
        try {
            $definitions = Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $entity_type);
            if (isset($definitions[$field_name])) {
                return $definitions[$field_name]->getLabel();
            }
        } catch (\Exception $e) {
            // Fallback on error.
        }

        // Default to simple prettified name.
        return ucwords(str_replace('_', ' ', $field_name));
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(ResultRow $row, $field = NULL)
    {
        if (isset($field)) {
            $alias = $this->aliases[$field] ?? $field;
            if (isset($row->{$alias})) {
                return $row->{$alias};
            }
        }
        return parent::getValue($row, $field);
    }

}
