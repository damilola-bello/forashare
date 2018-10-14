<?php
    include('includes/generic_includes.php');
    //If the user is logged in and wants to go to the login page, redirect the user to the home page.
    if($loggedin == true) {
      header("Location: index.php");
      exit(); // Quit the script.
    }

    $page_title = 'Login - ForaShare';
    $page = "login";

    $submitted = false; //variable that shows if the form has been submitted using a post request
    $errors = []; //variable to hold login errors

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $submitted = true;
      require('login_func.php');

      // Check the login:
      list ($check, $login_errors) = check_login($dbc, $_POST['email'], $_POST['password']);
      if ($check) { // OK!
        // Redirect the user:
        header("Location: index.php");
        exit(); // Quit the script.
      }
    }
    include('includes/header.php');
     
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
  	  		<a href="login.php" class="reg-tab-active">Login</a>
  	  	</span>
  	  	<span class="reg-link">
  	  		<a href="signup.php">Sign up</a>
  	  	</span>
  	  </div>
  	  <div class="reg-content flx-col">
  	  	<div class="reg-form">
  	  		<form method="post" class="login-form" action="<?php echo $_SERVER['PHP_SELF']; ?>">

            <div class="form-group form-error">
              <?php 
                //If there are errors with the login
                if ($submitted == true && !empty($login_errors)) {
                  echo '<ul>';
                  //Each error message forms a list item
                  foreach ($login_errors as $error) {
                    echo "<li>$error</li>";
                  }
                  echo '</ul>';            
                }
              ?>            
            </div>

            <div class="form-group">
              <span class="form-lbl">Email</span>
              <input type="email" tabindex="1" required name="email" placeholder="name@example.com" class="form-control email-login" id="email_login_page"
              <?php if($submitted == true) { echo "value='" . $_POST['email'] . "'"; } ?> >
  	  		  </div>
  	  		  
  	  		  <div class="form-group">
              <div class="form-label-box row">
                <span class="form-lbl">Password</span>
                <i class="far fa-eye label-info password-toggle" title="Show Password"></i>
              </div>
              <input type="password" tabindex="2" name="password" required placeholder="******" class="form-control password-input password-login" id="password_login_page">
  	  		  </div>

            <div class="form-group">
  	  			  <button class="btn reg-btn login-submit" tabindex="3" type="submit">Login</button>
            </div>

  	  		</form>
  	  	</div>
  	  </div>
  	</div>
  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>