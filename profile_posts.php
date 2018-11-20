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
	  	$total_posts = 0;
	  	$loaded_posts = 0;
	  	$posts = array();

		  //get the count of all posts the user has
		  $stmt = $dbc->prepare("SELECT COUNT(post_id) FROM post WHERE user_id = ?");
		  $stmt->bind_param("i", $user_id);
		  $stmt->execute();
		  $stmt->store_result();
		  $stmt->bind_result($total_posts);
		  $stmt->fetch();
		  $stmt->free_result();

		  if ($total_posts > 0) {
		  	//get the details of each post
			  $stmt_p = $dbc->prepare("SELECT post_id FROM post WHERE user_id = ? LIMIT ?, 20");
			  $stmt_p->bind_param("ii", $user_id, $start);
			  $stmt_p->execute();
			  $stmt_p->store_result();
			  $loaded_posts = $stmt_p->num_rows;

			  if($loaded_posts > 0) {
			  	//include script to calculate the post time
			  	include('includes/how_long.php');

			    $stmt_p->bind_result($post_id);
			    
			    while($stmt_p->fetch()) {
			    	//fetch the posts
			    	$stmt = $dbc->prepare("SELECT p.post_heading, p.post_text, p.post_date, p.likes, p.dislikes, p.comments, u.user_id, u.username, TIMESTAMPDIFF(SECOND, p.post_date, NOW()), DATE_FORMAT(p.post_date, '%b %e'), DATE_FORMAT(p.post_date, ' at %h:%i %p'), YEAR(p.post_date), YEAR(NOW()), DAY(p.post_date), DAY(NOW()) FROM post AS p JOIN user AS u ON p.user_id = u.user_id  WHERE post_id = ?");
			      $stmt->bind_param("i", $post_id);
			      $stmt->execute();
			      $stmt->store_result();
			      $stmt->bind_result($post_heading, $post_body, $post_date, $post_likes_count, $post_dislikes_count, $post_comments_count, $post_user_id, $post_user_name, $seconds, $post_date_open, $post_date_close, $post_year, $current_year, $post_day, $today);
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

			      $post_score = (($post_likes_count - $post_dislikes_count) * 3); 

			      $post = array(
			      	'tags' => $tags,
			      	'post_heading' => htmlspecialchars($post_heading),
			      	'post_body' => htmlspecialchars($post_body, ENT_QUOTES),
			      	'post_date' => $post_date,
			      	'post_likes_count' => $post_likes_count,
			      	'post_dislikes_count' => $post_dislikes_count,
			      	'post_comments_count' => $post_comments_count,
			      	'post_user_id' => $post_user_id,
			      	'post_user_name' => $post_user_name,
			      	'post_score' => $post_score,
			      	'post_id' => $post_id,
			      	'post_has_image' => $post_has_image,
			      	'how_long' => calculate($seconds, $post_date_open, $post_date_close, $post_year, $current_year, $post_day, $today)
			      );

			      //push the current post to the posts array
			      array_push($posts, $post);

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
			  		'total_posts'=> $total_posts,
			  		'loaded_posts'=> $loaded_posts,
			  		'posts'=> $posts
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