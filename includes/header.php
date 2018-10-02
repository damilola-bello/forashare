<?php
  // Need the database connection:
  require('mysqli_connect.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/favicon.png" />
    <title><?php echo $page_title; ?></title>

    <!-- BEGINNING OF EXTERNAL FILES -->
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
      <!-- jQuery library -->
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
      <!-- Popper JS -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>

      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <!-- END OF EXTERNAL FILES -->
    <!-- GOOGLE FONTS -->
    <link href='https://fonts.googleapis.com/css?family=Lato' rel='stylesheet'>
    <link href="css/main.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <!-- SEARCH BLUR -->
    <div class="search-page-blur"></div>
    <!-- SEARCH BLUR END -->
    
    <!-- PAGE HEADER BEGIN -->
    <header id="page_banner" role="banner">
      <div id="top_bar" class="row">
         <div class="row" id="logo_search">
          <a href="#">
            <span id="logo" class="row align-items-center"></span>
          </a>
          <div id="search_area" class="row align-items-center">
            <div id="page_search_box">
              <input placeholder="Search ForaShare" class="form-control" id="page_search_input" />
            </div>
            <div id="search_region" class="row">
              <?php
                $query = "SELECT forum_name, alpha_code FROM forum ORDER BY forum_name ASC";
                $r = mysqli_query($dbc, $query);

                //Image for the globe to search all forums
                $search_flag = "<img src='images/forum-32/globe.png' class='banner-search-image' data-code='globe'>";

                echo '<select class="form-control search-forum-list">';
                echo '<option value="globe">All</option>';
                while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                  $alpha_code = $row['alpha_code'];
                  echo "<option value='$alpha_code'>" . strtoupper($alpha_code) . " - " . $row['forum_name'] . "</option>"; 
                  $search_flag .= "<img src='images/forum-32/$alpha_code.png' class='banner-search-image' data-code='$alpha_code'>";
                }
                echo '</select>';
                echo $search_flag;
              ?>
            </div>
          </div>
          <span id="searchbar_trigger" class="banner-icon" data-toggle="tooltip" data-placement="bottom" data-original-title="Open Search" title="Open Search">
            <i class="fas fa-search ico"></i>
          </span>  
        </div>
       
        <!-- CONTROLS -->
        <div id="banner_controls" class="row">
          <?php
            if($page != "login" && $page != "signup") {
          ?>
          <!-- REGISTER GREETING <ONLY VISIBLE FOR USERS NOT LOGGED IN> -->
          <ul id="register_greeting" class="row">
            <li class="p-relative">
                <a href="./login.php" class="sign-in">
                  <span>Sign in</span>
                  <i class="fas fa-caret-down login-icon"></i>
                </a>
                <div id="sign_in_popup" class="toggle_">
                  <div id="sign_in_panel">
                    <form method="post" action="#">
                      <div class="form-group">
                        <div class="form-error"></div>
                      </div>
                      <div class="form-group">
                        <span class="form-lbl">Email</span>
                        <input type="text" class="form-control banner-form-element" name="email" placeholder="name@example.com">
                      </div>
                      <div class="form-group">
                        <span class="form-lbl">Password</span>
                        <input type="password" class="form-control banner-form-element" name="password" placeholder="******">
                      </div>
                      <div class="form-group">
                        <button type="submit" class="btn banner-form-element reg-btn">Sign In</button>
                      </div>
                    </form>
                  </div>
                </div>
            </li>
            <li>
              <a href="./signup.php"> Sign Up </a>
            </li>
          </ul>
          <!-- END OF REGISTER GREETING -->
          <?php
            }
          ?>
        
          <!-- CONTROLS FOR LOGGED IN USER -->
          <div class="row" id="banner_ctrls">
            <ul id="notification_ctrls" class="row">
              <li id="notification" class="banner-icon" data-toggle="tooltip" data-placement="bottom" title="Notifications">
                <i class="fas fa-bell ico"></i>
                <div id="notification_popup" class="toggle_">
                </div>
              </li>
              <li id="follow_activity" class="banner-icon" data-toggle="tooltip" data-placement="bottom" title="New followers">
                <i class="fas fa-user-plus ico"></i>
                <div class="toggle_" id="follow_activity_popup"></div>
              </li>
              <li id="profile" class="banner-icon" data-toggle="tooltip" data-placement="bottom" title="Profile">
                <i class="fas fa-user-circle ico"></i>
              </li>
            </ul>
            <div class="row divider">
              <i class="fas fa-ellipsis-v"></i>
            </div>
            <div id="more_options">
              <div class="banner-icon options" data-toggle="tooltip" data-placement="bottom" title="More Options">
                <i class="fas fa-caret-down ico"></i>
                <div class="toggle_" id="more_options_popup"></div>
              </div>
            </div>
          </div>
          <!-- END OF CONTROLS FOR LOGGED IN USER -->
        </div>
        <!-- END OF CONTROLS CONTAINER -->
      </div>
    </header>
    <!-- PAGE HEADER END -->