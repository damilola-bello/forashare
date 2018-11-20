<?php
	if(session_status() != PHP_SESSION_ACTIVE) {
    	session_start();
    }
    $loggedin = false;
	//Check if user is logged in
	if (isset($_SESSION['user_id']) && isset($_SESSION['agent']) && ($_SESSION['agent'] == sha1($_SERVER['HTTP_USER_AGENT']))) {
		//check if the user exists
		$stmt = $dbc->prepare("SELECT user_id FROM user WHERE user_id = ?");
    $stmt->bind_param("d", $_SESSION['user_id']);
    $stmt->execute();
    //Get the result of the query
    $result = $stmt->get_result();
    if($result->num_rows === 1) {
    	//user exists
			$loggedin = true;
    } else {
    	//if session exists but user doesn't exist, maybe the user got deleted
    	//redirect to logout page
			header("Location: logout.php");
			exit(); // Quit the script.
    }
		//Close the statement
		$stmt->close();
		unset($stmt);
	}
?>