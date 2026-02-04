<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access

use WP_Query;



class ComboStoreCrons
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";




    public function __construct()
    {


        // add_action('run_update_product_description', [$this, 'run_update_product_description_gemini']);
        // add_action('run_update_blog_description', [$this, 'run_update_blog_description_gemini']);
        // add_action('run_update_blog_slug', [$this, 'run_update_blog_slug']);
    }






    function run_update_blog_slug()
    {
        $meta_query = array();
        $tax_query = array();
        $job_category = "";
        $response = [];

        





        // $tax_query[] = array(
        //     'taxonomy' => 'job_category',
        //     'field'    => 'slug',
        //     'terms'    => $job_category,
        //     //'operator'    => '',
        // );

        $meta_query[] = array(
            'key' => 'run_update_blog_slug3',
            'value' => "",
            'compare' => 'NOT EXISTS',
        );

        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'pending',
            // 's' => "",
            'orderby' => 'date',
            'order' => 'DESC',
            // 'meta_query' => $meta_query,
            // 'tax_query' => $tax_query,
            'order' => 'DESC',
            'posts_per_page' => 1,
            'paged' => 1,
        );

        $wp_query = new WP_Query($query_args);



        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                //$post_data = get_post($post_id);


                

                $post_title = get_the_title();

                // $product_updated =  get_post_meta($post_id, 'run_update_blog_slug3', true);



                // if ($product_updated == 'done') {
                //     $response['already_updated'] = 'already_updated';
                //     die(wp_json_encode($response));
                // }










                if (empty($post_title)) {
                    //update_post_meta($post_id, 'run_update_blog_slug3', "skipped");

                    $response['run_update_blog_slug3'] = "post title empty $post_id";


                    $postData = [
                        "ID" => $post_id,
                        'post_status' => 'trash',
                    ];
                    wp_update_post($postData);


                    die(wp_json_encode($response));
                }

                $promptText = "Please translate following blog title to English for seo optimized post slug
'$post_title'

do not add any other text.
";



                $url = "https://text.pollinations.ai/" . urlencode_deep($promptText);



                // Send query to the license manager server
                $api_response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));


                // Check for error in the response
                if (is_wp_error($api_response)) {
                    

                    $response['failed'] = "API Error $post_id";



                    return wp_json_encode($response);
                } else {
                    $body_data = wp_remote_retrieve_body($api_response);



                    if (strpos($body_data, "502 Bad Gateway") !== false) {
                        $response['failed'] = "API Error $post_id";

                        


                        return wp_json_encode($response);
                    }


                    if (strpos($body_data, "azure-openai error") !== false) {
                        $response['failed'] = "API Error $post_id";
                        


                        return wp_json_encode($response);
                    }



                    //update_post_meta($post_id, 'run_update_blog_slug3', "done");


                    $postData = [
                        "ID" => $post_id,
                        "post_name" =>  sanitize_title($body_data),
                        'post_status' => 'publish',
                    ];
                    wp_update_post($postData);
                    $response['updated'] = "Updated $post_id";
                }










                $count++;
            endwhile;


            wp_reset_query();
            wp_reset_postdata();
        else :


        endif;

        return wp_json_encode($response);
    }














    function run_update_blog_description_gemini()
    {
        $meta_query = array();
        $tax_query = array();
        $job_category = "";
        $response = [];




        // $tax_query[] = array(
        //     'taxonomy' => 'job_category',
        //     'field'    => 'slug',
        //     'terms'    => $job_category,
        //     //'operator'    => '',
        // );

        $meta_query[] = array(
            'key' => 'blog_description_updated',
            'value' => "",
            'compare' => 'NOT EXISTS',
        );

        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            // 's' => "",
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
            // 'tax_query' => $tax_query,
            'order' => 'DESC',
            'posts_per_page' => 1,
            'paged' => 1,
        );

        $wp_query = new WP_Query($query_args);



        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                //$post_data = get_post($post_id);

                $post_title = get_the_title();

                $product_updated =  get_post_meta($post_id, 'blog_description_updated2', true);



                if ($product_updated == 'done') {
                    $response['already_updated'] = 'already_updated';
                    die(wp_json_encode($response));
                }










                if (empty($post_title)) {
                    update_post_meta($post_id, 'blog_description_updated2', "skipped");

                    $response['blog_description_updated2'] = "post title empty $post_id";

                    die(wp_json_encode($response));
                }

                $promptText = "Write a long-form, SEO-optimized blog in Bangla for parents of babies, toddlers, and young kids. The blog will be published on a kids store website.

