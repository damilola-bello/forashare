<?php

    $page_title = 'Login - ForaShare';
    $page = "login";
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
  	  		<form method="post" action="#">

            <div class="form-group">
              <div class="form-error"></div>            
            </div>

            <div class="form-group">
              <span class="form-lbl">Email</span>
              <input type="text" name="email" placeholder="name@example.com" class="form-control">
  	  		  </div>
  	  		  
  	  		  <div class="form-group">
              <span class="form-lbl">Password</span>
              <input type="password" name="password" placeholder="******" class="form-control">
  	  		  </div>

            <div class="form-group">
  	  			  <button class="btn reg-btn" type="submit">Login</button>
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