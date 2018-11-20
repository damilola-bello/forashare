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
	  	$post_id = $_GET['post_id'];
	  	$comment_id = $_GET['comment_id'];
	  	$reply_id = $_GET['reply_id'];

	  	/* Validations */
	  	if(!(is_numeric($is_like) && is_numeric($post_id) && is_numeric($comment_id) && is_numeric($reply_id))) {
	  		$errors [] = "Wrong parameters.";
	  	} else {
	  		$post_id = intval($post_id);
	  		$comment_id = intval($comment_id);
	  		$reply_id = intval($reply_id);
	  		$is_like = intval($is_like);
	  	}

			//check if the post exist and comment exists and the reply exists
			$stmt = $dbc->prepare("SELECT p.post_id, p.user_id, t.tag_id, c.comment_id FROM comment AS c JOIN post AS p ON c.post_id = p.post_id JOIN tag_associations AS ta ON p.post_id = ta.post_id JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE p.post_id = ? AND tt.name = 'forum' AND c.comment_id = ? AND c.parent_comment_id = ? ");
	    $stmt->bind_param("iii", $post_id, $reply_id, $comment_id);
	    $stmt->execute();
	    $stmt->store_result();
	    if($stmt->num_rows == 1) {
	      $stmt->bind_result($post_id, $post_owner_id, $post_forum_id, $reply_id);
	      $stmt->fetch();
	    } else {
	    	//post does not exist
	    	$errors [] = "An error occured. Comment not found.";
	    }
	    $stmt->free_result();
	    $stmt->close();

		  if(empty($errors)) {
		  	//if there are no errors till this point, then check if the user is eligible to like/dislike
		  	/*
		  		Rules are 
		  		- The user must belong to the same forum as the post
		  		- If not, then the user must be the owner of the post
		  	*/
		  	//get the view user's forum
		  	if($post_owner_id != $view_user_id) {
		    	$stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
		      $stmt->bind_param("i", $view_user_id);
		      $stmt->execute();
		      $stmt->store_result();
		      if($stmt->num_rows == 1) {
		        $stmt->bind_result($user_forum_id);
		        $stmt->fetch();
		        //check if the view user's forum matches with the post
		        if($user_forum_id != $post_forum_id) {
		        	$msg = ($is_like ? 'like' : 'dislike');
		        	$errors [] = "You are not eligible to $msg the reply.";
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
		  	}
		  }

	  	$reply_liked = false;
	  	$reply_disliked = false;
	  	$db_errors = array();
	  	
	  	if(empty($errors)) {
	  		//check if the user has ever liked or disliked the reply
	  		$stmt = $dbc->prepare("SELECT is_like FROM comment_likes WHERE user_id = ? AND comment_id = ? ");
	    	$stmt->bind_param("ii", $view_user_id, $reply_id);
    		$stmt->execute();
    		$stmt->store_result();
    		if($stmt->num_rows === 1) {
    			$stmt->bind_result($view_user_like);
	    		$stmt->fetch();

					//if view user has liked the reply before and clicked on the like button and vice versa, remove like/dislike
					if($view_user_like == $is_like) {
						$stmt = $dbc->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
			    	$stmt->bind_param("ii", $view_user_id, $reply_id);
		    		$stmt->execute();
					} else {
						//if the user liked the reply before and now clicked on dislike and vice versa, toggle it
						$stmt = $dbc->prepare("UPDATE comment_likes SET is_like = ?, like_date = NOW() WHERE user_id = ? AND comment_id = ?");
			    	$stmt->bind_param("iii", $is_like, $view_user_id, $reply_id);
		    		$stmt->execute();
		    		if($is_like == 0) {
		    			$reply_disliked = true;
		    		} else {
		    			$reply_liked = true;
		    		}
					}
    		} else {
					//if the record doesn't exist, create one
					$stmt = $dbc->prepare("INSERT INTO comment_likes (user_id, comment_id, like_date, is_like) VALUES (?, ?, NOW(), ?) ");
		    	$stmt->bind_param("iii", $view_user_id, $reply_id, $is_like);
	    		$stmt->execute();
	    		if($is_like == 0) {
	    			$reply_disliked = true;
	    		} else {
	    			$reply_liked = true;
	    		}
    		}

    		//update the likes/dislikes count in the comments table
    		$stmt = $dbc->prepare("UPDATE comment 
    			SET likes = 
    				(SELECT COUNT(*) FROM comment_likes WHERE is_like = 1 AND comment_id = ?),
    				dislikes = (SELECT COUNT(*) FROM comment_likes WHERE is_like = 0 AND comment_id = ?)
    			WHERE comment_id = ?");
    		$stmt->bind_param("iii", $reply_id, $reply_id, $reply_id);
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
	  		$stmt = $dbc->prepare("SELECT likes, dislikes FROM comment WHERE comment_id = ? AND parent_comment_id = ? ");
	    	$stmt->bind_param("ii", $reply_id, $comment_id);
    		$stmt->execute();
    		$stmt->bind_result($reply_likes_count, $reply_dislikes_count);
    		$stmt->fetch();

    		$return = array(
    			'isErr' => false,
    			'message' => array(
    				'reply_liked' => $reply_liked,
            'reply_disliked' => $reply_disliked,
            'reply_likes_count' => $reply_likes_count,
            'reply_dislikes_count' => $reply_dislikes_count
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