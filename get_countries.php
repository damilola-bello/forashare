<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$start = $_GET['start'];
		$sort_string = "";
		$region_sort_string = '';
		$search_string = '';
    $q = "";

		$regions_pattern = "/^(([a-z]+[,]([a-z]*))+)$|(^[a-z]+)$/";
		//Validation
		if( isset($_GET['start']) && (filter_var($_GET['start'], FILTER_VALIDATE_INT) === 0 || filter_var($_GET['start'], FILTER_VALIDATE_INT)) && (isset($_GET['regions']) && (preg_match($regions_pattern, $_GET['regions']) || empty($_GET['regions'])) ) ) {
      $start = intval($_GET['start']);
      $a = explode(',', $_GET['regions']);
      $region_sort_string = implode("','", $a);
      $region_sort_string = "'$region_sort_string'";
    } else {
      $errors [] = "Wrong parameters.";
    }

    //set the search string if there is any
    if( isset($_GET['q']) ) {
      $q = trim($_GET['q']);
    	//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = preg_replace('/(?<!\\\)([%_])/', '\\\$1',$_GET['q']);
    }
    //check if there is a name query parameter
    if( isset($_GET['name']) ) {
    	//validate the parameter
    	if( strtolower($_GET['name']) != "desc" ) {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " t.tag_name ASC ";
    	}
    } else if( isset($_GET['user']) ) {
    	//if there is a user query string, validate it
    	if( strtolower($_GET['user']) != "desc" ) {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " users DESC ";
    	}
    } else {
    	$sort_string = " questions DESC ";
    }

    if(empty($errors)) {
			$countries = array();

			//get the count of countries
			$stmt = $dbc->prepare("SELECT COUNT(t.tag_id) FROM region AS r
        JOIN region_associations AS ra ON r.region_id = ra.region_id
        JOIN tag AS t ON t.tag_id = ra.tag_id
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id 
        WHERE tt.name = 'forum' AND r.region_name IN ($region_sort_string) AND t.tag_name LIKE CONCAT('%',?,'%') ");
			$stmt->bind_param("s", $search_string);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($total_countries);
	    $stmt->fetch();

    	$stmt = $dbc->prepare("SELECT 
        (SELECT COUNT(user_id) FROM user AS u WHERE u.tag_id = t.tag_id) AS users, 
        (SELECT COUNT(question_id) FROM tag_associations AS ta WHERE ta.tag_id = t.tag_id) AS questions, t.tag_name, t.tag_id, c.alpha_code 
        FROM region AS r
        JOIN region_associations AS ra ON r.region_id = ra.region_id
        JOIN country_details AS c ON ra.tag_id = c.tag_id
        JOIN tag AS t ON t.tag_id = c.tag_id 
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id 
        WHERE tt.name = 'forum' AND r.region_name IN ($region_sort_string) AND t.tag_name LIKE CONCAT('%',?,'%')
        ORDER BY $sort_string
        LIMIT ?, 50");

      $stmt->bind_param("si", $search_string, $start);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($users, $questions, $country_name, $tag_id, $alpha_code);

      while ($stmt->fetch()) {

        $country_name_chars = str_split($country_name);
        $str = "";
        $country_name_array = array();
        if($q != "") {
          $q_count = strlen($q);
          $country_name_count = count($country_name_chars);
          for ($i=0; $i < $country_name_count ; $i++) {
            $str = $str . $country_name_chars[$i];

            $last_chars = substr($str, -$q_count);
            //if the last set of characters matches the query search
            if (strcasecmp($last_chars, $q) == 0) {
              $start_str = substr($str, 0, (strlen($str) - $q_count));
              //push the preceding characters to an array if it isn't the query search
              if(strcasecmp($start_str, $q) != 0) {      
                array_push($country_name_array, array(
                    'is_marked' => false,
                    'value' => $start_str
                  )
                );              
              }

              array_push($country_name_array, array(
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
          array_push($country_name_array, array(
              'is_marked' => false,
              'value' => $country_name
            )
          ); 
        }

        if ($str != "") {
          array_push($country_name_array, array(
              'is_marked' => false,
              'value' => $str
            )
          );
        }

	      $country = array(
          'alpha_code' => $alpha_code,
          'forum_name' => $country_name_array,
          'tag_id' => $tag_id,
          'questions' => $questions,
          'users' => $users
        );

        //push the new country to the countries array
        array_push($countries, $country);
      }
      $stmt->free_result();
      $stmt->close();

      $return = array("isErr" => false, "message" => array(
      	'total_countries' => $total_countries,
      	'countries' => $countries
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