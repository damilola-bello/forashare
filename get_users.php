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
        $country_id_sort_string = " u.tag_id = ".intval($_GET['country']) . " AND ";
      }
    }

    //set the search string if there is any
    if( isset($_GET['q']) ) {
      $q = trim($_GET['q']);
    	//escape _ and % to prevent users from entering queries that will fetch all results
    	$search_string = preg_replace('/(?<!\\\)([%_])/', '\\\$1', $_GET['q']);
    }
    //check if there is a date_joined query parameter
    if( isset($_GET['date_joined']) ) {
      $param = strtoupper($_GET['date_joined']);
    	//validate the parameter
    	if( $param != "DESC" && $param != "ASC") {
    		$errors [] = "Wrong parameters.";
    	} else {
    		$sort_string = " u.date_joined $param";
    	}
    } else {
    	$sort_string = " u.profile_score DESC ";
    }

    if(empty($errors)) {
			$users = array();

      //include script to calculate the user join date
      include('includes/how_long.php');

			//get the count of users
			$stmt = $dbc->prepare("SELECT COUNT(u.user_id) FROM user AS u JOIN tag AS t ON u.tag_id = t.tag_id
        WHERE $country_id_sort_string u.username LIKE CONCAT('%',?,'%')");
			$stmt->bind_param("s", $search_string);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($total_users);
	    $stmt->fetch();

    	$stmt = $dbc->prepare("SELECT u.username, u.profile_score, u.profile_image, u.user_id, t.tag_name, t.tag_id, TIMESTAMPDIFF(SECOND, u.date_joined, NOW()), DATE_FORMAT(u.date_joined, '%b %e'), YEAR(u.date_joined), YEAR(NOW()), DAY(u.date_joined), DAY(NOW()) 
        FROM user AS u
        JOIN tag AS t ON u.tag_id = t.tag_id
        WHERE $country_id_sort_string u.username LIKE CONCAT('%',?,'%')
        ORDER BY $sort_string
        LIMIT ?, 50");

      $stmt->bind_param("si", $search_string, $start);
      $stmt->execute();
      $stmt->store_result();
	    $stmt->bind_result($username, $profile_score, $profile_image, $user_id, $country_name, $country_id, $seconds, $user_date_open, $user_join_year, $current_year, $user_join_day, $today);

      while ($stmt->fetch()) {

        $username_chars = str_split($username);
        $str = "";
        $username_array = array();
        if($q != "") {
          $q_count = strlen($q);
          $username_count = count($username_chars);
          for ($i=0; $i < $username_count ; $i++) {
            $str = $str . $username_chars[$i];

            $last_chars = substr($str, -$q_count);
            //if the last set of characters matches the query search
            if (strcasecmp($last_chars, $q) == 0) {
              $start_str = substr($str, 0, (strlen($str) - $q_count));
              //push the preceding characters to an array if it isn't the query search
              if(strcasecmp($start_str, $q) != 0) {      
                array_push($username_array, array(
                    'is_marked' => false,
                    'value' => $start_str
                  )
                );              
              }

              array_push($username_array, array(
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
          array_push($username_array, array(
              'is_marked' => false,
              'value' => $username
            )
          ); 
        }

        if ($str != "") {
          array_push($username_array, array(
              'is_marked' => false,
              'value' => $str
            )
          );
        }

        $user = array(
          'username' => $username_array,
          'user_id' => $user_id,
          'country_name' => $country_name,
          'country_id' => $country_id,
          'profile_score' => $profile_score,
          'profile_image' => ($profile_image == null) ? 'user_icon.png' : $profile_image,
          'how_long' => calculate($seconds, $user_date_open, "", $user_join_year, $current_year, $user_join_day, $today)
        );

        //push the new user to the users array
        array_push($users, $user);

      }
      $stmt->free_result();
      $stmt->close();

      $return = array("isErr" => false, "message" => array(
      	'total_users' => $total_users,
      	'users' => $users
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