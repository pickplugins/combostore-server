<?php

namespace ComboStore\Classes;


class ComboStoreCore
{

    public function __construct()
    {

        // define('combo_store_plugin_url', plugins_url('/', __FILE__));
        // define('combo_store_plugin_dir', plugin_dir_path(__FILE__));
        // define('combo_store_plugin_name', 'Email Verify');
        // define('combo_store_plugin_version', '1.0.0');

        // require_once(combo_store_plugin_dir . 'includes/class-email-verifier.php');
        // require_once(combo_store_plugin_dir . 'includes/class-api-requests.php');
        // require_once(combo_store_plugin_dir . 'includes/class-api-key.php');
        // require_once(combo_store_plugin_dir . 'includes/class-credits.php');
        // require_once(combo_store_plugin_dir . 'includes/class-register-user.php');
        // require_once(combo_store_plugin_dir . 'includes/class-export.php');
        // require_once(combo_store_plugin_dir . 'includes/class-stats.php');
        // require_once(combo_store_plugin_dir . 'includes/class-spammers.php');
        // require_once(combo_store_plugin_dir . 'includes/class-object-meta.php');
        // require_once(combo_store_plugin_dir . 'includes/class-request-lemonsqueezy.php');

        // require_once(combo_store_plugin_dir . 'includes/functions.php');
        // require_once(combo_store_plugin_dir . 'includes/functions-rest.php');
        // require_once(combo_store_plugin_dir . 'includes/functions-crons.php');
        // require_once(combo_store_plugin_dir . 'includes/JWT.php');
        // require_once(combo_store_plugin_dir . 'includes/Key.php');



        //add_action('admin_enqueue_scripts', array($this, '_admin_scripts'));
        register_activation_hook(__FILE__, array($this, '_activation'));
        register_deactivation_hook(__FILE__, array($this, '_deactivation'));
        add_filter('cron_schedules', array($this, '_cron_schedules'));
    }

    function _cron_schedules($schedules)
    {



        $schedules['1minute'] = array(
            'interval'  => 60,
            'display'   => __('1 Minute', 'combo-store-server')
        );

        $schedules['10minute'] = array(
            'interval'  => 600,
            'display'   => __('1 Minute', 'combo-store-server')
        );

        $schedules['30minute'] = array(
            'interval'  => 1800,
            'display'   => __('30 Minute', 'combo-store-server')
        );

        $schedules['6hours'] = array(
            'interval'  => 21600,
            'display'   => __('6 hours', 'combo-store-server')
        );





        return $schedules;
    }



    public function _activation()
    {


        if (!wp_next_scheduled('run_check_tasks_status')) {
            //wp_schedule_event(time(), '10minute', 'run_check_tasks_status');
        }

        if (!wp_next_scheduled('run_combo_store_task')) {
            //wp_schedule_event(time(), '10minute', 'run_combo_store_task');
        }
        if (!wp_next_scheduled('run_combo_store_add_daily_credit')) {
            //wp_schedule_event(time(), 'daily', 'run_combo_store_add_daily_credit');
        }




        do_action('combo_store_activation');
    }


    public function _deactivation()
    {

        // wp_clear_scheduled_hook('run_check_tasks_status');
        // wp_clear_scheduled_hook('run_combo_store_task');
        // wp_clear_scheduled_hook('run_combo_store_add_daily_credit');


        /*
         * Custom action hook for plugin deactivation.
         * Action hook: combo_store_deactivation
         * */
        do_action('combo_store_deactivation');
    }


    public function _admin_scripts()
    {

        wp_register_script('combo_store_js', plugins_url('assets/admin/js/scripts-layouts.js', __FILE__), array('jquery'));
        wp_localize_script(
            'combo_store_js',
            'combo_store_ajax',
            array(
                'combo_store_ajaxurl' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('combo_store_ajax_nonce'),
            )
        );

        wp_register_style('font-awesome-4', combo_store_plugin_url . 'assets/global/css/font-awesome-4.css');
        wp_register_style('font-awesome-5', combo_store_plugin_url . 'assets/global/css/font-awesome-5.css');

        wp_enqueue_script('combo_store_js');
    }
}
