<?php
if (! defined('ABSPATH')) exit;  // if direct access



add_action("combo_store_comment_submitted", "combo_store_comment_submitted_email_subscribe",);

function combo_store_comment_submitted_email_subscribe($prams)
{
  $email     = isset($prams['comment_author_email']) ? sanitize_email($prams['comment_author_email']) : "";
  $name     = isset($prams['comment_author']) ? sanitize_text_field($prams['comment_author']) : "";
  $list = "1146382";

  $args = [];
  $args['email'] = $email;
  $args['name'] = $name;
  $args['list'] = $list;

  //combo_store_email_subscribe($args);
}



function combo_store_has_rated($prams)
{

  $userid = isset($prams['userid']) ? $prams['userid'] : null;
  $postId = isset($prams['postId']) ? $prams['postId'] : 0;

  $args = array(
    'post_id' => $postId,
    'user_id'   => $userid,
    'count'   => true,

    'meta_query' => array(
      array(
        'key'     => 'rate',
        'compare' => 'EXISTS'
      )
    )
  );
  $comments_count = get_comments($args);

  if ($comments_count > 0) {
    return true;
  } else {
    return false;
  }
}
