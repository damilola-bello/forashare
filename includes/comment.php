<?php
  function make_comment($dbc, $post_id, $comment_count, $loggedin, $start = 0, $limit = 10, $eligible_to_comment = false, $view_user_username = null, $view_user_profile_image = null,$view_user_id = null) {

    $c_a = array();
    $loaded_comments = 0;
    $comments = array();

    $stmt_c = $dbc->prepare("SELECT comment_id FROM comment WHERE post_id = ? AND parent_comment_id IS NULL ORDER BY comment_date DESC LIMIT ?, ? ");
    $stmt_c->bind_param("iii", $post_id, $start, $limit);
    $stmt_c->execute();
    $stmt_c->store_result();
    if ($stmt_c->num_rows > 0) {
      $stmt_c->bind_result($comment_id);
      //get the number of comments
      $loaded_comments = $stmt_c->num_rows;


      //loop through all comments
      while ($stmt_c->fetch()) {
        //query to get the details of the comment
        $stmt_temp = $dbc->prepare("SELECT c.comment_text, c.comment_date, c.likes, c.dislikes, c.replies, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, c.comment_date, NOW()) AS seconds, DATE_FORMAT(c.comment_date, '%b %e'), DATE_FORMAT(c.comment_date, ' at %h:%i %p'), DATE_FORMAT(c.comment_date, '%y'), DATE_FORMAT(NOW(), '%b %e'), DAY(c.comment_date), DAY(NOW()) FROM comment AS c JOIN user AS u ON c.user_id = u.user_id JOIN tag AS t ON u.tag_id = t.tag_id WHERE comment_id = ?");
        $stmt_temp->bind_param("i", $comment_id);
        $stmt_temp->execute();
        $stmt_temp->store_result();
        $stmt_temp->bind_result($comment_text, $comment_date, $comment_likes_count, $comment_dislikes_count, $total_replies, $comment_user_id, $comment_user_username, $comment_user_profile_image, $comment_user_forum, $seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today);
        $stmt_temp->fetch();
        $stmt_temp->free_result();
        $stmt_temp->close();
        unset($stmt_temp);

        $comment_liked = false;
        $comment_disliked = false;
        if($eligible_to_comment) {

          //query to check if the view user has ever liked/disliked the current comment
          $stmt = $dbc->prepare("SELECT is_like FROM comment_likes WHERE comment_id = ? AND user_id = ?");
          $stmt->bind_param("ii", $comment_id, $view_user_id);
          $stmt->execute();
          $stmt->store_result();


          if($stmt->num_rows == 1) {
            $stmt->bind_result($is_like);
            $stmt->fetch();


            if($is_like == 1) {
              $comment_liked = true;
            } else if($is_like == 0) {
              $comment_disliked = true;
            }

          }
          $stmt->free_result();
          $stmt->close();
        }
        
        //build the json array
        $new_comment = array(
          'comment_id' => $comment_id,
          'comment_text' => htmlspecialchars($comment_text),//, ENT_QUOTES),
          'comment_date' => $comment_date,
          'comment_likes_count' => $comment_likes_count,
          'comment_dislikes_count' => $comment_dislikes_count,
          'total_replies' => $total_replies,
          'loaded_replies' => 0,
          'comment_user_id' => $comment_user_id,
          'comment_user_username' => ucfirst($comment_user_username),
          'comment_user_forum' => $comment_user_forum,
          'comment_user_profile_image' => ($comment_user_profile_image == null ? 'user_icon.png' : $comment_user_profile_image),
          'comment_how_long' => calculate($seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today),
          'comment_liked' => $comment_liked,
          'comment_disliked' => $comment_disliked,
          'post_id' => $post_id,
          'view_user_profile_image' => ($view_user_profile_image == null ? 'user_icon.png' : $view_user_profile_image),
          'view_user_id' => $view_user_id,
          'is_owner' => ($view_user_id == $comment_user_id ? true : false)
        );

        //push to array
        array_push($comments, $new_comment);
      }


    }
    
    $c_a = array(
      /*'view_user_username' => ucfirst($view_user_username),
      'view_user_profile_image' => ($view_user_profile_image == null ? 'user_icon.png' : $view_user_profile_image),
      'view_user_id' => $view_user_id,
      'eligible_to_comment' => $eligible_to_comment,
      'is_loggedin' => $loggedin,
      'loaded_comments' => $loaded_comments,
      'post_id' => $post_id,*/
      'total_comments' => $comment_count,
      'comments' => $comments
    );

    return $c_a;
  }
?>