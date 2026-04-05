<?php

namespace ComboStore\Classes;

use Error;
use WP_Query;

use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStorePages
{
    //public $id = "";


    public function __construct()
    {


        //$this->id = $product_id;
    }






/**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_pages($prams)
    {
        

        $paged     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 12;
        $orderby     = isset($prams['orderby']) ? sanitize_text_field($prams['orderby']) : "rand";
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "";
        $keyword     = isset($prams['keyword']) ? sanitize_text_field($prams['keyword']) : "";
        $category     = isset($prams['category']) ? sanitize_text_field($prams['category']) : "";
        $categories     = isset($prams['categories']) ? ($prams['categories']) : [];


        $query_args = [];
        $meta_query = [];
        $tax_query = [];


     


    

        $query_args['post_type'] = 'page';
        $query_args['post_status'] = 'any';



        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }


        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
        }
        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($orderby)) {
            $query_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $query_args['order'] = $order;
        }
        // if (!empty($meta_query)) {
        //     $query_args['meta_query'] = $meta_query;
        // }


        $wp_query = new WP_Query($query_args);


        $entries = [];

        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                $postData = get_post($post_id);
                $postAuthorID = isset($postData->post_author) ? $postData->post_author : null;

                $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
                $featured = get_post_meta($post_id, 'featured', true);
                $author_name = isset($postAuthorID) ? get_the_author_meta("display_name", $postAuthorID) : null;
                $avatar_url = isset($postAuthorID) ? get_avatar_url($postAuthorID, ["size" => 40]) : null;

                $author_id = get_the_author_meta("ID", $postAuthorID);
                $author =  ["name" => $author_name, "id" => $author_id, "avatar" => $avatar_url];

         
                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "slug" => isset($postData->post_name) ? $postData->post_name : null,
                    "content" => isset($postData->post_content) ? $postData->post_content : null,
                    "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "author" => $author,
                  
                ];

            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;

        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }




    function create_page($prams)
    {
         $title     = isset($prams['title']) ? sanitize_text_field($prams['title']) :'';

        $args = array(
            'post_title'    => $title,
            'post_type'    => 'page',
            'post_content'  => '',
            'post_status'   => 'publish',
            'post_author'   => 1,
        );

        // Insert the post into the database.
        $post_id = wp_insert_post($args);

if(!is_wp_error($post_id)){

}else{


}

        return $response;
    }
    function get_page()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
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
