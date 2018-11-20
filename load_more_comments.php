<?php
	//if post request
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
		require_once('checkifloggedin.php');

  	$comment_count = 0;
		$return = array();
    
    $start = trim($_GET['start']);
    $post_id = trim($_GET['post_id']);

	  //validate the post id and the start query
	  if ( ( is_numeric($post_id) && filter_var($post_id, FILTER_VALIDATE_INT) != false) && (is_numeric($start) )) {
      $post_id = intval($post_id);
      $start = intval($start);

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
      	$errors [] = "Post not found.";
      	$return = array("isErr" => true, "message" => $errors);
      }
      $stmt->free_result();
      $stmt->close();

    } else {
    	$errors [] = "Wrong query value";
			$return = array("isErr" => true, "message" => $errors);
    }

    //if the parameters are ok, no errors and the post exist
    if(empty($errors)) {
		//function to calculate how long the comment was made
	  	require_once('includes/how_long.php');

    	require_once('includes/comment.php');
    	$eligible_to_comment = false;

			//array to hold the return data
      // count the comments
			$stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_comment_id IS NULL");
    	$stmt->bind_param("d", $post_id);
  		$stmt->execute();
  		$stmt->store_result();
  		$stmt->bind_result($comment_count);
  		$stmt->fetch();
  		$stmt->free_result();
		  if($loggedin == true) {
				$view_user_id = $_SESSION['user_id'];
	  		//check if the post exists
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
	          	$eligible_to_comment = true;
	          }
	        }
	        $stmt->free_result();
	        $stmt->close();

      	} else if($post_owner_id != $view_user_id) {
      		$eligible_to_comment = true;
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
    		$return = array("isErr" => false, "message" => make_comment($dbc, $post_id, $comment_count, $loggedin, $start, $limit, $eligible_to_comment, $view_user_username, $view_user_profile_image, $view_user_id));
			} else {
				//if not logged in
				$return = array("isErr" => false, "message" => make_comment($dbc, $post_id, $comment_count, $loggedin, $start));
			}
			
    }

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>