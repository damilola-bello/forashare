<?php
  $_SESSION = []; // Clear the variables.
  session_destroy(); // Destroy the session itself.
  setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0); // Destroy the cookie.
  setcookie('in', true, time()-3600, "/"); //destroy logged in cookie flag


  //redirect to the previous page, else redirect to the questions page
	if(isset($_GET['redirect'])) {
		header('Location: '.base64_decode($_GET['redirect']));  
	} else {
		header('Location: questions.php');  
	}
  exit(); // Quit the script.
?>