<?php

namespace Drupal\cooperative\Utility;

class FieldMaps {
  public const HEADER_FIELD_MAP = [
    'provider code'           => 'field_provider_code',
    'branch code'             => 'field_header_branch_code',
    'reference date'          => 'field_reference_date',
  ];
  public const INDIVIDUAL_FIELD_MAP = [
    'provider subject no'     => 'field_provider_subject_no',
    'provider code'           => 'field_provider_code',
    'branch code'             => 'field_branch_code',
    'family'                  => 'field_family',
    'address'                 => 'field_address',
    'identification'          => 'field_identification',
    'contact'                 => 'field_contact',
    'employment'              => 'field_employment',
    'title'                   => 'field_title',
    'first name'              => 'field_first_name',
    'last name'               => 'field_last_name',
    'middle name'             => 'field_middle_name',
    'suffix'                  => 'field_suffix',
    'previous last name'      => 'field_previous_last_name',
    'gender'                  => 'field_gender',
    'date of birth'           => 'field_date_of_birth',
    'place of birth'          => 'field_place_of_birth',
    'country of birth code'   => 'field_country_of_birth_code',
    'nationality'             => 'field_nationality',
    'resident'                => 'field_resident',
    'civil status'            => 'field_civil_status',
    'number of dependents'    => 'field_number_of_dependents',
    'cars owned'              => 'field_cars_owned',
  ];
  public const FAMILY_FIELD_MAP = [
    'spouse first name'       => 'field_spouse_first_name',
    'spouse last name'        => 'field_spouse_last_name',
    'spouse middle name'      => 'field_spouse_middle_name',
    'mother maiden full name' => 'field_mother_maiden_full_name',
    'father first name'       => 'field_father_first_name',
    'father last name'        => 'field_father_last_name',
    'father middle name'      => 'field_father_middle_name',
    'father suffix'           => 'field_father_suffix',
  ];
  public const ADDRESS_FIELD_MAP = [
    'address 1 address type'  => 'field_address1_type',
    'address 1 fulladdress'   => 'field_address1_fulladdress',
    'address 2 address type'  => 'field_address2_type',
    'address 2 fulladdress'   => 'field_address2_fulladdress',
  ];
  public const IDENTIFICATION_FIELD_MAP = [
    'identification 1 type'   => 'field_identification1_type',
    'identification 1 number' => 'field_identification1_number',
    'identification 2 type'   => 'field_identification2_type',
    'identification 2 number' => 'field_identification2_number',
    'id 1 type'               => 'field_id1_type',
    'id 1 number'             => 'field_id1_number',
    'id 1 issuedate'          => 'field_id1_issuedate',
    'id 1 issuecountry'       => 'field_id1_issuecountry',
    'id 1 expirydate'         => 'field_id1_expirydate',
    'id 1 issued by'          => 'field_id1_issuedby',
    'id 2 type'               => 'field_id2_type',
    'id 2 number'             => 'field_id2_number',
    'id 2 issuedate'          => 'field_id2_issuedate',
    'id 2 issuecountry'       => 'field_id2_issuecountry',
    'id 2 expirydate'         => 'field_id2_expirydate',
    'id 2 issued by'          => 'field_id2_issuedby',
  ];
  public const CONTACT_FIELD_MAP = [
    'contact 1 type'          => 'field_contact1_type',
    'contact 1 value'         => 'field_contact1_value',
    'contact 2 type'          => 'field_contact2_type',
    'contact 2 value'         => 'field_contact2_value',
  ];
  public const EMPLOYMENT_FIELD_MAP = [
    'employment trade name'       => 'field_employ_trade_name',
    'employment psic'             => 'field_employ_psic',
    'employment occupationstatus' => 'field_employ_occupation_status',
    'employment occupation'       => 'field_employ_occupation',
  ];
  public const INSTALLMENT_FIELD_MAP = [
    'provider contract no'          => 'field_provider_contract_no',
    'header'                        => 'field_header',
    'subject'                       => 'field_subject',
    'contract end actual date'      => 'field_contract_end_actual_date',
    'contract end planned date'     => 'field_contract_end_planned_date',
    'contract phase'                => 'field_contract_phase',
    'contract start date'           => 'field_contract_start_date',
    'contract type'                 => 'field_contract_type',
    'currency'                      => 'field_currency',
    'financed amount'               => 'field_financed_amount',
    'installments number'           => 'field_installments_no',
    'last payment amount'           => 'field_last_payment_amount',
    'monthly payment amount'        => 'field_monthly_payment_amount',
    'next payment date'             => 'field_next_payment_date',
    'original currency'             => 'field_original_currency',
    'outstanding balance'           => 'field_outstanding_balance',
    'outstanding payments number'   => 'field_outstanding_payment_no',
    'overdue days'                  => 'field_overdue_days',
    'overdue payments amount'       => 'field_overdue_payments_amount',
    'overdue payments number'       => 'field_overdue_payments_number',
    'payment periodicity'           => 'field_payment_periodicity',
    'role'                          => 'field_role',
    'transaction type sub facility' => 'field_transaction_type'
  ];
  public const COMPANY_FIELD_MAP = [
    'provider subject no'  => 'field_provider_subject_no',
    'trade name'           => 'field_trade_name',
    'provider code'        => 'field_provider_code',
    'branch code'          => 'field_branch_code',
    'address'              => 'field_address',
    'identification'       => 'field_identification',
    'contact'              => 'field_contact',
  ];
  public const NONINSTALLMENT_FIELD_MAP = [
    'provider contract no'          => 'field_provider_contract_no',
    'header'                        => 'field_header',
    'contract end actual date'      => 'field_contract_end_actual_date',
    'contract end planned date'     => 'field_contract_end_planned_date',
    'contract phase'                => 'field_contract_phase',
    'contract start date'           => 'field_contract_start_date',
    'contract type'                 => 'field_contract_type',
    'credit limit'                  => 'field_credit_limit',
    'currency'                      => 'field_currency',
    'original currency'             => 'field_original_currency',
    'outstanding balance'           => 'field_outstanding_balance',
    'overdue payments amount'       => 'field_overdue_payments_amount',
    'role'                          => 'field_role',
    'transaction type sub facility' => 'field_transaction_type'
  ];
}
