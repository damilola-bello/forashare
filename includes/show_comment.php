<?php
  function make_comment($dbc, $post_id) {
    $c_a = array();
    $stmt_c = $dbc->prepare("SELECT comment_id FROM comment WHERE post_id = ? AND parent_comment_id IS NULL ORDER BY comment_date DESC");
    $stmt_c->bind_param("i", $post_id);
    $stmt_c->execute();
    $stmt_c->store_result();
    if ($stmt_c->num_rows > 0) {
      $stmt_c->bind_result($comment_id);
      //get the number of comments
      $comment_count = $stmt_c->num_rows;

      $comments = array();

      //loop through all comments
      while ($stmt_c->fetch()) {
        //query to get the details of the comment
        $stmt_temp = $dbc->prepare("SELECT c.comment_text, c.comment_date, c.likes, c.dislikes, c.replies, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, c.comment_date, NOW()) AS seconds FROM comment AS c JOIN user AS u ON c.user_id = u.user_id JOIN tag AS t ON u.tag_id = t.tag_id WHERE comment_id = ?");
        $stmt_temp->bind_param("i", $comment_id);
        $stmt_temp->execute();
        $stmt_temp->store_result();
        $stmt_temp->bind_result($comment_text, $comment_date, $comment_likes_count, $comment_dislikes_count, $comment_replies_count, $comment_user_id, $comment_user_username, $comment_user_profile_image, $comment_user_forum, $seconds);
        $stmt_temp->fetch();
        $stmt_temp->free_result();
        $stmt_temp->close();
        unset($stmt_temp);
        
        $comment_user_first_letter = strtoupper(substr($comment_user_username, 0, 1));
        //build the json array
        $new_comment = array(
          'comment_id' => $comment_id,
          'comment_text' => $comment_text,
          'comment_date' => $comment_date,
          'comment_likes_count' => $comment_likes_count,
          'comment_dislikes_count' => $comment_dislikes_count,
          'comment_replies_count' => $comment_replies_count,
          'comment_user_id' => $comment_user_id,
          'comment_user_username' => ucfirst($comment_user_username),
          'comment_user_forum' => $comment_user_forum,
          'comment_user_profile_image' => $comment_user_profile_image,
          'comment_how_long' => calculate($seconds)
          'comment_user_first_letter' => $comment_user_first_letter
        );

        //push to array
        array_push($comments, $new_comment);

      }

      $c_a = array(
        'total_comments' => $comment_count,
        'comments' => $comments
      );


    }

    return $c_a;
  }
?>