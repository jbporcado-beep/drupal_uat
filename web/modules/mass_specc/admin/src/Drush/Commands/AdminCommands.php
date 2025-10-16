<?php

namespace Drupal\admin\Drush\Commands;

use Drupal\admin\Service\CicReportGenerationService;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

final class AdminCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    private readonly CicReportGenerationService $cicReportGenerationService,
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'admin:generate-cic-report', aliases: ['gcr-run'])]
  #[CLI\Usage(name: 'admin:generate-cic-report', description: 'Generates and sends a CIC Report through FTPS')]
  public function generateCicReport() {
    date_default_timezone_set('Asia/Manila');
    $current_month_1st = new \DateTime('first day of this month');
    $current_month_1st->setTime(0, 0, 0);
    $start_date_str = $current_month_1st->format('Y-m-d H:i:s');

    $current_month_6th = new \DateTime('first day of this month');
    $current_month_6th->modify('+5 days');
    $current_month_6th->setTime(0, 0, 0);
    $end_date_str = $current_month_6th->format('Y-m-d H:i:s');

    $this->logger()->notice('Starting automated CIC report generation...');
    $this->logger()->notice("Consolidating data from $start_date_str to $end_date_str...");
    \Drupal::logger('Automated CIC Report Generation')->notice('Starting automated CIC report generation...');
    
    $this->cicReportGenerationService->create($current_month_1st, $current_month_6th, "Automated");

    $this->logger()->success('Automated CIC report generation completed.');
    \Drupal::logger('Automated CIC Report Generation')->notice('Automated CIC report generation completed.');
  }
}
