<?php
	//if post request
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
			$view_user_id = $_SESSION['user_id'];

			$errors = array();
	  	$is_like = $_GET['is_like'];
	  	$question_id = $_GET['question_id'];
	  	$answer_id = $_GET['answer_id'];

	  	/* Validations */
	  	if(!(is_numeric($is_like) && is_numeric($question_id) && is_numeric($answer_id))) {
	  		$errors [] = "Wrong parameters.";
	  	} else {
	  		$question_id = intval($question_id);
	  		$answer_id = intval($answer_id);
	  		$is_like = intval($is_like);
	  	}

			//check if the question exist and answer exists
			$stmt = $dbc->prepare("SELECT p.question_id, p.user_id, t.tag_id, c.answer_id, c.user_id FROM answer AS c JOIN question AS p ON c.question_id = p.question_id JOIN tag_associations AS ta ON p.question_id = ta.question_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.question_id = ? AND tt.name = 'forum' AND c.answer_id = ? AND c.parent_answer_id IS NULL");
	    $stmt->bind_param("ii", $question_id, $answer_id);
	    $stmt->execute();
	    $stmt->store_result();
	    if($stmt->num_rows == 1) {
	      $stmt->bind_result($question_id, $question_owner_id, $question_forum_id, $answer_id, $answer_user_id);
	      $stmt->fetch();

	      //a user cannot like or dislike his answer, therefore check if the user want to like/dislike his answer
	    	if($view_user_id == $answer_user_id) {
	    		$like_str = ($is_like == 1 ? 'like' : 'dislike');
	    		$errors [] = "You cannot $like_str your answer.";
	    	}
	    } else {
	    	//question does not exist
	    	$errors [] = "An error occured. Question/Answer not found.";
	    }
	    $stmt->free_result();
	    $stmt->close();

		  if(empty($errors)) {
		  	//if there are no errors till this point, then check if the user is eligible to like/dislike
		  	/*
		  		Rules are 
		  		- The user must belong to the same forum as the question
		  		- If not, then the user must be the owner of the question
		  	*/
		  	//get the view user's forum
		  	if($question_owner_id != $view_user_id) {
		    	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
		      $stmt->bind_param("i", $view_user_id);
		      $stmt->execute();
		      $stmt->store_result();
		      if($stmt->num_rows == 1) {
		        $stmt->bind_result($user_forum_id);
		        $stmt->fetch();
		        //check if the view user's forum matches with the question
		        if($user_forum_id != $question_forum_id) {
		        	$msg = ($is_like ? 'like' : 'dislike');
		        	$errors [] = "You are not eligible to $msg the answer.";
		        } else {
		        	$eligible = true;
		        }
		      } else {
		      	$errors [] = "User not found.";
		      }
		      $stmt->free_result();
		      $stmt->close();

		  	} else {
		  		$eligible = true;
		  	}
		  }

	  	$answer_liked = false;
	  	$answer_disliked = false;
	  	$db_errors = array();
	  	$score_addition = 0;
	  	
	  	if(empty($errors)) {
	  		//check if the user has ever liked or disliked the answer
	  		$stmt = $dbc->prepare("SELECT is_like FROM answer_likes WHERE user_id = ? AND answer_id = ? ");
	    	$stmt->bind_param("ii", $view_user_id, $answer_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
    			$stmt->bind_result($view_user_like);
	    		$stmt->fetch();

					//if view user has liked the answer before and clicked on the like button and vice versa, remove like/dislike
					if($view_user_like == $is_like) {
						$stmt = $dbc->prepare("DELETE FROM answer_likes WHERE user_id = ? AND answer_id = ?");
			    	$stmt->bind_param("ii", $view_user_id, $answer_id);
		    		$stmt->execute();

		    		//if a like is removed, deduct 6 points from the answer owner; if a dislike is removed, add 2 points
		    		$score_addition = ($view_user_like == 0) ? 2 : -6;
					} else {
						//if the user liked the answer before and now clicked on dislike and vice versa, toggle it
						$stmt = $dbc->prepare("UPDATE answer_likes SET is_like = ?, like_date = NOW() WHERE user_id = ? AND answer_id = ?");
			    	$stmt->bind_param("iii", $is_like, $view_user_id, $answer_id);
		    		$stmt->execute();
		    		if($is_like == 0) {
		    			$answer_disliked = true;
		    		} else {
		    			$answer_liked = true;
		    		}

		    		//if a like is changed to dislike, deduct 8 points(-6-2) from the answer owner; if a dislike is changed to like, add 8 points
		    		$score_addition = ($is_like == 0) ? -8 : 8;
					}
    		} else {
					//if the record doesn't exist, create one
					$stmt = $dbc->prepare("INSERT INTO answer_likes (user_id, answer_id, like_date, is_like) VALUES (?, ?, NOW(), ?) ");
		    	$stmt->bind_param("iii", $view_user_id, $answer_id, $is_like);
	    		$stmt->execute();
	    		if($is_like == 0) {
	    			$answer_disliked = true;
	    		} else {
	    			$answer_liked = true;
	    		}

	    		//if a answer is liked, add 6 points, else if disliked, deduct 2 points
		    	$score_addition = ($is_like == 0) ? -2 : 6;
    		}

    		//update the likes/dislikes count in the answers table
    		$stmt = $dbc->prepare("UPDATE answer 
    			SET likes = 
    				(SELECT COUNT(*) FROM answer_likes WHERE is_like = 1 AND answer_id = ?),
    				dislikes = (SELECT COUNT(*) FROM answer_likes WHERE is_like = 0 AND answer_id = ?),
            score = likes - dislikes
    			WHERE answer_id = ?");
    		$stmt->bind_param("iii", $answer_id, $answer_id, $answer_id);
		    $stmt->execute();

		    //update the profile score of the owner of the answer based on the like/dislike
    		//like to an answer attracts a score of 6 points
    		//dislike of an answer means a deduction of 2 points
		    $stmt = $dbc->prepare("UPDATE user SET profile_score = profile_score + ? WHERE user_id = ?");
    		$stmt->bind_param("ii", $score_addition, $answer_user_id);
		    $stmt->execute();

		    $notification_type = "answer_like";
    		$notification_text = (($is_like == 0) ? "disliked" : "liked") . " your answer";
    		//set the notification
    		$stmt = $dbc->prepare("SELECT is_like, notification_id
    			FROM notifications 
    			WHERE notifier_id = ? AND notified_id = ? AND type = ? AND question_id = ? AND answer_id = ? AND seen = '0' ");
	    	$stmt->bind_param("iisii", $view_user_id, $question_owner_id, $notification_type, $question_id, $answer_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
    			//if a notification instance exists and hasn't been opened, update the instance
    			$stmt->bind_result($previous_like, $notification_id);
	    		$stmt->fetch();

	    		//remove the notification
	    		if(intval($previous_like) == intval($is_like)) {
	    			$stmt = $dbc->prepare("DELETE FROM notifications WHERE notification_id = ?");
			    	$stmt->bind_param("i", $notification_id);
		    		$stmt->execute();
	    		} else {
		    		$stmt = $dbc->prepare("UPDATE notifications SET notification_time = NOW(), is_like = ?, text = ? WHERE notification_id = ?");
		    		$stmt->bind_param("ssi", $is_like, $notification_text, $notification_id);
				    $stmt->execute();
	    		}

    		} else {
    			//if the notification instance does not exist, create a new one
    			$stmt = $dbc->prepare("INSERT INTO notifications (notifier_id, notified_id, type, notification_time, question_id, answer_id, is_like, text) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?) ");
		    	$stmt->bind_param("iisiiss", $view_user_id, $question_owner_id, $notification_type, $question_id, $answer_id, $is_like, $notification_text);
	    		$stmt->execute();
    		}

    		$stmt->free_result();
	    	//Close the statement
				$stmt->close();
				unset($stmt);
	  	}

	  	//final error check
	  	if(!empty($errors)) {
	  		$return = array("isErr"=> true, "message"=> $errors);
	  	} else {

	  		//get the likes/dislikes count
	  		$stmt = $dbc->prepare("SELECT likes, dislikes, score FROM answer WHERE answer_id = ? AND parent_answer_id IS NULL");
	    	$stmt->bind_param("i", $answer_id);
    		$stmt->execute();
    		$stmt->bind_result($answer_likes_count, $answer_dislikes_count, $score);
    		$stmt->fetch();

    		$return = array(
    			'isErr' => false,
    			'message' => array(
    				'answer_liked' => $answer_liked,
            'answer_disliked' => $answer_disliked,
            'answer_likes_count' => $answer_likes_count,
            'answer_dislikes_count' => $answer_dislikes_count,
            'score' => $score
    			)
    		);
	  	}

		} else {
			$errors [] = "You need to sign in.";
			$return = array("isErr" => true, "message" => $errors);
		}

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>