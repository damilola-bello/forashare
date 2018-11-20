<?php
	//if post request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

		$eligible_to_reply = false;

		//array to hold the return data
	  $return = array();
	  if($loggedin == true) {
		$view_user_id = $_SESSION['user_id'];

		$errors = array();
	  	$body = (empty($_POST['body']) ? null : trim($_POST['body']));
	  	$post_id = (empty($_POST['post_id']) ? null : trim($_POST['post_id']));
	  	$comment_id = (empty($_POST['comment_id']) ? null : trim($_POST['comment_id']));

	  	/* Validations */
	  	//reply validation
	  	if(empty($body)) {
	  		$errors [] = "Reply cannot be empty.";
	  	} else {
	  		$body_len = strlen($body);
	  		if($body_len > 1000) {
	  			$errors [] = "Reply cannot be more than 1000 characters.";
	  		}
	  	}

	  	if(empty($comment_id)) {
	  		$errors [] = "Comment not found.";
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

        //check if reply exists
        $stmt = $dbc->prepare("SELECT comment_id FROM comment WHERE parent_comment_id IS NULL AND post_id = ? AND comment_id = ? ");
	      $stmt->bind_param("ii", $post_id, $comment_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows == 1) {
          $stmt->bind_result($comment_id);
          $stmt->fetch();
        } else {
        	//post does not exist
        	$errors [] = "An error occured. Comment not found.";
        }
        $stmt->free_result();
        $stmt->close();

        if(empty($errors)) {
        	//if there are no errors till this point, then check if the user is eligible to post a reply
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
		          	$errors [] = "You are not eligible to post a reply.";
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
	  		$stmt = $dbc->prepare("INSERT INTO comment (post_id, comment_text, user_id, comment_date, parent_comment_id) VALUES (?, ?, ?, NOW(), ?)");
	    	$stmt->bind_param("isii", $post_id, $body, $view_user_id, $comment_id);
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
					$stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_comment_id = ? ");
		    	$stmt->bind_param("ii", $post_id, $comment_id);
	    		$stmt->execute();
	    		$stmt->store_result();
	    		$stmt->bind_result($reply_count);
	    		$stmt->fetch();
	    		$stmt->free_result();

					//update the reply count for the comment
					$stmt = $dbc->prepare("UPDATE comment SET replies = ? WHERE parent_comment_id IS NULL AND post_id = ? AND comment_id = ? ");
		    	$stmt->bind_param("iii", $reply_count, $post_id, $comment_id);
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
	        if($reply_count > 0) {
	        	require_once('replies.php');
	        	$limit = 1;
	        	$return = array("isErr" => false, "message" => show_replies($dbc, $post_id, $reply_count, $comment_id, $loggedin, $limit, $eligible_to_reply, $view_user_username, $view_user_profile_image, $view_user_id));
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