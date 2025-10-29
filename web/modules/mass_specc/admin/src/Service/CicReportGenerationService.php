<?php

namespace Drupal\admin\Service;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\user\Entity\User;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;


use Drupal\cooperative\Repository\FileHistoryRepository;
use Drupal\cooperative\Repository\InstallmentContractRepository;
use Drupal\cooperative\Repository\NonInstallmentContractRepository;

use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;

class CicReportGenerationService
{
    private InstallmentContractRepository $installmentContractRepository;
    private NonInstallmentContractRepository $nonInstallmentContractRepository;
    private FileHistoryRepository $fileHistoryRepository;
    protected $currentUser;
    protected $activityLogger;

    public function __construct(
        InstallmentContractRepository $installmentContractRepository,
        NonInstallmentContractRepository $nonInstallmentContractRepository,
        FileHistoryRepository $fileHistoryRepository,
        UserActivityLogger $activityLogger,
        AccountProxyInterface $currentUser
    ) {
        $this->installmentContractRepository = $installmentContractRepository;
        $this->nonInstallmentContractRepository = $nonInstallmentContractRepository;
        $this->fileHistoryRepository = $fileHistoryRepository;
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }
    public function create(\DateTime $start_date, \DateTime $end_date, string $generationType)
    {
        $header_nids_by_coop_nids = $this->fileHistoryRepository->findHeadersByCoopApprovedAndBetweenDates($start_date, $end_date);
        $failed_coop_uploads = [];

        if (empty($header_nids_by_coop_nids)) {
            \Drupal::messenger()->addError('No approved file uploads found for the specified date range.');
            return;
        }

        foreach ($header_nids_by_coop_nids as $coop_nid => $header_nids) {
            $id_array = [];
            $bd_array = [];
            $ci_array = [];
            $cn_array = [];
            $added_subjs = [];
            $coop_node = Node::load($coop_nid);
            $provider_code = $coop_node->get('field_cic_provider_code')->value;
            $ftps_username = $coop_node->get('field_ftps_username')->value;
            $ftps_password = $coop_node->get('field_ftps_password')->value;

            if (empty($ftps_username) || empty($ftps_password)) {
                \Drupal::logger('FTPS')->error('@coop has incomplete FTPS credentials', ['@coop' => $provider_code]);
                \Drupal::messenger()->addError("Incomplete FTPS credentials for $provider_code");
                $failed_coop_uploads[] = $provider_code;
                continue;
            }

            $hd_ft_reference_date = date('dmY');
            foreach ($header_nids as $header_nid) {
                $header_node = '';
                if ($header_nid) {
                    $header_node = Node::load($header_nid);
                }
                $reference_date = $header_node->get('field_reference_date')->value ?? '';
                $ci_nids = $this->installmentContractRepository->findAllByHeader($header_nid);
                $cn_nids = $this->nonInstallmentContractRepository->findAllByHeader($header_nid);
                foreach ($ci_nids as $ci_nid) {
                    $ci_node = Node::load($ci_nid);
                    if ($ci_node) {
                        $ci_string = $this->generateCiString($ci_node);
                        $ci_array[] = $ci_string;

                        $subject_node = $ci_node->get('field_subject')->entity;

                        if ($subject_node) {
                            if (isset($added_subjs[$subject_node->id()])) {
                                continue;
                            }

                            $content_type = $subject_node->bundle();
                            if ($content_type == 'individual') {
                                $id_string = $this->generateIndividualString($subject_node, $reference_date);
                                $id_array[] = $id_string;
                            } else if ($content_type == 'company') {
                                $bd_string = $this->generateCompanyString($subject_node, $reference_date);
                                $bd_array[] = $bd_string;
                            }
                            $added_subjs[$subject_node->id()] = TRUE;
                        }
                    }
                }

                foreach ($cn_nids as $cn_nid) {
                    $cn_node = Node::load($cn_nid);
                    if ($cn_node) {
                        $cn_string = $this->generateCnString($cn_node);
                        $cn_array[] = $cn_string;

                        $subject_node = $cn_node->get('field_subject')->entity;

                        if ($subject_node) {
                            if (isset($added_subjs[$subject_node->id()])) {
                                continue;
                            }
                            $content_type = $subject_node->bundle();
                            if ($content_type == 'individual') {
                                $id_string = $this->generateIndividualString($subject_node, $reference_date);
                                $id_array[] = $id_string;
                            } else if ($content_type == 'company') {
                                $bd_string = $this->generateCompanyString($subject_node, $reference_date);
                                $bd_array[] = $bd_string;
                            }
                            $added_subjs[$subject_node->id()] = TRUE;
                        }
                    }
                }
            }
            $num_of_records = count($id_array) + count($bd_array) + count($ci_array) + count($cn_array);
            $header_string = $this->generateHeaderString($provider_code, $hd_ft_reference_date);
            $footer_string = $this->generateFooterString($provider_code, $hd_ft_reference_date, $num_of_records);
            $all_arrays = array_merge([$header_string], $id_array, $bd_array, $ci_array, $cn_array, [$footer_string]);
            $output = implode(PHP_EOL, $all_arrays);

            $utf8_data = mb_convert_encoding($output, 'UTF-8');
            $datetime = date('YmdHis');
            $file_system = \Drupal::service('file_system');
            $public_dir = "public://cic-reports";
            $file_system->prepareDirectory($public_dir, FileSystemInterface::CREATE_DIRECTORY);

            $filename = "{$provider_code}_CSDF_{$datetime}";
            $text_uri = "{$public_dir}/{$filename}.txt";
            $zip_uri = "{$public_dir}/{$filename}.zip";
            $encrypted_uri = "{$public_dir}/{$filename}.zip.gpg";

            $text_file = $file_system->realpath($text_uri);
            $zip_file = $file_system->realpath($zip_uri);
            $encrypted_file = $file_system->realpath($encrypted_uri);
            file_put_contents($text_file, $utf8_data);

            $zip = new \ZipArchive();
            if ($zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                $zip->addFile($text_file, basename($text_file));
                $zip->close();
            } else {
                \Drupal::logger('zip')->error('Failed to create zip archive.');
                \Drupal::messenger()->addError("Failed to zip the file for $provider_code");
                @unlink($text_file);
                return FALSE;
            }

            @unlink($text_file);

            $recipient = $_ENV['GPG_RECIPIENT_KEY'];
            $command_array = [
                'gpg',
                '--batch',
                '--yes',
                '--trust-model',
                'always',
                '--output',
                escapeshellarg($encrypted_file),
                '--recipient',
                escapeshellarg($recipient),
                '--encrypt',
                escapeshellarg($zip_file)
            ];
            $command = implode(' ', $command_array) . ' 2>&1';

            $output = [];
            $gpg_status = '';

            exec($command, $output, $gpg_status);

            if ($gpg_status !== 0) {
                \Drupal::logger('encryption')->error('GPG encryption failed. @output', ['@output' => implode("\n", $output)]);
                \Drupal::messenger()->addError('Failed to encrypt file due to an unexpected error');
                @unlink($zip_file);
                return FALSE;
            }

            $ftps_success = $this->upload_file_via_ftps($encrypted_file, $ftps_username, $ftps_password, $provider_code);

            if ($ftps_success) {
                @unlink($encrypted_file);
            } else {
                @unlink($encrypted_file);
                @unlink($zip_file);
                $failed_coop_uploads[] = $provider_code;
                continue;
            }

            $file_storage = \Drupal::entityTypeManager()->getStorage('file');
            $file = $file_storage->loadByProperties(['uri' => $zip_uri]);
            $file = $file ? reset($file) : NULL;
            if (!$file) {
                $file = File::create([
                    'uri' => $zip_uri,
                ]);
                $file->setPermanent();
                $file->save();
            }

            $file_id = $file->id();
            $user_id = '';
            if ($generationType === "Automated") {
                $uids = \Drupal::entityQuery('user')
                    ->condition('status', 1)
                    ->condition('roles', 'administrator')
                    ->accessCheck(FALSE)
                    ->execute();

                if (!empty($uids)) {
                    $admin_user = User::load(reset($uids));
                    $user_id = $admin_user->id();
                }
            } else {
                $current_user = \Drupal::currentUser();
                $user_id = $current_user->id();
            }

            $current_date = date('F j, Y g:i A');
            $values = [
                'type' => 'cic_report',
                'title' => "[" . basename($zip_uri) . "] CIC Report",
                'status' => 1,
                'field_file' => $file_id,
                'field_file_name' => basename($zip_uri),
                'field_generation_date' => $current_date,
                'field_generated_by' => $user_id,
                'field_generation_type' => $generationType,
                'field_cooperative' => $coop_nid,
            ];
            $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
            $node->save();
        }

        $data = [
            'changed_fields' => [],
            'performed_by_name' => $this->currentUser->getAccountName(),
        ];
        if (empty($failed_coop_uploads)) {
            \Drupal::messenger()->addMessage('Successfully generated CIC report!');
            $action = "Finished CIC Report Generation task for " . $file_name;


            $this->activityLogger->log($action, 'node', NULL, $data, NULL, $this->currentUser);

        } else {

            $group_list = implode(', ', $failed_coop_uploads);
            $action = "Failed CIC Report Generation task for " . $file_name;
            $this->activityLogger->log($action, 'node', NULL, $data, NULL, $this->currentUser);

            $message = "CIC report generated. **Failed to upload for the following groups:** [{$group_list}]";

            \Drupal::messenger()->addWarning($message);
            \Drupal::logger('FTPS')->warning('CIC report generated with failed uploads for: @groups', ['@groups' => $group_list]);
        }
    }

