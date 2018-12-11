<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		require_once('includes/mysqli_connect.php');

		$country_id = "";
		$country_name = "";
		$search_string = "";
		$q = "";

		$errors = array();
		$return = array();

		$number_pattern = "/^([0-9]+)$/";

		if( isset($_GET['country_id']) && (preg_match($number_pattern, $_GET['country_id'])) ) {
			$country_id = $_GET['country_id'];
			//check if country_id is a country
      $stmt = $dbc->prepare("SELECT t.tag_name
        FROM tag AS t
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
        WHERE tt.name = 'forum' AND t.tag_id = ?
      ");
      $stmt->bind_param("i", $country_id);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows === 1) {
	      $stmt->bind_result($country_name);
	      $stmt->fetch();
      } else {
      	//tag does not exist or it is not a country
        $errors [] = "Wrong parameters.";
      }
		}

		if( isset($_GET['q']) ) {
			$q = trim($_GET['q']);
			//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = trim(preg_replace('/(?<!\\\)([%_])/', '\\\$1', $q));
		}

		if (empty($errors)) {
			$search_results_array = array();
      $q_count = strlen($q);

			//get the questions related to the search param
			if($country_id == "") {
				$stmt = $dbc->prepare("SELECT question_heading, question_id
	        FROM question
	        WHERE question_heading LIKE CONCAT('%',?,'%')
	        LIMIT 5
	      ");
	      $stmt->bind_param("s", $search_string);
			} else {
	      $stmt = $dbc->prepare("SELECT q.question_heading, q.question_id
	        FROM tag AS t
	        JOIN tag_associations AS ta ON t.tag_id = ta.tag_id
	        JOIN question AS q ON ta.question_id = q.question_id
	        WHERE question_heading LIKE CONCAT('%',?,'%') AND t.tag_id = ?
	        LIMIT 5
	      ");
	      $stmt->bind_param("si", $search_string, $country_id);
			}
			
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($heading, $heading_id);

	    while($stmt->fetch()) {
		    $heading_chars = str_split($heading);
	      $str = "";
	      $heading_array = array();

        $heading_count = count($heading_chars);
        for ($i=0; $i < $heading_count ; $i++) {
          $str = $str . $heading_chars[$i];

          $last_chars = substr($str, -$q_count);
          //if the last set of characters matches the query search
          if (strcasecmp($last_chars, $q) == 0) {
            $start_str = substr($str, 0, (strlen($str) - $q_count));
            //push the preceding characters to an array if it isn't the query search
            if(strcasecmp($start_str, $q) != 0 && $start_str != "") {      
              array_push($heading_array, array(
                  'is_marked' => false,
                  'value' => $start_str
                )
              );              
            }

            array_push($heading_array, array(
                'is_marked' => true,
                'value' => $last_chars
              )
            );
            //reset the string
            $str = "";
          }
        }      
	      if ($str != "") {
	        array_push($heading_array, array(
	            'is_marked' => false,
	            'value' => $str
	          )
	        );
	      }

	      //push the question to search array
	      array_push($search_results_array, array(
	      		'type' => 'Question',
	      		'id' => "question.php?id=$heading_id",
	      		'values' => $heading_array,
	      		'image' => ''
	      	)
	    	);
	    }

	    //if there is no specific country selected - Get tags and countries related to the search param
	    if($country_id == "") {
	    	//get the countries related to the search param
				$stmt = $dbc->prepare("SELECT t.tag_name, t.tag_id, c.alpha_code
					FROM country_details AS c 
	        JOIN tag AS t ON c.tag_id = t.tag_id
	        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
	        WHERE t.tag_name LIKE CONCAT('%',?,'%') AND tt.name = 'forum'
	        LIMIT 2
	      ");
				$stmt->bind_param("s", $search_string);
	      $stmt->execute();
	      $stmt->store_result();
		    $stmt->bind_result($heading, $heading_id, $image_name);

		    while($stmt->fetch()) {
			    $heading_chars = str_split($heading);
		      $str = "";
		      $heading_array = array();

	        $heading_count = count($heading_chars);
	        for ($i=0; $i < $heading_count ; $i++) {
	          $str = $str . $heading_chars[$i];

	          $last_chars = substr($str, -$q_count);
	          //if the last set of characters matches the query search
	          if (strcasecmp($last_chars, $q) == 0) {
	            $start_str = substr($str, 0, (strlen($str) - $q_count));
	            //push the preceding characters to an array if it isn't the query search
	            if(strcasecmp($start_str, $q) != 0 && $start_str != "") {      
	              array_push($heading_array, array(
	                  'is_marked' => false,
	                  'value' => $start_str
	                )
	              );              
	            }

	            array_push($heading_array, array(
	                'is_marked' => true,
	                'value' => $last_chars
	              )
	            );
	            //reset the string
	            $str = "";
	          }
	        }      
		      if ($str != "") {
		        array_push($heading_array, array(
		            'is_marked' => false,
		            'value' => $str
		          )
		        );
		      }

		      //push the country to search array
		      array_push($search_results_array, array(
		      		'type' => 'Country',
		      		'id' => "country.php?id=$heading_id",
		      		'values' => $heading_array,
		      		'image' => "images/forum-32/$image_name.png"
		      	)
		    	);
		    }

		    //get the tags related to the search param
				$stmt = $dbc->prepare("SELECT t.tag_name, t.tag_id
	        FROM tag AS t
	        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
	        WHERE t.tag_name LIKE CONCAT('%',?,'%') AND tt.name <> 'forum'
	        LIMIT 2
	      ");
				$stmt->bind_param("s", $search_string);
	      $stmt->execute();
	      $stmt->store_result();
		    $stmt->bind_result($heading, $heading_id);

		    while($stmt->fetch()) {
			    $heading_chars = str_split($heading);
		      $str = "";
		      $heading_array = array();

	        $heading_count = count($heading_chars);
	        for ($i=0; $i < $heading_count ; $i++) {
	          $str = $str . $heading_chars[$i];

	          $last_chars = substr($str, -$q_count);
	          //if the last set of characters matches the query search
	          if (strcasecmp($last_chars, $q) == 0) {
	            $start_str = substr($str, 0, (strlen($str) - $q_count));
	            //push the preceding characters to an array if it isn't the query search
	            if(strcasecmp($start_str, $q) != 0 && $start_str != "") {      
	              array_push($heading_array, array(
	                  'is_marked' => false,
	                  'value' => $start_str
	                )
	              );              
	            }

	            array_push($heading_array, array(
	                'is_marked' => true,
	                'value' => $last_chars
	              )
	            );
	            //reset the string
	            $str = "";
	          }
	        }      
		      if ($str != "") {
		        array_push($heading_array, array(
		            'is_marked' => false,
		            'value' => $str
		          )
		        );
		      }

		      //push the tag to search array
		      array_push($search_results_array, array(
		      		'type' => 'Tag',
		      		'id' => "tag.php?id=$heading_id",
		      		'values' => $heading_array,
		      		'image' => ''
		      	)
		    	);
		    }
	    }

	    //get the users related to the search param

	    if ($country_id == "") {
	    	$stmt = $dbc->prepare("SELECT u.username, u.user_id, u.profile_image
	        FROM user AS u
	        WHERE u.username LIKE CONCAT('%',?,'%')
	        LIMIT 2
	      ");
				$stmt->bind_param("s", $search_string);
	    } else {
	    	$stmt = $dbc->prepare("SELECT u.username, u.user_id, u.profile_image
	        FROM user AS u
	        WHERE u.username LIKE CONCAT('%',?,'%') AND u.tag_id = ?
	        LIMIT 2
	      ");
				$stmt->bind_param("si", $search_string, $country_id);
	    }
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($heading, $heading_id, $image_name);

	    while($stmt->fetch()) {
		    $heading_chars = str_split($heading);
	      $str = "";
	      $heading_array = array();

        $heading_count = count($heading_chars);
        for ($i=0; $i < $heading_count ; $i++) {
          $str = $str . $heading_chars[$i];

          $last_chars = substr($str, -$q_count);
          //if the last set of characters matches the query search
          if (strcasecmp($last_chars, $q) == 0) {
            $start_str = substr($str, 0, (strlen($str) - $q_count));
            //push the preceding characters to an array if it isn't the query search
            if(strcasecmp($start_str, $q) != 0 && $start_str != "") {      
              array_push($heading_array, array(
                  'is_marked' => false,
                  'value' => $start_str
                )
              );              
            }

            array_push($heading_array, array(
                'is_marked' => true,
                'value' => $last_chars
              )
            );
            //reset the string
            $str = "";
          }
        }      
	      if ($str != "") {
	        array_push($heading_array, array(
	            'is_marked' => false,
	            'value' => $str
	          )
	        );
	      }

	      //push the user to search array
	      array_push($search_results_array, array(
	      		'type' => 'User',
	      		'id' => "user.php?id=$heading_id",
	      		'values' => $heading_array,
	      		'image' => ($image_name != null) ? "images/$image_name" : 'images/user_icon.png'
	      	)
	    	);
	    }


	    $return = array(
	    	"isErr" => false, 
	    	"message" => array(
	    		"results" => $search_results_array,
	    		"country" => $country_name,
	    		"country_id" => $country_id
	    	)
	    );

		} else {
    	$return = array("isErr" => true, "message" => $errors);
    }

		// Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}
?>