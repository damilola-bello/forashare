<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$user_id = $_GET['profile_id'];
		$start = $_GET['start'];

		//Validation
		if( !(is_numeric($user_id) || (is_numeric($start) && $start(intval($start) ) > 0) ) ) {
  		$errors [] = "Wrong parameters.";
  	} else {
  		$user_id = intval($user_id);
  		$start = intval($start);
  	}

  	//check if the user exists
  	$stmt = $dbc->prepare("SELECT user_id FROM user WHERE user_id = ? ");
	  $stmt->bind_param("i", $user_id);
	  $stmt->execute();
	  $stmt->store_result();
	  if($stmt->num_rows <= 0) {
	  	$errors [] = "User doesn't exist.";
	  }

	  if(empty($errors)) {
	  	$total_comments = 0;
	  	$loaded_comments = 0;
	  	$comments = array();

	  	//get the count of all comments by the user
	  	$stmt = $dbc->prepare("SELECT COUNT(comment_id) FROM comment WHERE user_id = ? AND parent_comment_id IS NULL");
		  $stmt->bind_param("i", $user_id);
		  $stmt->execute();
		  $stmt->store_result();
		  $stmt->bind_result($total_comments);
		  $stmt->fetch();
		  $stmt->free_result();

		  if ($total_comments > 0) {
		  	//get the details of each comment
			  $stmt_p = $dbc->prepare("SELECT comment_id FROM comment WHERE user_id = ? AND parent_comment_id IS NULL LIMIT ?, 20");
			  $stmt_p->bind_param("ii", $user_id, $start);
			  $stmt_p->execute();
			  $stmt_p->store_result();
			  $loaded_comments = $stmt_p->num_rows;

			  if($loaded_comments > 0) {
			  	//include script to calculate the comment time
			  	include('includes/how_long.php');

			    $stmt_p->bind_result($comment_id);
			    
			    while($stmt_p->fetch()) {
			    	//fetch the posts
			    	$stmt = $dbc->prepare("SELECT p.post_heading, p.post_id, c.comment_text, c.comment_date, c.likes, c.dislikes, c.replies, TIMESTAMPDIFF(SECOND, c.comment_date, NOW()), DATE_FORMAT(c.comment_date, '%b %e'), DATE_FORMAT(c.comment_date, ' at %h:%i %p'), YEAR(c.comment_date), YEAR(NOW()), DAY(c.comment_date), DAY(NOW()) FROM comment AS c JOIN post AS p ON c.post_id = p.post_id WHERE comment_id = ?");
			      $stmt->bind_param("i", $comment_id);
			      $stmt->execute();
			      $stmt->store_result();
			      $stmt->bind_result($post_heading, $post_id, $comment_body, $comment_date, $comment_likes_count, $comment_dislikes_count, $replies_count, $seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today);
			      $stmt->fetch();
			      $stmt->free_result();

			      //fetch the tags sorted by the forum first, followed by default tags then custom tags 
			      $stmt = $dbc->prepare("SELECT t.tag_id, t.tag_name, tt.name FROM tag_associations AS ta JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE ta.post_id = ? ORDER BY FIELD (tt.name, 'forum', 'default_tag', 'custom_tag')");
			      $stmt->bind_param("d", $post_id);
			      $stmt->execute();
			      $stmt->store_result();
			      $stmt->bind_result($tag_id, $tag_name, $tag_type);

			      $tags = array();
			      $tag = array();

			      while ($stmt->fetch()) {
			      	$tag = array(
			      		'tag_type' => $tag_type,
			      		'tag_name' => $tag_name,
			      		'tag_id' => $tag_id
			      	);
			        //push the tag to the tags array
			        array_push($tags, $tag);
			      }

			      $post_has_image = false;
			      //check if the post has images
			      $stmt = $dbc->prepare("SELECT post_id FROM post_images WHERE post_id = ?");
			      $stmt->bind_param("i", $post_id);
			      $stmt->execute();
			      $stmt->store_result();
			      if($stmt->num_rows > 0) {
			      	$post_has_image = true;
			      }

			      $comment_score = (($comment_likes_count - $comment_dislikes_count) * 3); 

			      $comment = array(
			      	'tags' => $tags,
			      	'post_heading' => htmlspecialchars($post_heading),
			      	'comment_id' => $comment_id,
			      	'comment_body' => htmlspecialchars($comment_body, ENT_QUOTES),
			      	'comment_date' => $comment_date,
			      	'comment_likes_count' => $comment_likes_count,
			      	'comment_dislikes_count' => $comment_dislikes_count,
			      	'replies_count' => $replies_count,
			      	'comment_score' => $comment_score,
			      	'post_id' => $post_id,
			      	'post_has_image' => $post_has_image,
			      	'how_long' => calculate($seconds, $comment_date_open, $comment_date_close, $comment_year, $current_year, $comment_day, $today)
			      );

			      //push the current post to the posts array
			      array_push($comments, $comment);

			      $stmt->free_result();
			      $stmt->close();
			      unset($stmt);
			    }

				}

			  $stmt_p->free_result();
			  $stmt_p->close();		
			  
			  $return = array(
			  	'isErr'=> false,
			  	'message'=> array(
			  		'total_comments'=> $total_comments,
			  		'loaded_comments'=> $loaded_comments,
			  		'comments'=> $comments
			  	)
			  );
		  }

			
	  } else {
	  	$return = array("isErr" => true, "message" => $errors);
	  }

	  //close connection
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>