👉 Blog Title / Topic: $post_title

Instructions:

AI should automatically generate relevant SEO keywords based on the above topic and naturally integrate them into the content, please do not keep keywords list in content.
Start with an engaging introduction that highlights why the topic matters for parents.
Organize the blog with H2 and H3 subheadings related to the topic.
Provide practical parenting tips, bullet points, checklists, or age-wise guidance where applicable.
Keep the tone warm, supportive, and conversational in Bangla.
Blend expert insights + relatable real-life parenting examples.
End with a call-to-action linking to the kids store products or reminding parents about healthier alternatives (toys, books, child care essentials, etc.).
Do not include meta description (we will add later).
Please do not add any other text and reference.
";




                // Gemini API endpoint
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyDDxpa0KvgO2MSKMv2CO-ilx8ihslV1bQg";

                // Your prompt/input
                $data = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $promptText]
                            ]
                        ]
                    ]
                ];

                // Initialize cURL
                $ch = curl_init($url);

                // Set cURL options
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                // Execute request
                $response = curl_exec($ch);

                // Error handling
                if (curl_errno($ch)) {
                } else {
                    // Decode JSON response
                    $result = json_decode($response, true);



                    //print_r($result);

                    // Extract only text output
                    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {


                        $body_data = $result['candidates'][0]['content']['parts'][0]['text'];

                        $postData = [
                            "ID" => $post_id,
                            "post_content" => $body_data,
                            'post_status' => 'pending',
                        ];
                        wp_update_post($postData);
                        update_post_meta($post_id, 'blog_description_updated2', "done");
                    } else {
                    }
                }

                curl_close($ch);





                $count++;
            endwhile;


            wp_reset_query();
            wp_reset_postdata();
        else :


        endif;

        return wp_json_encode($response);
    }

    function run_update_product_description_gemini()
    {
        $meta_query = array();
        $tax_query = array();
        $job_category = "";
        $response = [];

        


        // $tax_query[] = array(
        //     'taxonomy' => 'job_category',
        //     'field'    => 'slug',
        //     'terms'    => $job_category,
        //     //'operator'    => '',
        // );

        $meta_query[] = array(
            'key' => 'product_description_updated4',
            'value' => "",
            'compare' => 'NOT EXISTS',
        );

        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'draft',
            // 's' => "",
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
            // 'tax_query' => $tax_query,
            'order' => 'DESC',
            'posts_per_page' => 1,
            'paged' => 1,
        );

        $wp_query = new WP_Query($query_args);



        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                //$post_data = get_post($post_id);

                $post_title = get_the_title();

                $product_updated =  get_post_meta($post_id, 'product_description_updated4', true);



                if ($product_updated == 'done') {
                    $response['already_updated'] = 'already_updated';
                    die(wp_json_encode($response));
                }










                if (empty($post_title)) {
                    update_post_meta($post_id, 'product_description_updated4', "skipped");

                    $response['product_description_updated4'] = "post title empty $post_id";

                    die(wp_json_encode($response));
                }

                $promptText = "Write a detailed product description in English for a kids & parenting audience. The content should be SEO-friendly, engaging, and parent-focused. The product will be provided as input.

👉 Product Name / Type: $post_title

Content Structure:

Introduction (2 Paragraphs)
Paragraph 1: Introduce the product, its purpose, and how it relates to kids/parents.
Paragraph 2: Highlight why parents should consider it, emphasizing safety, quality, and parenting convenience.

