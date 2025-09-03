<?php 
namespace Drupal\admin\Controller;
use Drupal\Core\Controller\ControllerBase;

class ReportsController extends ControllerBase {

    public function createReport(): array {
        return $this->formBuilder()->getForm(\Drupal\admin\Form\ReportCreateForm::class);
    }
    public function list(): array {
        return [
            '#title' => $this->t('Reports'),
            '#plain_text' => $this->t('Reports list goes here'),
        ];
    }

    public function view(string $id): array {
        return [
            '#title' => $this->t('Report @id', ['@id' => $id]),
            '#plain_text' => $this->t('Details for report @id', ['@id' => $id]),
        ];
    }

    public function memberCredit(): array {
        return [
            '#title' => $this->t('Member Credit Report'),
            '#plain_text' => $this->t('Details for the member credit report go here'),
        ];
    }

    public function creditRiskBureau(): array {
        return [
            '#title' => $this->t('Credit Risk Bureau Report'),
            '#plain_text' => $this->t('Details for the credit risk bureau report go here'),
        ];
    }

    public function generalReportViewer(): array {
        return [
            '#title' => $this->t('General Report Viewer'),
            '#plain_text' => $this->t('Details for the general report viewer go here'),
        ];
    }

    public function systemLoginReports(): array {
        return [
            '#title' => $this->t('System Login Reports'),
            '#plain_text' => $this->t('Details for the system login reports go here'),
        ];
    }
}