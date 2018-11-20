<?php
	//if post request
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

  	$reply_count = 0;
		$return = array();
    
    $limit = (empty($_GET['limit']) ? 10 : trim($_GET['limit']));
    $comment_id = trim($_GET['comment_id']);
    $post_id = trim($_GET['post_id']);

	  //validate the post id and the limit query
	  if ( (is_numeric($limit) && filter_var($limit, FILTER_VALIDATE_INT) != false && intval($limit) > 0) && (is_numeric($post_id) && filter_var($post_id, FILTER_VALIDATE_INT) != false && intval($post_id) > 0) && (is_numeric($comment_id) && filter_var($comment_id, FILTER_VALIDATE_INT) != false && intval($comment_id) > 0) ) {
      $post_id = intval($post_id);
      $comment_id = intval($comment_id);

      //check if the comment exists
  		$stmt = $dbc->prepare("SELECT comment_id FROM comment WHERE post_id = ? AND comment_id = ? AND parent_comment_id IS NULL");
      $stmt->bind_param("ii", $post_id, $comment_id);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows == 1) {
        $stmt->bind_result($comment_id);
        $stmt->fetch();
      } else {
      	//comment does not exist
      	$errors [] = "Comment not found.";
      	$return = array("isErr" => true, "message" => $errors);
      }
      $stmt->free_result();
      $stmt->close();

    } else {
    	$errors [] = "Wrong query value";
			$return = array("isErr" => true, "message" => $errors);
    }

    //if the parameters are ok, no errors and the comment exist
    if(empty($errors)) {
		//function to calculate how long the reply was made
	  	require_once('includes/how_long.php');

    	require_once('replies.php');
    	$eligible_to_reply = false;

      // count the comments
			$stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_comment_id = ?");
    	$stmt->bind_param("ii", $post_id, $comment_id);
  		$stmt->execute();
  		$stmt->store_result();
  		$stmt->bind_result($reply_count);
  		$stmt->fetch();
  		$stmt->free_result();
		  if($loggedin == true) {
				$view_user_id = $_SESSION['user_id'];
	  		//get the post details
	  		$stmt = $dbc->prepare("SELECT p.post_id, t.tag_id FROM post AS p JOIN tag_associations AS ta ON p.post_id = ta.post_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.post_id = ? AND tt.name = 'forum'");
	      $stmt->bind_param("d", $post_id);
	      $stmt->execute();
	      $stmt->store_result();
        $stmt->bind_result($post_id, $post_forum_id);
        $stmt->fetch();
        $stmt->free_result();
        $stmt->close();

        	//if there are no errors till this point, then check if the user is eligible to post a comment
        	/*
        		Rules are 
        		- The user must belong to the same forum as the post
        		- If not, then the user must be the owner of the post
        	*/
        	//get the user's(commeter) forum
        	//check if the commeter is the owner of the post, if not get the user's(commeter) forum
      	if($post_forum_id != $view_user_id) {
        	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
		      $stmt->bind_param("i", $view_user_id);
		      $stmt->execute();
		      $stmt->store_result();
		      if($stmt->num_rows == 1) {
	          $stmt->bind_result($user_forum_id);
	          $stmt->fetch();
	          //check if the commeter's forum matches with the post
	          if($user_forum_id == $post_forum_id) {
	          	$eligible_to_reply = true;
	          }
	        }
	        $stmt->free_result();
	        $stmt->close();

      	} else if($post_owner_id != $view_user_id) {
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


    		$return = array("isErr" => false, "message" => show_replies($dbc, $post_id, $reply_count, $comment_id, $loggedin, $limit, $eligible_to_reply, $view_user_username, $view_user_profile_image, $view_user_id));
			} else {
				//if not logged in
				$return = array("isErr" => false, "message" => show_replies($dbc, $post_id, $reply_count, $comment_id, $loggedin, $limit));
			}
			
    }

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>