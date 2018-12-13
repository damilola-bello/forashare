<?php
	//if question request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible_to_answer = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
			$view_user_id = $_SESSION['user_id'];

			$errors = array();
	  	$body = (empty($_POST['body']) ? null : trim($_POST['body']));
	  	//replace newline as one character
	  	$body = str_replace("\r\n", "\n", $body);
	  	$question_id = (empty($_POST['id']) ? null : trim($_POST['id']));

	  	/* Validations */
	  	//answer validation
	  	if(empty($body)) {
	  		$errors [] = "Answer cannot be empty.";
	  	} else {
	  		$body_len = mb_strlen($body);
	  		if($body_len > 1000) {
	  			$errors [] = "Answer cannot be more than 1000 characters.";
	  		}
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

	      if(empty($errors)) {
	      	//if there are no errors till this point, then check if the user is eligible to question a answer
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
		          	$errors [] = "You are not eligible to question a answer.";
		          } else {
		          	$eligible_to_answer = true;
		          }
		        } else {
		        	$errors [] = "User not found.";
		        }
		        $stmt->free_result();
		        $stmt->close();

	      	} else {
	      		$eligible_to_answer = true;
	      	}
	      }
	  	}

	  	$answer_count = 0;
	  	$db_errors = array();
	  	//After validation, if there are no errors, insert the answer and return, else return the errors
	  	if(empty($errors)) {
	  		//there are no errors, insert the commnet to the db
	  		$stmt = $dbc->prepare("INSERT INTO answer (question_id, answer_text, user_id, answer_date) VALUES (?, ?, ?, NOW())");
	    	$stmt->bind_param("dsd", $question_id, $body, $view_user_id);
	  		$stmt->execute();
	  		if($stmt->affected_rows === 0) {
	  			$db_errors [] = "Answer could not be added.";
	  		} else if($stmt->affected_rows === 1) {
	  			//Close the statement
					$stmt->close();

					//get the id of the inserted answer
					$last_id = $dbc->insert_id;

					/* count the answers and update the answer count */
					// count the answers
					$stmt = $dbc->prepare("SELECT COUNT(*) FROM answer WHERE question_id = ? AND parent_answer_id IS NULL");
		    	$stmt->bind_param("d", $question_id);
	    		$stmt->execute();
	    		$stmt->store_result();
	    		$stmt->bind_result($answer_count);
	    		$stmt->fetch();

					//update the answer count in the question
					$stmt = $dbc->prepare("UPDATE question SET answers = ? WHERE question_id = $question_id");
		    	$stmt->bind_param("d", $answer_count);
	    		$stmt->execute();

	    		//set the notification only if the answer is from another user
	    		if($question_owner_id != $view_user_id) {
			    	$notification_type = "answer";
		    		$notification_text = "answered your question";

		  			$stmt = $dbc->prepare("INSERT INTO notifications (notifier_id, notified_id, type, notification_time, question_id, answer_id, text) VALUES (?, ?, ?, NOW(), ?, ?, ?) ");
			    	$stmt->bind_param("iisiis", $view_user_id, $question_owner_id, $notification_type, $question_id, $last_id, $notification_text);
		    		$stmt->execute();
	    		}
	  		}
	    	


	    	$stmt->free_result();
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
	      if($answer_count > 0) {
	      	require_once('includes/answer.php');
	      	$start = 0;
	      	$limit = 1;
	      	$sortType = 'newest';
	      	$focus_answer_id = null;
	      	$return = array("isErr" => false, "message" => make_answer($dbc, $question_id, $answer_count, $loggedin, $focus_answer_id, $sortType, $start, $limit, $eligible_to_answer, $view_user_username, $view_user_profile_image, $view_user_id));
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