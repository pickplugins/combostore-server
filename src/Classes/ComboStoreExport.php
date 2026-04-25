<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreExport
{
    public $email = "";
    public $user_id = "";


    public function __construct() {}



    function export_combo_store_data($task_id, $queryPrams = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_validation_tasks_entries';


        $status               = (isset($queryPrams['status']) && !empty($queryPrams['status'])) ? sanitize_text_field($queryPrams['status']) : null;
        $safeToSend           = (isset($queryPrams['safeToSend']) && !empty($queryPrams['safeToSend'])) ? sanitize_text_field($queryPrams['safeToSend']) : null;
        $isSyntaxValid        = (isset($queryPrams['isSyntaxValid']) && !empty($queryPrams['isSyntaxValid'])) ? sanitize_text_field($queryPrams['isSyntaxValid']) : null;
        $isValidEmail         = (isset($queryPrams['isValidEmail']) && !empty($queryPrams['isValidEmail'])) ? sanitize_text_field($queryPrams['isValidEmail']) : null;
        $hasValidDomain       = (isset($queryPrams['hasValidDomain']) && !empty($queryPrams['hasValidDomain'])) ? sanitize_text_field($queryPrams['hasValidDomain']) : null;
        $isDisposableDomain   = (isset($queryPrams['isDisposableDomain']) && !empty($queryPrams['isDisposableDomain'])) ? sanitize_text_field($queryPrams['isDisposableDomain']) : null;
        $isInboxFull          = (isset($queryPrams['isInboxFull']) && !empty($queryPrams['isInboxFull'])) ? sanitize_text_field($queryPrams['isInboxFull']) : null;
        $isFreeEmailProvider  = (isset($queryPrams['isFreeEmailProvider']) && !empty($queryPrams['isFreeEmailProvider'])) ? sanitize_text_field($queryPrams['isFreeEmailProvider']) : null;
        $isGibberishEmail     = (isset($queryPrams['isGibberishEmail']) && !empty($queryPrams['isGibberishEmail'])) ? sanitize_text_field($queryPrams['isGibberishEmail']) : null;
        $checkDomainReputation = (isset($queryPrams['checkDomainReputation']) && !empty($queryPrams['checkDomainReputation'])) ? sanitize_text_field($queryPrams['checkDomainReputation']) : null;
        $isSMTPBlacklisted    = (isset($queryPrams['isSMTPBlacklisted']) && !empty($queryPrams['isSMTPBlacklisted'])) ? sanitize_text_field($queryPrams['isSMTPBlacklisted']) : null;
        $isRoleBasedEmail     = (isset($queryPrams['isRoleBasedEmail']) && !empty($queryPrams['isRoleBasedEmail'])) ? sanitize_text_field($queryPrams['isRoleBasedEmail']) : null;
        $isCatchAllDomain     = (isset($queryPrams['isCatchAllDomain']) && !empty($queryPrams['isCatchAllDomain'])) ? sanitize_text_field($queryPrams['isCatchAllDomain']) : null;
        $verifySMTP           = (isset($queryPrams['verifySMTP']) && !empty($queryPrams['verifySMTP'])) ? sanitize_text_field($queryPrams['verifySMTP']) : null;







        $where = ['task_id' => $task_id];
        $conditions = ['task_id = %d'];
        $params = [$task_id];

        if ($status) {
            $conditions[] = "JSON_CONTAINS(result, %s, '$.status')";
            $params[] = json_encode($status);
        }

        if (!is_null($safeToSend)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.safeToSend')) = %s";
            $params[] = $safeToSend;
        }

        if (!is_null($hasValidDomain)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.hasValidDomain')) = %d";
            $params[] = (int) $hasValidDomain;
        }

        $query = "SELECT email, result, status FROM $table_name WHERE " . implode(" AND ", $conditions);
        $prepared_query = $wpdb->prepare($query, ...$params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);

        if (empty($results)) {
            return 'No records found.';
        }
        // return;

        $csv_filename = 'combo_store_export_' . time() . '.csv';
        $csv_path = WP_CONTENT_DIR . '/uploads/' . $csv_filename;
        $csv_file = fopen($csv_path, 'w');

        fputcsv($csv_file, ['Email', 'Status', 'Safe To Send', 'Has Valid Domain', 'Disposable Domain', 'Free Email Provider', 'Inbox Full', 'Gibberish Email', 'SMTP Blacklisted', 'Catch All Domain', 'Verify SMTP']);

        foreach ($results as $row) {


            $email = isset($row['email']) ? $row['email'] : '';
            $result_data = json_decode($row['result'], true);

            $status = isset($result_data['status']) ? ($result_data['status']) : '';
            $status =  is_array($status) ? implode(', ', $status) : $status;

            $safeToSend = isset($result_data['safeToSend']) ? ($result_data['safeToSend']) : 'no';
            $safeToSend = ($safeToSend == 'yes') ? "Yes" : "No";

            $isSyntaxValid = isset($result_data['isSyntaxValid']) ? ($result_data['isSyntaxValid']) : '';
            $isSyntaxValid = ($isSyntaxValid == 'yes') ? "Yes" : "No";

            $isValidEmail = isset($result_data['isValidEmail']) ? ($result_data['isValidEmail']) : '';
            $isValidEmail = ($isValidEmail == 'yes') ? "Yes" : "No";

            $hasValidDomain = isset($result_data['hasValidDomain']) ? ($result_data['hasValidDomain']) : '';
            $hasValidDomain = ($hasValidDomain == 'yes') ? "Yes" : "No";

            $isDisposableDomain = isset($result_data['isDisposableDomain']) ? ($result_data['isDisposableDomain']) : '';
            $isDisposableDomain = ($isDisposableDomain == 'yes') ? "Yes" : "No";

            $isFreeEmailProvider = isset($result_data['isFreeEmailProvider']) ? ($result_data['isFreeEmailProvider']) : '';
            $isFreeEmailProvider = ($isFreeEmailProvider == 'yes') ? "Yes" : "No";


            $isInboxFull = isset($result_data['isInboxFull']) ? ($result_data['isInboxFull']) : '';
            $isInboxFull = ($isInboxFull == 'yes') ? "Yes" : "No";

            $isGibberishEmail = isset($result_data['isGibberishEmail']) ? ($result_data['isGibberishEmail']) : '';
            $isGibberishEmail = ($isGibberishEmail == 'yes') ? "Yes" : "No";

            $checkDomainReputation = isset($result_data['checkDomainReputation']) ? ($result_data['checkDomainReputation']) : '';
            $checkDomainReputation = ($checkDomainReputation == 'yes') ? "Yes" : "No";

            $isSMTPBlacklisted = isset($result_data['isSMTPBlacklisted']) ? ($result_data['isSMTPBlacklisted']) : '';
            $isSMTPBlacklisted = ($isSMTPBlacklisted == 'yes') ? "Yes" : "No";

            $isRoleBasedEmail = isset($result_data['isRoleBasedEmail']) ? ($result_data['isRoleBasedEmail']) : '';
            $isRoleBasedEmail = ($isRoleBasedEmail == 'yes') ? "Yes" : "No";

            $isCatchAllDomain = isset($result_data['isCatchAllDomain']) ? ($result_data['isCatchAllDomain']) : '';
            $isCatchAllDomain = ($isCatchAllDomain == 'yes') ? "Yes" : "No";

            $verifySMTP = isset($result_data['verifySMTP']) ? ($result_data['verifySMTP']) : '';
            $verifySMTP = ($verifySMTP == 'yes') ? "Yes" : "No";





            fputcsv($csv_file, [
                $email,
                $status,
                $safeToSend,
                $hasValidDomain,
                $isDisposableDomain,
                $isFreeEmailProvider,
                $isInboxFull,
                $isGibberishEmail,
                $isSMTPBlacklisted,
                $isCatchAllDomain,
                $verifySMTP,
            ]);
        }

        fclose($csv_file);

        return content_url('/uploads/' . $csv_filename);
    }










    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }





    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = gmdate('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = gmdate('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}


$ComboStoreExport = new ComboStoreExport();
