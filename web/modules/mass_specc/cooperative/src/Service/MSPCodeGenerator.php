<?php

namespace Drupal\cooperative\Service;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;

class MSPCodeGenerator
{

    protected $database;

    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * Generate the next code for a member ([I||C]########).
     */
    public function generateMemberCode(string $prefix = 'I'): string
    {
        $query = $this->database->select('node__field_msp_subject_code', 'm')
            ->fields('m', ['field_msp_subject_code_value'])
            ->condition('m.field_msp_subject_code_value', $prefix . '%', 'LIKE')
            ->orderBy('m.field_msp_subject_code_value', 'DESC')
            ->range(0, 1);

        $last_code = $query->execute()->fetchField();

        $next_number = 1;
        if ($last_code) {
            $next_number = intval(substr($last_code, 1)) + 1;
        }

        return $prefix . str_pad($next_number, 8, '0', STR_PAD_LEFT);
    }



    /**
     * Generate the next code for a contract (X########).
     */
    public function generateContractCode(): string
    {
        $maxRetries = 5;
        $counter = 0;

        $query = $this->database->select('node__field_msp_contract_code', 'c')
            ->fields('c', ['field_msp_contract_code_value'])
            ->orderBy('c.field_msp_contract_code_value', 'DESC');
        $all_codes = $query->execute()->fetchCol();

        $max_number = 0;
        foreach ($all_codes as $code) {
            $number = intval(substr($code, 1));
            if ($number > $max_number) {
                $max_number = $number;
            }
        }

        $next_number = $max_number + 1;

        do {
            $prefix = strtoupper(bin2hex(random_bytes(1)))[0];

            $new_code = $prefix . str_pad($next_number, 8, '0', STR_PAD_LEFT);

            $exists = $this->database->select('node__field_msp_contract_code', 'c')
                ->fields('c', ['field_msp_contract_code_value'])
                ->condition('c.field_msp_contract_code_value', $new_code)
                ->execute()
                ->fetchField();

            $counter++;
        } while ($exists && $counter < $maxRetries);

        if ($exists) {
            $new_code = strtoupper(bin2hex(random_bytes(2))) . str_pad($next_number, 8, '0', STR_PAD_LEFT);
        }

        return $new_code;
    }


    public function generateBranchCode(): string
    {
        $yearPrefix = date('y');

        $last_code = \Drupal::database()->select('node__field_branch_code', 'b')
            ->fields('b', ['field_branch_code_value'])
            ->condition('b.field_branch_code_value', $yearPrefix . '%', 'LIKE')
            ->orderBy('field_branch_code_value', 'DESC')
            ->range(0, 1)
            ->execute()
            ->fetchField();

        $next = $last_code ? ((int) substr($last_code, 2)) + 1 : 1;

        $new_code = $yearPrefix . str_pad($next, 3, '0', STR_PAD_LEFT);

        $exists = \Drupal::database()->select('node__field_branch_code', 'b')
            ->condition('b.field_branch_code_value', $new_code)
            ->range(0, 1)
            ->countQuery()
            ->execute()
            ->fetchField();

        if ($exists) {
            return $this->generateBranchCode();
        }

        return $new_code;
    }


}
