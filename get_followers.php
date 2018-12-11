<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');

		$errors = array();
		$return = array();
		$start = 0;
		$user_id = '';

		$num_pattern = "/^([0-9]+)$/";
		//Validation
		if( (isset($_GET['start']) && preg_match($num_pattern, $_GET['start'])) && (isset($_GET['user_id']) && preg_match($num_pattern, $_GET['user_id']))) {
      $start = intval($_GET['start']);
      $user_id = intval($_GET['user_id']);
    } else {
      $errors [] = "Wrong parameters.";
    }


    if(empty($errors)) {
    	//check if the user exists
	    $stmt = $dbc->prepare("SELECT COUNT(user_id) FROM user WHERE user_id = ? ");
			$stmt->bind_param("i", $user_id);
	    $stmt->execute();
	    $stmt->store_result();
	    $stmt->bind_result($count);
	    if($stmt->num_rows != 1) {
	    	$errors [] = "User doesn't exist.";
	    	$return = array("isErr" => true, "message" => $errors);
	    } else {
	    	$users = array();
	    	$total_followers = 0;

        //get the total followers of the user
	    	$stmt = $dbc->prepare("SELECT followers FROM user
          WHERE user_id  = ? ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($total_followers);
        $stmt->fetch();

        //get the basic details of each of the user's followers
        $stmt = $dbc->prepare("SELECT u.user_id, u.username, u.profile_image, u.followers, u.following, u.profile_score, u.tag_id, t.tag_name 
          FROM user_following AS uf 
          JOIN user AS u ON uf.follower_id = u.user_id 
          JOIN tag AS t ON u.tag_id = t.tag_id
        	WHERE uf.following_id = ?
        	ORDER BY uf.follow_date DESC
        	LIMIT ?, 20");

        $stmt->bind_param("ii", $user_id, $start);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_id, $username, $profile_image, $followers, $following, $user_score, $follower_country_id, $follower_country_name);

        while ($stmt->fetch()) {
        	$user = array(
        		'user_id' => $user_id,
        		'username' => $username,
            'followers' => $followers,
            'following' => $following,
            'user_score' => $user_score,
            'country_id' => $follower_country_id,
            'country_name' => $follower_country_name,
        		'profile_image' => ($profile_image == null) ? 'user_icon.png' : $profile_image
        	);
        	array_push($users, $user);
        }

        $return = array("isErr" => false, "message" => array(
        	'total_followers' => $total_followers,
        	'users' => $users
        	)
      	);
	    }
    } else {
    	$return = array("isErr" => true, "message" => $errors);
    }

    // Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);

	}
?>