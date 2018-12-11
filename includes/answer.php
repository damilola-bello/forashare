<?php
  function make_answer($dbc, $question_id, $answer_count, $loggedin, $ansID, $sortType = 'newest', $start = 0, $limit = 10, $eligible_to_answer = false, $view_user_username = null, $view_user_profile_image = null,$view_user_id = null) {

    $c_a = array();
    $loaded_answers = 0;
    $answers = array();
    $answer_focus_count = 0;


    $sortString = '';
    switch ($sortType) {
      case 'oldest':
        $sortString = " answer_date ASC ";
        break;
      case 'score':
        $sortString = " score DESC ";
        break;
      default:
        $sortString = " answer_date DESC ";
        break;
    }

    if($ansID != null) {
      //fetch a particular answer if the user requested it
      $stmt_c = $dbc->prepare("SELECT answer_id FROM answer WHERE question_id = ? AND answer_id = ? AND parent_answer_id IS NULL ORDER BY answer_date DESC LIMIT 0, 1 ");
      $stmt_c->bind_param("ii", $question_id, $ansID);
      $stmt_c->execute();
      $stmt_c->store_result();
      $answer_focus_count = $stmt_c->num_rows;
    }
    if($answer_focus_count == 0) {
      //if the user requested a particular answer and it doesn't exist or if the user didn't request one at all.
      $stmt_c = $dbc->prepare("SELECT answer_id FROM answer WHERE question_id = ? AND parent_answer_id IS NULL ORDER BY $sortString LIMIT ?, ? ");
      $stmt_c->bind_param("iii", $question_id, $start, $limit);
      $stmt_c->execute();
      $stmt_c->store_result();
    }

    if ($stmt_c->num_rows > 0) {
      $stmt_c->bind_result($answer_id);
      //get the number of answers
      $loaded_answers = $stmt_c->num_rows;

      //loop through all answers
      while ($stmt_c->fetch()) {
        //query to get the details of the answer
        $stmt_temp = $dbc->prepare("SELECT a.answer_text, a.answer_date, a.likes, a.dislikes, a.replies, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, a.answer_date, NOW()), DATE_FORMAT(a.answer_date, '%b %e'), DATE_FORMAT(a.answer_date, ' at %h:%i%p'), YEAR(a.answer_date), YEAR(NOW()), DAY(a.answer_date), DAY(NOW()) 
          FROM answer AS a 
          JOIN user AS u ON a.user_id = u.user_id 
          JOIN tag AS t ON u.tag_id = t.tag_id 
          WHERE answer_id = ?");
        $stmt_temp->bind_param("i", $answer_id);
        $stmt_temp->execute();
        $stmt_temp->store_result();
        $stmt_temp->bind_result($answer_text, $answer_date, $answer_likes_count, $answer_dislikes_count, $total_replies, $answer_user_id, $answer_user_username, $answer_user_profile_image, $answer_user_forum, $seconds, $answer_date_open, $answer_date_close, $answer_year, $current_year, $answer_day, $today);
        $stmt_temp->fetch();
        $stmt_temp->free_result();
        $stmt_temp->close();
        unset($stmt_temp);

        $answer_liked = false;
        $answer_disliked = false;
        if($eligible_to_answer) {

          //query to check if the view user has ever liked/disliked the current answer
          $stmt = $dbc->prepare("SELECT is_like FROM answer_likes WHERE answer_id = ? AND user_id = ?");
          $stmt->bind_param("ii", $answer_id, $view_user_id);
          $stmt->execute();
          $stmt->store_result();

          if($stmt->num_rows == 1) {
            $stmt->bind_result($is_like);
            $stmt->fetch();


            if($is_like == 1) {
              $answer_liked = true;
            } else if($is_like == 0) {
              $answer_disliked = true;
            }

          }
          $stmt->free_result();
          $stmt->close();
        }
        
        //build the json array
        $new_answer = array(
          'answer_id' => $answer_id,
          'answer_text' => htmlspecialchars($answer_text, ENT_QUOTES),//, ENT_QUOTES),
          'answer_date' => $answer_date,
          'answer_likes_count' => $answer_likes_count,
          'answer_dislikes_count' => $answer_dislikes_count,
          'total_replies' => $total_replies,
          'loaded_replies' => 0,
          'answer_user_id' => $answer_user_id,
          'answer_user_username' => ucfirst($answer_user_username),
          'answer_user_forum' => $answer_user_forum,
          'answer_user_profile_image' => ($answer_user_profile_image == null ? 'user_icon.png' : $answer_user_profile_image),
          'answer_how_long' => calculate($seconds, $answer_date_open, $answer_date_close, $answer_year, $current_year, $answer_day, $today),
          'answer_liked' => $answer_liked,
          'answer_disliked' => $answer_disliked,
          'question_id' => $question_id,
          'view_user_profile_image' => ($view_user_profile_image == null ? 'user_icon.png' : $view_user_profile_image),
          'view_user_id' => $view_user_id,
          'is_owner' => ($view_user_id == $answer_user_id ? true : false)
        );

        //push to array
        array_push($answers, $new_answer);

        //break out of loop if a specific answer id was given
        if($ansID != null) {
          break;
        }
      }


    }
    
    $c_a = array(
      /*'view_user_username' => ucfirst($view_user_username),
      'view_user_profile_image' => ($view_user_profile_image == null ? 'user_icon.png' : $view_user_profile_image),
      'view_user_id' => $view_user_id,
      'eligible_to_answer' => $eligible_to_answer,
      'is_loggedin' => $loggedin,
      'loaded_answers' => $loaded_answers,
      'question_id' => $question_id,*/
      'total_answers' => $answer_count,
      'answers' => $answers
    );

    return $c_a;
  }
?>