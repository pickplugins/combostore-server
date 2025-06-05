<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access

use EmailValidation\Classes\EmailVerifier;
use EmailValidation\Classes\EmailValidationCredits;


class EmailValidationCrons
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";




    public function __construct()
    {

        add_filter('wp_login',  array($this, 'ev_wp_login'), 9999, 3);
        add_shortcode('run_check_tasks_status', array($this, 'run_check_tasks_status'));
        add_action('run_check_tasks_status',  array($this, 'run_check_tasks_status'));
        add_action('run_email_validation_task',  array($this, 'run_email_validation_task'));

        // add_shortcode('run_email_validation_add_daily_credit', 'run_email_validation_add_daily_credit');
        // add_action('run_email_validation_add_daily_credit', 'run_email_validation_add_daily_credit');


    }



    function run_check_tasks_status()
    {

        error_log("run_check_tasks_status");


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks = $prefix . 'cstore_validation_tasks';
        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';
        $limit = 3;
        $page = 1;
        $response = [];
        $offset = ($page - 1) * $limit;

        $tasks_pending = $wpdb->get_results("SELECT * FROM $table_validation_tasks WHERE status='pending' ORDER BY id ASC LIMIT $offset, $limit");


        if (!empty($tasks_pending)) {

            foreach ($tasks_pending as $task) {
                $task_id = isset($task->id) ? $task->id : 0;
                $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_validation_tasks_entries WHERE status = 'pending' AND task_id='$task_id'");

                $pending_count = $pending_count ? $pending_count : 0;

                if ($pending_count > 0) {
                    $updated_data = array(
                        'status' => 'running',
                    );
                    $where = array('id' => $task_id);

                    $updated = $wpdb->update($table_validation_tasks, $updated_data, $where);
                }
                if ($pending_count == 0) {
                    $updated_data = array(
                        'status' => 'completed',
                    );
                    $where = array('id' => $task_id);

                    $updated = $wpdb->update($table_validation_tasks, $updated_data, $where);
                }
            }
        }




        // $updated_data = array(
        //   'status' => 'completed',
        //   'result' => wp_json_encode($response),
        // );
        // $where = array('id' => $id);

        // $updated = $wpdb->update($table_validation_tasks_entries, $updated_data, $where);
    }




    function run_email_validation_task()
    {

        $EmailVerifier = new EmailVerifier();
        $EmailValidationCredits = new EmailValidationCredits();

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks = $prefix . 'cstore_validation_tasks';
        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';
        $limit = 10;
        $page = 1;
        $response = [];
        $offset = ($page - 1) * $limit;


        $running_tasks = $wpdb->get_results("SELECT * FROM $table_validation_tasks WHERE status='running' ORDER BY id ASC LIMIT $offset, $limit");




        $completed_count = [];


        if (!empty($running_tasks)) {

            foreach ($running_tasks as $task) {
                $task_id = isset($task->id) ? $task->id : 0;
                $userid = isset($task->userid) ? $task->userid : 0;



                $has_credit = $EmailValidationCredits->has_credit($userid);


                if (!$has_credit) {
                    $updated_data = array(
                        'status' => 'skiped',
                    );
                    $where = array('id' => $task_id);

                    $updated = $wpdb->update($table_validation_tasks, $updated_data, $where);
                    break;
                }


                $entries = $wpdb->get_results("SELECT * FROM $table_validation_tasks_entries WHERE status='pending' AND task_id='$task_id' ORDER BY id DESC LIMIT $offset, $limit");


                foreach ($entries as $entry) {

                    $id = isset($entry->id) ? $entry->id : 0;
                    $status = isset($entry->status) ? $entry->status : 'pending';
                    $email = isset($entry->email) ? $entry->email : '';
                    $userid = isset($entry->userid) ? $entry->userid : '';


                    $response = $EmailVerifier->verifyEmail($email);

                    if (isset($response['status'])) {
                        $updated_data = array(
                            'status' => 'completed',
                            'result' => wp_json_encode($response),
                        );
                        $where = array('id' => $id);

                        $updated = $wpdb->update($table_validation_tasks_entries, $updated_data, $where);

                        if (!isset($completed_count[$userid])) {
                            $completed_count[$userid] = 0;
                        }

                        $completed_count[$userid] += 1;
                    } else {

                        $updated_data = array(
                            'status' => 'failed',
                        );
                        $where = array('id' => $id);

                        $updated = $wpdb->update($table_validation_tasks_entries, $updated_data, $where);
                    }
                }
            }
        }



        $EmailValidationCredits = new EmailValidationCredits();

        $credit_type = '';
        $notes = 'added by task';
        $source = "task";
        foreach ($completed_count as $userid => $amount) {
            $response = $EmailValidationCredits->debit_credit($userid, $amount, $credit_type, $source, $notes);
        }
    }



    function run_email_validation_add_daily_credit()
    {


        $userid     =  1;
        $amount     = 100;
        $credit_type     = 'daily'; // instant, daily
        $source     = 'cron'; // codes. redeem, 
        $notes     = ''; // codes. redeem, 


        // $response = $EmailValidationCredits->add_credit($userid, $amount, $credit_type, $source, $notes);
    }


    function ev_wp_login($user_login, $user)
    {

        $user_id = isset($user->ID) ? $user->ID : 0;
        $EmailValidationCredits = new EmailValidationCredits();


        $EmailValidationCredits->add_daily_credit($user_id);
    }



    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }





    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = date('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = date('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}
