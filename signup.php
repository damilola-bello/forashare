<?php
    include('includes/generic_includes.php');
    //If the user is logged in and wants to go to the sign up page, redirect the user to the home page.
    if ($loggedin == true) {
      header("Location: index.php");
      exit(); // Quit the script.
    }

    $page_title = 'Sign up - ForaShare';
    $page = "signup";
    include('includes/header.php');

    $submitted = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $submitted = true;
      //Validate sign up details

      //Array to hold the errors
      $signup_errors = [];

      //username validation
      $username = $_POST['username'];
      if(empty($username)) {
        $signup_errors[] = "Username cannot be empty.";
      } else {
        $username = trim($username);
        $username_length = strlen($username);
        if($username_length < 3 || $username_length > 25) {
          $signup_errors[] = "Username can only have 3-25 characters.";
        } else if (!preg_match("/^([a-zA-Z][a-zA-Z0-9_]{2,24})$/", $username)) {
          $signup_errors[] = "Username must start with an alphabet and can only have alphabets, numbers and an underscore(_).";
        }
      }

      //forum validation
      $forum = $_POST['forum'];
      if(empty($forum) || $forum == "empty") {
        $signup_errors[] = "Choose a country to belong to.";
      } else {
        //Escape harmful characters
        $forum_sterilize = mysqli_real_escape_string($dbc, trim($forum));

        $stmt = $dbc->prepare("SELECT tag_id FROM forum_details WHERE alpha_code = ?");
        $stmt->bind_param("s", $forum_sterilize);
        $stmt->execute();
        //Get the result of the query
        $result = $stmt->get_result();
        if(!($result->num_rows === 1)) {
          //If the alpha code submitted through the post doesn't belong to a valid forum
          $signup_errors[] = "Choose a country to belong to.";
        } else {
          //Get the forum id the new user belongs to
          $row = $result->fetch_assoc();
          $forum_id = $row['tag_id'];
        }
        //Close the statement
        $stmt->close();
        unset($stmt);
      }

      //email validation
      $email = $_POST['email'];
      if(empty($email)) {
        $signup_errors[] = "Email cannot be empty.";
      } else {
        $email = trim($email);
        //check if $email is a valid email
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
          $signup_errors[] = "Enter a valid email address.";
        } else {
          //If the email is a valid one, check if the email is already registered

          $stmt = $dbc->prepare("SELECT user_id FROM user WHERE email = ?");
          $stmt->bind_param("s", $email);
          $stmt->execute();
          //Get the result of the query
          $result = $stmt->get_result();
          if($result->num_rows != 0) {
            //If a user already exist with the same email
            $signup_errors[] = "A user is already registered with the same email.";
          }
          //Close the statement
          $stmt->close();
          unset($stmt);
        }
      }

      //password validation
      $password1 = $_POST['password'];
      $password2 = $_POST['password_verify'];
      if(empty($password1) || empty($password2)) {
        $signup_errors[] = "Enter a password in both password fields.";
      } else {
        $password1 = trim($password1);
        $password2 = trim($password2);
        //if the passwords don't match
        if($password1 != $password2) {
          $signup_errors[] = "Passwords don't match.";
        } else {
          //at this point, only $password1 will be used for the password since the previous condition ensured that $password1 == $password2
          $password_length = strlen($password1);
          if($password_length < 6 || $password_length > 20) {
            $signup_errors[] = "Password must have 6-20 characters.";
          }
          if(!preg_match("/^([0-9]+[a-zA-Z_%*]+|[a-zA-Z_%*]+[0-9]+)[0-9a-zA-Z_%*]*$/", $password1)) {
            $signup_errors[] = "Password must contain numbers and alphabets. Special characters like _*% are allowed.";
          }
        }
      }

      //After validating each field, check if there errors
      if(empty($signup_errors)) {
        //there are no errors, register the user
        $empty_value = NULL;
        $default = "DEFAULT";
        $pass = password_hash($password1, PASSWORD_DEFAULT);
        $stmt = $dbc->prepare("INSERT INTO user (email, password, username, date_joined, tag_id) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->bind_param("ssss", $email, $pass, $username, $forum_id);
        $stmt->execute();

        //Check if the new record was added
        if($stmt->affected_rows === 1) {
          //Success
        } else {
          //Not able to register user
          $error_lists = "<li>Unable to register you now. Please try again.</li>";
        }
        //Close the statement
        $stmt->close();
        unset($stmt);
      } else {
        //if there are errors with the fields
        $error_lists = "";
        //Each error message forms a list item
        foreach ($signup_errors as $err) {
          $error_lists .= "<li>$err</li>";
        }
      }

    }
     
