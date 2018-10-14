<?php
	function check_login($dbc, $email = '', $pass = '') {

		$errors = []; // Initialize error array.

		// Validate the email address:
		if (empty($email)) {
			$errors[] = 'Email address cannot be empty.';
		} else {
			$e = mysqli_real_escape_string($dbc, trim($email));
		}

		// Validate the password:
		if (empty($pass)) {
			$errors[] = 'Password cannot be empty.';
		} else {
			$p = mysqli_real_escape_string($dbc, trim($pass));
		}

		if (empty($errors)) { // If everything's OK.
			$stmt = $dbc->prepare("SELECT user_id, password FROM user WHERE email = ?");
	        $stmt->bind_param("s", $e);
	        $stmt->execute();
	        //Get the result of the query
	        $result = $stmt->get_result();
	        if(!($result->num_rows === 1)) {
	          //If the user is not found
	          $errors[] = "Invalid email or password.";
	        } else {
	          $row = $result->fetch_assoc();
		      //Close the statement
		      $stmt->close();
		      unset($stmt);
		      //if password does not match
		      if(password_verify($p, $row['password']) == false) {
		      	$errors[] = "Invalid email or password.";
		      	return [false, $errors];
		      } else {
			      // Set the session data:
			      session_start();
			      $_SESSION['user_id'] = $row['user_id'];

			      // Store the HTTP_USER_AGENT:
			      $_SESSION['agent'] = sha1($_SERVER['HTTP_USER_AGENT']);
				  // Return true and the record:
				  return [true, 1];	
		      }
	        }		
		} // End of empty($errors) IF.

		//Close the statement
		$stmt->close();
		unset($stmt);
		// Return false and the errors:
		return [false, $errors];

	} // End of check_login() function.
?>