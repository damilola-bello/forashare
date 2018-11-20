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
	  	$post_id = $_GET['post_id'];

	  	/* Validations */
	  	if(!is_numeric($post_id)) {
	  		$errors [] = "Wrong parameters.";
	  	} else {
	  		$post_id = intval($post_id);
	  	}

			//check if the post exists
			$stmt = $dbc->prepare("SELECT post_id FROM post WHERE post_id = ?");
	    $stmt->bind_param("d", $post_id);
	    $stmt->execute();
	    $stmt->store_result();
	    if($stmt->num_rows == 1) {
	      $stmt->bind_result($post_id);
	      $stmt->fetch();
	    } else {
	    	//post does not exist
	    	$errors [] = "An error occured. Post not found.";
	    }
	    $stmt->free_result();
	    $stmt->close();

	  	$is_post_saved = false;
	  	
	  	if(empty($errors)) {
	  		//check if the user has ever saved the post before
	  		$stmt = $dbc->prepare("SELECT post_id FROM saved_post WHERE user_id = ? AND post_id = ?");
	    	$stmt->bind_param("dd", $view_user_id, $post_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
					//if view user has saved the post before, unsave it
					$stmt = $dbc->prepare("DELETE FROM saved_post WHERE user_id = ? AND post_id = ?");
		    	$stmt->bind_param("ii", $view_user_id, $post_id);
	    		$stmt->execute();
    		} else {
					//if the record doesn't exist, save the post
					$stmt = $dbc->prepare("INSERT INTO saved_post (post_id, user_id, saved_date) VALUES (?, ?, NOW()) ");
		    	$stmt->bind_param("ii", $post_id, $view_user_id);
	    		$stmt->execute();

	    		$is_post_saved = true;
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
    				'is_post_saved' => $is_post_saved
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