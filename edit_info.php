<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];
      $id = 0;
      $info = "";

      $user_id_pattern = "/^([0-9]+)$/";

      //validate the parameters
      if( (isset($_GET['user_id']) && (preg_match($user_id_pattern, $_GET['user_id'])))  && (isset($_GET['info']) && is_string($_GET['info'])) ) {
        $id = intval($_GET['user_id']);

        //A user can only edit his/her profile info
        if ($id != $view_user_id) {
          $errors [] = "Wrong parameters.";
        }

        $info = $_GET['info'];

        //newline character should be counted as one character
        $info = str_replace("\r\n", "\n", trim($info));
        //max length of info is 256 characters
        $len = mb_strlen($info);
        if($len > 256) {
        	$errors [] = "Info cannot be more than 256 characters.";
        }
        unset($len);

      } else {
        $errors [] = " Wrong parameters.";
      }

      if(empty($errors)) {
      	//replace multiple newlines (\r\n or\n or \r) with just one
      	$info = preg_replace("/(\R){2,}/", "\n", $info);
        //update the user's info
        $stmt = $dbc->prepare("UPDATE user
          SET info = ?
          WHERE user_id = ?
        ");
        $stmt->bind_param("si", $info, $id);
        $stmt->execute();

        //fetch the new info from the user
        $stmt = $dbc->prepare("SELECT info FROM user WHERE user_id = ? ");
	      $stmt->bind_param("i", $id);
	      $stmt->execute();
	      $stmt->store_result();
        $stmt->bind_result($new_info);
        $stmt->fetch();

        $return = array(
          "isErr" => false,
          "message" => array("info" => $new_info)
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