    function upload_file_via_ftps(string $localFile, string $username, string $encrypted_pw, string $provider_code)
    {
        $keyAscii = $_ENV['FTPS_PW_ENCRYPT_KEY'];
        $key = Key::loadFromAsciiSafeString($keyAscii);

        $password = Crypto::decrypt($encrypted_pw, $key);
        $host = $_ENV['CIC_HOST'];
        $port = $_ENV['CIC_PORT'];

        $max_attempts = 3;
        $delay_seconds = 5;

        $conn = FALSE;

        for ($i = 0; $i < $max_attempts; $i++) {
            // max_attempts * (timeout + delay) = max time spent
            $conn = @ftp_ssl_connect($host, $port, 30);

            if ($conn !== FALSE) {
                break;
            }

            if ($i < $max_attempts - 1) {
                \Drupal::logger('FTPS')->warning('FTPS connection attempt @attempt failed to @host. Retrying in @delay seconds.', [
                    '@attempt' => $i + 1,
                    '@host' => $host,
                    '@delay' => $delay_seconds,
                ]);
                sleep($delay_seconds);
            }
        }

        if (!$conn) {
            \Drupal::logger('FTPS')->error('Could not connect to FTPS server: @host', ['@host' => $host]);
            \Drupal::messenger()->addError("Could not connect to the FTPS server with coop $provider_code. Try again later.");
            return FALSE;
        }

        if (!ftp_login($conn, $username, $password)) {
            \Drupal::logger('FTPS')->error('Invalid FTPS login credentials for coop @coop', ['@coop' => $provider_code]);
            \Drupal::messenger()->addError("Invalid FTPS login credentials for coop $provider_code");
            @ftp_close($conn);
            return FALSE;
        }

        ftp_pasv($conn, TRUE);
        ftp_chdir($conn, $_ENV['CIC_SUBMISSION_DIR']);
        $remoteFile = basename($localFile);

        if (@ftp_put($conn, $remoteFile, $localFile, FTP_BINARY)) {
            sleep(2);

            $remote_size = @ftp_size($conn, $remoteFile);
            $local_size = filesize($localFile);

            if ($remote_size > 0 && $remote_size === $local_size) {
                \Drupal::logger('FTPS')->info('FTPS upload successful and verified for @file', ['@file' => $remoteFile]);
            } else {
                \Drupal::logger('FTPS')->warning('FTPS upload unverified for @file', ['@file' => $remoteFile]);
            }
        } else {
            \Drupal::logger('FTPS')->error('FTPS upload failed for @file', [
                '@file' => $localFile,
            ]);
            \Drupal::messenger()->addError("FTPS upload failed for $provider_code");
            @ftp_quit($conn);
            @ftp_close($conn);
            return FALSE;
        }

        @ftp_quit($conn);
        @ftp_close($conn);
        return TRUE;
    }


