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
      $loggedin = true;
    }
  }
  $font = 16;
  $is_default_font = true;
  if(isset($_COOKIE['font'])) {
    $temp = $_COOKIE['font'];
    if($temp >= 13 && $temp <= 22) {
      $font = $temp;
      $is_default_font = false;
    }
  }
  $search_country_ID = 0;
  if(isset($_COOKIE['searchCountryID']) && (preg_match("/^([0-9]+)$/", $_COOKIE['searchCountryID']))) {
    //validate the cookie
    $search_country_ID = $_COOKIE['searchCountryID'];
    $stmt = $dbc->prepare("SELECT t.tag_id
      FROM tag AS t
      JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
      WHERE tt.name = 'forum' AND t.tag_id = ?
    ");
    $stmt->bind_param("i", $search_country_ID);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows === 1) {
      $stmt->bind_result($search_country_ID);
      $stmt->fetch();
    } else {
      $search_country_ID = 0;
    }
  }
?>
<!DOCTYPE html>
<html lang="en" <?php echo ($is_default_font == false) ? "style='font-size: $font"."px'" : ""; ?> >
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

    <script type="text/javascript">
      <?php echo "var isDefaultFont = ".($is_default_font == true ? '1' : '0').";" ?>
    </script>
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
              <div data-bind="if: pageSearchQueryLength() > 0">
                <div data-bind=" attr: { id: 'search_result_box_outer' }">
                  <div data-bind=" attr: { id: 'search_result_box' }">
                    <span data-bind="if: loaded() == false">
                      <span data-bind="html: HTMLSpinner, attr: { class: 'search-loading-box' }"></span>
                    </span>
                    <span data-bind="if: loaded() == true ">
                      <ul data-bind=" foreach: searchItems, attr: { class: 'page-search-list'}">
                        <li data-bind=" attr: { class: 'search-list-item' }">
                          <a data-bind=" attr: { class:'search-link', href: link }">
                            <span data-bind=" attr: { class:'search-type' }, text: `${type} :`"></span>
                            <span data-bind="attr: { class: 'search-value' }">
                              <span data-bind="attr: { class: 'search-value-name' }">
                                <span data-bind=" if: image != ''">
                                  <img data-bind="attr: { src: `${image}`, class: 'search-item-image'}">
                                </span>
                                <span data-bind=" attr: { class:'search-content filter-heading' }, foreach: values">
                                  <span data-bind="text: value, css: { 'marked-text': is_marked }"></span>
                                </span>
                              </span>
                              <span data-bind=" html: navArrowString()"></span>
                            </span>
                          </a>
                        </li>
                      </ul>
                      <span data-bind="attr: { class: 'search-list-item ask-question' }">
                        <a data-bind="attr: { class: 'search-link ask', href: 'ask.php' }">
                          <span data-bind="text: 'Ask New Question'"></span>
                        </a>
                      </span>
                      <span data-bind="if: (searchCountry() != '')">
                        <span class="results-from-box">
                          <span class="results-from-text">
                            <span data-bind="text: 'Result(s) from '"></span>
                            <a data-bind="text: `${searchCountry()}`, attr: { href: `country.php?id=${searchCountryID()}` }"></a>
                          </span>
                          <span>
                            <a data-bind="event: { click: showAllCountries }" class="show-all-link" href="#">Show All</a>
                          </span>
                        </span>
                      </span>
                    </span>
                  </div>
                </div>
              </div>
              <div id="page_search_box">
                <input placeholder="Search ForaShare" class="form-control" id="page_search_input" data-bind="value: pageSearchQuery, valueUpdate:['afterkeydown','propertychange','input']" />
              </div>
              <div id="search_region" class="row">
                <?php
                  $page_search_countries = array();
                  $query = "SELECT t.tag_name, c.alpha_code, t.tag_id FROM country_details AS c JOIN tag AS t ON t.tag_id = c.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'forum' ORDER BY t.tag_name ASC";
                  $r = mysqli_query($dbc, $query);
                  $page_search_countries = array(
                    array(
                    'tag_name' => "All",
                    'image_name' => "globe",
                    'tag_id' => 0,
                    'alpha_code' => ''
                    )
                  );
                  $search_flag = "<img src='images/forum-32/globe.png' class='banner-search-image ".($search_country_ID == 0 ? 'current-country' : '') ."' data-code='globe' data-id='0'>\r\n";
                  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                    $alpha_code = $row['alpha_code'];
                    $page_search_country = array(
                      'tag_name' => $row['tag_name'],
                      'tag_id' => $row['tag_id'],
                      'alpha_code' => $alpha_code
                    );
                    array_push($page_search_countries, $page_search_country);

                    $search_flag .= "<img src='images/forum-32/$alpha_code.png' class='banner-search-image ".($search_country_ID == $row['tag_id'] ? 'current-country' : '') ."' data-code='$alpha_code' data-id=".$row['tag_id'].">\r\n";
                  }
                  
                ?>
                <select data-bind="options: countries, optionsText: function(item){ return `${item.tag_name != 'All' ? item.alpha_code + ' - ' : '' }` + item.tag_name}, optionsValue: 'tag_id', value: search_country_ID, event: {change: changeSearchCountryName, click: setClickValue }, attr: { class: 'form-control search-forum-list'}"></select>
                <?php echo "$search_flag"; ?>
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
                        echo "<img class='profile-icon-pic' id='profileIconPic' src = '$image' />";
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
                    <a href="saved.php" class="more-options-link">Saved Questions</a>
                  </li>
                  <li class="dropdown-divider"></li>
                  <li class="dropdown-item">
                    <a href="#" class="more-options-link">Activity Log</a>
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
        <span class="fontsize-toggler-icon">A</span>
      </div>
      <div class="fontsize-tray">
        <div class="row fontsize-heading">
          <span>Font Size</span>
        </div>
        <div class="row reset-font-box">
          <a href="#" class="reset-font" onclick="return resetFont(this);">reset</a>
        </div>
        <span class="font-indicator-box">
          <span class="fontsize-text row">Aa</span>
          <span class="font-percentage"><?php echo ((10 - (22 - $font)) * 10)."%"; ?></span>
        </span>
        <script type="text/javascript">
          <?php echo "var fontSize = $font ;"; ?>
          <?php
            $a = array(
              'search_country_ID' => $search_country_ID,
              'countries' => $page_search_countries
            );
            echo "var json_search_countries_payload = " . json_encode($a) . ";";
            unset($a);
          ?>

          function navArrowString() {
            return "<i class='fas fa-arrow-right search-item-go-icon'></i>";
          }

          function SearchItemModel(data) {
            var self = this;

            self.type = data.type;
            self.link = data.id;
            self.values = data.values;
            self.image = data.image;
          }

          function SearchModel() {
            var self = this;

            self.HTMLSpinner ="<i class='fas fa-spinner loading-icon'></i>";
            self.searchTextTimeOut = ko.observable(0);
            self.searchDDLTimeOut = ko.observable(0);
            self.countries = json_search_countries_payload.countries;
            self.search_country_ID = json_search_countries_payload.search_country_ID;
            self.selectedCountryID = ko.observable(self.search_country_ID);
            self.pageSearchQuery = ko.observable('');
            self.loaded = ko.observable(false);
            self.pageSearchQueryLength = ko.computed(
              function () {
                return self.pageSearchQuery().trim().length;
              }
            );
            self.previousSearch = ko.observable('');
            self.searchParam = ko.observable({});
            self.countryIDParam = ko.observable((self.selectedCountryID() == 0) ? {} : { country_id: self.selectedCountryID() });
            self.pageSearch = ko.computed(function () {
              let q = self.pageSearchQuery().trim();
              let currentLength = self.pageSearchQueryLength();
              //if the filter string changes
              if(currentLength > 0 && self.previousSearch() != q) {
                //clear the timeout
                clearTimeout(self.searchTextTimeOut());
                self.searchTextTimeOut(setTimeout(() => {
                  self.search();
                }, 500));

                self.searchParam({q: q});
                //set the new search param
                self.previousSearch(q);
              }
            });
            self.setClickValue = function () {
              clickEl = 'search_select_country';
            }

            self.changeSearchCountryName = function(koObj, e) {
              clearTimeout(self.searchDDLTimeOut());
              let selectedID = Number(e.currentTarget.value);
              if(!isNaN(selectedID) && selectedID >= 0 ) {
                //reset timer
                self.selectedCountryID(selectedID);

                $(".banner-search-image").removeClass('current-country');
                $(`.banner-search-image[data-id="${selectedID}"]`).addClass('current-country');

                //set the country param
                let param = (selectedID == 0) ? {} : { country_id: selectedID };
                self.countryIDParam(param);

                //set the search country id as a cookie, if id is 0 remove cookie
                if(selectedID == 0) {
                  removeCookie('searchCountryID');
                } else {
                  setCookie('searchCountryID', selectedID, 30);
                }

                //call search only if there is a search query
                if(self.searchParam().q && self.searchParam().q.length > 0) {
                  self.searchDDLTimeOut(setTimeout(() => {
                    self.search();
                  }, 500));
                }
              }
            }
            self.searchItems = ko.observable([]);
            self.searchItemsCount = ko.observable(0);
            self.searchCountry = ko.observable("");
            self.searchCountryID = ko.observable("");
            self.showAllCountries = function(koObj, e) {
              //change the search country
              $('.search-forum-list').val('0').change();
              clickEl = 'page_search_input';
            };
            self.search = function() {
              self.loaded(false);
              let payload = { ...self.searchParam(), ...self.countryIDParam() };
              $.get('page_search.php', payload, 
                function(data, status) {
                  if(status == "success") {
                    if(data.isErr == false) {
                      let mappedSearchItems = data.message.results.map(searchItem => new SearchItemModel(searchItem));
                      self.searchItems(mappedSearchItems);
                      self.searchCountry(data.message.country);
                      self.searchCountryID(data.message.country_id);
                      //reset the count of the search items
                      self.searchItemsCount(self.searchItems().length);
                      //set the focus to the input box
                      $('#page_search_input').focus();

                      self.loaded(true);
                    } else {
                      self.loaded(true);
                    }
                  } 
              }, "json");
            }
          }

          var searchModel = new SearchModel();
          ko.applyBindings(searchModel, document.getElementById("top_bar"));
        </script>
        <span class="fontsize-change-box">
          <i class="far fa-minus-square change-font font-subtract <?php if($font == 13) echo 'disabled'; ?>" title="Decrease Font" data-font="subtract"></i>
          <i class="far fa-plus-square change-font font-add <?php if($font == 22) echo 'disabled'; ?>" title="Increase Font" data-font="add"></i>
        </span>

      </div>
    </div>