Features (with heading)
Provide a bullet list of main features of the product.
Focus on design, usability, material, safety, and age-appropriateness.

Benefits (with heading)
Provide a bullet list of benefits for both kids and parents.
Explain how it improves daily life, health, or learning experience.

Disclaimer (with heading)
Provide a short bullet list of disclaimers (safety instructions, age restrictions, usage guidelines).

FAQ (with heading)
Add a list of frequently asked questions parents might search on Google about this product type.
Each FAQ should have a concise answer.

Important Rules:
Do not add any references, external links, or unnecessary text outside the structure.
Keep the tone warm, supportive, and trustworthy for parents.
Automatically generate SEO keywords naturally based on the product type.
";




                // Gemini API endpoint
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyDDxpa0KvgO2MSKMv2CO-ilx8ihslV1bQg";

                // Your prompt/input
                $data = [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $promptText]
                            ]
                        ]
                    ]
                ];

                // Initialize cURL
                $ch = curl_init($url);

                // Set cURL options
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                // Execute request
                $response = curl_exec($ch);
                

                // Error handling
                if (curl_errno($ch)) {
                } else {
                    // Decode JSON response
                    $result = json_decode($response, true);



                    //print_r($result);

                    // Extract only text output
                    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {


                        $body_data = $result['candidates'][0]['content']['parts'][0]['text'];

                        $postData = [
                            "ID" => $post_id,
                            "post_content" => $body_data,
                            'post_status' => 'publish',
                        ];
                        wp_update_post($postData);
                        update_post_meta($post_id, 'product_description_updated4', "done");
                    } else {
                    }
                }

                curl_close($ch);





                $count++;
            endwhile;


            wp_reset_query();
            wp_reset_postdata();
        else :


        endif;

        return wp_json_encode($response);
    }






    function run_update_product_description()
    {
        $meta_query = array();
        $tax_query = array();
        $job_category = "";
        $response = [];




        // $tax_query[] = array(
        //     'taxonomy' => 'job_category',
        //     'field'    => 'slug',
        //     'terms'    => $job_category,
        //     //'operator'    => '',
        // );

        $meta_query[] = array(
            'key' => 'product_description_updated1',
            'value' => "",
            'compare' => 'NOT EXISTS',
        );

        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'draft',
            // 's' => "",
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
            // 'tax_query' => $tax_query,
            'order' => 'DESC',
            'posts_per_page' => 1,
            'paged' => 1,
        );

        $wp_query = new WP_Query($query_args);



        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                //$post_data = get_post($post_id);

                $post_title = get_the_title();

                $product_updated =  get_post_meta($post_id, 'product_description_updated1', true);



                if ($product_updated == 'done') {
                    $response['already_updated'] = 'already_updated';
                    die(wp_json_encode($response));
                }










                if (empty($post_title)) {
                    update_post_meta($post_id, 'product_description_updated1', "skipped");

                    $response['product_description_updated1'] = "post title empty $post_id";

                    die(wp_json_encode($response));
                }

                $promptText = "I want you act as expert product description writer and expert marketer and SEO analyzer for ecommerce website, we are running baby shop online, we need to write product description for following product *$post_title* Please do a keyword research first, our content should write in SEO optimized and well keyword distribution.
**Introduction**
write introduction in 120 word in two separate paragraphs, introduction should attract customer attention and hook the product. 

List of features, as much as possible in bullet list with heading h2 tag
List of Benefits with heading h2 tag.
Section List of Disclaimer with heading h2 tag.
Section List of FAQ with heading h2 tag.
5 Top frequently asked questions with answer that people asked on Google and other search engine.


