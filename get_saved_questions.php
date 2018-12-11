<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

    //if the user is not loggedin
    if (!$loggedin) {
      $errors [] = "You need to sign in.";
      $return = array("isErr" => true, "message" => $errors);
      // Close the database connection.
      mysqli_close($dbc);
      unset($dbc);
      echo json_encode($return);
      exit();
    }

		$errors = array();
		$return = array();
		$start = $_GET['start'];
		$sort_string = "";
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
    	if( $param != "DESC" && $param != "ASC") {
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
    } else {
    	$sort_string = " points DESC ";
    }

    if(empty($errors)) {
			$questions = array();
      $total_questions = 0;

      //variable to hold the view user id
      $view_user_id = $_SESSION['user_id'];

      //include script to calculate the question time
      include('includes/how_long.php');

      $question_to_saved_date_map = array();
      $q_ids = array();
      //get the user's saved questions
      $stmt = $dbc->prepare("SELECT question_id, TIMESTAMPDIFF(SECOND, saved_date, NOW()), DATE_FORMAT(saved_date, '%b %e'), DATE_FORMAT(saved_date, 'at %h:%i%p'), YEAR(saved_date), YEAR(NOW()), DAY(saved_date), DAY(NOW()) FROM saved_question WHERE user_id = ?");
      $stmt->bind_param("i", $view_user_id);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($q_id, $seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);
      if($stmt->num_rows > 0) {
        while($stmt->fetch()) {
          array_push($q_ids, $q_id);
          $question_to_saved_date_map[$q_id] = calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);
        }
        $q_ids_string = implode(",", $q_ids);
        //get the count of questions based on ids of the view user's saved questions
        $stmt = $dbc->prepare("SELECT COUNT(DISTINCT q.question_id) FROM question AS q JOIN tag_associations AS ta ON q.question_id = ta.question_id 
          WHERE ta.tag_id IN ($tags_id_string) AND q.question_heading LIKE CONCAT('%',?,'%') AND q.question_id IN ($q_ids_string)");
        $stmt->bind_param("s", $search_string);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($total_questions);
        $stmt->fetch();

        $stmt = $dbc->prepare("SELECT q.question_id, q.question_heading, q.points, q.answers, TIMESTAMPDIFF(SECOND, q.question_date, NOW()), DATE_FORMAT(q.question_date, '%b %e'), DATE_FORMAT(q.question_date, 'at %h:%i%p'), YEAR(q.question_date), YEAR(NOW()), DAY(q.question_date), DAY(NOW()) 
          FROM question AS q 
          JOIN tag_associations AS ta ON q.question_id = ta.question_id
          WHERE ta.tag_id IN ($tags_id_string) AND q.question_heading LIKE CONCAT('%',?,'%') AND q.question_id IN ($q_ids_string)
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
                if(strcasecmp($start_str, $q) != 0 && $start_str != "") {      
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
            'question_saved_when' => $question_to_saved_date_map[$question_id],
            'how_long' => calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today),
            'tags' => $tags
          );

          //push the new question to the questions array
          array_push($questions, $question);
        }
        $stmt->free_result();
        $stmt->close();
      } else {

      }

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