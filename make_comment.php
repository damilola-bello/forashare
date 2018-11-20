<?php
	//if post request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible_to_comment = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
			$view_user_id = $_SESSION['user_id'];

			$errors = array();
	  	$body = (empty($_POST['body']) ? null : trim($_POST['body']));
	  	$post_id = (empty($_POST['id']) ? null : trim($_POST['id']));

	  	/* Validations */
	  	//comment validation
	  	if(empty($body)) {
	  		$errors [] = "Comment cannot be empty.";
	  	} else {
	  		$body_len = strlen($body);
	  		if($body_len > 1000) {
	  			$errors [] = "Comment cannot be more than 1000 characters.";
	  		}
	  	}

	  	//post validation, check if post exists and if user is allowed to post in the forum
	  	if(empty($post_id)) {
	  		//post id cannot be empty
	  		$errors [] = "An error occured. Post not found.";
	  	} else {
	  		//check if the post exists
	  		$stmt = $dbc->prepare("SELECT p.post_id, p.user_id, t.tag_id FROM post AS p JOIN tag_associations AS ta ON p.post_id = ta.post_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.post_id = ? AND tt.name = 'forum'");
	      $stmt->bind_param("d", $post_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows == 1) {
	        $stmt->bind_result($post_id, $post_owner_id, $post_forum_id);
	        $stmt->fetch();
	      } else {
	      	//post does not exist
	      	$errors [] = "An error occured. Post not found.";
	      }
	      $stmt->free_result();
	      $stmt->close();

	      if(empty($errors)) {
	      	//if there are no errors till this point, then check if the user is eligible to post a comment
	      	/*
	      		Rules are 
	      		- The user must belong to the same forum as the post
	      		- If not, then the user must be the owner of the post
	      	*/
	      	//get the user's(commeter) forum
	      	//check if the commeter is the owner of the post, if not get the user's(commeter) forum
	      	if($post_owner_id != $view_user_id) {
	        	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
			      $stmt->bind_param("i", $view_user_id);
			      $stmt->execute();
			      $stmt->store_result();
			      if($stmt->num_rows == 1) {
		          $stmt->bind_result($user_forum_id);
		          $stmt->fetch();
		          //check if the commeter's forum matches with the post
		          if($user_forum_id != $post_forum_id) {
		          	$errors [] = "You are not eligible to post a comment.";
		          } else {
		          	$eligible_to_comment = true;
		          }
		        } else {
		        	$errors [] = "User not found.";
		        }
		        $stmt->free_result();
		        $stmt->close();

	      	} else {
	      		$eligible_to_comment = true;
	      	}
	      }
	  	}

	  	$comment_count = 0;
	  	$db_errors = array();
	  	//After validation, if there are no errors, insert the comment and return, else return the errors
	  	if(empty($errors)) {
	  		//there are no errors, insert the commnet to the db
	  		$stmt = $dbc->prepare("INSERT INTO comment (post_id, comment_text, user_id, comment_date) VALUES (?, ?, ?, NOW())");
	    	$stmt->bind_param("dsd", $post_id, $body, $view_user_id);
	  		$stmt->execute();
	  		if($stmt->affected_rows === 0) {
	  			$db_errors [] = "Comment could not be added.";
	  		} else if($stmt->affected_rows === 1) {
	  			//Close the statement
					$stmt->close();

					//get the id of the inserted comment
					$last_id = $dbc->insert_id;

					/* count the comments and update the comment count */
					// count the comments
					$stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_comment_id IS NULL");
		    	$stmt->bind_param("d", $post_id);
	    		$stmt->execute();
	    		$stmt->store_result();
	    		$stmt->bind_result($comment_count);
	    		$stmt->fetch();
	    		$stmt->free_result();

					//update the comment count in the post
					$stmt = $dbc->prepare("UPDATE post SET comments = ? WHERE post_id = $post_id");
		    	$stmt->bind_param("d", $comment_count);
	    		$stmt->execute();
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

	  		//function to calculate how long the comment was made
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

	      //if there are comments 
	      if($comment_count > 0) {
	      	require_once('includes/comment.php');
	      	$start = 0;
	      	$limit = 1;
	      	$return = array("isErr" => false, "message" => make_comment($dbc, $post_id, $comment_count, $loggedin, $start, $limit, $eligible_to_comment, $view_user_username, $view_user_profile_image, $view_user_id));
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