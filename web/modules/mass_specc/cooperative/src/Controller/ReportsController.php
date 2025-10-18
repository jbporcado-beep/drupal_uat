<?php
namespace Drupal\cooperative\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class ReportsController extends ControllerBase
{

    public function list(): array
    {
        return [
            '#title' => $this->t('Reports'),
            '#plain_text' => $this->t('Reports list goes here'),
        ];
    }

    public function view(string $id): array
    {
        return [
            '#title' => $this->t('Report @id', ['@id' => $id]),
            '#plain_text' => $this->t('Details for report @id', ['@id' => $id]),
        ];
    }

    public function downloadReport($nid, $format = 'Letter', $orientation = 'P')
    {
        $service = \Drupal::service('cooperative.member_credit_service');
        $pdfData = $service->generateMemberReportPdfContent($nid, $format, $orientation);

        if (empty($pdfData)) {
            return new Response('Invalid or missing member node.', 404);
        }

        return new Response($pdfData['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdfData['filename'] . '"',
            'Content-Length' => strlen($pdfData['content']),
        ]);
    }

}