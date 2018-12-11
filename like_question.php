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

	  	/* Validations */
	  	if(!(is_numeric($is_like) && is_numeric($question_id))) {
	  		$errors [] = "Wrong parameters.";
	  	} else {
	  		$question_id = intval($question_id);
	  		$is_like = intval($is_like);
	  	}

			//check if the question exists
			$stmt = $dbc->prepare("SELECT q.question_id, q.user_id, t.tag_id FROM question AS q JOIN tag_associations AS ta ON q.question_id = ta.question_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE q.question_id = ? AND tt.name = 'forum'");
	    $stmt->bind_param("i", $question_id);
	    $stmt->execute();
	    $stmt->store_result();
	    if($stmt->num_rows == 1) {
	      $stmt->bind_result($question_id, $question_owner_id, $question_forum_id);
	      $stmt->fetch();

	      //a user cannot like or dislike his question, therefore check if the user wants to like/dislike his question
	    	if($view_user_id == $question_owner_id) {
	    		$like_str = ($is_like == 1 ? 'like' : 'dislike');
	    		$errors [] = "You cannot $like_str your question.";
	    	}
	    } else {
	    	//question does not exist
	    	$errors [] = "An error occured. Question not found.";
	    }
	    $stmt->free_result();
	    $stmt->close();

	    //for now, as long at the user is logged in, he/she is eligible to like a question
		  if(empty($errors)) {
		  	//if there are no errors till this point, then check if the user is eligible to like/dislike
		  	/*
		  		Rules are 
		  		- The user must belong to the same forum as the question
		  		- If not, then the user must be the owner of the question
		  	*/
		  	//get the view user's forum
		  	/*if($question_owner_id != $view_user_id) {
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
		        	$errors [] = "You are not eligible to $msg the question.";
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
		  	}*/
		  }

	  	$question_liked = false;
	  	$question_disliked = false;
	  	$db_errors = array();
	  	
	  	$score_addition = 0;


	  	if(empty($errors)) {
	  		//check if the user has ever liked or disliked the question
	  		$stmt = $dbc->prepare("SELECT is_like FROM question_likes WHERE user_id = ? AND question_id = ?");
	    	$stmt->bind_param("dd", $view_user_id, $question_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
    			$stmt->bind_result($view_user_like);
	    		$stmt->fetch();

					//if view user has liked the question before and clicked on the like button and vice versa, remove like/dislike
					if($view_user_like == $is_like) {
						$stmt = $dbc->prepare("DELETE FROM question_likes WHERE user_id = ? AND question_id = ?");
			    	$stmt->bind_param("ii", $view_user_id, $question_id);
		    		$stmt->execute();

		    		//if a like is removed, deduct 3 points from the question owner; if a dislike is removed, add 2 points
		    		$score_addition = ($view_user_like == 0) ? 2 : -3;
					} else {
						//if the user liked the question before and now clicked on dislike and vice versa, toggle it
						$stmt = $dbc->prepare("UPDATE question_likes SET is_like = ?, like_date = NOW() WHERE user_id = ? AND question_id = ?");
			    	$stmt->bind_param("iii", $is_like, $view_user_id, $question_id);
		    		$stmt->execute();
		    		if($is_like == 0) {
		    			$question_disliked = true;
		    		} else {
		    			$question_liked = true;
		    		}

		    		//if a like is changed to dislike, deduct 5 points(-3-2) from the question owner; if a dislike is changed to like, add 5 points
		    		$score_addition = ($is_like == 0) ? -5 : 5;
					}
    		} else {
					//if the record doesn't exist, create one
					$stmt = $dbc->prepare("INSERT INTO question_likes (like_date, is_like, user_id, question_id) VALUES (NOW(), ?, ?, ?) ");
		    	$stmt->bind_param("iii", $is_like, $view_user_id, $question_id);
	    		$stmt->execute();
	    		if($is_like == 0) {
	    			$question_disliked = true;
	    		} else {
	    			$question_liked = true;
	    		}
	    			//if a question is liked, add 3 points, else if disliked, deduct 2 points
		    		$score_addition = ($is_like == 0) ? -2 : 3;
    		}

    		//update the likes/dislikes count in the question table
    		$stmt = $dbc->prepare("UPDATE question 
    			SET likes = 
    				(SELECT COUNT(*) FROM question_likes WHERE is_like = 1 AND question_id = ?),
    				dislikes = (SELECT COUNT(*) FROM question_likes WHERE is_like = 0 AND question_id = ?),
    				points = (likes - dislikes)
    			WHERE question_id = ?");
    		$stmt->bind_param("iii", $question_id, $question_id, $question_id);
		    $stmt->execute();


    		//update the profile score of the owner of the question based on the like/dislike
    		//like to a question attracts a score of 3 points
    		//dislike of a question means a deduction of 2 points
		    $stmt = $dbc->prepare("UPDATE user SET profile_score = profile_score + ? WHERE user_id = ?");
    		$stmt->bind_param("ii", $score_addition, $question_owner_id);
		    $stmt->execute();

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
	  		$stmt = $dbc->prepare("SELECT likes, dislikes, points FROM question WHERE question_id = ?");
	    	$stmt->bind_param("i", $question_id);
    		$stmt->execute();
    		$stmt->bind_result($question_likes_count, $question_dislikes_count, $question_score);
    		$stmt->fetch();

    		$return = array(
    			'isErr' => false,
    			'message' => array(
    				'question_liked' => $question_liked,
            'question_disliked' => $question_disliked,
            'question_likes_count' => $question_likes_count,
            'question_dislikes_count' => $question_dislikes_count,
            'question_score' => $question_score
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