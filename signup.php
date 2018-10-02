<?php

    $page_title = 'Sign up - ForaShare';
    $page = "signup";
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
  	  		<a href="login.php">Login</a>
  	  	</span>
  	  	<span class="reg-link">
  	  		<a href="sigup.php" class="reg-tab-active">Sign up</a>
  	  	</span>
  	  </div>
  	  <div class="reg-content flx-col">
  	  	<div class="reg-info">
  	  	  <p>ForaShare is a platform where you can ask about things peculiar to a region and get response(s) from fellow users.<br>Easy and Simple!</p>
  	  	</div>
  	  	<div class="reg-form">
  	  		<form method="post" action="#">
            <div class="form-group">
              <div class="form-error"></div>            
            </div>
  	  		  <div class="form-group">
              <span class="form-lbl">Username</span>
              <input type="text" name="username" placeholder="user123" class="form-control">
  	  		  </div>

            <div class="form-group">
              <span class="form-lbl">Forum</span>
              <?php
                  $query = "SELECT forum_name, alpha_code FROM forum ORDER BY forum_name ASC";
                  $r = mysqli_query($dbc, $query);

                  echo '<select class="form-control">';
                  echo '<option value="null" selected>-- Choose Forum --</option>';
                  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                    $alpha_code = $row['alpha_code'];
                    echo "<option value='$alpha_code'>" . strtoupper($alpha_code) . " - " . $row['forum_name'] . "</option>"; 
                  }
                  echo '</select>';
              ?>
  	  		  </div>

            <div class="form-group">
              <span class="form-lbl">Email</span>
              <input type="text" name="email" placeholder="name@example.com" class="form-control">
  	  		  </div>
  	  		  
  	  		  <div class="form-group">
              <span class="form-lbl">Password</span>
              <input type="text" name="password" placeholder="******" class="form-control">
  	  		  </div>
            
            <div class="form-group">
              <span class="form-lbl">Verify Password</span>
              <input type="text" name="verify_password" placeholder="******" class="form-control">
  	  		  </div>

            <div class="form-group">
  	  			  <button class="btn reg-btn" type="submit">Sign Up</button>
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