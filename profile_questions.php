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
	  	$total_questions = 0;
	  	$loaded_questions = 0;
	  	$questions = array();

		  //get the count of all questions the user has
		  $stmt = $dbc->prepare("SELECT COUNT(question_id) FROM question WHERE user_id = ?");
		  $stmt->bind_param("i", $user_id);
		  $stmt->execute();
		  $stmt->store_result();
		  $stmt->bind_result($total_questions);
		  $stmt->fetch();
		  $stmt->free_result();

		  if ($total_questions > 0) {
		  	//get the details of each question
			  $stmt_p = $dbc->prepare("SELECT question_id 
			  	FROM question 
			  	WHERE user_id = ? 
			  	ORDER BY question_date DESC
			  	LIMIT ?, 20");
			  $stmt_p->bind_param("ii", $user_id, $start);
			  $stmt_p->execute();
			  $stmt_p->store_result();
			  $loaded_questions = $stmt_p->num_rows;

			  if($loaded_questions > 0) {
			  	//include script to calculate the question time
			  	include('includes/how_long.php');

			    $stmt_p->bind_result($question_id);
			    
			    while($stmt_p->fetch()) {
			    	//fetch the questions
			    	$stmt = $dbc->prepare("SELECT q.question_heading, q.question_text, q.question_date, q.likes, q.dislikes, q.answers, q.has_image, u.user_id, u.username, TIMESTAMPDIFF(SECOND, q.question_date, NOW()), DATE_FORMAT(q.question_date, '%b %e'), DATE_FORMAT(q.question_date, 'at %h:%i%p'), YEAR(q.question_date), YEAR(NOW()), DAY(q.question_date), DAY(NOW()) 
			    		FROM question AS q 
			    		JOIN user AS u ON q.user_id = u.user_id  
			    		WHERE question_id = ?");
			      $stmt->bind_param("i", $question_id);
			      $stmt->execute();
			      $stmt->store_result();
			      $stmt->bind_result($question_heading, $question_body, $question_date, $question_likes_count, $question_dislikes_count, $question_answers_count, $has_image, $question_user_id, $question_user_name, $seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);
			      $stmt->fetch();
			      $stmt->free_result();

			      //fetch the tags sorted by the forum first, followed by default tags then custom tags 
			      $stmt = $dbc->prepare("SELECT t.tag_id, t.tag_name, tt.name FROM tag_associations AS ta JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE ta.question_id = ? ORDER BY FIELD (tt.name, 'forum', 'default_tag', 'custom_tag')");
			      $stmt->bind_param("d", $question_id);
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

			      //check if the question has images
			      $question_has_image = ($has_image == 0) ? false : true;

			      $question_score = ($question_likes_count - $question_dislikes_count); 

			      $question = array(
			      	'tags' => $tags,
			      	'question_heading' => htmlspecialchars($question_heading),
			      	'question_body' => htmlspecialchars($question_body, ENT_QUOTES),
			      	'question_date' => $question_date,
			      	'question_likes_count' => $question_likes_count,
			      	'question_dislikes_count' => $question_dislikes_count,
			      	'question_answers_count' => $question_answers_count,
			      	'question_user_id' => $question_user_id,
			      	'question_user_name' => $question_user_name,
			      	'question_score' => $question_score,
			      	'question_id' => $question_id,
			      	'question_has_image' => $question_has_image,
			      	'how_long' => calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today)
			      );

			      //push the current question to the questions array
			      array_push($questions, $question);

			      $stmt->free_result();
			      $stmt->close();
			      unset($stmt);
			    }

				}

			  $stmt_p->free_result();
			  $stmt_p->close();		
			  
			  
		  }
		  $return = array(
		  	'isErr'=> false,
		  	'message'=> array(
		  		'total_questions'=> $total_questions,
		  		'loaded_questions'=> $loaded_questions,
		  		'questions'=> $questions
		  	)
		  );


			
	  } else {
	  	$return = array("isErr" => true, "message" => $errors);
	  }

	  //close connection
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>