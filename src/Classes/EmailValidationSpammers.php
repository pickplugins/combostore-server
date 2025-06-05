<?php

namespace EmailValidation\Classes;

use Error;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationSpammers
{
    public $object_name = "";


    public function __construct() {}

    function ceate_spammer($prams)
    {

        //error_log(serialize($prams));

        $email = isset($prams['email']) ? $prams['email'] : '';
        $name = isset($prams['name']) ? $prams['name'] : '';
        $website = isset($prams['website']) ? $prams['website'] : '';
        $content = isset($prams['content']) ? $prams['content'] : '';
        $domains = isset($prams['domains']) ? $prams['domains'] : '';
        $report_count = isset($prams['report_count']) ? $prams['report_count'] : 1;
        $level = isset($prams['level']) ? $prams['level'] : 0;
        $last_date = isset($prams['last_date']) ? $prams['last_date'] : '';


        $last_date = empty($last_date) ? $this->get_datetime() : $last_date;

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';

        $response = [];

        $has_prams = [];
        $has_prams['email'] = $email;
        $has_prams['name'] = $name;
        $has_prams['website'] = $website;

        $has_spammer = $this->has_spammer($has_prams);


        if ($has_spammer) return;

        $data = array(
            'email'       => $email,
            'name'       => $name,
            'website'       => $website,
            'content'       => $content,
            'domains'           => $domains,
            'report_count'         => $report_count,
            'level'         => $level,
            'last_date'         => $last_date,
            'date_created'         => $this->get_datetime(),
        );

        $format = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s'
        );

        $wpdb->insert($table, $data, $format);


        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {
            $response['success'] = true;
        }



        error_log(serialize($response));






        return $response;
    }


    function has_spammer($prams)
    {

        $email = isset($prams['email']) ? $prams['email'] : '';
        $name = isset($prams['name']) ? $prams['name'] : '';
        $website = isset($prams['website']) ? $prams['website'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_spammers';



        // $spammer_count = $wpdb->get_var(
        //     $wpdb->prepare(
        //         "SELECT COUNT(*) FROM $table WHERE  email = %s AND name = %s AND website = %s",
        //         $email,
        //         $name,
        //         $website
        //     )
        // );

        $query = "SELECT COUNT(*) FROM $table WHERE email = %s";
        $params = [$email];

        if (!empty($name)) {
            $query .= " AND name = %s";
            $params[] = $name;
        }

        if (!empty($website)) {
            $query .= " AND website = %s";
            $params[] = $website;
        }

        $spammer_count = $wpdb->get_var($wpdb->prepare($query, ...$params));



        //error_log("spammer_count: $spammer_count");

        if ($spammer_count > 0) {
            return true;
        } else {
            return false;
        }
    }


    function ceate_spammer_domain($domain, $userid = 1, $contact_email = '')
    {


        $get_spammer_domain = $this->get_spammer_domain($domain);


        $response = [];

        if (!$get_spammer_domain) {

            $last_date = empty($last_date) ? $this->get_datetime() : $last_date;

            global $wpdb;
            $prefix = $wpdb->prefix;
            $table = $prefix . 'cstore_spammers_domain';

            global $wpdb;


            $data = array(
                'userid'           => $userid,
                'contact_email'         => $contact_email,
                'domain'         => $domain,
                'report_count'         => '',
                'last_date'         => $last_date,
                'date_created'         => $this->get_datetime(),
            );

            $format = array(
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            );

            $wpdb->insert($table, $data, $format);


            if ($wpdb->last_error) {
                $response['errors'] = true;
            } else {
                $response['success'] = true;
            }
        }

        return $response;
    }











    function delete_spammer($id, $email = '')
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id,), array('%s'));


        $response['status'] = $status;

        return $response;
    }


    function delete_spammer_domain($id)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id,), array('%d'));


        $response['status'] = $status;

        return $response;
    }


    function loop_update_domain()
    {
        $limit     =  999999999;
        $page     =  1;

        $offset = ($page - 1) * $limit;
        $order     = 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';
        //$table = $prefix . 'cstore_spammers_domain';

        $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY id $order LIMIT $offset, $limit");


        foreach ($entries as $index => $entry) {


            $id = isset($entry->id) ? $entry->id : 0;
            $email = isset($entry->email) ? $entry->email : '';
            $domains = isset($entry->domains) ? $entry->domains : [];
            $domains = json_decode($domains, true);

            $domain_ids = [];

            if (is_array($domains)) {

                foreach ($domains as $domain) {
                    $domain = str_replace(['http://', 'https://', 'www.'], '', $domain);

                    if (!empty($domain)) {
                        $get_spammer_domain = $this->get_spammer_domain($domain);

                        if ($get_spammer_domain->id) {
                            $domain_ids[] = isset($get_spammer_domain->id) ? $get_spammer_domain->id : '';
                        }
                    }
                }
            } else {
                $domain = str_replace(['http://', 'https://', 'www.'], '', $domains);
                if (!empty($domain)) {
                    $get_spammer_domain = $this->get_spammer_domain($domain);

                    if ($get_spammer_domain->id) {
                        $domain_ids[] = isset($get_spammer_domain->id) ? $get_spammer_domain->id : '';
                    }
                }
            }


            if (!empty($domain_ids)) {
                $domain_ids = json_encode($domain_ids);

                $response = $this->update_spammer($id, '', '', $domain_ids);
            }
        }
    }

    function loop_update_domain_array_to_single()
    {
        $limit     =  10;
        $page     =  1;

        $offset = ($page - 1) * $limit;
        $order     = 'ASC';


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';
        //$table = $prefix . 'cstore_spammers_domain';

        $entries = $wpdb->get_results("SELECT * FROM $table  ORDER BY id $order LIMIT $offset, $limit");


        foreach ($entries as $index => $entry) {


            $id = isset($entry->id) ? $entry->id : 0;
            $email = isset($entry->email) ? $entry->email : '';
            $domains = isset($entry->domains) ? $entry->domains : '';
            //$domains = json_decode($domains, true);
            //error_log(($domains));


            if ($domains == 'deleted') {
                //$this->delete_spammer($id);
            }
            if (!$domains) {
                error_log("$id");
                //error_log("$email");
                error_log(($domains));

                //$this->update_spammer($id, '', '', null);
            }

            if (is_array($domains)) {



                error_log("$id");
                error_log("$email");
                error_log(serialize($domains));


                //$this->delete_spammer($id);
                //$this->update_spammer($id, '', '', 'deleted');
                // foreach ($domains as $domain) {
                //     $has_spammer = $this->has_spammer($email, $domain);
                //     error_log("has_spammer: $has_spammer");

                //     if (!$has_spammer) {
                //         $response = $this->ceate_spammer($email, $domain);
                //         error_log(json_encode($response));
                //     }
                // }
            }


            //$this->update_spammer($id, '', '', 'deleted');
        }
    }







    function update_spammer($id, $report_count = '', $level = '', $domains = '')
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';

        $last_date = $this->get_datetime();
        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting API key failed";
        }


        global $wpdb;
        $updated_data = [];

        if (!empty($report_count)) {
            $updated_data['report_count'] = $report_count;
        }
        if (!empty($level)) {
            $updated_data['level'] = $level;
        }
        //if (!empty($domains)) {
        $updated_data['domains'] = $domains;
        //}
        if (!empty($last_date)) {
            $updated_data['last_date'] = $last_date;
        }

        $where = array('id' => $id);
        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }
        return $response;
    }
    function get_spammer($email)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';

        $response = [];

        if (!$email) {
            $response['errors'] = "No spammer found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email=%s",  $email));


        return $meta_data;
    }
    function get_spammer_domain($domain)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers_domain';

        $response = [];

        if (!$domain) {
            $response['errors'] = "No spammer doamin found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE domain=%s",  $domain));



        return $meta_data;
    }
    function get_spammer_domains($userid)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers_domain';

        $response = [];

        if (!$userid) {
            $response['errors'] = "No spammer doamin found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE userid=%s",  $userid));



        return $meta_data;
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
