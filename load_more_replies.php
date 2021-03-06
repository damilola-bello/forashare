<?php
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

  	$reply_count = 0;
		$return = array();
    
    $limit = (empty($_GET['limit']) ? 10 : trim($_GET['limit']));
    $answer_id = trim($_GET['answer_id']);
    $question_id = trim($_GET['question_id']);

	  //validate the question id and the limit query
	  if ( (is_numeric($limit) && filter_var($limit, FILTER_VALIDATE_INT) != false && intval($limit) > 0) && (is_numeric($question_id) && filter_var($question_id, FILTER_VALIDATE_INT) != false && intval($question_id) > 0) && (is_numeric($answer_id) && filter_var($answer_id, FILTER_VALIDATE_INT) != false && intval($answer_id) > 0) ) {
      $question_id = intval($question_id);
      $answer_id = intval($answer_id);

      //check if the answer exists
  		$stmt = $dbc->prepare("SELECT answer_id FROM answer WHERE question_id = ? AND answer_id = ? AND parent_answer_id IS NULL");
      $stmt->bind_param("ii", $question_id, $answer_id);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows == 1) {
        $stmt->bind_result($answer_id);
        $stmt->fetch();
      } else {
      	//answer does not exist
      	$errors [] = "Answer not found.";
      	$return = array("isErr" => true, "message" => $errors);
      }
      $stmt->free_result();
      $stmt->close();

    } else {
    	$errors [] = "Wrong query value";
			$return = array("isErr" => true, "message" => $errors);
    }

    //if the parameters are ok, no errors and the answer exist
    if(empty($errors)) {
		//function to calculate how long the reply was made
	  	require_once('includes/how_long.php');

    	require_once('replies.php');
    	$eligible_to_reply = false;

      // count the answers
			$stmt = $dbc->prepare("SELECT COUNT(*) FROM answer WHERE question_id = ? AND parent_answer_id = ?");
    	$stmt->bind_param("ii", $question_id, $answer_id);
  		$stmt->execute();
  		$stmt->store_result();
  		$stmt->bind_result($reply_count);
  		$stmt->fetch();
  		$stmt->free_result();
		  if($loggedin == true) {
				$view_user_id = $_SESSION['user_id'];
	  		//get the question details
	  		$stmt = $dbc->prepare("SELECT p.question_id, t.tag_id FROM question AS p JOIN tag_associations AS ta ON p.question_id = ta.question_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.question_id = ? AND tt.name = 'forum'");
	      $stmt->bind_param("d", $question_id);
	      $stmt->execute();
	      $stmt->store_result();
        $stmt->bind_result($question_id, $question_forum_id);
        $stmt->fetch();
        $stmt->free_result();
        $stmt->close();

        	//if there are no errors till this point, then check if the user is eligible to question a answer
        	/*
        		Rules are 
        		- The user must belong to the same forum as the question
        		- If not, then the user must be the owner of the question
        	*/
        	//get the user's(commeter) forum
        	//check if the commeter is the owner of the question, if not get the user's(commeter) forum
      	if($question_forum_id != $view_user_id) {
        	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
		      $stmt->bind_param("i", $view_user_id);
		      $stmt->execute();
		      $stmt->store_result();
		      if($stmt->num_rows == 1) {
	          $stmt->bind_result($user_forum_id);
	          $stmt->fetch();
	          //check if the commeter's forum matches with the question
	          if($user_forum_id == $question_forum_id) {
	          	$eligible_to_reply = true;
	          }
	        }
	        $stmt->free_result();
	        $stmt->close();

      	} else if($question_owner_id != $view_user_id) {
      		$eligible_to_reply = true;
      	}
	  		
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


    		$return = array("isErr" => false, "message" => show_replies($dbc, $question_id, $reply_count, $answer_id, $loggedin, $limit, $eligible_to_reply, $view_user_username, $view_user_profile_image, $view_user_id));
			} else {
				//if not logged in
				$return = array("isErr" => false, "message" => show_replies($dbc, $question_id, $reply_count, $answer_id, $loggedin, $limit));
			}
			
    }

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>