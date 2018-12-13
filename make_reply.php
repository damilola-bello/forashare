<?php
	//if question request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible_to_reply = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
	  	$answer_owner_id = -1;
			$view_user_id = $_SESSION['user_id'];

			$errors = array();
	  	$body = (empty($_POST['body']) ? null : trim($_POST['body']));
	  	$body = str_replace("\r\n", "\n", $body);
	  	$question_id = (empty($_POST['question_id']) ? null : trim($_POST['question_id']));
	  	$answer_id = (empty($_POST['answer_id']) ? null : trim($_POST['answer_id']));

	  	/* Validations */
	  	//reply validation
	  	if(empty($body)) {
	  		$errors [] = "Reply cannot be empty.";
	  	} else {
	  		$body_len = mb_strlen($body);
	  		if($body_len > 1000) {
	  			$errors [] = "Reply cannot be more than 1000 characters.";
	  		}
	  	}

	  	if(empty($answer_id)) {
	  		$errors [] = "Answer not found.";
 	  	}

	  	//question validation, check if question exists and if user is allowed to question in the forum
	  	if(empty($question_id)) {
	  		//question id cannot be empty
	  		$errors [] = "An error occured. Question not found.";
	  	} else {
	  		//check if the question exists
	  		$stmt = $dbc->prepare("SELECT p.question_id, p.user_id, t.tag_id FROM question AS p JOIN tag_associations AS ta ON p.question_id = ta.question_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.question_id = ? AND tt.name = 'forum'");
	      $stmt->bind_param("d", $question_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows == 1) {
          $stmt->bind_result($question_id, $question_owner_id, $question_forum_id);
          $stmt->fetch();
        } else {
        	//question does not exist
        	$errors [] = "An error occured. Question not found.";
        }
        $stmt->free_result();
        $stmt->close();

        //check if reply exists
        $stmt = $dbc->prepare("SELECT answer_id, user_id FROM answer WHERE parent_answer_id IS NULL AND question_id = ? AND answer_id = ? ");
	      $stmt->bind_param("ii", $question_id, $answer_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows == 1) {
          $stmt->bind_result($answer_id, $answer_owner_id);
          $stmt->fetch();
        } else {
        	//question does not exist
        	$errors [] = "An error occured. Answer not found.";
        }
        $stmt->free_result();
        $stmt->close();

        if(empty($errors)) {
        	//if there are no errors till this point, then check if the user is eligible to question a reply
        	/*
        		Rules are 
        		- The user must belong to the same forum as the question
        		- If not, then the user must be the owner of the question
        	*/
        	//get the user's(commeter) forum
        	//check if the commeter is the owner of the question, if not get the user's(commeter) forum
        	if($question_owner_id != $view_user_id) {
	        	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
			      $stmt->bind_param("i", $view_user_id);
			      $stmt->execute();
			      $stmt->store_result();
			      if($stmt->num_rows == 1) {
		          $stmt->bind_result($user_forum_id);
		          $stmt->fetch();
		          //check if the commeter's forum matches with the question
		          if($user_forum_id != $question_forum_id) {
		          	$errors [] = "You are not eligible to question a reply.";
		          } else {
		          	$eligible_to_reply = true;
		          }
		        } else {
		        	$errors [] = "User not found.";
		        }
		        $stmt->free_result();
		        $stmt->close();

        	} else {
        		$eligible_to_reply = true;
        	}
        }
	  	}

	  	$reply_count = 0;
	  	$db_errors = array();
	  	//After validation, if there are no errors, insert the reply and return, else return the errors
	  	if(empty($errors)) {
	  		//there are no errors, insert the reply to the db
	  		$stmt = $dbc->prepare("INSERT INTO answer (question_id, answer_text, user_id, answer_date, parent_answer_id) VALUES (?, ?, ?, NOW(), ?)");
	    	$stmt->bind_param("isii", $question_id, $body, $view_user_id, $answer_id);
    		$stmt->execute();
    		if($stmt->affected_rows === 0) {
    			$db_errors [] = "Reply could not be added.";
    		} else if($stmt->affected_rows === 1) {
    			//Close the statement
					$stmt->close();

					//get the id of the inserted reply
					$last_id = $dbc->insert_id;

					/* count the replies and update the reply count */
					// count the replies
					$stmt = $dbc->prepare("SELECT COUNT(*) FROM answer WHERE question_id = ? AND parent_answer_id = ? ");
		    	$stmt->bind_param("ii", $question_id, $answer_id);
	    		$stmt->execute();
	    		$stmt->store_result();
	    		$stmt->bind_result($reply_count);
	    		$stmt->fetch();
	    		$stmt->free_result();

					//update the reply count for the answer
					$stmt = $dbc->prepare("UPDATE answer SET replies = ? WHERE parent_answer_id IS NULL AND question_id = ? AND answer_id = ? ");
		    	$stmt->bind_param("iii", $reply_count, $question_id, $answer_id);
	    		$stmt->execute();

	    		//set the notification only if the reply is from another user
	    		if($answer_owner_id != $view_user_id) {
			    	$notification_type = "reply";
		    		$notification_text = "replied to your answer";

		  			$stmt = $dbc->prepare("INSERT INTO notifications (notifier_id, notified_id, type, notification_time, question_id, answer_id, reply_id, text) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?) ");
			    	$stmt->bind_param("iisiiis", $view_user_id, $question_owner_id, $notification_type, $question_id, $answer_id, $last_id, $notification_text);
		    		$stmt->execute();
	    		}
    		}
	    	//Close the statement
				$stmt->close();
				unset($stmt);
	  	}

	  	//final error check
	  	if(!empty($errors)) {
	  		$return = array("isErr"=> true, "message"=> $errors);
	  	} else if(!empty($db_errors)) {
	  		$return = array("isErr"=> true, "message"=> $db_errors);
	  	} else {

	  		//function to calculate how long the answer was made
	  		require_once('includes/how_long.php');

	  		//get the user's profile picture/icon
	  		$stmt = $dbc->prepare("SELECT username, profile_image FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($view_user_username, $view_user_profile_image);
        $stmt->fetch();
        $stmt->free_result();
        $stmt->close();
        unset($stmt);

        //if there are answers 
        if($reply_count > 0) {
        	require_once('replies.php');
        	$limit = 1;
        	$return = array("isErr" => false, "message" => show_replies($dbc, $question_id, $reply_count, $answer_id, $loggedin, $limit, $eligible_to_reply, $view_user_username, $view_user_profile_image, $view_user_id));
        }
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