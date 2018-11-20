<?php
  $login_submitted = false; //variable that shows if the form has been submitted using a post request
  $errors = []; //variable to hold login errors

  //if the header login form is submitted and the user isn't logged in
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['banner-login-form']) && $_POST['banner-login-form'] == "bannerLoginForm" && $loggedin == false) {
    $login_submitted = true;
    require('login_func.php');

    // Check the login:
    list ($check, $err) = @check_login($dbc, $_POST['email'], $_POST['password']);

    if (!$check){ // Unsuccessful!
      // Assign $err to $errors
      $errors = $err;
    } else {
      //If log in successful, redirect to home page.
      //header("Location: index.php");
      //exit(); // Quit the script.
      //echo $_SERVER['REQUEST_URI'];
      //exit();
      $loggedin = true;
    }
  }
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
      <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.4.2/knockout-min.js"></script>

      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css" integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <!-- END OF EXTERNAL FILES -->
    <link href="css/main.css" rel="stylesheet" type="text/css">

    <script src="javascript/main.js"></script>
  </head>
  <body>
    <!-- SEARCH BLUR -->
    <div class="search-page-blur"></div>
    <!-- SEARCH BLUR END -->
    
    <!-- PAGE HEADER BEGIN -->
    <header id="page_banner" class="row" role="banner">
      <div id="banner_content" class="row">
        <div id="hamburger_box" class="banner-icon">
          <div id="nav_hamburger">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
        <div id="top_bar" class="row">
           <div class="row" id="logo_search">
            <a href="<?php echo pageDir() . '/'; ?>">
              <span id="logo" class="row align-items-center"></span>
            </a>
            <div id="search_area" class="row align-items-center">
              <div id="page_search_box">
                <input placeholder="Search ForaShare" class="form-control" id="page_search_input" />
              </div>
              <div id="search_region" class="row">
                <?php
                  $query = "SELECT t.tag_name, f.alpha_code FROM forum_details AS f JOIN tag AS t ON t.tag_id = f.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'forum' ORDER BY t.tag_name ASC";
                  $r = mysqli_query($dbc, $query);

                  //Image for the globe to search all forums
                  $search_flag = "<img src='images/forum-32/globe.png' class='banner-search-image' data-code='globe'>";

                  echo '<select class="form-control search-forum-list">';
                  echo '<option value="globe">All</option>';
                  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                    $alpha_code = $row['alpha_code'];
                    echo "<option value='$alpha_code'>" . strtoupper($alpha_code) . " - " . $row['tag_name'] . "</option>"; 
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
              if ($loggedin == false && ($page != "login" && $page != "signup")) {
                $show_login_popup = "";
                if($login_submitted == true && !empty($errors)) {
                  $show_login_popup = "show";
                }
            ?>
            <!-- REGISTER GREETING <ONLY VISIBLE FOR USERS NOT LOGGED IN> -->
            <ul id="register_greeting" class="row">
              <li class="p-relative reg-access dropdown <?php echo $show_login_popup; ?>" id="banner_signin">
                  <a href="#" class="sign-in dropdown-toggle reg-access-link" data-toggle="dropdown">
                    <span>Sign in</span>
                  </a>

                  <div id="sign_in_popup" class="dropdown-menu dropdown-menu-right <?php echo $show_login_popup; ?>">
                    <div id="sign_in_panel">
                      <form method="post" class="login-form" action="<?php echo pageURL(); ?>">
                        <div class="form-group form-error">
                          <?php if($login_submitted == true && !empty($errors)) {
                            echo '<ul>';
                            //Each error message forms a list item
                            foreach ($errors as $error) {
                              echo "<li>$error</li>";
                            }
                            echo '</ul>';  
                          } ?>
                        </div>
                        <div class="form-group">
                          <span class="form-lbl">Email</span>
                          <input type="email" tabindex="1" required class="form-control banner-form-element email-login" name="email" id="email_banner_login" placeholder="name@example.com"
                          value="<?php if($login_submitted == true) { echo $_POST['email']; } ?>">
                        </div>
                        <div class="form-group">
                          <div class="form-label-box row">
                            <span class="form-lbl">Password</span>
                            <i class="far fa-eye label-info password-toggle" title="Show Password"></i>
                          </div>
                          <input type="password" tabindex="2" required class="form-control banner-form-element password-input password-login" name="password" placeholder="******">
                        </div>
                        <div class="form-group">
                          <button type="submit" tabindex="3" class="btn banner-form-element submit-btn banner-login-submit">Sign In</button>
                        </div>
                        <input type="hidden" name="banner-login-form" value="bannerLoginForm"/>
                      </form>
                    </div>
                  </div>
              </li>
              <li class="reg-access">
                <a href="./signup.php" class="reg-access-link"> Sign Up </a>
              </li>
            </ul>
            <!-- END OF REGISTER GREETING -->
            <?php
              } else if($loggedin == true) {
            ?>
            <!-- CONTROLS FOR LOGGED IN USER -->
            <div class="row" id="banner_ctrls">
              <ul id="notification_ctrls" class="row">
                <li id="notification" class="banner-icon" data-toggle="tooltip" data-placement="bottom" title="Notifications">
                  <i class="fas fa-bell ico"></i>
                </li>
                <li id="follow_activity" class="banner-icon" data-toggle="tooltip" data-placement="bottom" title="New followers">
                  <i class="fas fa-user-plus ico"></i>
                </li>
                <li id="profile" class="banner-icon">
                  <a href="user.php?id=<?php echo $_SESSION['user_id']; ?>" class="banner-profile-pic-link" title="Go to your Profile Page">
                  <!-- Set the profile icon -->
                  <?php
                    if(isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
                      $stmt = $dbc->prepare("SELECT username, profile_image FROM user WHERE user_id = ?");
                      $stmt->bind_param("d", $_SESSION['user_id']);
                      $stmt->execute();
                      //Get the result of the query
                      $result = $stmt->get_result();
                      if($result->num_rows === 1) {
                        $row = $result->fetch_assoc();
                        $image = ($row['profile_image'] == null) ? 'images/user_icon.png': 'images/'.$row['profile_image'];
                        echo "<img class='profile-icon-pic' src = '$image' />";
                      } 
                      //Close the statement
                      $stmt->close();
                      unset($stmt);
                    }
                  ?>
                  </a>
                </li>
              </ul>
              <div class="row divider">
                <i class="fas fa-ellipsis-v"></i>
              </div>
              <div id="more_options" class="dropdown">
                <div class="banner-icon dropdown-toggle" id="options" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                </div>
                <ul class="dropdown-menu dropdown-menu-right" id="more_options_menu" aria-labelledby="options">
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Create Topic</a>
                  </li>
                  <li class="dropdown-divider"></li>
                  <li class="dropdown-item">
                    <a href="user.php?id=<?php echo $_SESSION['user_id']; ?>" class="more-options-link">Profile</a>
                  </li>
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Posts</a>
                  </li>
                  <li class="dropdown-divider"></li>
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Activity Log</a>
                  </li>
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Followers</a>
                  </li>
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Notifications</a>
                  </li>
                  <li class="dropdown-divider"></li>
                  <li class="dropdown-item">
                    <a href="./logout.php?redirect=<?php echo base64_encode(pageURL()); ?>" class="more-options-link">Logout</a>
                  </li>
                </ul>
              </div>
            </div>
            <!-- END OF CONTROLS FOR LOGGED IN USER -->
            <?php
              }
            ?>
          </div>
          <!-- END OF CONTROLS CONTAINER -->
      </div>
      </div>
    </header>
    <!-- PAGE HEADER END -->
    <div class="fontsize-adjust row">
      <div class="fontsize-toggler row" title="Adjust Font Size">
        <span>A</span>
      </div>
      <div class="fontsize-tray">
        <div class="row fontsize-heading">
          <span>Font Size</span>
        </div>
        <div class="row reset-font-box">
          <a href="#" class="reset-font" onclick="return resetFont(this);">reset</a>
        </div>
        <span class="fontsize-text row">Aa</span>
        <input class="fontsize-range" id="fontSizeRange" onchange="changeFontSize(this)" type="range" min="10" max="19" value="16">
      </div>
    </div>