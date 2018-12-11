<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$start = 0;
		$sort_string = "";
    $country_query = "";
    $unanswered = "";
    $q = "";

		$number_pattern = "/^[0-9]+$/";
		//Validation
		if( isset($_GET['start']) && (preg_match($number_pattern, $_GET['start']) && (isset($_GET['country_id']) && (preg_match($number_pattern, $_GET['country_id']) ) ) ) ) {
      $start = intval($_GET['start']);
      $country_id = intval($_GET['country_id']);

      //check if country_id is a country
      $stmt = $dbc->prepare("SELECT COUNT(t.tag_id)
        FROM tag AS t
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
        WHERE tt.name = 'forum' AND t.tag_id = ?
      ");
      $stmt->bind_param("i", $country_id);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($temp_count);
      $stmt->fetch();
      //tag does not exist or it is a country
      if($temp_count != 1) {
        $errors [] = "Wrong parameters.";
      }
      unset($temp_count);
    } else {
      $errors [] = "Wrong parameters.";
    }

    //set the search string if there is any
    if( isset($_GET['q']) ) {
      $q = trim($_GET['q']);
    	//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = preg_replace('/(?<!\\\)([%_])/', '\\\$1', $q);
    }
    //check for the optional query parameters
    if( isset($_GET['score']) && !empty($_GET['score'])) {
      $param = strtoupper($_GET['score']);
    	//validate the parameter
    	if( $param != "DESC") {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " q.points $param ";
    	}
    } else if( isset($_GET['unanswered']) ) {
      $param = $_GET['unanswered'];
      //validate the parameter
      if( $param != 1) {
        $errors [] = "Wrong parameters.";
      } else {
        $unanswered = " AND q.answers = 0 ";
        $sort_string = " q.question_date DESC ";
      }
    } else if( isset($_GET['date']) ) {
      $param = strtoupper($_GET['date']);
      //validate the parameter
      if( $param != "DESC" && $param != "ASC" ) {
        $errors [] = "Wrong parameters.";
      } else {
        $sort_string = " q.question_date $param ";
      }
    } else {
    	$sort_string = " q.question_date DESC ";
    }

    if(empty($errors)) {
			$questions = array();
      $total_questions = 0;

      //include script to calculate the question time
      include('includes/how_long.php');

			//get the count of questions
			$stmt = $dbc->prepare("SELECT COUNT(q.question_id)
        FROM question AS q
        JOIN tag_associations AS ta ON q.question_id = ta.question_id
        JOIN tag AS t ON ta.tag_id = t.tag_id
        WHERE t.tag_id = ? $unanswered AND q.question_heading LIKE CONCAT('%',?,'%')
      ");
			$stmt->bind_param("is", $country_id, $search_string);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($total_questions);
	    $stmt->fetch();

      if($total_questions > 0) {
        require("includes/minify_text.php");

        $stmt = $dbc->prepare("SELECT q.question_id, q.question_heading, q.points, q.answers, q.question_text, q.likes, q.dislikes, q.has_image, TIMESTAMPDIFF(SECOND, q.question_date, NOW()), DATE_FORMAT(q.question_date, '%b %e'), DATE_FORMAT(q.question_date, 'at %h:%i%p'), YEAR(q.question_date), YEAR(NOW()), DAY(q.question_date), DAY(NOW()) 
        FROM question AS q
        JOIN tag_associations AS ta ON q.question_id = ta.question_id
        JOIN tag AS t ON ta.tag_id = t.tag_id
        WHERE t.tag_id = ? $unanswered AND q.question_heading LIKE CONCAT('%',?,'%')
        ORDER BY $sort_string
        LIMIT ?, 10");

        $stmt->bind_param("isi", $country_id, $search_string, $start);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($question_id, $question_heading, $points, $answers, $question_text, $question_likes, $question_dislikes, $has_image, $seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);

        while ($stmt->fetch()) {

          //fetch the tags sorted by the forum first, followed by default tags then custom tags 
          $stmt_t = $dbc->prepare("SELECT t.tag_id, t.tag_name, tt.name FROM tag_associations AS ta JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE ta.question_id = ? ORDER BY FIELD (tt.name, 'forum', 'default_tag', 'custom_tag')");
          $stmt_t->bind_param("d", $question_id);
          $stmt_t->execute();
          $stmt_t->store_result();
          $stmt_t->bind_result($tag_id, $tag_name, $tag_type);

          $tags = array();

          while ($stmt_t->fetch()) {
            $tag = array(
              'tag_type' => $tag_type,
              'tag_name' => $tag_name,
              'tag_id' => $tag_id
            );
            //push the tag to the tags array
            array_push($tags, $tag);
          }

          $question_heading_chars = str_split($question_heading);
          $str = "";
          $question_heading_array = array();
          if($q != "") {
            $q_count = strlen($q);
            $question_heading_count = count($question_heading_chars);
            for ($i=0; $i < $question_heading_count ; $i++) {
              $str = $str . $question_heading_chars[$i];

              $last_chars = substr($str, -$q_count);
              //if the last set of characters matches the query search
              if (strcasecmp($last_chars, $q) == 0) {
                $start_str = substr($str, 0, (strlen($str) - $q_count));
                //push the preceding characters to an array if it isn't the query search
                if(strcasecmp($start_str, $q) != 0) {      
                  array_push($question_heading_array, array(
                      'is_marked' => false,
                      'value' => $start_str
                    )
                  );              
                }

                array_push($question_heading_array, array(
                    'is_marked' => true,
                    'value' => $last_chars
                  )
                );
                //reset the string
                $str = "";
              }
            }
          } else {
            //push to array if there is no search query
            array_push($question_heading_array, array(
                'is_marked' => false,
                'value' => $question_heading
              )
            ); 
          }

          if ($str != "") {
            array_push($question_heading_array, array(
                'is_marked' => false,
                'value' => $str
              )
            );
          }

          $question = array(
            'question_score' => $points,
            'answers' => $answers,
            'question_id' => $question_id,
            'question_heading' => $question_heading_array,
            'question_text' => minify($question_text),
            'question_likes' => $question_likes,
            'question_dislikes' => $question_dislikes,
            'has_image' => (($has_image == 0) ? false : true),
            'how_long' => calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today),
            'tags' => $tags
          );

          //push the new question to the questions array
          array_push($questions, $question);
        }
      }

    	
      $stmt->free_result();
      $stmt->close();

      $return = array("isErr" => false, "message" => array(
      	'total_questions' => $total_questions,
      	'questions' => $questions
      ));
    } else {
    	$return = array("isErr" => true, "message" => $errors);
    }

    // Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
  }
?>