<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\InstallmentContractDto;

class InstallmentContractRepository {
    public function save(InstallmentContractDto $dto): Node {
        $values = [
            'type' => 'installment_contract',
            'title' => "[" . $dto->header->get('field_provider_code')->value . "- $dto->providerContractNo] Installment Contract",
            'status' => 1,
            'field_header'                      => $dto->header,
            'field_subject'                     => $dto->subject,
            'field_provider_contract_no'        => $dto->providerContractNo,
            'field_contract_end_actual_date'    => $dto->contractEndActualDate,
            'field_contract_end_planned_date'   => $dto->contractEndPlannedDate,
            'field_contract_phase'              => $dto->contractPhase,
            'field_contract_start_date'         => $dto->contractStartDate,
            'field_contract_type'               => $dto->contractType,
            'field_currency'                    => $dto->currency,
            'field_financed_amount'             => $dto->financedAmount,
            'field_installments_no'             => $dto->installmentsNumber,
            'field_last_payment_amount'         => $dto->lastPaymentAmount,
            'field_monthly_payment_amount'      => $dto->monthlyPaymentAmount,
            'field_next_payment_date'           => $dto->nextPaymentDate,
            'field_original_currency'           => $dto->originalCurrency,
            'field_outstanding_balance'         => $dto->outstandingBalance,
            'field_outstanding_payment_no'      => $dto->outstandingPaymentsNumber,
            'field_overdue_days'                => $dto->overdueDays,
            'field_overdue_payments_amount'     => $dto->overduePaymentsAmount,
            'field_overdue_payments_number'     => $dto->overduePaymentsNumber,
            'field_payment_periodicity'         => $dto->paymentPeriodicity,
            'field_role'                        => $dto->role,
            'field_transaction_type'            => $dto->transactionType
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }


    public function update(InstallmentContractDto $dto, Node $existingContract) {
        $existingContract->set('field_header', $dto->header);
        $existingContract->set('field_subject', $dto->subject);
        $existingContract->set('field_contract_end_actual_date', $dto->contractEndActualDate);
        $existingContract->set('field_contract_end_planned_date', $dto->contractEndPlannedDate);
        $existingContract->set('field_contract_phase', $dto->contractPhase);
        $existingContract->set('field_contract_start_date', $dto->contractStartDate);
        $existingContract->set('field_contract_type', $dto->contractType);
        $existingContract->set('field_currency', $dto->currency);
        $existingContract->set('field_financed_amount', $dto->financedAmount);
        $existingContract->set('field_installments_no', $dto->installmentsNumber);
        $existingContract->set('field_last_payment_amount', $dto->lastPaymentAmount);
        $existingContract->set('field_monthly_payment_amount', $dto->monthlyPaymentAmount);
        $existingContract->set('field_next_payment_date', $dto->nextPaymentDate);
        $existingContract->set('field_original_currency', $dto->originalCurrency);
        $existingContract->set('field_outstanding_balance', $dto->outstandingBalance);
        $existingContract->set('field_outstanding_payment_no', $dto->outstandingPaymentsNumber);
        $existingContract->set('field_overdue_days', $dto->overdueDays);
        $existingContract->set('field_overdue_payments_amount', $dto->overduePaymentsAmount);
        $existingContract->set('field_overdue_payments_number', $dto->overduePaymentsNumber);
        $existingContract->set('field_payment_periodicity', $dto->paymentPeriodicity);
        $existingContract->set('field_role', $dto->role);
        $existingContract->set('field_transaction_type', $dto->transactionType);
        $existingContract->save();
    }
    public function findByCodes(
        ?string $providerCode, ?string $providerContractNo, ?string $branchCode
    ): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'installment_contract')
        ->condition('field_subject.entity.field_provider_code', $providerCode);

        if (!empty($branchCode)) {
            $query->condition('field_subject.entity.field_branch_code', $branchCode);
        }
        
        $query->condition('field_provider_contract_no', $providerContractNo)
            ->accessCheck(TRUE)
            ->range(0, 1);
        $result = $query->execute();
        if (!empty($result)) {
            $nid = reset($result);
            $node = Node::load($nid);
            return $node;
        }
        return null;
    }
    public function findAllByHeader(?int $header_nid): array {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'installment_contract')
        ->condition('field_header.target_id', $header_nid)
        ->accessCheck(TRUE);

        $nids = $query->execute();
        return $nids;
    }
}
?>