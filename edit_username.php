<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];
      $id = 0;
      $username = "";

      $user_id_pattern = "/^([0-9]+)$/";

      //validate the parameters
      if( (isset($_GET['user_id']) && (preg_match($user_id_pattern, $_GET['user_id'])))  && (isset($_GET['username']) && is_string($_GET['username'])) ) {
        $id = intval($_GET['user_id']);

        //A user can only edit his/her profile username
        if ($id != $view_user_id) {
          $errors [] = "Wrong parameters.";
        }

        $username = $_GET['username'];

        $username = trim($username);
        $username_length = mb_strlen($username);
        if($username_length < 3 || $username_length > 25) {
          $errors [] = "Username can only have 3-25 characters.";
        } else if (!preg_match("/^([a-zA-Z][a-zA-Z0-9_]{2,24})$/", $username)) {
          $errors [] = "Username must start with an alphabet and can only have alphabets, numbers and an underscore(_).";
        }

      } else {
        $errors [] = " Wrong parameters.";
      }

      if(empty($errors)) {
        //update the user's username
        $stmt = $dbc->prepare("UPDATE user
          SET username = ?
          WHERE user_id = ?
        ");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();

        //fetch the new username from the user
        $stmt = $dbc->prepare("SELECT username FROM user WHERE user_id = ? ");
	      $stmt->bind_param("i", $id);
	      $stmt->execute();
	      $stmt->store_result();
        $stmt->bind_result($new_username);
        $stmt->fetch();

        $return = array(
          "isErr" => false,
          "message" => array("username" => $new_username)
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