<?php
if (! defined('ABSPATH')) exit;  // if direct access





function combo_store_email_subscribe($request)
{


  $response = [];


  $list     = isset($request['list']) ? sanitize_text_field($request['list']) : "";
  $email     = isset($request['email']) ? sanitize_email($request['email']) : "";
  $name     = isset($request['name']) ? sanitize_text_field($request['name']) : "";


  if (empty($email)) {
    $response['messages']['email_empty'] = 'Email Address should not empty.';
    $response['errors'] = true;

    die(wp_json_encode($response));
  }
  if (empty($name)) {
    $response['messages']['name_empty'] = 'Name Address should not empty.';
    $response['errors'] = true;

    die(wp_json_encode($response));
  }




  $lists = ($list) ? $list : '1142930';

  $acumbamailApiKeys =  "0256e1e206d2473aaa6344f7cddc7617";



  $subscribers_data = array(
    array("email" => $email, "name" => $name),
  );

  $subscribers_data = json_encode($subscribers_data);


  $data = array(
    "list_id" => $lists,
    "subscribers_data" => $subscribers_data
  );

  $url = "https://acumbamail.com/api/1/batchAddSubscribers/";

  $fields = array(
    //'customer_id' => $this->customer_id,
    'auth_token' => $acumbamailApiKeys,
    'response_type' => 'json',
  );

  if (count($data) != 0) {
    $fields = array_merge($fields, $data);
  }

  $postdata = http_build_query($fields);

  $opts = array('http' => array(
    'method' => 'POST',
    'header' => 'Content-type: application/x-www-form-urlencoded',
    'content' => $postdata
  ));

  $api_response = @file_get_contents(
    $url,
    false,
    stream_context_create($opts)
  );


  $json = json_decode($api_response, true);



  if (empty($json) || $json == null) {
    $response['messages']['failed'] = 'Sorry, there is an error.';
    die(wp_json_encode($response));
  }

  if (is_array($json)) {

    foreach ($json as $index => $json_data) {
      $errorText = reset($json_data)['error'];

      if (!empty($errorText)) {
        $response['messages'][$index] = $errorText;
        die(wp_json_encode($response));
      }
    }
  }


  $response['messages']['subscribed'] = "Thank you for your subscribe.";


  die(wp_json_encode($response));
}


add_action("combo_store_user_created", "combo_store_user_created_email_subscribe");

function combo_store_user_created_email_subscribe($request)
{
  // Subscribe to News Letter

  $email     = isset($request['email']) ? sanitize_email($request['email']) : "";
  $username     = isset($request['username']) ? sanitize_text_field($request['username']) : "";


  $lists = '1142930';
  $acumbamailApiKeys =  "0256e1e206d2473aaa6344f7cddc7617";
  $subscribers_data = array(
    array("email" => $email, "name" => $username),
  );

  $subscribers_data = json_encode($subscribers_data);

  $data = array(
    "list_id" => $lists,
    "subscribers_data" => $subscribers_data
  );

  $url = "https://acumbamail.com/api/1/batchAddSubscribers/";

  $fields = array(
    //'customer_id' => $this->customer_id,
    'auth_token' => $acumbamailApiKeys,
    'response_type' => 'json',
  );

  if (count($data) != 0) {
    $fields = array_merge($fields, $data);
  }

  $postdata = http_build_query($fields);

  $opts = array('http' => array(
    'method' => 'POST',
    'header' => 'Content-type: application/x-www-form-urlencoded',
    'content' => $postdata
  ));
  $api_response = @file_get_contents(
    $url,
    false,
    stream_context_create($opts)
  );
}
