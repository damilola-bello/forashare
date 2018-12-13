<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];

      $follower_id = $view_user_id;
      $tag_id = "";
      $follow = "";


      $tag_id_pattern = "/^([0-9]+)$/";
      $follow_pattern = "/^[0|1]$/";
      //validate the parameters
      if( (isset($_GET['tag_id']) && (preg_match($tag_id_pattern, $_GET['tag_id'])))  && (isset($_GET['follow']) && (preg_match($follow_pattern, $_GET['follow']))) ) {
        $tag_id = $_GET['tag_id'];
        $follow = $_GET['follow'];
      } else {
        $errors [] = " Wrong parameters.";
      }

      if(empty($errors)) {
        $is_following = false;

        //check if there is already a following relationship
        $stmt = $dbc->prepare("SELECT COUNT(user_id) FROM tag_following WHERE user_id = ? AND tag_id = ? ");
        $stmt->bind_param("ii", $view_user_id, $tag_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($follow_relationship_count);
        $stmt->fetch();

        //variable to store if a follow relationship already exists
        $follow_relationship_exist = ($follow_relationship_count == 0) ? false : true;
        $is_following = ($follow_relationship_count == 0) ? false : true;
        //if there is an unfollow request and a relationship already exists, remove the relationship
        if($follow == 0 && $follow_relationship_exist == true) {
          $stmt = $dbc->prepare("DELETE FROM tag_following WHERE user_id = ? AND tag_id = ? ");
          $stmt->bind_param("ii", $view_user_id, $tag_id);
          $stmt->execute();

          $is_following = false;
        } else if($follow == 1 && $follow_relationship_exist == false) {
          //if there is a follow request and no relationship exists, create one
          $stmt = $dbc->prepare("INSERT INTO tag_following (user_id, tag_id, follow_date) VALUES (?, ?, NOW()) ");
          $stmt->bind_param("ii", $view_user_id, $tag_id);
          $stmt->execute();

          $is_following = true;
        }

        $return = array(
          "isErr" => false,
          "message" => array(
            'is_following' => $is_following
          )
        );

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