?>
<!-- CONTAINER -->
<div class="container-fluid content-container row">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
  	<div class="register">
  	  <div class="reg-tab row">
  	  	<span class="reg-link">
  	  		<a href="login.php">Login</a>
  	  	</span>
  	  	<span class="reg-link">
  	  		<a href="sigup.php" class="reg-tab-active">Sign up</a>
  	  	</span>
  	  </div>
  	  <div class="reg-content flx-col">
        <?php
          //If the registration is complete without any errors
          if($submitted == true && empty($error_lists)) {
        ?>
        <div class="reg-info">
          <p>Registration complete!<br><br>You can now login using the tab above or <a href="login.php">click here to go to the login page</a>.</p>
        </div>
        <?php
          } else {
        ?>
  	  	<div class="reg-info">
  	  	  <h1 class="page-title">ForaShare is a platform where you can ask about things peculiar to a region and get response(s) from fellow users.</h1>
  	  	</div>
  	  	<div class="reg-form">
  	  		<form method="post" method="post" class="signup-form" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group form-error">
              <?php 
                if ($submitted == true && !empty($error_lists)) {
                  echo '<ul>';
                  echo $error_lists;
                  echo '</ul>';            
                }
              ?>
            </div>
  	  		  <div class="form-group">
              <span class="form-lbl">Username</span>
              <input type="text" name="username" id="username_signup" tabindex="1" required minlength="3" maxlength="25" placeholder="user123" class="form-control" 
              <?php if($submitted == true) { echo "value='" . $_POST['username'] . "'"; } ?> >
  	  		  </div>

            <div class="form-group">
              <div class="form-label-box row">
                <span class="form-lbl">Country</span>
                <i class="far fa-question-circle label-info" tabindex="2" data-toggle="tooltip" data-placement="top" title="Choose a country you want to belong to"></i>
              </div>
              <?php
                  $query = "SELECT t.tag_name, f.alpha_code FROM forum_details AS f JOIN tag AS t ON t.tag_id = f.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id ORDER BY t.tag_name ASC";
                  $r = mysqli_query($dbc, $query);

                  echo '<select class="form-control" name="forum" tabindex="3" id="forum_signup">';
                  echo '<option value="empty">-- Choose Country --</option>';
                  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                    $alpha_code = $row['alpha_code'];
                    echo "<option value='$alpha_code'" . (($submitted == true && $_POST['forum'] == $alpha_code) ? ' selected' : ''). ">" . strtoupper($alpha_code) . " - " . $row['tag_name'] . "</option>"; 
                  }
                  echo '</select>';
              ?>
  	  		  </div>

            <div class="form-group">
              <span class="form-lbl">Email</span>
              <input type="email" name="email" id="email_signup" tabindex="4" required placeholder="name@example.com" class="form-control"
              <?php if($submitted == true) { echo "value='" . $_POST['email'] . "'"; } ?>>
  	  		  </div>
  	  		  
  	  		  <div class="form-group">
              <div class="form-label-box row">
                <span class="form-lbl">Password</span>
                <i class="far fa-eye label-info password-toggle" title="Show Password"></i>
              </div>
              <input type="password" name="password" id="password1_signup" tabindex="5" required minlength="6" maxlength="20" placeholder="******" class="form-control password-input">
  	  		  </div>
            
            <div class="form-group">
              <span class="form-lbl">Verify Password</span>
              <input type="password" name="password_verify" id="password2_signup" tabindex="6" required minlength="6" maxlength="20" placeholder="******" class="form-control password-input">
  	  		  </div>

            <div class="form-group">
  	  			  <button class="btn submit-btn sign-up-submit" type="submit" tabindex="7">Sign Up</button>
            </div>
  	  		</form>
  	  	</div>
        <?php
          }
        ?>
  	  </div>
  	</div>
  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>