    private function generateHeaderString(string $provider_code, string $reference_date): string
    {
        $string = "HD|$provider_code|$reference_date|1.0|1|";
        return $string;
    }

    private function generateFooterString(string $provider_code, string $reference_date, int $num_of_records): string
    {
        $string = "FT|$provider_code|$reference_date|$num_of_records";
        return $string;
    }

    private function generateIndividualString(Node $indiv_node, string $reference_date): string
    {
        $family_node = $indiv_node->get('field_family')->isEmpty() ? NULL : $indiv_node->get('field_family')->entity;
        $address_node = $indiv_node->get('field_address')->isEmpty() ? NULL : $indiv_node->get('field_address')->entity;
        $id_node = $indiv_node->get('field_identification')->isEmpty() ? NULL : $indiv_node->get('field_identification')->entity;
        $contact_node = $indiv_node->get('field_contact')->isEmpty() ? NULL : $indiv_node->get('field_contact')->entity;
        $employment_node = $indiv_node->get('field_employment')->isEmpty() ? NULL : $indiv_node->get('field_employment')->entity;

        $provider_subject_no = $indiv_node->get('field_provider_subject_no')->value ?? '';
        $provider_code = $indiv_node->get('field_provider_code')->value ?? '';
        $branch_code = $indiv_node->get('field_branch_code')->value ?? '';
        $title = $indiv_node->get('field_title')->value ?? '';
        $first_name = $indiv_node->get('field_first_name')->value ?? '';
        $last_name = $indiv_node->get('field_last_name')->value ?? '';
        $middle_name = $indiv_node->get('field_middle_name')->value ?? '';
        $suffix = $indiv_node->get('field_suffix')->value ?? '';
        $previous_last_name = $indiv_node->get('field_previous_last_name')->value ?? '';
        $gender = $indiv_node->get('field_gender')->value ?? '';
        $date_of_birth = $indiv_node->get('field_date_of_birth')->value ?? '';
        $place_of_birth = $indiv_node->get('field_place_of_birth')->value ?? '';
        $country_of_birth_code = $indiv_node->get('field_country_of_birth_code')->value ?? '';
        $nationality = $indiv_node->get('field_nationality')->value ?? '';
        $resident = $indiv_node->get('field_resident')->value ?? '';
        $civil_status = $indiv_node->get('field_civil_status')->value ?? '';
        $number_of_dependents = $indiv_node->get('field_number_of_dependents')->value ?? '';
        $cars_owned = $indiv_node->get('field_cars_owned')->value ?? '';

        $spouse_first_name = $family_node?->get('field_spouse_first_name')->value ?? '';
        $spouse_last_name = $family_node?->get('field_spouse_last_name')->value ?? '';
        $spouse_middle_name = $family_node?->get('field_spouse_middle_name')->value ?? '';
        $mother_maiden_full_name = $family_node?->get('field_mother_maiden_full_name')->value ?? '';
        $father_first_name = $family_node?->get('field_father_first_name')->value ?? '';
        $father_last_name = $family_node?->get('field_father_last_name')->value ?? '';
        $father_middle_name = $family_node?->get('field_father_middle_name')->value ?? '';
        $father_suffix = $family_node?->get('field_father_suffix')->value ?? '';

        $address1_type = $address_node?->get('field_address1_type')->value ?? '';
        $address1_fulladdress = $address_node?->get('field_address1_fulladdress')->value ?? '';
        $address2_type = $address_node?->get('field_address2_type')->value ?? '';
        $address2_fulladdress = $address_node?->get('field_address2_fulladdress')->value ?? '';

        $identification1_type = $id_node?->get('field_identification1_type')->value ?? '';
        $identification1_number = $id_node?->get('field_identification1_number')->value ?? '';
        $identification2_type = $id_node?->get('field_identification2_type')->value ?? '';
        $identification2_number = $id_node?->get('field_identification2_number')->value ?? '';
        $id1_type = $id_node?->get('field_id1_type')->value ?? '';
        $id1_number = $id_node?->get('field_id1_number')->value ?? '';
        $id1_issuedate = $id_node?->get('field_id1_issuedate')->value ?? '';
        $id1_issuecountry = $id_node?->get('field_id1_issuecountry')->value ?? '';
        $id1_expirydate = $id_node?->get('field_id1_expirydate')->value ?? '';
        $id1_issuedby = $id_node?->get('field_id1_issuedby')->value ?? '';
        $id2_type = $id_node?->get('field_id2_type')->value ?? '';
        $id2_number = $id_node?->get('field_id2_number')->value ?? '';
        $id2_issuedate = $id_node?->get('field_id2_issuedate')->value ?? '';
        $id2_issuecountry = $id_node?->get('field_id2_issuecountry')->value ?? '';
        $id2_expirydate = $id_node?->get('field_id2_expirydate')->value ?? '';
        $id2_issuedby = $id_node?->get('field_id2_issuedby')->value ?? '';

        $contact1_type = $contact_node?->get('field_contact1_type')->value ?? '';
        $contact1_value = $contact_node?->get('field_contact1_value')->value ?? '';
        $contact2_type = $contact_node?->get('field_contact2_type')->value ?? '';
        $contact2_value = $contact_node?->get('field_contact2_value')->value ?? '';

        $employ_trade_name = $employment_node?->get('field_employ_trade_name')->value ?? '';
        $employ_psic = $employment_node?->get('field_employ_psic')->value ?? '';
        $employ_occupation_status = $employment_node?->get('field_employ_occupation_status')->value ?? '';
        $employ_occupation = $employment_node?->get('field_employ_occupation')->value ?? '';

        $string = "ID|$provider_code|$branch_code|$reference_date|$provider_subject_no|$title|$first_name|$last_name|$middle_name|$suffix||" .
            "$previous_last_name|$gender|$date_of_birth|$place_of_birth|$country_of_birth_code|$nationality|$resident|$civil_status|" .
            "$number_of_dependents|$cars_owned|$spouse_first_name|$spouse_last_name|$spouse_middle_name||$mother_maiden_full_name||" .
            "$father_first_name|$father_last_name|$father_middle_name|$father_suffix|$address1_type|$address1_fulladdress||||||||||" .
            "$address2_type|$address2_fulladdress||||||||||$identification1_type|$identification1_number|$identification2_type|" .
            "$identification2_number|||$id1_type|$id1_number|$id1_issuedate|$id1_issuecountry|$id1_expirydate|$id1_issuedby|$id2_type|" .
            "$id2_number|$id2_issuedate|$id2_issuecountry|$id2_expirydate|$id2_issuedby|||||||$contact1_type|$contact1_value|" .
            "$contact2_type|$contact2_value|$employ_trade_name|||$employ_psic||||$employ_occupation_status|||" .
            "$employ_occupation|||||||||||||||||||||||||||||||";
        return $string;
    }

