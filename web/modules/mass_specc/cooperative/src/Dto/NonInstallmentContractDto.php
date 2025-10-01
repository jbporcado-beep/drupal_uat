<?php

namespace Drupal\cooperative\Dto;

use Drupal\node\Entity\Node;

class NonInstallmentContractDto {
    public function __construct(
        public readonly ?Node $header,                        
        public readonly ?Node $subject,     
        public readonly string $providerContractNo,          
        public readonly string $contractEndActualDate,      
        public readonly string $contractEndPlannedDate,     
        public readonly string $contractPhase,                
        public readonly string $contractStartDate,           
        public readonly string $contractType, 
        public readonly string $creditLimit,                
        public readonly string $currency,     
        public readonly string $originalCurrency,             
        public readonly string $outstandingBalance,           
        public readonly string $overduePaymentsAmount,       
        public readonly string $role,                          
        public readonly string $transactionType     
    ) {}
}
?>