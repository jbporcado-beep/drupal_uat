<?php
namespace Drupal\admin\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

use Drupal\node\Entity\Node;

use Drupal\admin\Plugin\Validation\Constraint\AlphaNumericConstraintValidator;
use Drupal\admin\Service\ReportBuilderService;

use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;

class ReportBuilderForm extends FormBase
{
    protected $reportBuilderService;
    protected $currentUser;
    protected $activityLogger;
    public function __construct(ReportBuilderService $reportBuilderService, UserActivityLogger $activityLogger, AccountProxyInterface $currentUser)
    {
        $this->reportBuilderService = $reportBuilderService;
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('admin.report_builder'),
            $container->get('admin.user_activity_logger'),
            $container->get('current_user')
        );
    }

    public function getFormId()
    {
        return 'report_builder_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, ?int $id = NULL)
    {
        $form['#attached']['library'][] = 'admin/report_builder';
        $form['#prefix'] = '<div id="report-builder-wrapper">';
        $form['#suffix'] = '</div>';

        $node_types = \Drupal::entityTypeManager()
            ->getStorage('node_type')
            ->loadMultiple();

        $field_manager = \Drupal::service('entity_field.manager');
        $excluded_content_types = ['report_template', 'custom_fields', 'report'];
        $excluded_fields = [
            'nid',
            'uuid',
            'vid',
            'langcode',
            'status',
            'uid',
            'created',
            'changed',
            'promote',
            'sticky',
            'revision_translation_affected',
            'revision_default',
            'path',
            'revision_timestamp',
            'revision_log',
            'revision_uid',
            'title',
            'type',
            'default_langcode',
        ];

        $excluded_field_types = ['entity_reference', 'entity_reference_revisions'];

        $report_config = [
            'selected_fields' => [],
            'custom_fields' => [],
        ];

        $template_name = '';
        if ($id) {

            $node = $this->reportBuilderService->getTemplateById($id);

            if ($node) {
                $template_name = $node->label();
                $report_config = json_decode($node->get('field_report_config')->value, TRUE) ?? $report_config;
                $form_state->set('custom_fields', $report_config['custom_fields'] ?? []);

                $form_state->set('report_node_id', $node->id());
            }
        }

        $form['actions_top_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['report-builder-actions-top'],
                'style' => 'display:flex; justify-content:flex-end; margin-bottom:10px;',
            ],
        ];

        $form['actions_top_wrapper']['actions_top'] = [
            '#type' => 'actions',
        ];

        $form['actions_top_wrapper']['actions_top']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
            '#attributes' => [
                'class' => ['report-builder-save-button'],
                'style' => 'margin-left:auto;',
            ],
        ];

        $form['template_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Template Name'),
            '#required' => TRUE,
            '#default_value' => $template_name,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
            '#attributes' => [
                'class' => ['template-name-field'],
            ],
        ];

        $header = [
            'content_type' => $this->t('Content Type'),
            'select' => '',
            'field_name' => $this->t('Field Name'),
            'field_type' => $this->t('Data Type'),
            'tooltip' => $this->t('Tooltip'),
        ];

        $form['fields'] = [
            '#type' => 'table',
            '#header' => $header,
            '#empty' => $this->t('No fields found.'),
            '#attributes' => ['class' => ['report-builder-table']],
        ];

        $all_field_names = [];


        foreach ($node_types as $type_id => $type) {
            if (in_array($type_id, $excluded_content_types, true)) {
                continue;
            }

            $type_label = $type->label();
            $fields = $field_manager->getFieldDefinitions('node', $type_id);

            $form['fields'][$type_id . '_header'] = [
                'content_type' => [
                    '#markup' => Markup::create("<strong>$type_label</strong>"),
                ],
                'select' => [
                    '#type' => 'checkbox',
                    '#attributes' => ['class' => ['content-type-select-all'], 'data-type' => $type_id],
                ],
                'field_name' => ['#markup' => ''],
                'field_type' => ['#markup' => ''],
                'tooltip' => ['#markup' => ''],
            ];

            foreach ($fields as $field_name => $definition) {
                $storage_def = $definition->getFieldStorageDefinition();
                $field_type = $storage_def->getType();

                if (
                    ($storage_def->isBaseField() || strpos($field_name, 'field_') === 0)
                    && !in_array($field_name, $excluded_fields)
                    && !in_array($field_type, $excluded_field_types)
                ) {

                    $clean_field_name = preg_replace('/^field_/', '', $field_name);
                    if ($field_type === 'string') {
                        $field_type = 'text';
                    }

                    $checked = in_array($type_id . ':' . $field_name, $report_config['selected_fields'] ?? []);

                    $form['fields'][$type_id . ':' . $field_name] = [
                        'content_type' => ['#markup' => ''],
                        'select' => [
                            '#type' => 'checkbox',
                            '#default_value' => $checked,
                            '#attributes' => [
                                'class' => ['field-checkbox', 'field-checkbox-' . $type_id],
                                'data-type' => $type_id,
                                'title' => $definition->getLabel(),
                            ],
                        ],
                        'field_name' => ['#markup' => $clean_field_name],
                        'field_type' => ['#markup' => $field_type],
                        'tooltip' => [
                            '#markup' => !empty($definition->getDescription()) && strlen($definition->getDescription()) <= 20 ?
                                $definition->getDescription() : $definition->getLabel()
                        ]
                    ];

                    $all_field_names[] = $clean_field_name;
                }
            }
        }

        $form['fields']['custom_header'] = [
            'content_type' => ['#markup' => Markup::create('<strong>Custom</strong>')],
            'select' => [
                '#type' => 'checkbox',
                '#attributes' => ['class' => ['content-type-select-all'], 'data-type' => 'custom'],
            ],
            'field_name' => ['#markup' => ''],
            'field_type' => ['#markup' => ''],
            'tooltip' => ['#markup' => ''],
        ];

        $saved_custom_fields = $report_config['custom_fields'] ?? [];

        $all_field_definitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
        $allowed_primitive_types = ['text', 'integer', 'decimal', 'boolean', 'datetime', 'date'];
        $field_types = [];
        foreach ($all_field_definitions as $machine_name => $definition) {
            if (in_array($machine_name, $allowed_primitive_types, TRUE)) {
                $field_types[$machine_name] = $machine_name;
            }
        }

        $existing_custom_fields = $this->reportBuilderService->getCustomFields();

        foreach ($existing_custom_fields as $cf_node) {
            $name = $cf_node->get('field_field_name')->value;
            $tooltip = $cf_node->get('field_tooltip')->value;
            $type = $cf_node->get('field_type')->value;

            if (empty($name)) {
                continue;
            }

            $is_selected = false;
            foreach ($saved_custom_fields as $saved_cf) {
                if ($saved_cf['name'] === $name && $saved_cf['type'] === $type) {
                    $is_selected = $saved_cf['selected'];
                    break;
                }
            }

            $form['fields']['custom:' . $cf_node->id()] = [
                'content_type' => ['#markup' => ''],
                'select' => [
                    '#type' => 'checkbox',
                    '#default_value' => $is_selected,
                    '#attributes' => ['class' => ['field-checkbox', 'field-checkbox-custom'], 'data-type' => 'custom'],
                ],
                'field_name' => ['#markup' => $name],
                'field_type' => ['#markup' => $type],
                'tooltip' => ['#markup' => $tooltip],
            ];

            $all_field_names[] = $name;
        }

        $new_custom_fields = $form_state->get('new_custom_fields') ?? [];
        $submitted_fields = $form_state->getUserInput()['fields'] ?? [];

        foreach ($new_custom_fields as $index => &$custom_field) {
            $custom_key = "custom:$index";
            if (isset($submitted_fields[$custom_key]['select'])) {
                $custom_field['selected'] = !empty($submitted_fields[$custom_key]['select']);
            }

            $is_reusable = !empty($custom_field['_reusable']);

            $form['fields'][$custom_key] = [
                'content_type' => ['#markup' => ''],
                'select' => [
                    '#type' => 'checkbox',
                    '#default_value' => $custom_field['selected'] ?? FALSE,
                    '#attributes' => ['class' => ['field-checkbox', 'field-checkbox-custom'], 'data-type' => 'custom'],
                ],
                'field_name' => [
                    '#type' => 'textfield',
                    '#default_value' => $custom_field['name'],
                    '#placeholder' => $this->t('Field name'),
                    '#disabled' => $is_reusable,
                ],
                'field_type' => [
                    '#type' => 'select',
                    '#options' => $field_types,
                    '#default_value' => $custom_field['type'],
                    '#disabled' => $is_reusable,
                ],
                'tooltip' => [
                    '#type' => 'textfield',
                    '#default_value' => $custom_field['tooltip'],
                    '#placeholder' => $this->t('Tooltip'),
                ],
            ];

            if (!empty($custom_field['name'])) {
                $all_field_names[] = $custom_field['name'];
            }
        }

        $form_state->set('all_field_names', $all_field_names);
        $form_state->set('new_custom_fields', $new_custom_fields);

        $form['add_custom_field'] = [
            '#type' => 'submit',
            '#value' => $this->t('+ Add Custom Field'),
            '#submit' => ['::addCustomField'],
            '#ajax' => [
                'callback' => '::ajaxRebuildForm',
                'wrapper' => 'report-builder-wrapper',
                'effect' => 'fade',
            ],
            '#limit_validation_errors' => [],
            '#attributes' => [
                'class' => ['add-custom-field-btn'],
            ],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $template_name = $form_state->getValue('template_name');
        $editing_node_id = $form_state->get('report_node_id');

        $existing_node_id = $this->reportBuilderService->getIdByTemplateName($template_name);
        if ($existing_node_id && $existing_node_id != $editing_node_id) {
            $form_state->setErrorByName('template_name', $this->t('Report Template name already in use.'));
        }

        $submitted_fields = $form_state->getValue('fields') ?? [];
        $all_field_names = $form_state->get('all_field_names') ?? [];

        $custom_fields_saved = $form_state->get('custom_fields') ?? [];
        foreach ($custom_fields_saved as $cf) {
            if (!empty($cf['name'])) {
                $all_field_names[] = $cf['name'];
            }
        }
        $all_field_names = array_unique($all_field_names);

        $new_custom_field_names = [];

        foreach ($submitted_fields as $key => $row) {
            if (strpos($key, 'custom:') !== 0) {
                continue;
            }

            if (empty($row['select'])) {
                continue;
            }

            $field_name = trim($row['field_name'] ?? '');
            $field_type = trim($row['field_type'] ?? '');
            $tooltip = trim($row['tooltip'] ?? '');

            if ($field_name === '') {
                $form_state->setErrorByName("fields][$key][field_name", $this->t('Please provide a field name.'));
                continue;
            }

            if ($field_type === '') {
                $form_state->setErrorByName("fields][$key][field_type", $this->t('Please select a field type.'));
            }

            if ($tooltip === '') {
                $form_state->setErrorByName("fields][$key][tooltip", $this->t('Please provide a tooltip.'));
            }

            if (!preg_match('/^[a-z][a-z0-9_]*$/', $field_name)) {
                $form_state->setErrorByName("fields][$key][field_name", $this->t('Invalid format: lowercase letters, numbers, underscores, starting with a letter.'));
            }

            if (in_array($field_name, $new_custom_field_names, true)) {
                $form_state->setErrorByName("fields][$key][field_name", $this->t('This custom field name is already used in this template.'));
            } else {
                $new_custom_field_names[] = $field_name;
            }

            if (in_array($field_name, $all_field_names, true)) {
                $form_state->setErrorByName("fields][$key][field_name", $this->t('This field name conflicts with an existing field.'));
            }
        }
    }




    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $selected_fields = [];
        $submitted_fields = $form_state->getValue('fields') ?? [];
        $custom_fields = [];

        foreach ($submitted_fields as $key => $row) {
            if (!empty($row['select']) && strpos($key, '_header') === FALSE && strpos($key, 'custom:') !== 0) {
                $selected_fields[] = $key;
            }
        }

        $existing_custom_fields = $this->reportBuilderService->getCustomFields();
        foreach ($existing_custom_fields as $cf_node) {
            $name = $cf_node->get('field_field_name')->value;
            $tooltip = $cf_node->get('field_tooltip')->value;
            $type = $cf_node->get('field_type')->value;

            $key = 'custom:' . $cf_node->id();
            $selected = !empty($submitted_fields[$key]['select']);

            if (!$selected) {
                continue;
            }

            $custom_fields[] = [
                'name' => $name,
                'type' => $type,
                'tooltip' => $tooltip,
                'selected' => $selected,
            ];
        }
        $new_custom_fields = $form_state->get('new_custom_fields') ?? [];
        foreach ($new_custom_fields as $index => $custom_field) {
            $key = "custom:$index";

            $selected = !empty($submitted_fields[$key]['select']);
            if (!$selected) {
                continue;
            }

            $custom_fields[] = [
                'name' => $submitted_fields[$key]['field_name'] ?? $custom_field['name'],
                'type' => $submitted_fields[$key]['field_type'] ?? $custom_field['type'],
                'tooltip' => $submitted_fields[$key]['tooltip'] ?? $custom_field['tooltip'],
                'selected' => $selected,
            ];
        }

        if (empty($selected_fields) && empty($custom_fields)) {
            \Drupal::messenger()->addWarning($this->t('No fields selected.'));
            return;
        }

        if (!empty($custom_fields)) {
            $this->reportBuilderService->saveCustomFields($custom_fields);
        }

        $report_config = [
            'selected_fields' => $selected_fields,
            'custom_fields' => $custom_fields,
        ];

        $node_values = [
            'type' => 'report_template',
            'title' => $form_state->getValue('template_name') ?: 'Untitled Report',
            'field_report_config' => json_encode($report_config, JSON_PRETTY_PRINT),
            'status' => 1,
        ];

        $node_id = $form_state->get('report_node_id');

        if ($node_id) {
            $node = Node::load($node_id);
            if ($node) {
                $node->setTitle($form_state->getValue('template_name') ?: 'Untitled Report');
                $node->set('field_report_config', json_encode($report_config, JSON_PRETTY_PRINT));
                $action = 'Updated report builder template ' . $node->getTitle();
            }
        } else {
            $node_values = [
                'type' => 'report_template',
                'title' => $form_state->getValue('template_name') ?: 'Untitled Report',
                'field_report_config' => json_encode($report_config, JSON_PRETTY_PRINT),
                'status' => 1,
            ];
            $node = Node::create($node_values);

            $action = 'Created report builder template ' . $node->getTitle();
        }
        $node->save();

        $this->activityLogger->log($action);

        $form_state->setRedirect('report-builder.list');
    }
    public function addCustomField(array &$form, FormStateInterface $form_state)
    {
        $custom_fields = $form_state->get('new_custom_fields') ?? [];
        $custom_fields[] = [
            'name' => '',
            'type' => '',
            'tooltip' => '',
            '_reusable' => FALSE,
        ];
        $form_state->set('new_custom_fields', $custom_fields);
        $form_state->setRebuild(TRUE);
    }
    public function ajaxRebuildForm(array &$form, FormStateInterface $form_state)
    {
        return $form;
    }
}
