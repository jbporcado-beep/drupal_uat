<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\admin\Service\CicReportGenerationService;

class ManualCicGenerationModalForm extends FormBase {
    private CicReportGenerationService $cicReportGenerationService;

    public function __construct(
        CicReportGenerationService $cicReportGenerationService,
    ) {
        $this->cicReportGenerationService = $cicReportGenerationService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('admin.cic_report_generation_service'),
        );
    }

    public function getFormId() {
        return 'manual_cic_generation_modal_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/cic-report-generation';

        $form['message'] = [
            '#markup' => '
                <p class="modal-header"><strong>Manual Report Generation</strong></p>
                <p>Please select a date range of the data that will be included in the CIC generated report.</p>
            ',
        ];

        $form['date_range'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['date-range-container']],
        ];
        $form['date_range']['start_date'] = [
            '#type' => 'date',
            '#title' => 'Start Date',
            '#required' => TRUE,
        ];
        $form['date_range']['end_date'] = [
            '#type' => 'date',
            '#title' => 'End Date',
            '#required' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['buttons-container']],
        ];
        $form['actions']['cancel'] = [
            '#type' => 'button',
            '#value' => 'Cancel',
            '#ajax' => [
                'callback' => '::closeModal',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['cancel-button']],
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => 'Generate now',
            '#button_type' => 'primary',
            '#ajax' => [
                'callback' => '::closeModal',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['submit-button']],
        ];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        date_default_timezone_set('Asia/Manila');
        $start_date = new \DateTime($form_state->getValue('start_date'));
        $end_date = new \DateTime($form_state->getValue('end_date'));
        $str_start_date = $start_date->format('F j, Y');
        $str_end_date = $end_date->format('F j, Y');

        $this->cicReportGenerationService->create($start_date, $end_date);
    }

    public function closeModal(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $response->addCommand(new CloseModalDialogCommand());

        $response->addCommand(new RedirectCommand('/cic-report-generation'));

        return $response;
    }


}