Please use heading tags h2 and other where possible and use html tags ass needed. 
Do not add any other text and any reference.
";

                //At bottom please keep comma separate tags from keywords.


                $url = "https://text.pollinations.ai/" . urlencode_deep($promptText);



                // Send query to the license manager server
                $api_response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));

                // Check for error in the response
                if (is_wp_error($api_response)) {
                    

                    $response['failed'] = "API Error $post_id";



                    return wp_json_encode($response);
                } else {
                    $body_data = wp_remote_retrieve_body($api_response);


                    if (strpos($body_data, "azure-openai error") !== false) {
                        $response['failed'] = "API Error $post_id";
                    } else {

                        update_post_meta($post_id, 'product_description_updated1', "done");



                        $postData = [
                            "ID" => $post_id,
                            "post_content" => $body_data,
                            'post_status' => 'publish',

                        ];
                        wp_update_post($postData);
                        $response['updated'] = "Updated $post_id";
                    }
                }










                $count++;
            endwhile;


            wp_reset_query();
            wp_reset_postdata();
        else :


        endif;

        return wp_json_encode($response);
    }

















    function run_update_blog_description()
    {
        $meta_query = array();
        $tax_query = array();
        $job_category = "";
        $response = [];





        // $tax_query[] = array(
        //     'taxonomy' => 'job_category',
        //     'field'    => 'slug',
        //     'terms'    => $job_category,
        //     //'operator'    => '',
        // );

        $meta_query[] = array(
            'key' => 'blog_description_updated',
            'value' => "",
            'compare' => 'NOT EXISTS',
        );

        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            // 's' => "",
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
            // 'tax_query' => $tax_query,
            'order' => 'DESC',
            'posts_per_page' => 1,
            'paged' => 1,
        );

        $wp_query = new WP_Query($query_args);



        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                //$post_data = get_post($post_id);

                $post_title = get_the_title();

                $product_updated =  get_post_meta($post_id, 'blog_description_updated', true);



                if ($product_updated == 'done') {
                    $response['already_updated'] = 'already_updated';
                    die(wp_json_encode($response));
                }










                if (empty($post_title)) {
                    update_post_meta($post_id, 'blog_description_updated', "skipped");

                    $response['blog_description_updated'] = "post title empty $post_id";

                    die(wp_json_encode($response));
                }

                $promptText = "Write a long-form, SEO-optimized blog in Bangla for parents of babies, toddlers, and young kids. The blog will be published on a kids store website.

👉 Blog Title / Topic: $post_title

Instructions:

AI should automatically generate relevant SEO keywords based on the above topic and naturally integrate them into the content.
Start with an engaging introduction that highlights why the topic matters for parents.
Organize the blog with H2 and H3 subheadings related to the topic.
Provide practical parenting tips, bullet points, checklists, or age-wise guidance where applicable.
Keep the tone warm, supportive, and conversational in Bangla.
Blend expert insights + relatable real-life parenting examples.
End with a call-to-action linking to the kids store products or reminding parents about healthier alternatives (toys, books, child care essentials, etc.).
Do not include meta description (we will add later).
";



                $url = "https://text.pollinations.ai/" . urlencode_deep($promptText);



                // Send query to the license manager server
                $api_response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));


                // Check for error in the response
                if (is_wp_error($api_response)) {

                    $response['failed'] = "API Error $post_id";


                    return wp_json_encode($response);
                } else {
                    $body_data = wp_remote_retrieve_body($api_response);



                    if (strpos($body_data, "502 Bad Gateway") !== false) {
                        $response['failed'] = "API Error $post_id";



                        return wp_json_encode($response);
                    }


                    if (strpos($body_data, "azure-openai error") !== false) {
                        $response['failed'] = "API Error $post_id";


                        return wp_json_encode($response);
                    }



                    update_post_meta($post_id, 'blog_description_updated', "done");



                    $postData = [
                        "ID" => $post_id,
                        "post_content" => $body_data,
                        'post_status' => 'pending',
                    ];
                    wp_update_post($postData);
                    $response['updated'] = "Updated $post_id";
                }










                $count++;
            endwhile;


            wp_reset_postdata();
        else :


        endif;

        return wp_json_encode($response);
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