    private function generateCompanyString(Node $company_node, string $reference_date): string
    {
        $address_node = $company_node->get('field_address')->isEmpty() ? NULL : $company_node->get('field_address')->entity;
        $id_node = $company_node->get('field_identification')->isEmpty() ? NULL : $company_node->get('field_identification')->entity;
        $contact_node = $company_node->get('field_contact')->isEmpty() ? NULL : $company_node->get('field_contact')->entity;

        $provider_subject_no = $company_node->get('field_provider_subject_no')->value ?? '';
        $provider_code = $company_node->get('field_provider_code')->value ?? '';
        $branch_code = $company_node->get('field_branch_code')->value ?? '';
        $trade_name = $company_node->get('field_trade_name')->value ?? '';

        $address1_type = $address_node?->get('field_address1_type')->value ?? '';
        $address1_fulladdress = $address_node?->get('field_address1_fulladdress')->value ?? '';
        $address2_type = $address_node?->get('field_address2_type')->value ?? '';
        $address2_fulladdress = $address_node?->get('field_address2_fulladdress')->value ?? '';

        $identification1_type = $id_node?->get('field_identification1_type')->value ?? '';
        $identification1_number = $id_node?->get('field_identification1_number')->value ?? '';
        $identification2_type = $id_node?->get('field_identification2_type')->value ?? '';
        $identification2_number = $id_node?->get('field_identification2_number')->value ?? '';

        $contact1_type = $contact_node?->get('field_contact1_type')->value ?? '';
        $contact1_value = $contact_node?->get('field_contact1_value')->value ?? '';
        $contact2_type = $contact_node?->get('field_contact2_type')->value ?? '';
        $contact2_value = $contact_node?->get('field_contact2_value')->value ?? '';

        $string = "BD|$provider_code|$branch_code|$reference_date|$provider_subject_no|$trade_name||||||||||||||$address1_type|" .
            "$address1_fulladdress||||||||||$address2_type|$address2_fulladdress||||||||||$identification1_type|" .
            "$identification1_number|$identification2_type|$identification2_number|$contact1_type|$contact1_value|" .
            "$contact2_type|$contact2_value";
        return $string;
    }

