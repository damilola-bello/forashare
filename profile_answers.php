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
	  	$total_answers = 0;
	  	$loaded_answers = 0;
	  	$answers = array();

	  	//get the count of all answers by the user
	  	$stmt = $dbc->prepare("SELECT COUNT(answer_id) FROM answer WHERE user_id = ? AND parent_answer_id IS NULL");
		  $stmt->bind_param("i", $user_id);
		  $stmt->execute();
		  $stmt->store_result();
		  $stmt->bind_result($total_answers);
		  $stmt->fetch();
		  $stmt->free_result();

		  if ($total_answers > 0) {
		  	//get the details of each answer
			  $stmt_p = $dbc->prepare("SELECT answer_id 
			  	FROM answer 
			  	WHERE user_id = ? AND parent_answer_id IS NULL 
			  	ORDER BY answer_date DESC
			  	LIMIT ?, 20");
			  $stmt_p->bind_param("ii", $user_id, $start);
			  $stmt_p->execute();
			  $stmt_p->store_result();
			  $loaded_answers = $stmt_p->num_rows;

			  if($loaded_answers > 0) {
			  	//include script to calculate the answer time
			  	include('includes/how_long.php');

			    $stmt_p->bind_result($answer_id);
			    
			    while($stmt_p->fetch()) {
			    	//fetch the questions
			    	$stmt = $dbc->prepare("SELECT q.question_heading, q.question_id, a.answer_text, a.answer_date, a.likes, a.dislikes, a.replies, TIMESTAMPDIFF(SECOND, a.answer_date, NOW()), DATE_FORMAT(a.answer_date, '%b %e'), DATE_FORMAT(a.answer_date, 'at %h:%i%p'), YEAR(a.answer_date), YEAR(NOW()), DAY(a.answer_date), DAY(NOW()) 
			    		FROM answer AS a 
			    		JOIN question AS q 
			    		ON a.question_id = q.question_id 
			    		WHERE answer_id = ?");
			      $stmt->bind_param("i", $answer_id);
			      $stmt->execute();
			      $stmt->store_result();
			      $stmt->bind_result($question_heading, $question_id, $answer_body, $answer_date, $answer_likes_count, $answer_dislikes_count, $replies_count, $seconds, $answer_date_open, $answer_date_close, $answer_year, $current_year, $answer_day, $today);
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

			      $question_has_image = false;
			      //check if the question has images
			      $stmt = $dbc->prepare("SELECT question_id FROM question_images WHERE question_id = ?");
			      $stmt->bind_param("i", $question_id);
			      $stmt->execute();
			      $stmt->store_result();
			      if($stmt->num_rows > 0) {
			      	$question_has_image = true;
			      }

			      $answer_score = ($answer_likes_count - $answer_dislikes_count); 

			      $answer = array(
			      	'tags' => $tags,
			      	'question_heading' => htmlspecialchars($question_heading),
			      	'answer_id' => $answer_id,
			      	'answer_body' => htmlspecialchars($answer_body, ENT_QUOTES),
			      	'answer_date' => $answer_date,
			      	'answer_likes_count' => $answer_likes_count,
			      	'answer_dislikes_count' => $answer_dislikes_count,
			      	'answer_score' => $answer_score,
			      	'user_id' => $user_id,
			      	'replies_count' => $replies_count,
			      	'question_id' => $question_id,
			      	'question_has_image' => $question_has_image,
			      	'how_long' => calculate($seconds, $answer_date_open, $answer_date_close, $answer_year, $current_year, $answer_day, $today)
			      );

			      //push the current question to the questions array
			      array_push($answers, $answer);

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
		  		'total_answers'=> $total_answers,
		  		'loaded_answers'=> $loaded_answers,
		  		'answers'=> $answers
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