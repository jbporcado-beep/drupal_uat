<?php

namespace Drupal\cooperative\Service;

use Drupal\file\FileInterface;
use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Repository\FileHistoryRepository;
use Drupal\cooperative\Repository\headerRepository;

class FileHistoryService {
    private FileHistoryRepository $fileHistoryRepository;
    private HeaderRepository $headerRepository;

    public function __construct(
        FileHistoryRepository $fileHistoryRepository,
        HeaderRepository $headerRepository
    ) {
        $this->fileHistoryRepository = $fileHistoryRepository;
        $this->headerRepository = $headerRepository;
    }

    public function create(FileInterface $file, array $row) {
        $provider_code  = trim((string) ($row['provider code'] ?? ''));
        $branch_code    = trim((string) ($row['branch code'] ?? ''));
        $reference_date = trim((string) ($row['reference date'] ?? ''));

        $header_node = $this->headerRepository
            ->findByCodesAndDate($provider_code, $reference_date, $branch_code);
        
        if ($header_node !== null) {
            $this->fileHistoryRepository->save($file, $header_node);
        }
    }
}
?>