    private function generateCiString(Node $ci_node): string
    {
        $header_node = $ci_node->get('field_header')->isEmpty() ? NULL : $ci_node->get('field_header')->entity;
        $subj_node = $ci_node->get('field_subject')->isEmpty() ? NULL : $ci_node->get('field_subject')->entity;

        $provider_code = $header_node?->get('field_provider_code')->value ?? '';
        $branch_code = $header_node?->get('field_branch_code')->value ?? '';
        $reference_date = $header_node?->get('field_reference_date')->value ?? '';

        $provider_subj_no = $subj_node?->get('field_provider_subject_no')->value ?? '';

        $provider_contract_no = $ci_node?->get('field_provider_contract_no')->value ?? '';
        $contract_end_actl_date = $ci_node?->get('field_contract_end_actual_date')->value ?? '';
        $contract_end_plnd_date = $ci_node?->get('field_contract_end_planned_date')->value ?? '';
        $contract_phase = $ci_node?->get('field_contract_phase')->value ?? '';
        $contract_start_date = $ci_node?->get('field_contract_start_date')->value ?? '';
        $contract_type = $ci_node?->get('field_contract_type')->value ?? '';
        $currency = $ci_node?->get('field_currency')->value ?? '';
        $financed_amt = $ci_node?->get('field_financed_amount')->value ?? '';
        $installments_no = $ci_node?->get('field_installments_no')->value ?? '';
        $last_payment_amt = $ci_node?->get('field_last_payment_amount')->value ?? '';
        $monthly_payment_amt = $ci_node?->get('field_monthly_payment_amount')->value ?? '';
        $next_payment_date = $ci_node?->get('field_next_payment_date')->value ?? '';
        $original_currency = $ci_node?->get('field_original_currency')->value ?? '';
        $outstanding_balance = $ci_node?->get('field_outstanding_balance')->value ?? '';
        $outstanding_payment_no = $ci_node?->get('field_outstanding_payment_no')->value ?? '';
        $overdue_days = $ci_node?->get('field_overdue_days')->value ?? '';
        $overdue_payments_amt = $ci_node?->get('field_overdue_payments_amount')->value ?? '';
        $overdue_payments_number = $ci_node?->get('field_overdue_payments_number')->value ?? '';
        $payment_periodicity = $ci_node?->get('field_payment_periodicity')->value ?? '';
        $role = $ci_node?->get('field_role')->value ?? '';
        $transaction_type = $ci_node?->get('field_transaction_type')->value ?? '';

        $string = "CI|$provider_code|$branch_code|$reference_date|$provider_subj_no|$role|$provider_contract_no|$contract_type|" .
            "$contract_phase||$currency|$original_currency|$contract_start_date|$contract_start_date|$contract_end_plnd_date|$contract_end_actl_date" .
            "||||$financed_amt|$installments_no|$transaction_type||$payment_periodicity||$monthly_payment_amt||" .
            "$last_payment_amt|$next_payment_date||$outstanding_payment_no|$outstanding_balance|$overdue_payments_number|" .
            "$overdue_payments_amt|$overdue_days||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||";
        return $string;
    }

