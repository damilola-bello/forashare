<?php
	//if question request
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
			$view_user_id = $_SESSION['user_id'];

			$errors = array();
	  	$question_id = $_GET['question_id'];

	  	/* Validations */
	  	if(!is_numeric($question_id)) {
	  		$errors [] = "Wrong parameters.";
	  	} else {
	  		$question_id = intval($question_id);
	  	}

			//check if the question exists
			$stmt = $dbc->prepare("SELECT question_id FROM question WHERE question_id = ?");
	    $stmt->bind_param("d", $question_id);
	    $stmt->execute();
	    $stmt->store_result();
	    if($stmt->num_rows == 1) {
	      $stmt->bind_result($question_id);
	      $stmt->fetch();
	    } else {
	    	//question does not exist
	    	$errors [] = "An error occured. Question not found.";
	    }
	    $stmt->free_result();
	    $stmt->close();

	  	$is_question_saved = false;
	  	
	  	if(empty($errors)) {
	  		//check if the user has ever saved the question before
	  		$stmt = $dbc->prepare("SELECT question_id FROM saved_question WHERE user_id = ? AND question_id = ?");
	    	$stmt->bind_param("dd", $view_user_id, $question_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
					//if view user has saved the question before, unsave it
					$stmt = $dbc->prepare("DELETE FROM saved_question WHERE user_id = ? AND question_id = ?");
		    	$stmt->bind_param("ii", $view_user_id, $question_id);
	    		$stmt->execute();
    		} else {
					//if the record doesn't exist, save the question
					$stmt = $dbc->prepare("INSERT INTO saved_question (question_id, user_id, saved_date) VALUES (?, ?, NOW()) ");
		    	$stmt->bind_param("ii", $question_id, $view_user_id);
	    		$stmt->execute();

	    		$is_question_saved = true;
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

    		$return = array(
    			'isErr' => false,
    			'message' => array(
    				'is_question_saved' => $is_question_saved
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