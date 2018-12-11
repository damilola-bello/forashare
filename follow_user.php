<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];

      $follower_id = $view_user_id;
      $following_id = "";
      $follow = "";


      $user_id_pattern = "/^([0-9]+)$/";
      $follow_pattern = "/^[0|1]$/";
      //validate the parameters
      if( (isset($_GET['following_id']) && (preg_match($user_id_pattern, $_GET['following_id'])))  && (isset($_GET['follow']) && (preg_match($follow_pattern, $_GET['follow']))) ) {
        $following_id = $_GET['following_id'];
        $follow = $_GET['follow'];
        //you cannot follow yourself
        if ($follower_id == $following_id) {
          $errors [] = " Wrong parameters.";
        }
      } else {
        $errors [] = " Wrong parameters.";
      }

      if(empty($errors)) {
        $users_id = "$follower_id,$following_id";
        //check if the users exist
        $stmt = $dbc->prepare("SELECT COUNT(user_id) FROM user WHERE user_id IN ($users_id)");
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($user_count);
        $stmt->fetch();

        //user not found
        if($user_count != 2) {
          $errors [] = "Wrong parameters.";
          $return = array("isErr" => true, "message" => $errors);
        } else if ($user_count == 2) {
          $is_following = false;

          //check if there is already a following relationship
          $stmt = $dbc->prepare("SELECT COUNT(follower_id) FROM user_following WHERE follower_id = ? AND following_id = ? ");
          $stmt->bind_param("ii", $follower_id, $following_id);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($follow_relationship_count);
          $stmt->fetch();

          //variable to store if a follow relationship already exists
          $follow_relationship_exist = ($follow_relationship_count == 0) ? false : true;
          $is_following = ($follow_relationship_count == 0) ? false : true;
          //if there is an unfollow request and a relationship already exists, remove the relationship
          if($follow == 0 && $follow_relationship_exist == true) {
            $stmt = $dbc->prepare("DELETE FROM user_following WHERE follower_id = ? AND following_id = ? ");
            $stmt->bind_param("ii", $follower_id, $following_id);
            $stmt->execute();

            $is_following = false;
          } else if($follow == 1 && $follow_relationship_exist == false) {
            //if there is a follow request and no relationship exists, create one
            $stmt = $dbc->prepare("INSERT INTO user_following (follower_id, following_id, follow_date) VALUES (?, ?, NOW()) ");
            $stmt->bind_param("ii", $follower_id, $following_id);
            $stmt->execute();

            $is_following = true;
          }

          //update the following count of the follower
          $stmt = $dbc->prepare("UPDATE user AS u
            SET following = (SELECT COUNT(follower_id) FROM user_following WHERE follower_id = u.user_id )
            WHERE u.user_id = ?
          ");
          $stmt->bind_param("i", $follower_id);
          $stmt->execute();

          //update the followers count of the followed user
          $stmt = $dbc->prepare("UPDATE user AS u
            SET followers = (SELECT COUNT(following_id) FROM user_following WHERE following_id = u.user_id )
            WHERE u.user_id = ?
          ");
          $stmt->bind_param("i", $following_id);
          $stmt->execute();

          //get the number of users following the following_id user
          $stmt = $dbc->prepare("SELECT COUNT(follower_id) FROM user_following WHERE following_id = ? ");
          $stmt->bind_param("i", $following_id);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($following_count);
          $stmt->fetch();
          $stmt->free_result();
          $stmt->close();

          $return = array(
            "isErr" => false,
            "message" => array(
              'is_following' => $is_following,
              'total_followers' => $following_count
            )
          );
        }

      } else {
        $return = array("isErr" => true, "message" => $errors);
      }

    } else {
      $errors [] = "You need to sign in.";
      $return = array("isErr" => true, "message" => $errors);
    }

    // Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
  }
?>