    private function generateCnString(Node $cn_node): string
    {
        $header_node = $cn_node->get('field_header')->isEmpty() ? NULL : $cn_node->get('field_header')->entity;
        $subj_node = $cn_node->get('field_subject')->isEmpty() ? NULL : $cn_node->get('field_subject')->entity;

        $provider_code = $header_node?->get('field_provider_code')->value ?? '';
        $branch_code = $header_node?->get('field_branch_code')->value ?? '';
        $reference_date = $header_node?->get('field_reference_date')->value ?? '';

        $provider_subj_no = $subj_node?->get('field_provider_subject_no')->value ?? '';

        $provider_contract_no = $cn_node?->get('field_provider_contract_no')->value ?? '';
        $contract_end_actl_date = $cn_node?->get('field_contract_end_actual_date')->value ?? '';
        $contract_end_plnd_date = $cn_node?->get('field_contract_end_planned_date')->value ?? '';
        $contract_phase = $cn_node?->get('field_contract_phase')->value ?? '';
        $contract_start_date = $cn_node?->get('field_contract_start_date')->value ?? '';
        $contract_type = $cn_node?->get('field_contract_type')->value ?? '';
        $credit_limit = $cn_node?->get('field_credit_limit')->value ?? '';
        $currency = $cn_node?->get('field_currency')->value ?? '';
        $original_currency = $cn_node?->get('field_original_currency')->value ?? '';
        $outstanding_balance = $cn_node?->get('field_outstanding_balance')->value ?? '';
        $overdue_payments_amt = $cn_node?->get('field_overdue_payments_amount')->value ?? '';
        $role = $cn_node?->get('field_role')->value ?? '';
        $transaction_type = $cn_node?->get('field_transaction_type')->value ?? '';

        $string = "CN|$provider_code|$branch_code|$reference_date|$provider_subj_no|$role|$provider_contract_no|$contract_type|$contract_phase" .
            "||$currency|$original_currency|$contract_start_date|$contract_start_date|$contract_end_plnd_date|$contract_end_actl_date||||$credit_limit|" .
            "$transaction_type||$outstanding_balance|$overdue_payments_amt|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||";
        return $string;
    }
}
?>