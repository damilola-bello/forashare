<?php
	// Check if submitted by post method:
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
    	$view_user_id = $_SESSION['user_id'];

    	if(isset($_POST['imagebase64'])) {
	      $data = $_POST['imagebase64'];
	      
	      list($type, $data) = explode(';', $data);
	      list(, $data)      = explode(',', $data);

				$f = finfo_open();

		    $imgdata = base64_decode($data);
				$mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);

				//echo "$mime_type";exit();
				//if($mime_type == "image/png") {
					//image name 
			    $img_name = "u_" . uniqid() . "_" . $view_user_id . ".png";
					
			    /* move image to temp folder */
					$TempPath = 'images/temp/'.$img_name;
					file_put_contents($TempPath, $imgdata);

					$ImageSize = filesize($TempPath);/* get the image size */

						
					if($ImageSize < 1000000) { /* limit size to around 1MB */
						/** move the uploaded image **/
						$path = 'images/'.$img_name;
						file_put_contents($path, $imgdata);

						$imgdata = $path;
						/** get the image path and store in database **/

						unlink($TempPath);/* delete the temporay file */

						//fetch the old image name
		        $stmt = $dbc->prepare("SELECT profile_image FROM user WHERE user_id = ? ");
			      $stmt->bind_param("i", $view_user_id);
			      $stmt->execute();
			      $stmt->store_result();
		        $stmt->bind_result($old_image_name);
		        $stmt->fetch();
						//delete the old profile image 
						unlink("images/$old_image_name");

						//update the user profile image
		        $stmt = $dbc->prepare("UPDATE user SET profile_image = ? WHERE user_id = ? ");
			      $stmt->bind_param("si", $img_name, $view_user_id);
			      $stmt->execute();

			      //fetch the user image name
		        $stmt = $dbc->prepare("SELECT profile_image FROM user WHERE user_id = ? ");
			      $stmt->bind_param("i", $view_user_id);
			      $stmt->execute();
			      $stmt->store_result();
		        $stmt->bind_result($updated_image_name);
		        $stmt->fetch();

						$return = array("isErr" => false, "message" => array(
							'image_name' => $updated_image_name
						));
					} else{
						unlink($TempPath);/* delete the temporay file */
						/** image size limit exceded **/
						$errors [] = "Image too large.";
						$return = array("isErr" => true, "message" => $errors);
					}
				//}
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