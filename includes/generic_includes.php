<?php
    require_once('mysqli_connect.php');
    require_once('checkifloggedin.php');
    require_once('declare_constants.php');

    function pageURL () {
    	return $_SERVER["REQUEST_URI"];
    }

    function pageDir () {
    	return dirname($_SERVER['PHP_SELF']);
    }
?>