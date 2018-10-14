<?php
  $_SESSION = []; // Clear the variables.
  session_destroy(); // Destroy the session itself.
  setcookie('PHPSESSID', '', time()-3600, '/', '', 0, 0); // Destroy the cookie.

  //redirect to questions page
  header("Location: questions.php");
  exit(); // Quit the script.
?>