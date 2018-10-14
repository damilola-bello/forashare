<?php
	if(session_status() != PHP_SESSION_ACTIVE) {
    	session_start();
    }
	//Check if user is logged in
	if (isset($_SESSION['user_id']) && isset($_SESSION['agent']) && ($_SESSION['agent'] == sha1($_SERVER['HTTP_USER_AGENT']))) {
		$loggedin = true;
	} else {
		$loggedin = false;
	}
?>