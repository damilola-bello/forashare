<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

  	$answer_count = 0;
		$return = array();
    
    $question_id = trim($_GET['question_id']);

    $start = 0;
    $focus_answer_id = null;

    if( isset($_GET['start']) && (filter_var($_GET['start'], FILTER_VALIDATE_INT) === 0 || filter_var($_GET['start'], FILTER_VALIDATE_INT)) ) {
      $start = intval($_GET['start']);
    } else if( isset($_GET['ansID']) && (filter_var($_GET['ansID'], FILTER_VALIDATE_INT) === 0 || filter_var($_GET['ansID'], FILTER_VALIDATE_INT)) ) {
      $focus_answer_id = intval($_GET['ansID']);
    } else {
      $errors [] = "Wrong parameters.";
    }
    
	  //validate the question id and the start query
	  if ( ( is_numeric($question_id) && filter_var($question_id, FILTER_VALIDATE_INT) != false) && (is_numeric($start) )) {
      $question_id = intval($question_id);
      $start = intval($start);

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
      	$errors [] = "Question not found.";
      	$return = array("isErr" => true, "message" => $errors);
      }
      $stmt->free_result();
      $stmt->close();

    } else {
    	$errors [] = "Wrong query value";
			$return = array("isErr" => true, "message" => $errors);
    }

    //if the parameters are ok, no errors and the question exist
    if(empty($errors)) {
		//function to calculate how long the answer was made
	  	require_once('includes/how_long.php');

    	require_once('includes/answer.php');

      //sort type
      $sortTypes = array('newest', 'oldest', 'score', 'active');

      $sortType = 'newest';
      //if sort type is specified
      if(isset($_GET['sortType'])) {
        $temp = strtolower($_GET['sortType']);
        if (in_array($temp, $sortTypes)) {
          $sortType = $temp;
        }
      }

    	$eligible_to_answer = false;

			//array to hold the return data
      // count the answers
			$stmt = $dbc->prepare("SELECT COUNT(*) FROM answer WHERE question_id = ? AND parent_answer_id IS NULL");
    	$stmt->bind_param("d", $question_id);
  		$stmt->execute();
  		$stmt->store_result();
  		$stmt->bind_result($answer_count);
  		$stmt->fetch();
  		$stmt->free_result();
		  if($loggedin == true) {
				$view_user_id = $_SESSION['user_id'];
	  		//check if the question exists
	  		$stmt = $dbc->prepare("SELECT q.question_id, t.tag_id FROM question AS q JOIN tag_associations AS ta ON q.question_id = ta.question_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE q.question_id = ? AND tt.name = 'forum'");
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
	          	$eligible_to_answer = true;
	          }
	        }
	        $stmt->free_result();
	        $stmt->close();

      	} else if($question_owner_id != $view_user_id) {
      		$eligible_to_answer = true;
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

        $limit = 10;
    		$return = array("isErr" => false, "message" => make_answer($dbc, $question_id, $answer_count, $loggedin, $focus_answer_id, $sortType, $start, $limit, $eligible_to_answer, $view_user_username, $view_user_profile_image, $view_user_id));
			} else {
				//if not logged in
        $return = array("isErr" => false, "message" => make_answer($dbc, $question_id, $answer_count, $loggedin, $focus_answer_id, $sortType, $start));
			}
			
    }

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>