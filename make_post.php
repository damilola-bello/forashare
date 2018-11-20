<?php
	//if post request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		require_once('includes/mysqli_connect.php');
	  require_once('checkifloggedin.php');

	  //array to hold the return data
	  $return = array();
	  if($loggedin == true) {
			$user_id = $_SESSION['user_id'];

			$errors = array();
	  	$heading = (empty($_POST['heading']) ? null : trim($_POST['heading']));
	  	$body = (empty($_POST['body']) ? null : trim($_POST['body']));
	  	$forum = (empty($_POST['forum']) ? null : trim($_POST['forum']));
	  	$tag_ids = (empty($_POST['tags']) ? null : json_decode($_POST['tags']));


	  	//heading validation
	  	if(empty($heading)) {
	  		$errors [] = "Question heading cannot be empty.";
	  	} else {
	  		$heading_len = strlen($heading);
	  		if($heading_len < 5) {
	  			$errors [] = "Question heading too short.";
	  		} else if($heading_len > 100) {
	  			$errors [] = "Question heading cannot be more than 100 characters.";
	  		}
	  	}

	  	//body validation
	  	if(empty($body)) {
	  		$errors [] = "Question body cannot be empty.";
	  	} else {
	  		$body_len = strlen($body);
	  		if($body_len < 10) {
	  			$errors [] = "Question body too short.";
	  		} else if($body_len > 1500) {
	  			$errors [] = "$body_len Question body cannot be more than 1500 characters.";
	  		}
	  	}

	  	//forum validation
	  	if(empty($forum)) {
	  		$errors [] = "Choose a forum to post to.";
	  	} else {
	  		//check if forum exists
				$stmt = $dbc->prepare("SELECT tag_id FROM forum_details WHERE alpha_code = ?");
		    $stmt->bind_param("s", $forum);
		    $stmt->execute();
		    //Get the result of the query
		    $result = $stmt->get_result();
		    if($result->num_rows != 1) {
		    	//forum does not exist
		    	$errors [] = "Forum does not exist.";
		    } else {
		    	$row = $result->fetch_assoc();
		    	$forum_id = $row['tag_id'];
		    }
		    //Close the statement
				$stmt->close();
				unset($stmt);
	  	}

	  	//tag_ids validation
	  	if(empty($tag_ids) || count($tag_ids) == 0) {
	  		$errors [] = "You must attach at least one topic to your question.";
	  	} else if(count($tag_ids) > 3){
	  		$errors [] = "You can't attach more than three topics to your question.";
	  	} else {
	  		$invalid_tag = false;
	  		for ($i=0; $i < count($tag_ids); $i++) { 
	  			//check if the tag ids sent to the server are numeric, if so convert each to integer, else display error
	  			if(is_numeric($tag_ids[$i])) {
	  				$tag_ids[$i] = intval($tag_ids[$i]);
	  			} else {
	  				$errors [] = "Topic with id $tag_ids[$i] does not exist.";
	  				$invalid_tag = true;
	  				break;
	  			}
	  		}

	  		//if the tag ids contain numbers
	  		if($invalid_tag == false) {
	  			//check if the tags exist
	  			$query = "SELECT COUNT(*) AS count FROM tag WHERE tag_id IN (" . implode(',', $tag_ids) . ")";
	        $r = mysqli_query($dbc, $query);
	        $row = mysqli_fetch_array($r, MYSQLI_ASSOC);

	        //if the count does not match throw error
	        $count = intval($row['count']);
	        if($count != count($tag_ids)) {
	        	$errors [] = "Topic does not exist.";
	        }
	        unset($row);
	  		}
	  	}

	  	$is_image1 = false;
	  	$is_image2 = false;
	  	//images validation
	  	//image formats allowed
	  	$allowed = ['image/jpeg', 'image/png', 'image/gif'];
	  	//check if image 1 was uploaded
	  	if(isset($_FILES['image1'])) {
	  		// Check for an error:
				if ($_FILES['image1']['error'] > 0) {
	  			$errors [] = "image 1 couldn't be uploaded.";
	  		} else {
	  			if ($_FILES['image1']['size'] > 1048576) {
	  				$errors [] = "Image 1 too large. Max of 1MB.";
	  			} else if (!in_array($_FILES['image1']['type'], $allowed)) {
	  				$errors [] = "Wrong image format. Only png, jpeg and gif formats allowed.";	
					} else {
						//all good
						$is_image1 = true;
					}
	  		}
	  	}

	  	for ($i=1; $i <= 2; $i++) { 
	  		if(isset($_FILES["image$i"])) {
		  		// Check for an error:
					if ($_FILES["image$i"]['error'] > 0) {
		  			$errors [] = "image$i couldn't be uploaded.";
		  			break;
		  		} else {
		  			if ($_FILES["image$i"]['size'] > 1048576) {
		  				$errors [] = "image$i too large. Max of 1MB.";
		  			} else if (!in_array($_FILES["image$i"]['type'], $allowed)) {
		  				$errors [] = "Wrong image format. Only png, jpeg and gif formats allowed.";	
						} else {
							//all good
							switch ($i) {
								case '1':
									$is_image1 = true;
									break;
								case '2':
									$is_image2 = true;
									break;
							}
						}
		  		}
		  	}
	  	}

	  	//array to hold the image names
	  	$image_names = array();

	  	if(count($errors) == 0) {
	  		if($is_image1 || $is_image2) {
		  		for ($i=1; $i <= 2; $i++) {
		  			$var = "is_image$i";
		  			if($$var) {
			  			//Rename the uploaded file
							$temp = explode(".", $_FILES["image$i"]["name"]);
							$img_name = "q_" . $i . "_" . uniqid() . "_" . $_SESSION['user_id'] . '.' . end($temp);
							// Move the file over.
							if (!move_uploaded_file ($_FILES["image$i"]['tmp_name'], "images/question/" . $img_name)) {
								$errors [] = "Image could not be uploaded. Try again.";	
								//break out of loop if error
								break;
							}
							//store the name of the image
							$image_names [] = $img_name;
							// Delete the file if it still exists:
							if (file_exists ($_FILES["image$i"]['tmp_name']) && is_file($_FILES["image$i"]['tmp_name']) ) {
								unlink ($_FILES["image$i"]['tmp_name']);
							}
		  				
		  			}
		  		}
	  		}

  			//if the image(s) upload didn't result in errors
	  		if(count($errors) == 0) {
	  			$stmt = $dbc->prepare("INSERT INTO post (post_text, post_heading, post_date, user_id) VALUES (?, ?, NOW(), ?)");
			    $stmt->bind_param("ssd", $body, $heading, $_SESSION['user_id']);
			    //$stmt->execute();
			    if ($stmt->execute()) {
				    
				    if($stmt->affected_rows === 1) {
				    	//store the id of the newly inserted question
				    	$last_id = $dbc->insert_id;

				    	//insert the question images if any
				    	if(count($image_names) > 0) {
				    		$stmt = $dbc->prepare("INSERT INTO post_images (post_id, image_name) VALUES (?, ?)");
					    	foreach ($image_names as $img_name) {
					    		$stmt->bind_param("ds", $last_id, $img_name);
			    				$stmt->execute();
					    	}
				    		//Close the statement
								$stmt->close();
								unset($stmt);
				    	}
				    
				    	//insert the tag associations
				    	$stmt = $dbc->prepare("INSERT INTO tag_associations (post_id, tag_id) VALUES (?, ?)");

				    	//add the forum id as a tag
				    	$stmt->bind_param("dd", $last_id, $forum_id);
			    		$stmt->execute();

				    	//loop through each tag and add it to the question
				    	foreach ($tag_ids as $tag_id) {
			    			$stmt->bind_param("dd", $last_id, $tag_id);
			    			$stmt->execute();
				    	}
				    	//Close the statement
							$stmt->close();
							unset($stmt);

				    	$return = array("isErr"=> false, "message"=> $last_id);
				    }
					    
					}
	  		} else {
	  			$return = array("isErr"=> true, "message"=> $errors);
	  		}
	  	} else {
	  		$return = array("isErr"=> true, "message"=> $errors);
	  	}


	  } else {
	  	$errors [] = "You need to login.";
	  	$return = array("isErr"=> true, "message"=> $errors);
	  }

	  // Close the database connection.
	  mysqli_close($dbc);
	  unset($dbc);
	  echo json_encode($return);
	}

?>