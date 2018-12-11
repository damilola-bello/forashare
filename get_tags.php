<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$start = $_GET['start'];
		$sort_string = "";
		$country_id_sort_string = "";
    $q = "";

		$country_id_pattern = "/^([0-9]+)$/";
    //Validation
		if( isset($_GET['start']) && (filter_var($_GET['start'], FILTER_VALIDATE_INT) === 0 || filter_var($_GET['start'], FILTER_VALIDATE_INT)) ) {
      $start = intval($_GET['start']);
    } else {
      $errors [] = "Wrong parameters.";
    }

    //validate the country id parameter if it exists
    if( isset($_GET['country']) ) {
      if(!preg_match($country_id_pattern, $_GET['country'])) {
        $errors [] = "Wrong parameters.";
      } else {
        $country_id_sort_string = " tag_id = ".intval($_GET['country']) . " AND ";
      }
    }

    //set the search string if there is any
    if( isset($_GET['q']) ) {
      $q = trim($_GET['q']);
    	//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = preg_replace('/(?<!\\\)([%_])/', '\\\$1', $q);
    }
    //check if there is a name query parameter
    if( isset($_GET['name']) ) {
      $param = strtoupper($_GET['name']);
    	//validate the parameter
    	if( $param != "ASC") {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " t.tag_name $param";
    	}
    } else {
    	$sort_string = " t.questions DESC ";
    }

    if(empty($errors)) {
			$tags = array();

			//get the count of the tags
			$stmt = $dbc->prepare("SELECT COUNT(t.tag_id) FROM tag AS t
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
        WHERE $country_id_sort_string t.tag_name LIKE CONCAT('%',?,'%') AND tt.name <> 'forum' ");
			$stmt->bind_param("s", $search_string);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($total_tags);
	    $stmt->fetch();

    	$stmt = $dbc->prepare("SELECT t.tag_name, t.questions, tt.name, t.tag_id
        FROM tag AS t
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
        WHERE $country_id_sort_string t.tag_name LIKE CONCAT('%',?,'%') AND tt.name <> 'forum'
        ORDER BY $sort_string
        LIMIT ?, 50");

      $stmt->bind_param("si", $search_string, $start);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($tag_name, $questions, $tag_type, $tag_id);

      while ($stmt->fetch()) {

        $tag_name_chars = str_split($tag_name);
        $str = "";
        $tag_name_array = array();
        if($q != "") {
          $q_count = strlen($q);
          $tag_name_count = count($tag_name_chars);
          for ($i=0; $i < $tag_name_count ; $i++) {
            $str = $str . $tag_name_chars[$i];

            $last_chars = substr($str, -$q_count);
            //if the last set of characters matches the query search
            if (strcasecmp($last_chars, $q) == 0) {
              $start_str = substr($str, 0, (strlen($str) - $q_count));
              //push the preceding characters to an array if it isn't the query search
              if(strcasecmp($start_str, $q) != 0) {      
                array_push($tag_name_array, array(
                    'is_marked' => false,
                    'value' => $start_str
                  )
                );              
              }

              array_push($tag_name_array, array(
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
          array_push($tag_name_array, array(
              'is_marked' => false,
              'value' => $tag_name
            )
          ); 
        }

        if ($str != "") {
          array_push($tag_name_array, array(
              'is_marked' => false,
              'value' => $str
            )
          );
        }

	      $tag = array(
          'tag_name' => $tag_name_array,
          'tag_id' => $tag_id,
          'tag_type' => $tag_type,
          'questions' => $questions
        );

        //push the current tag to the tags array
        array_push($tags, $tag);
      }
      $stmt->free_result();
      $stmt->close();

      $return = array("isErr" => false, "message" => array(
      	'total_tags' => $total_tags,
      	'tags' => $tags
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