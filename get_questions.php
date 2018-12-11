<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$start = 0;
		$sort_string = "";
    $unanswered_string = "";
		$tags_id_string = '';
    $q = "";

		$tags_id_string_pattern = "/^(([0-9]+[,]([0-9]*))+)$|(^[0-9]+)$/";
		//Validation
		if( isset($_GET['start']) && (filter_var($_GET['start'], FILTER_VALIDATE_INT) === 0 || filter_var($_GET['start'], FILTER_VALIDATE_INT)) && (isset($_GET['tags']) && (preg_match($tags_id_string_pattern, $_GET['tags']) || empty($_GET['tags'])) ) ) {
      $start = intval($_GET['start']);
      $a = explode(',', $_GET['tags']);
      $tags_id_string = implode("','", $a);
      $tags_id_string = "'$tags_id_string'";
    } else {
      $errors [] = "Wrong parameters.";
    }

    //set the search string if there is any
    if( isset($_GET['q']) ) {
      $q = trim($_GET['q']);
    	//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = preg_replace('/(?<!\\\)([%_])/', '\\\$1', $q);
    }
    //check if there is a date query parameter
    if( isset($_GET['date']) ) {
      $param = strtoupper($_GET['date']);
    	//validate the parameter
    	if( $param != "ASC") {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " q.question_date $param";
    	}
    } else if( isset($_GET['answer']) ) {
      $param = strtoupper($_GET['answer']);
      //validate the parameter
      if( $param != "DESC") {
        $errors [] = "Wrong parameters.";
      } else {
        $sort_string = " q.answers $param";
      }
    } else if( isset($_GET['point']) ) {
      $param = strtoupper($_GET['point']);
      //validate the parameter
      if( $param != "DESC") {
        $errors [] = "Wrong parameters.";
      } else {
        $sort_string = " points $param";
      }
    } else if( isset($_GET['unanswered']) ) {
      //validate the parameter
      if( $_GET['unanswered'] != 1 ) {
        $errors [] = "Wrong parameters.";
      } else {
        //if the user wnats to see unnanswered questions, filter it by date in descending order
        $unanswered_string = " AND q.answers = 0 ";
        $sort_string = " q.question_date DESC ";
      }
    } else {
    	$sort_string = " q.question_date DESC ";
    }

    if(empty($errors)) {
			$questions = array();

      //include script to calculate the question time
      include('includes/how_long.php');

			//get the count of questions
			$stmt = $dbc->prepare("SELECT COUNT(DISTINCT q.question_id) FROM question AS q JOIN tag_associations AS ta ON q.question_id = ta.question_id 
        WHERE ta.tag_id IN ($tags_id_string) AND q.question_heading LIKE CONCAT('%',?,'%') $unanswered_string ");
			$stmt->bind_param("s", $search_string);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($total_questions);
	    $stmt->fetch();

    	$stmt = $dbc->prepare("SELECT q.question_id, q.question_heading, q.points, q.answers, TIMESTAMPDIFF(SECOND, q.question_date, NOW()), DATE_FORMAT(q.question_date, '%b %e'), DATE_FORMAT(q.question_date, 'at %h:%i%p'), YEAR(q.question_date), YEAR(NOW()), DAY(q.question_date), DAY(NOW()) 
        FROM question AS q 
        JOIN tag_associations AS ta ON q.question_id = ta.question_id
        WHERE ta.tag_id IN ($tags_id_string) AND q.question_heading LIKE CONCAT('%',?,'%') $unanswered_string
        GROUP BY ta.question_id
        ORDER BY $sort_string
        LIMIT ?, 15");

      $stmt->bind_param("sd", $search_string, $start);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($question_id, $question_heading, $points, $answers, $seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);

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

        $heading_chars = str_split($question_heading);
        $str = "";
        $question_heading_array = array();
        if($q != "") {
          $q_count = strlen($q);
          $heading_count = count($heading_chars);
          for ($i=0; $i < $heading_count ; $i++) {
            $str = $str . $heading_chars[$i];

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
          'how_long' => calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today),
          'tags' => $tags
        );

        //push the new question to the questions array
        array_push($questions, $question);
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