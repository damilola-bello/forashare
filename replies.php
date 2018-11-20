<?php
  function show_replies($dbc, $post_id, $reply_count, $comment_id, $loggedin, $limit = 10, $eligible_to_reply = false, $view_user_username = null, $view_user_profile_image = null,$view_user_id = null) {

    $c_a = array();
    $eligible_to_reply = true;
    $loaded_replies = 0;
    $replies = array();

    //load all replies
    $stmt_c = $dbc->prepare("SELECT comment_id FROM comment WHERE post_id = ? AND parent_comment_id = ? ORDER BY comment_date DESC LIMIT ? ");
    $stmt_c->bind_param("iii", $post_id, $comment_id, $limit);
    $stmt_c->execute();
    $stmt_c->store_result();
    if ($stmt_c->num_rows > 0) {
      $stmt_c->bind_result($reply_id);
      //get the number of replies
      $loaded_replies = $stmt_c->num_rows;


      //loop through all replies
      while ($stmt_c->fetch()) {
        //query to get the details of the reply
        $stmt_temp = $dbc->prepare("SELECT c.comment_text, c.comment_date, c.likes, c.dislikes, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, c.comment_date, NOW()) AS seconds, DATE_FORMAT(c.comment_date, '%b %e'), DATE_FORMAT(c.comment_date, ' at %h:%i %p'), DATE_FORMAT(c.comment_date, '%y'), DATE_FORMAT(NOW(), '%b %e'), DAY(c.comment_date), DAY(NOW()) FROM comment AS c JOIN user AS u ON c.user_id = u.user_id JOIN tag AS t ON u.tag_id = t.tag_id WHERE comment_id = ?");
        $stmt_temp->bind_param("i", $reply_id);
        $stmt_temp->execute();
        $stmt_temp->store_result();
        $stmt_temp->bind_result($reply_text, $reply_date, $reply_likes_count, $reply_dislikes_count, $reply_user_id, $reply_user_username, $reply_user_profile_image, $reply_user_forum, $seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today);
        $stmt_temp->fetch();
        $stmt_temp->free_result();
        $stmt_temp->close();
        unset($stmt_temp);

        $reply_liked = false;
        $reply_disliked = false;


        //query to check if the view user has ever liked/disliked the current reply
        $stmt = $dbc->prepare("SELECT is_like FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reply_id, $view_user_id);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows == 1) {
          $stmt->bind_result($is_like);
          $stmt->fetch();


          if($is_like == 1) {
            $reply_liked = true;
          } else if($is_like == 0) {
            $reply_disliked = true;
          }

        }
        $stmt->free_result();
        $stmt->close();
        
        //build the json array
        $new_reply = array(
          'reply_id' => $reply_id,
          'reply_text' => htmlspecialchars($reply_text),
          'reply_date' => $reply_date,
          'reply_likes_count' => $reply_likes_count,
          'reply_dislikes_count' => $reply_dislikes_count,
          'reply_user_id' => $reply_user_id,
          'reply_user_username' => ucfirst($reply_user_username),
          'reply_user_forum' => $reply_user_forum,
          'reply_user_profile_image' => ($reply_user_profile_image == null ? 'user_icon.png' : $reply_user_profile_image),
          'reply_how_long' => calculate($seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today),
          'reply_liked' => $reply_liked,
          'reply_disliked' => $reply_disliked,
          'post_id' => $post_id,
          'comment_id' => $comment_id,
          'view_user_id' => $view_user_id
        );

        //push to array
        array_push($replies, $new_reply);
      }


    }
    $stmt_c->free_result();
    $stmt_c->close();
    
    $r_a = array(
      'view_user_username' => ucfirst($view_user_username),
      'view_user_profile_image' => ($view_user_profile_image == null ? 'user_icon.png' : $view_user_profile_image),
      'view_user_id' => $view_user_id,
      'eligible_to_reply' => $eligible_to_reply,
      'is_loggedin' => $loggedin,
      'post_id' => $post_id,
      'comment_id' => $comment_id,
      'total_replies' => $reply_count,
      'loaded_replies' => $loaded_replies,
      'replies' => $replies
    );

    return $r_a;
  }
?>