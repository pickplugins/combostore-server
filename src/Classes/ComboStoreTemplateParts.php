<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreTemplateParts
{
    public $object_name = "";


    public function __construct() {}







    function create_template($params)
    {


        $name       = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description       = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $thumbnail       = isset($params['thumbnail']) ? sanitize_text_field($params['thumbnail']) : '';
        $style = isset($params['style']) ? wp_kses_post($params['style']) : null;
        $meta = isset($params['meta']) ? stripslashes_deep($params['meta']) : null;
        /* A template file sends meta (fonts, typography, colors) as an object; it was
           written as-is into a %s column, so it never reached get_template_settings.
           Stored as JSON, as update_template does. */
        if (is_array($meta) || is_object($meta)) {
            $meta = wp_json_encode($meta, JSON_UNESCAPED_UNICODE);
        }
        $pages = isset($params['pages']) ? stripslashes_deep($params['pages']) : null;


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_templates = $prefix . 'cstore_templates';

        $response = [];






        $data = array(
            'name'    => $name,
            'description'    => $description,
            'thumbnail'    => $thumbnail,
            'style'    => $style,
            'meta'    => $meta,
            'datetime'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        $format = array(
            '%s',   // name
            '%s',   // description
            '%d',   // thumbnail
            '%s',    // style
            '%s',    // meta
            '%s',    // datetime
        );

        $wpdb->insert($table_templates, $data, $format);

        $template_id = $wpdb->insert_id; // Get inserted order_id



        $template_pages = [

            'header' => ['type' => 'header',  'title' => 'Header'],
            'footer' => ['type' => 'footer',  'title' => 'Footer'],

            'shop' => ['type' => 'shop',  'title' => 'Shop'],
            'product' => ['type' => 'product', 'title' => 'Product'],
            'categories' => ['type' => 'categories', 'title' => 'Categories Archive'],
            'categories_single' => ['type' => 'categories_single', 'title' => 'Categories Single'],
            'tags' => ['type' => 'tags', 'title' => 'Tags Archive'],
            'tags_single' => ['type' => 'tags_single', 'title' => 'Tags Single'],
            'brands' => ['type' => 'brands', 'title' => 'Brands Archive'],
            'brands_single' => ['type' => 'brands_single', 'title' => 'Brands Single'],

            'thank_you' => ['type' => 'thank_you', 'title' => 'Thank you'],
            'account' => ['type' => 'account', 'title' => 'Account'],

            'home' => ['type' => 'home', 'title' => 'Home'],
            '404' => ['type' => '404', 'title' => '404'],
            'blog' => ['type' => 'blog', 'title' => 'Blog Archive'],
            'blog_single' => ['type' => 'blog_single', 'title' => 'Blog Single'],


        ];


        $pages = !empty($pages) ? $pages : $template_pages;



        foreach ($pages as $index => $page) {

            $title = isset($page['title']) ? $page['title'] : '';
            $type = isset($page['type']) ? $page['type'] : '';
            $content = isset($page['content']) ? $page['content'] : [];
            $style = isset($page['style']) ? $page['style'] : [];
            $meta = isset($page['meta']) ? $page['meta'] : [];




            $params_template_part = [
                'title' => $title,
                'template_id' => $template_id,
                'type' => $type,
                'slug' => $type,
                'content' => $content,
                'style' => $style,
                'meta' => $meta,
            ];

            $this->create_template_part($params_template_part);
        }

        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {

            $response['success'] = true;
            $response['id'] = $template_id;
        }






        return $response;
    }



    function create_template_part($params)
    {



        $title       = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $template_id = isset($params['template_id']) ? sanitize_text_field($params['template_id']) : '';
        $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $slug = isset($params['slug']) ? sanitize_text_field($params['slug']) : '';
        $content = isset($params['content']) ? stripslashes_deep($params['content']) : [];
        $style = isset($params['css']) ? wp_kses_post($params['css']) : '';
        $meta = isset($params['meta']) ? stripslashes_deep($params['meta']) : [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';

        $response = [];


        error_log(wp_json_encode($params, JSON_UNESCAPED_UNICODE));



        $data = array(
            'title'    => $title,
            'template_id'  => $template_id,
            'slug'  => $slug,
            'type'  => $type,
            'content'  => wp_json_encode($content),
            'style'  => $style,
            'meta'  => $meta,
            'datetime'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        $format = array(
            '%s',   // title
            '%d',   // template_id
            '%s',   // slug
            '%s',   // type
            '%s',   // content
            '%s',   // style
            '%s',   // meta
            '%s',    // datetime
        );

        $wpdb->insert($table, $data, $format);

        $order_id = $wpdb->insert_id; // Get inserted order_id


        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        //$ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);




        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {

            $response['success'] = true;
            $response['id'] = $order_id;
        }






        return $response;
    }


    function get_template_part($prams)
    {

        $id = isset($prams['id']) ? $prams['id'] : '';
        $slug = isset($prams['slug']) ? $prams['slug'] : '';
        $type = isset($prams['type']) ? $prams['type'] : '';
        $template_id = isset($prams['template_id']) ? $prams['template_id'] : '';
        $scope = isset($prams['scope']) ? (bool) $prams['scope'] : null;
        $includeHeaderFooter = isset($prams['includeHeaderFooter']) ? (bool) $prams['includeHeaderFooter'] : false;


        $rowData = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';

        if (!empty($id)) {
            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE id = %d ",
                    $id
                ),
                ARRAY_A
            );

            $template_id = isset($rowData['template_id']) ? $rowData['template_id'] : null;



            $content     = isset($rowData['content']) ? json_decode($rowData['content'], true) : [];
            $meta     = isset($rowData['meta']) ? json_decode($rowData['meta'], true) : [];



            /* $meta is an array (json_decode(..., true)); reading it as an object was always unset, so the toggles never applied here. */
            $disableHeader     = is_array($meta) && !empty($meta['disableHeader']);
            $disableFooter     = is_array($meta) && !empty($meta['disableFooter']);

            $rowData['content'] = $content;
            $rowData['meta'] = $meta;

            if (!$disableHeader) {

                $headerData = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE type = %s AND template_id = %d",
                        'header',
                        $template_id
                    ),
                    ARRAY_A
                );



                $rowData['header'] = $headerData;
            }
            if (!$disableFooter) {

                $footerData = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE type = %s AND template_id = %d",
                        'footer',
                        $template_id
                    ),
                    ARRAY_A
                );


                $rowData['footer'] = $footerData;
            }
        }


        if (!empty($slug)) {
            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE slug = %s AND template_id = %d",
                    $slug,
                    $template_id
                ),
                ARRAY_A
            );
        }

        if (!empty($type)) {
            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE type = %s AND template_id = %d",
                    $type,
                    $template_id
                ),
                ARRAY_A
            );
        }










        // $content = $rowData['content'];


        return $rowData;
    }


    function get_active_template()
    {



        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_templates';
        $combo_store_settings = get_option('combo_store_settings', []);
        $template_id = isset($combo_store_settings['site_template']['template_id']) ? $combo_store_settings['site_template']['template_id'] : '';



        $expenseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $template_id
            ),
            ARRAY_A
        );

        $prams = ['template_id' => $template_id];


        $files =  $this->get_template_parts($prams);

        $posts = isset($files['posts']) ? $files['posts'] : [];
        $expenseRow['pages'] = $posts;




        return $expenseRow;
    }



    function get_template($id)
    {



        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_templates';

        $id = 9;


        $expenseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        $prams = ['template_id' => $id];


        $files =  $this->get_template_parts($prams);

        $posts = isset($files['posts']) ? $files['posts'] : [];
        $expenseRow['pages'] = $posts;




        return $expenseRow;
    }




    function get_template_parts($prams)
    {


        $responses = [];
        $type     = isset($prams['type']) ? sanitize_text_field($prams['type']) : "";
        $template_id     = isset($prams['template_id']) ? sanitize_text_field($prams['template_id']) : "";


        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 99;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";


        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_expenses = $prefix . 'cstore_template_parts';
        $offset = ($page - 1) * $per_page;



        $where   = [];
        $params  = [];

        if (!empty($template_id)) {
            $where[]  = "template_id = %s";
            $params[] = $template_id;
        }
        if (!empty($type)) {
            $where[]  = "type = %s";
            $params[] = $type;
        }



        $where_sql = '';

        if (!empty($where)) {
            $where_sql = "WHERE " . implode(' AND ', $where);
        }

        $allowed_order = ['ASC', 'DESC'];
        $order = strtoupper($order);

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        $sql = "SELECT * FROM $table_expenses
                    $where_sql
                    ORDER BY id $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;

        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);

        foreach ($entries as $entry) {

            $content     = isset($entry->content) ? json_decode($entry->content, true) : [];
            $meta     = isset($entry->meta) ? json_decode($entry->meta, true) : [];

            $entry->content = $content;
            $entry->meta = $meta;
        }


        $total = count($entries);





        $big = 999999999; // need an unlikely integer


        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }
    function get_page_template($prams)
    {

        $combo_store_settings = get_option('combo_store_settings', []);
        $template_id = isset($combo_store_settings['site_template']['template_id']) ? $combo_store_settings['site_template']['template_id'] : '';


        $responses = [];
        $type     = isset($prams->type) ? sanitize_text_field($prams->type) : null;
        $slug     = isset($prams->slug) ? sanitize_text_field($prams->slug) : null;
        $id     = isset($prams->id) ? sanitize_text_field($prams->id) : null;


        error_log(wp_json_encode($prams, JSON_UNESCAPED_UNICODE));



        global $wpdb;

        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_template_parts';


        if ($id) {

            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE  id = %s",
                    $id
                ),
                ARRAY_A
            );
        }
        if ($type) {

            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE template_id = %d AND type = %s",
                    $template_id,
                    $type
                ),
                ARRAY_A
            );


            $meta     = isset($rowData['meta']) ? json_decode($rowData['meta'], true) : [];
            $disableHeader     = isset($meta['disableHeader']) ? ($meta['disableHeader']) : false;
            $disableFooter     = isset($meta['disableFooter']) ? ($meta['disableFooter']) : false;




            if (!$disableHeader) {
                $rowHeader = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE template_id = %d AND type = %s",
                        $template_id,
                        'header'
                    ),
                    ARRAY_A
                );

                $rowData['header'] = $rowHeader;
            }

            if (!$disableFooter) {

                $rowFooter = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE template_id = %d AND type = %s",
                        $template_id,
                        'footer'
                    ),
                    ARRAY_A
                );

                $rowData['footer'] = $rowFooter;
            }
        }
        if ($slug) {

            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE template_id = %d AND slug = %s",
                    $template_id,
                    $slug
                ),
                ARRAY_A
            );


            $meta     = isset($rowData['meta']) ? json_decode($rowData['meta'], true) : [];
            $disableHeader     = isset($meta['disableHeader']) ? ($meta['disableHeader']) : false;
            $disableFooter     = isset($meta['disableFooter']) ? ($meta['disableFooter']) : false;




            if (!$disableHeader) {
                $rowHeader = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE template_id = %d AND type = %s",
                        $template_id,
                        'header'
                    ),
                    ARRAY_A
                );

                $rowData['header'] = $rowHeader;
            }

            if (!$disableFooter) {

                $rowFooter = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE template_id = %d AND type = %s",
                        $template_id,
                        'footer'
                    ),
                    ARRAY_A
                );

                $rowData['footer'] = $rowFooter;
            }
        }



        return $rowData;
    }


    function get_templates($prams)
    {


        $responses = [];
        $type     = isset($prams['type']) ? sanitize_text_field($prams['type']) : "";
        $template_id     = isset($prams['template_id']) ? sanitize_text_field($prams['template_id']) : "";


        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";


        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_expenses = $prefix . 'cstore_templates';
        $offset = ($page - 1) * $per_page;



        $where   = [];
        $params  = [];


        // if (!empty($type)) {
        //     $where[]  = "type = %s";
        //     $params[] = $type;
        // }



        $where_sql = '';

        if (!empty($where)) {
            $where_sql = "WHERE " . implode(' AND ', $where);
        }

        $allowed_order = ['ASC', 'DESC'];
        $order = strtoupper($order);

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        $sql = "SELECT * FROM $table_expenses
                    $where_sql
                    ORDER BY id $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;

        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);



        $total = count($entries);

        $posts = [];



        foreach ($entries as $key => $entry) {


            $template_id = isset($entry->id) ? $entry->id : '';

            $prams = ['template_id' => $template_id];


            $files =  $this->get_template_parts($prams);

            $posts = isset($files['posts']) ? $files['posts'] : [];



            $entries[$key]->pages = $posts;
        }





        $big = 999999999; // need an unlikely integer


        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }






    function update_template_part($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';




        $id   = isset($params['id']) ? floatval($params['id']) : '';
        $title   = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $type       = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $style       = isset($params['css']) ? ($params['css']) : '';
        $meta       = isset($params['meta']) ? stripslashes_deep($params['meta']) : [];
        $meta = wp_json_encode($meta, JSON_UNESCAPED_UNICODE);

        $content = isset($params['blocks']) ? stripslashes_deep($params['blocks']) : [];
        $content = wp_json_encode($content, JSON_UNESCAPED_UNICODE);



        $response = [];




        $data = array(
            'title'    => $title,
            'type'        => $type,
            'content'  => $content,
            'style'  => $style,
            'meta'  => $meta,

        );

        $format = array(
            '%s',   // title
            '%s',   // type
            '%s',   // content
            '%s',   // style
            '%s',   // meta
        );


        // ✅ WHERE clause
        $where = array('id' => $id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        if ($updated) {
            $response = true;
        } else {
            $response = false;
        }
        return $response;
    }
    function update_template($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_templates';


        $id   = isset($params['id']) ? floatval($params['id']) : '';
        $name   = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description       = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $thumbnail       = isset($params['thumbnail']) ? ($params['thumbnail']) : '';
        $style       = isset($params['style']) ? ($params['style']) : '';
        $meta       = isset($params['meta']) ? stripslashes_deep($params['meta']) : [];
        $meta = wp_json_encode($meta, JSON_UNESCAPED_UNICODE);


        $response = [];






        // Original data
        $data = array(
            'name'    => $name,
            'description'        => $description,
            'thumbnail'  => $thumbnail,
            'style'  => $style,
            'meta'  => $meta,

        );

        // Format map
        $format_map = array(
            'name'         => '%s',
            'description'         => '%s',
            /* These were '%f', so the JSON meta (fonts, colors), the stylesheet and a
               thumbnail URL were all written as a number - every template's meta in
               the table was empty. */
            'thumbnail'   => '%s',
            'style' => '%s',
            'meta' => '%s',

        );

        // Filter only non-empty values
        $filtered_data   = array();
        $filtered_format = array();

        foreach ($data as $key => $value) {

            // Skip NULL or empty string
            if ($value !== 0 && $value !== null && $value !== '') {

                $filtered_data[$key]   = $value;
                $filtered_format[]     = $format_map[$key];
            }
        }



        $where = array('id' => $id);
        $where_format = array('%d');

        $updated = false;

        // Run update only if data exists
        if (!empty($filtered_data)) {
            $updated = $wpdb->update(
                $table,
                $filtered_data,
                $where,
                $filtered_format,
                $where_format
            );
        }







        // // ✅ WHERE clause
        // $where = array('id' => $id);
        // $where_format = array('%d');

        // // ✅ Perform update
        // $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        /* $wpdb->update returns 0 when the row already held these values; only false is an error. */
        if ($updated !== false && !empty($filtered_data)) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }
        return $response;
    }







    function delete_template($template_id)
    {


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_templates = $prefix . 'cstore_templates';
        $table_template_parts = $prefix . 'cstore_template_parts';


        $response = [];

        if (!$template_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table_template_parts, array('template_id' => $template_id), array('%d'));
        $status = $wpdb->delete($table_templates, array('id' => $template_id), array('%d'));



        $response['status'] = $status;

        return $response;
    }









    function delete_template_part($id)
    {


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id), array('%d'));



        $response['status'] = $status;

        return $response;
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
