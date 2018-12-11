<?php
  include('includes/generic_includes.php');


  $is_404 = false;
  $page_heading = "Oops! User not found";

  //if id query parameter doesn't exist, redirect to users page
  if(isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = trim($_GET['id']);

    //if id is numeric and greater than 0
    if (is_numeric($user_id) && filter_var($user_id, FILTER_VALIDATE_INT) != false && intval($user_id) > 0) {
      $user_id = intval($user_id);
      //check if user exists
      $stmt = $dbc->prepare("SELECT COUNT(*) AS count, username FROM user WHERE user_id = ?");
      $stmt->bind_param("d", $user_id);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($count, $username);
      $stmt->fetch();
      $stmt->free_result();
      $stmt->close();
      //if user does not exists
      if(intval($count) != 1) {
        $is_404 = true;
      } else if(intval($count) == 1) {
        $page_heading = "User $username";
      }
    }
  } else {
    //if id query parameter is not an integer
    header("Location: users.php");
    exit(); // Quit the script.
  }
  $page_title = $page_heading . " &ndash; ForaShare";
  $page = USER;
  include('includes/header.php');
     
?>
<!-- CONTAINER -->
<div class="container-fluid content-container" id="main_content">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="create-link-box row">
      <a href="ask.php" class="create-link">Ask Question</a>
    </div>
    <?php
      if($is_404):
        $page_err_msg = "User not found.";
        include('includes/err404.php');
      else:
        $question_count = 0;
        $answer_count = 0;
        $reply_count = 0;
        $loaded_count = 0;

        $show_follow = false;
        $is_owner = false;
        $is_following = null;

        if($loggedin) {
          $view_user_id = intval($_SESSION['user_id']);
          //check if the view user is not the owner of the profile
          if($view_user_id != $user_id) {
            $show_follow = true;

            //check if a follow relationship already exist
            $stmt = $dbc->prepare("SELECT COUNT(follower_id) FROM user_following WHERE follower_id = ? AND following_id = ? ");
            $stmt->bind_param("ii", $view_user_id, $user_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($follow_relationship_count);
            $stmt->fetch();

            $is_following = ($follow_relationship_count == 0) ? false : true ;
          } else if($view_user_id == $user_id) {
            $is_owner = true;
          }

          if($is_owner) {
            $is_country_change_allowed = 0;
            $stmt = $dbc->prepare("SELECT 
              IF(u.last_country_change IS NOT NULL, DATE_FORMAT(u.last_country_change, '%W, %M %e %Y'), ''),
              IF(u.last_country_change IS NOT NULL, DATEDIFF(NOW(), u.last_country_change), NULL)
              FROM user AS u
              WHERE u.user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($last_country_change_date, $datediff);
            $stmt->fetch();

            if(is_null($datediff) || $datediff > 90) {
              $is_country_change_allowed = 1;
            }
            if($last_country_change_date != NULL) {
              $last_country_change_date .= (" ($datediff day" . ($datediff <= 1 ? "" : "s") . " ago)");
            }
          }
        }

        //get the user's info
        $stmt = $dbc->prepare("SELECT u.username, u.profile_score, u.info, YEAR(u.date_joined), MONTHNAME(u.date_joined), DAY(u.date_joined), u.profile_image, t.tag_name, t.tag_id FROM user AS u JOIN tag AS t ON u.tag_id = t.tag_id WHERE user_id = ?");
        $stmt->bind_param("d", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($username, $profile_score, $info, $year_joined, $month_joined, $day_joined, $profile_image, $user_country_name, $user_country_id);
        $stmt->fetch();
        $stmt->free_result();
        $stmt->close();

        //if no profile picture, set the default
        $profile_image = ($profile_image == null) ? 'user_icon.png' : $profile_image;

        //get the number of question the user has
        $stmt = $dbc->prepare("SELECT COUNT(question_id) FROM question WHERE user_id = ?");
        $stmt->bind_param("d", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($question_count);
        $stmt->fetch();
        $stmt->free_result();
        $stmt->close();

        //get the number of answers the user has
        $stmt = $dbc->prepare("SELECT COUNT(answer_id) FROM answer WHERE user_id = ? AND parent_answer_id IS NULL");
        $stmt->bind_param("d", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($answer_count);
        $stmt->fetch();

        //get the number replies the user has
        $stmt = $dbc->prepare("SELECT COUNT(answer_id) FROM answer WHERE user_id = ? AND parent_answer_id IS NOT NULL");
        $stmt->bind_param("d", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($reply_count);
        $stmt->fetch();

        $profile_score_positive = ($profile_score > 0) ? true : false;

        //get the number of followers
        $stmt = $dbc->prepare("SELECT COUNT(following_id) FROM user_following WHERE following_id = ? ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($followers_count);
        $stmt->fetch();

        //get the number of following
        $stmt = $dbc->prepare("SELECT COUNT(follower_id) FROM user_following WHERE follower_id = ? ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($following_count);
        $stmt->fetch();

        $stmt->free_result();
        $stmt->close();

    ?>
    <div class="profile-outer-container">
      <?php
        if($is_owner) {
      ?>
      <p class="edit-profile-mode-box">
        <span class="mode-text">Edit Mode</span>
        <label class="switch">
          <input type="checkbox" data-bind="event: { change: toggleEditMode }">
          <span class="slider"></span>
        </label>
      </p>
      <?php
        }
      ?>
      <div class="profile-intro">
        <div class="profile-name-pic">
          <div class="profile-pic-box">
            <?php if($is_owner) {
            ?>
              <span data-bind=" if: is_owner && showEditMode() == true">
                <span data-bind="if: is_owner" class="change-profile-picture-icon-box">
                  <a class="edit-profile-link" data-bind="attr: { href: '#', 'data-toggle': 'modal', 'data-target': '#editProfileImageModal', title: 'Change Profile Picture' }">
                    <span data-bind="html: editTextIcon()"></span>
                  </a>
                </span>
              </span>
            <?php
              }
            ?>
            <img class="profile-pic" data-bind="attr: { src: `images/${profileImage()}`}">
          </div>
          <div class="profile-credentials">
            <div class="profile-username-box">
              <div class="profile-username">
                <h1 class="page-title">
                  <span>
                    <span data-bind="text: username"></span>
                    <?php if($is_owner) {
                    ?>
                    <span data-bind="if: is_owner && showEditMode() == true">
                      <a class="edit-profile-link" data-bind="attr: { href: '#', 'data-toggle': 'modal', 'data-target': '#editUsernameModal', title: 'Change your username' }">
                        <span data-bind="html: editTextIcon()"></span>
                      </a>
                    </span>
                    
                    <?php
                      }
                    ?>
                  </span>
                  <span title="Profile Score" class="profile-score <?php if($profile_score_positive) { echo "positive"; } ?>"><?php echo $profile_score; ?></span>
                </h1>
              </div>
              <?php
                if($show_follow == true) {
              ?>
              <div>
                <span data-bind="if: show_follow && is_following() == true">
                  <button data-bind="attr: {class: 'btn unfollow-button', title: 'Stop following'}, event: {click: followUser.bind($data, '0')}">
                    <span data-bind="html: checkIconHTML"></span>
                    <span data-bind="text: (followLoading() == true ? '...Loading' : 'Following'), css: {italic: followLoading() == true }"></span>
                  </button>
                </span>
                <span data-bind="if: show_follow && is_following() == false">
                  <button data-bind="attr: {class: 'btn follow-button'}, event: {click: followUser.bind($data, '1')}">
                    <span data-bind="html: addUserIconHTML"></span>
                    <span data-bind="text: (followLoading() == true ? '...Loading' : 'Follow'), css: {italic: followLoading() == true }"></span>
                  </button>
                </span>
              </div>
              <?php
                }
              ?>
            </div>
            <div class="profile-info">
              <span data-bind="text: info, attr: { class: 'user-info-text'}"></span>
              <?php if($is_owner) {
              ?>
              <span data-bind="if: is_owner && showEditMode() == true">
                <span data-bind="attr: { class: 'edit-profile-link-box' }">
                  <a class="edit-profile-link edit-info-link" data-bind="attr: { href: '#', 'data-toggle': 'modal', 'data-target': '#editInfoModal' }">
                    <span data-bind="html: editTextIcon()"></span>
                    <span data-bind="text: `${info() == '' ? 'Add' : 'Edit'} info`"></span>
                  </a>
                </span>
              </span>
              
              <?php
                }
              ?>
            </div>
            
          </div>
        </div>
        <div class="profile-breakdown">
          <section class="profile-breakdown-section">
            <ul class="section-details">
              <li>
                <span class="type">
                  <i class="fas fa-history icon"></i>
                  <span>Joined</span>
                </span>
                <span class="value"><?php echo "$day_joined $month_joined $year_joined"; ?></span>
              </li>
              <li>
                <span class="type">
                  <i class="fas fa-map-marker-alt icon"></i>
                  <span>Country</span>
                </span>
                <span class="value">
                  <a  data-bind="attr: { href: `country.php?id=${user_country_id()}`, class: 'country-name'}, text: user_country_name"></a>
                  <?php if($is_owner) {
                  ?>
                  <span data-bind=" if: showEditMode() == true">
                    <span class="edit-country-link">
                      <a class="edit-profile-link" data-bind="attr: { href: '#', 'data-toggle': 'modal', 'data-target': '#editCountryModal' }">
                        <span data-bind="html: editTextIcon()"></span>
                        <span data-bind="text: 'Change Country'"></span>
                      </a>
                    </span>
                  </span>
                <?php } ?>
                </span>
              </li>
            </ul>
          </section>
          <section class="profile-breakdown-section">
            <ul class="section-details">
              <li>
                <span class="count" data-bind="text: totalQuestions"></span>
                <span data-bind="text: ' Question'+(totalQuestions() > 1 ? 's' : '')"></span>
              </li>
              <li>
                <span class="count" data-bind="text: totalAnswers"></span>
                <span data-bind="text: ' Answer'+(totalAnswers() > 1 ? 's' : '')"></span>
              </li>
              <li>
                <?php
                  $reply_count_str = "<span class='count'>$reply_count</span> " . ($reply_count > 0 ? 'Replies' : 'Reply');
                ?>
                <span><?php echo "$reply_count_str"; ?></span>
              </li>
            </ul>
          </section>
        </div>
      </div>
      <div class="profile-content">
        <div class="profile-feeds">
          <nav aria-labelledby="profile-feed-nav-header" class="profile-feeds-nav" role="navigation">
            <h3 class="section-header feed-head" id="profile-feed-nav-header">Feeds</h3>
            <ul class="profile-feeds-nav-list">
              <li class="feed-list-item" data-bind="css: {active: !questionsHidden()}">
                <a href="#" data-bind="event: {click: showQuestions}">
                  <span>Questions</span>
                  <span class="item-count" data-bind="text: totalQuestions"></span>
                </a>
              </li>
              <li class="feed-list-item" data-bind="css: {active: !answersHidden()}">
                <a href="#" data-bind="event: {click: showAnswers}">
                  <span>Answers</span>
                  <span class="item-count" data-bind="text: totalAnswers"></span>
                </a>
              </li>
              <li class="feed-list-item" data-bind="css: {active: !followingHidden()}">
                <a href="#" data-bind="event: {click: showFollowing}">
                  <span>Following</span>
                  <span class="item-count" data-bind="text: totalFollowing"></span>
                </a>
              </li>
              <li class="feed-list-item" data-bind="css: {active: !followersHidden()}">
                <a href="#" data-bind="event: {click: showFollowers}">
                  <span>Followers</span>
                  <span class="item-count" data-bind="text: totalFollowers"></span>
                </a>
              </li>
            </ul>
          </nav>
          <div class="feed-content">
            <div class="loading-box" title="Loading..." data-bind="css: {hide: !loading() }">
              <i class="fas fa-spinner loading-icon"></i>
            </div>
            <!-- Questions -->
            <div data-bind="if: !questionsHidden()">
              <h3 data-bind="text: questionHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-questions" data-bind=" foreach: questions ">
                <div data-bind="attr: {class:'question-preview'}">
                  <div class="question-heading-box">
                    <div class="question-meta">
                      <span data-bind="text: questionScore, css: {positive: questionScore > 0, negative: questionScore <= 0, score: true}"></span>
                      <span data-bind="if: hasImage, attr: {title: 'This question has at least one image'}">
                        <span data-bind="html: imageIcon()"></span>
                      </span>
                    </div>
                    <div class="question-heading">
                      <a class="question-heading-text" data-bind="attr: {href: `question.php?id=${questionID}`}, text: questionHeading"></a>
                      <div class="question-tags-box" data-bind=" foreach: tags ">
                        <a data-bind="attr: { href: `${tag_type == 'forum' ? 'country' : 'tag'}.php?id=${tag_id}`, class: `${tag_type}-tag question-tag` }, text: tag_name"></a>
                      </div>
                    </div>
                  </div>
                  <ul class="attributes">
                    <li class="attribute">
                      <span class="count" data-bind="text: questionLikes"></span>
                      <span data-bind="text: ` Like${questionLikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li data-bind="attr: {class: 'list-divider'}"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: questionDislikes"></span>
                      <span data-bind="text: ` Dislike${questionDislikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li data-bind="attr: {class: 'list-divider'}"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: answerCount"></span>
                      <span data-bind="text: ` Answer${answerCount>1 ? 's' : ''}`"></span>
                    </li>
                  </ul>
                  <div data-bind="attr: {class: 'details'}">
                    <p>
                      <span class="text" data-bind="text: questionBodyStart(questionBody)"></span>
                      <span data-bind="if: questionBodyIsMore() && !questionMoreShown()">
                        <span data-bind="text: '...', attr: { class: 'ditto' }"></span>
                      </span>
                      <span data-bind="if: questionBodyMore() && questionMoreShown()">
                        <span class="text" data-bind="text: questionBodyMore"></span>
                      </span>
                      <a href="#" data-bind="if: questionBodyMore(), event: {click: questionShowMore}">
                        <span class="more-text" data-bind="attr: { title: `Show ${questionMoreShown() ? 'Less' : 'More'}` }, text: `${questionMoreShown() ? '[Less]' : '[More]'}`"></span>
                      </a>
                    </p>

                  </div>
                  <div>
                    <span class="when" data-bind=" text: ` &ndash; ${howLong}`"></span>
                  </div>
                </div>
              </div>
            </div>
            <!-- End of Question -->

            <!-- Answers -->
            <div data-bind="if: !answersHidden()">
              <h3 data-bind="text: answerHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-questions" data-bind=" foreach: answers ">
                <div data-bind="attr: {class:'question-preview'}">
                  <div class="question-heading-box">
                    <div class="question-meta">
                      <span data-bind="text: answerScore, css: {positive: answerScore > 0, negative: answerScore <= 0, score: true}"></span>
                    </div>
                    <div class="question-heading">
                      <a class="question-heading-text" data-bind="attr: {href: `question.php?id=${questionID}&answer=${answerID}&uid=${userID}`}, text: questionHeading"></a>
                      <div class="question-tags-box" data-bind=" foreach: tags ">
                        <a data-bind="attr: { href: `${tag_type == 'forum' ? 'country' : 'tag'}.php?id=${tag_id}`, class: `${tag_type}-tag question-tag` }, text: tag_name"></a>
                      </div>
                    </div>
                  </div>
                  <ul class="attributes min">
                    <li class="attribute">
                      <span class="count" data-bind="text: answerLikes"></span>
                      <span data-bind="text: ` Like${answerLikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li data-bind="attr: {class: 'list-divider'}"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: answerDislikes"></span>
                      <span data-bind="text: ` Dislike${answerDislikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li data-bind="attr: {class: 'list-divider'}"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: replyCount"></span>
                      <span data-bind="text: ` ${replyCount>1 ? 'Replies' : 'Reply'}`"></span>
                    </li>
                  </ul>
                  <div data-bind="attr: {class: 'details'}">
                    <p>
                      <span data-bind="html: answerIcon()"></span>
                      <span class="text" data-bind="text: answerBodyStart(answerBody)"></span>
                      <span data-bind="if: answerBodyIsMore() && !answerMoreShown()">
                        <span data-bind="text: '...', attr: { class: 'ditto' }"></span>
                      </span>
                      <span data-bind="if: answerBodyMore() && answerMoreShown()">
                        <span class="text" data-bind="text: answerBodyMore"></span>
                      </span>
                      <a href="#" data-bind="if: answerBodyMore(), event: {click: answerShowMore}">
                        <span class="more-text" data-bind="attr: { title: `Show ${answerMoreShown() ? 'Less' : 'More'}` }, text: `${answerMoreShown() ? '[Less]' : '[More]'}`"></span>
                      </a>
                    </p>

                  </div>
                  <div>
                    <span class="when" data-bind=" text: ` &ndash; ${howLong}`"></span>
                  </div>
                </div>
              </div>
              <div data-bind="if: loadedAnswers() < totalAnswers() && loadedAnswers() > 0">
                <div class="view-more-box">
                  <a href="#" data-bind="event: { click: loadAnswers.bind($data, true)  }, text: 'view more answers'" class="view-more view-more-big"></a>
                  <span class="track track-big" data-bind="text: answersTrack()"></span>
                </div>          
              </div>
              <span data-bind="if: loadedAnswers() > 20 && loadedAnswers() == totalAnswers()">
                <span class="load-more-finished" data-bind="text: 'No more answers to show'"></span>
              </span>
            </div>
            <!-- End of Answers -->

            <!-- Following -->
            <div data-bind="if: !followingHidden()">
              <h3 data-bind="text: followingHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-followers" data-bind="foreach: following">
                <div data-bind="attr: {class: 'following-preview preview'}">
                  <a data-bind="attr: {href: `user.php?id=${user_id}`}">
                    <img data-bind="attr: {src:`images/${profile_image}`, alt: `${username}'s profile image`, class: 'follow-preview-image'}">
                  </a>
                  <div data-bind="attr: {class: 'preview-details'}">
                    <a data-bind="attr: {class: 'user-preview-name detail', href: `user.php?id=${user_id}`}, text: username"></a>
                    <a data-bind="attr: { href: `country.php?id=${country_id}`, class: 'user-preview-country' }, text: country_name"></a>
                    <span data-bind="attr: { class: 'user-preview-score', title: 'User\'s Score' }, text: user_score"></span>
                    <ul data-bind="attr: { class: 'follow-follow-details'}">
                      <li data-bind="text: `${following} Following`"></li>
                      <li data-bind="attr: { class: 'list-divider' }"></li>
                      <li data-bind="text: `${followers} Follower${followers > 1 ? 's' : ''}`"></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div data-bind="if: loadedFollowing() < totalFollowing() && loadedFollowing() > 0">
                <div class="view-more-box">
                  <a href="#" data-bind="event: { click: loadFollowing.bind($data, true)  }, text: 'view more'" class="view-more view-more-big"></a>
                  <span class="track track-big" data-bind="text: followingTrack()"></span>
                </div>          
              </div>
              <span data-bind="if: loadedFollowing() > 20 && loadedFollowing() == totalFollowing()">
                <span class="load-more-finished" data-bind="text: 'No more following to show'"></span>
              </span>
            </div>
            <!-- End of Following -->

            <!-- Followers -->
            <div data-bind="if: !followersHidden()">
              <h3 data-bind="text: followersHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-followers" data-bind="foreach: followers">
                <div data-bind="attr: {class: 'follow-preview preview'}">
                  <a data-bind="attr: {href: `user.php?id=${user_id}`}">
                    <img data-bind="attr: {src:`images/${profile_image}`, alt: `${username}'s profile image`, class: 'follow-preview-image'}">
                  </a>
                  <div data-bind="attr: {class: 'preview-details'}">
                    <a data-bind="attr: {class: 'user-preview-name detail', href: `user.php?id=${user_id}`}, text: username"></a>
                    <a data-bind="attr: { href: `country.php?id=${country_id}`, class: 'user-preview-country' }, text: country_name"></a>
                    <span data-bind="attr: { class: 'user-preview-score', title: 'User\'s Score' }, text: user_score"></span>
                    <ul data-bind="attr: { class: 'follow-follow-details'}">
                      <li data-bind="text: `${following} Following`"></li>
                      <li data-bind="attr: { class: 'list-divider' }"></li>
                      <li data-bind="text: `${followers} Follower${followers > 1 ? 's' : ''}`"></li>
                    </ul>
                  </div>
                </div>
              </div>
              <div data-bind="if: loadedFollowers() < totalFollowers() && loadedFollowers() > 0">
                <div class="view-more-box">
                  <a href="#" data-bind="event: { click: loadFollowers.bind($data, true)  }, text: 'view more'" class="view-more view-more-big"></a>
                  <span class="track track-big" data-bind="text: followersTrack()"></span>
                </div>          
              </div>
              <span data-bind="if: loadedFollowers() > 20 && loadedFollowers() == totalFollowers()">
                <span class="load-more-finished" data-bind="text: 'No more followers to show'"></span>
              </span>
            </div>
            <!-- End of Followers -->

          </div>
        </div>
      </div>
    </div>

    <?php if($is_owner) { ?>

    <!-- Username Edit Modal -->

    <div class="modal fade" id="editUsernameModal" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <span data-bind="html: editTextIcon()" class="icon-modal-header"></span>
              <span>Username</span>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div>
              <span class="edit-username-error" data-bind="text: editUsernameError"></span>
              <input type="text" class="form-control" placeholder="Username" data-bind="value: usernameEditInputText, valueUpdate:['afterkeydown','propertychange','input']">
              <span class="edit-count-box">
                <span class="edit-count" data-bind="css: { over: usernameEditInputTextExceeded() == true }, text: usernameEditInputTextCount"></span>
              </span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-primary" id="saveUsernameBtn" data-bind="event: { click: saveUsername }, attr: { disabled: (saveUsernameBtnDisabled() == true || saveUsernameLoading() == true) }, text: `${saveUsernameLoading() == true ? 'Saving...' : 'Save' }`, css: { italic: saveUsernameLoading() == true }"></button>
          </div>
        </div>
      </div>
    </div>

    <!-- End of Usename Edit Modal -->

    <!-- Info Edit Modal -->

    <div class="modal fade" id="editInfoModal" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <span data-bind="html: editTextIcon()" class="icon-modal-header"></span>
              <span>Info</span>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div>
              <div contenteditable="true" id="profileInfoText" placeholder="Info" onpaste="updateInfoCount()" onkeyup="updateInfoCount()"></div>
              <span class="edit-count-box">
                <span class="edit-count" id="infoCount"></span>
              </span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-primary" id="saveInfoBtn" data-bind="event: { click: saveInfo }">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- End of Info Edit Modal -->

    <!-- Country Edit Modal -->

    <div class="modal fade" id="editCountryModal" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <span data-bind="html: editTextIcon()" class="icon-modal-header"></span>
              <span>Change Country</span>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div>
              <p class="country-change-alert">
                <span>
                  <i class="fas fa-exclamation-circle"></i>
                  <span class="warning">Warning:</span>
                  <span>You can only change your country once in 90 days.</span>
                </span>
              </p>
              <select id="editCountryDDL" data-bind="options: countries, optionsText: 'tag_name', optionsValue: 'tag_id', value: selectedCountryID, event: { change: editCountryDDLChange }, attr: { class: 'form-control change-country-ddl', disabled: (countryChangeAllowed() == 0) }"></select>
              <p class="country-change-alert" data-bind="if: lastCountryChangeDate() != ''">
                <span class="change-notice">
                  <span data-bind="if: countryChangeAllowed() == 0">
                    <span data-bind="text: 'You cannot change your country at this moment.'"></span>                
                  </span>
                  <span data-bind="if: lastCountryChangeDate() != '', attr: { class: 'change-notice-date'}">
                    <span data-bind="text: 'Your last country change was on'"></span>
                    <strong><span data-bind="text: `${lastCountryChangeDate()}`"></span></strong>
                  </span>
                </span>
              </p>

              <p data-bind="if: countryChangeAllowed() == 1">
                <span class="copy-country-box">
                  <input type="text" class="form-control" placeholder="Type in country name" data-bind="value: countryNameEditInputText, valueUpdate:['afterkeydown','propertychange','input']">
                  <span class="copy-country-icon" data-bind="event: { click: copySelectedCountry }">
                    <i class="far fa-copy copy-country-icon"></i>
                  </span>
                </span>
              </p>

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-primary" id="saveCountryBtn" data-bind="event: { click: saveCountry }, attr: { disabled: (user_country_id() == selectedCountryID() || saveCountryLoading() == true || countryChangeAllowed() == false || countryNameMismatch() == true) }, text: `${saveCountryLoading() == true ? 'Saving...' : 'Save' }`, css: { italic: saveCountryLoading() == true }">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- End of Country Edit Modal -->

    <!-- Country Edit Image -->

    <div class="modal fade" id="editProfileImageModal" role="dialog">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <span data-bind="html: editTextIcon()" class="icon-modal-header"></span>
              <span>Change Profile Image</span>
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div id="cropImageWrapper">
              <div id="cropImage"></div>
            </div>
            <input class="edit-profile-img-input" type="file" accept="image/png, image/jpeg, image/gif" data-bind="event: { change: readURL }">
            <div class="crop-image-controls">
              <button class="btn btn-sm btn-info rotate-btn">Rotate</button>
              <button class="btn btn-sm btn-danger delete-btn">Delete</button>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-primary" id="saveCountryBtn" data-bind="event: { click: uploadProfileImage }, attr: { disabled: (uploadImageLoading() == true ||  uploadImageEmpty() == true) }, text: `${uploadImageLoading() == true ? 'Saving...' : 'Save' }`, css: { italic: uploadImageLoading() == true }">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- End of Country Edit Image -->

  <?php } ?>
  <?php
    if($is_owner) {
  ?>
  <script type="text/javascript" src="javascript/croppie.min.js"></script>
  <link rel="stylesheet" href="css/croppie.css" />
  <?php
    }
  ?>
    
    <script type="text/javascript">
      <?php
        $payload = array(
          'total_questions' => $question_count,
          'total_answers' => $answer_count,
          'total_replies' => $reply_count,
          'total_followers' => $followers_count,
          'total_following' => $following_count,
          'profile_id' => $user_id,
          'show_follow' => $show_follow,
          'is_following' => $is_following,
          'is_owner' => $is_owner,
          'user_country_name' => $user_country_name,
          'user_country_id' => $user_country_id,
          'info' => ($info == null) ? '' : $info,
          'username' => $username,
          'profile_image' => $profile_image
        );
        echo "var json_payload = " . json_encode($payload) . ";";
      ?>

      <?php
        if($is_owner) {
      ?>
        function updateInfoCount() {
          let $input = $('#profileInfoText');
          //get the count of the characters
          let len = $input.text().trim().length;
          let countStr = `${len}/256`;
          $('#infoCount').text(countStr);
          if(len == 0) {
            $input.addClass('dim-placehodler');
          } else {
            $input.removeClass('dim-placehodler');
          }
          if(len > 256) {
            $('#infoCount').addClass('over');
            $('#saveInfoBtn').attr('disabled', true);
          } else {
            $('#infoCount').removeClass('over');
            $('#saveInfoBtn').removeAttr('disabled');
          }
        }

      <?php
        }
      ?>

      function answerIcon() {
        return '<i class="fas fa-comments answer-icon" title="This is an answer."></i>';
      }
      function imageIcon() {
        return '<i class="fas fa-images images-icon"></i>';
      }
      function editTextIcon() {
        return "<i class='far fa-edit edit-profile-icon'></i>";
      }

      //function to decode the characters and print them as text
      function htmlDecode( html ) {
        let a = document.createElement( 'a' ); a.innerHTML = html;
        return a.textContent;
      };

      function QuestionModel(data) {
        var self = this;
        self.questionHeading = htmlDecode(data.question_heading);
        self.questionBody = htmlDecode(data.question_body);
        self.questionTags = data.question_tags;
        self.questionScore = data.question_score;
        self.questionLikes = data.question_likes_count;
        self.questionDislikes = data.question_dislikes_count;
        self.questionID = data.question_id;
        self.howLong = data.how_long;
        self.questionDate = data.question_date;
        self.questionUserName = data.question_user_name;
        self.questionUserID = data.question_user_id;
        self.answerCount = data.question_answers_count;
        self.hasImage = data.question_has_image;
        self.questionBodyIsMore = ko.observable(false);
        self.questionBodyMore = ko.observable('');
        self.questionMoreShown = ko.observable(false);
        self.questionShowMore = function() {
          self.questionMoreShown(!self.questionMoreShown());
        };
        self.questionBodyStart = function(str) {
          var newStr = self.questionBody;
          if(str.length > 210) {
            self.questionBodyIsMore(true);
            //get the the first 197 characters
            newStr = str.substring(0, 197);
            //save the remainder
            self.questionBodyMore(str.substring(197))
          } 
          return newStr;
        }
        self.tags = ko.observableArray(data.tags);
      }

      function AnswerModel(data) {
        var self = this;
        self.questionHeading = htmlDecode(data.question_heading);
        self.answerBody = htmlDecode(data.answer_body);
        self.questionTags = data.question_tags;
        self.answerScore = data.answer_score;
        self.answerLikes = data.answer_likes_count;
        self.answerDislikes = data.answer_dislikes_count;
        self.answerID = data.answer_id;
        self.userID = data.user_id;
        self.questionID = data.question_id;
        self.howLong = data.how_long;
        self.answerDate = data.answer_date;
        self.replyCount = data.replies_count;
        self.hasImage = data.question_has_image;
        self.answerBodyIsMore = ko.observable(false);
        self.answerBodyMore = ko.observable('');
        self.answerMoreShown = ko.observable(false);
        self.answerShowMore = function() {
          self.answerMoreShown(!self.answerMoreShown());
        };
        self.answerBodyStart = function(str) {
          var newStr = self.answerBody;
          if(str.length > 210) {
            self.answerBodyIsMore(true);
            //get the the first 197 characters
            newStr = str.substring(0, 197);
            //save the remainder
            self.answerBodyMore(str.substring(197))
          } 
          return newStr;
        }
        self.tags = ko.observableArray(data.tags);
      }

      function FollowingModel(data) {
        var self = this;

        self.user_id = data.user_id;
        self.username = data.username;
        self.profile_image = data.profile_image;
        self.country_id = data.country_id;
        self.country_name = data.country_name;
        self.user_score = data.user_score;
        self.followers = data.followers;
        self.following = data.following;
      }

      function FollowersModel(data) {
        var self = this;

        self.user_id = data.user_id;
        self.username = data.username;
        self.profile_image = data.profile_image;
        self.country_id = data.country_id;
        self.country_name = data.country_name;
        self.user_score = data.user_score;
        self.followers = data.followers;
        self.following = data.following;
      }

      function AppViewModel() {
        var self = this;
        self.loading = ko.observable(false);
        self.addUserIconHTML = '<i class="fas fa-user-plus"></i>';
        self.checkIconHTML = '<i class="fas fa-check"></i>';
        self.profileID = json_payload.profile_id;
        self.show_follow = json_payload.show_follow;
        self.is_following = ko.observable(json_payload.is_following);
        self.profileImage = ko.observable(json_payload.profile_image);
        self.followLoading = ko.observable(false);
        self.followersCountChanged = ko.observable(false);
        self.followUser = function(param) {
          //validate follow parameter
          let pattern = /^[0|1]$/;
          if(pattern.test(param)) {
            self.followLoading(true);
            let payload = {follow: param, following_id: self.profileID};
            $.get('follow_user.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  self.is_following(data.message.is_following);
                  self.totalFollowers(data.message.total_followers);
                  if(self.is_following() == true) {
                    displayFeedback('Following');
                  }
                  self.followLoading(false);
                  //reset the followers list
                  self.followersFetched(false);
                  self.followersCountChanged(true);
                }
              } else {
                self.followLoading(false);
              }
            }, "json"
          );
          }
        }
        self.info = ko.observable(json_payload.info);
        self.is_owner = json_payload.is_owner;
        self.user_country_id = ko.observable(json_payload.user_country_id);
        self.user_country_name = ko.observable(json_payload.user_country_name);
        self.username = ko.observable(json_payload.username);

        <?php
          if($is_owner) {
            $c = array();
            $query = "SELECT t.tag_name, t.tag_id 
            FROM tag AS t 
            JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id 
            WHERE tt.name = 'forum' 
            ORDER BY t.tag_name ASC";
            $r = mysqli_query($dbc, $query);
            $c_ = array();
            while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
              $c = array(
                'tag_name' => $row['tag_name'],
                'tag_id' => $row['tag_id']
              );
              array_push($c_, $c);
            }

            $a = array(
              'countries' => $c_
            );
            echo "var json_all_countries_payload = " . json_encode($a) . ";";
            unset($a);
        ?>

          self.showEditMode = ko.observable(false);
          self.toggleEditMode = function(koObj, e) {
            let is_checked = e.currentTarget.checked;
            self.showEditMode(is_checked);
          };

          /* CHANGE COUNTRY */
          self.selectedCountryID = ko.observable(self.user_country_id());
          self.countryNameEditInputText = ko.observable('');
          $('body #editCountryModal').on('shown.bs.modal', function (e) {
            self.selectedCountryID(self.user_country_id());
            self.countryNameEditInputText('');
          });
          self.copySelectedCountry = function (koObj, e) {
            const el = document.createElement('textarea');
            el.value = $("#editCountryDDL option:selected").text();
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
          }
          self.editCountryTextInput = ko.observable(self.user_country_name().toLowerCase().trim());
          self.editCountryDDLChange = function (koObj, e) {
            let sel = e.currentTarget;
            self.editCountryTextInput(sel.options[sel.selectedIndex].text.toLowerCase().trim());
          }
          self.countryNameMismatch = ko.observable(true);
          self.countryNameMatch = ko.computed(function() {
            if(self.countryNameEditInputText().toLowerCase().trim() == self.editCountryTextInput()) {
              self.countryNameMismatch(false)
            } else {
              self.countryNameMismatch(true);
            }
          });
          self.countries = json_all_countries_payload.countries;
          self.lastCountryChangeDate = ko.observable(<?php echo "'$last_country_change_date'"; ?>);
          self.countryChangeAllowed = ko.observable(<?php echo $is_country_change_allowed; ?>);
          self.saveCountryLoading = ko.observable(false);
          self.saveCountry = function (koObj, e) {
            if(self.saveCountryLoading() == true || e.currentTarget.hasAttribute('disabled')) { return; }
            self.saveCountryLoading(true);

            if(self.user_country_id() == self.selectedCountryID()) {
              return;
            }
            let payload = { user_id: self.profileID, country_id: self.selectedCountryID()};
            $.get('edit_user_country.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    let message = data.message;
                    self.user_country_id(message.country_id);
                    self.user_country_name(message.country_name);
                    self.countryChangeAllowed(message.is_country_change_allowed);
                    self.lastCountryChangeDate(message.last_country_change_date);
                  }
                  self.saveCountryLoading(false);
                  //close the modal
                  $('body #editCountryModal').modal('hide');
                } else {
                  self.saveCountryLoading(false);
                }
              }, "json"
            );
          }

          /* CHANGE PROFILE IMAGE */
          self.uploadImageLoading = ko.observable(false);
          self.uploadImageEmpty = ko.observable(true);
          $('body #editProfileImageModal').on('shown.bs.modal', function (e) {
            self.clearCropImg(true);
          });
          self.clearCropImg = function(clearInput = false) {
            if(clearInput) {
              $('#editProfileImageModal .edit-profile-img-input').val('');
            }
            $('#cropImage').croppie('destroy');
            self.uploadImageEmpty(true);
          }
          self.readURL = function (koObj, e) {
            let input = e.currentTarget;
            let imgURL = '';
            if (input.files && input.files[0]) {
              let imageType = input.files[0].type;
              if(!(imageType === 'image/jpeg' || imageType === 'image/png' || imageType === 'image/gif')) {
                //wrong file type
                alert('Wrong Image type.\nYou can only upload a jpeg, png or gif image.');
                $(input).val('');
                return;
              }

              let reader = new FileReader();

              reader.onload = function (e) {
                self.clearCropImg();
                imgURL = e.target.result;
                $('#cropImage').croppie({
                  url: imgURL,
                  viewport: {
                    width: 120,
                    height: 120,
                    type: 'square'
                  },
                  enableOrientation: true
                });
                
                $('#editProfileImageModal .rotate-btn').on('click', function() {
                  let o = -90;
                  $('#cropImage').croppie('rotate', o);
                }); 
                
                $('#editProfileImageModal .delete-btn').on('click', function() {
                  self.clearCropImg(true);
                });
                self.uploadImageEmpty(false);
              };
              reader.readAsDataURL(input.files[0]);
            }
          }


          self.uploadProfileImage = function() {
            if(self.uploadImageEmpty() == true) {
              return;
            }
            self.uploadImageLoading(true);
            $('#cropImage').croppie('result', {
              type: 'canvas',
              size: 'viewport',
              circle: false
            }).then(function (resp) {

              let payload = { imagebase64: resp };
              $.post('upload_profile_image.php', payload, function(data, status) {
                if(status == "success") {
                  var dataObj = JSON.parse(data);
                  if(dataObj.isErr === false) {
                    let newImage = dataObj.message.image_name;
                    self.profileImage(newImage);
                    $('#profileIconPic').attr('src', `images/${newImage}`);
                  }
                  self.uploadImageLoading(false);
                  //close the modal
                  $('body #editProfileImageModal').modal('hide');
                } else {
                  self.uploadImageLoading(false);
                }
              });
            });
          }


          /* EDIT INFO */
          $('body #editInfoModal').on('shown.bs.modal', function (e) {
            $('#profileInfoText').text(self.info());
            updateInfoCount();
          });

          self.saveInfoLoading = ko.observable(false);
          self.saveInfo = function (koObj, e) {
            if(self.saveInfoLoading() == true || e.currentTarget.hasAttribute('disabled')) { return; }
            self.saveInfoLoading(true);
            $('#saveInfoBtn').attr('disabled', true);

            let inputText = $('#profileInfoText')[0].innerText.trim();
            //get the count of the characters
            let len = inputText.length;
            if(len > 256) {
              $('#saveInfoBtn').removeAttr('disabled');
              return;
            }

            $('#saveInfoBtn').text('Saving...');
            $('#saveInfoBtn').addClass('italic');

            let payload = { user_id: self.profileID, info: inputText};
            $.get('edit_info.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    self.info(data.message.info);
                  }
                  self.saveInfoLoading(false);
                  //close the modal
                  $('body #editInfoModal').modal('hide');
                } else {
                  self.saveInfoLoading(false);
                }
                $('#saveInfoBtn').removeAttr('disabled');
                $('#saveInfoBtn').text('Save');
                $('#saveInfoBtn').removeClass('italic');
              }, "json"
            );
          }

          /* EDIT USERNAME */
          self.saveUsernameBtnDisabled = ko.observable(false);
          self.usernameEditInputText = ko.observable(json_payload.username);
          self.usernameEditInputTextExceeded = ko.observable(false);
          self.editUsernameError = ko.observable('');
          self.usernameEditInputTextCount = ko.computed(function() {
            let len = self.usernameEditInputText().trim().length;
            if(len >= 3 && len <= 25) {
              self.saveUsernameBtnDisabled(false);
            } else {
              self.saveUsernameBtnDisabled(true);
            }
            if(len > 25) {
              self.usernameEditInputTextExceeded(true);
            } else {
              self.usernameEditInputTextExceeded(false)
            }

            self.editUsernameError('');

            return `${len}/25`;
            
          });

          $('body #editUsernameModal').on('shown.bs.modal', function (e) {
            self.usernameEditInputText(self.username());
            self.editUsernameError('');
          });

          self.saveUsernameLoading = ko.observable(false);
          self.saveUsername = function (koObj, e) {
            let inputText = self.usernameEditInputText().trim();
            let error = "";


            if(self.saveUsernameLoading() == true || e.currentTarget.hasAttribute('disabled')) { return; }
            self.saveUsernameLoading(true);

            //get the count of the characters
            let len = inputText.length;
            if(len < 3 || len > 25) {
              return;
            }

            //validate the username
            let username_patt = /^([a-zA-Z][a-zA-Z0-9_]{2,24})$/;
            if(username_patt.test(inputText) === false) {
              self.editUsernameError('Username must start with an alphabet and must contain numbers and alphabets. Underscore(_) is optional.');
              self.saveUsernameLoading(false);
              return;
            }
            let payload = { user_id: self.profileID, username: inputText};
            $.get('edit_username.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    self.username(data.message.username);
                  }
                  self.saveUsernameLoading(false);
                  //change the page title
                  document.title = `User ${self.username()} - ForaShare`;
                  //close the modal
                  $('body #editUsernameModal').modal('hide');
                } else {
                  self.saveUsernameLoading(false);
                }
              }, "json"
            );
          }

        <?php
          }
        ?>

        /* Following */
        self.totalFollowing = ko.observable(json_payload.total_following);
        self.following = ko.observableArray([]);
        self.followingFetched = ko.observable(false);
        self.loadedFollowing = ko.observable(self.following().length);
        self.followingHead = ko.computed(function(){
          return `${self.totalFollowing()} Following`;
        });
        self.followingTrack = ko.computed(function(){
          return `${self.loadedFollowing()} of ${self.totalFollowing()}`;
        });
        self.followingHidden = ko.observable(true);
        self.followingLoading = ko.observable(false);
        self.showFollowing = function() {
          if(self.followingFetched()){
            self.toggleLoading('following', false);
          } else {
            self.loadFollowing();
          }
        };
        self.followingReset = function(data) {
          var mappedFollowing = data.users.map(user => new FollowingModel(user));
          //update the observable array
          self.following.push(...mappedFollowing);
          self.loadedFollowing(self.following().length);
          self.totalFollowing(data.total_following);
        };
        self.loadFollowing = function(){
          self.toggleLoading('following', true);
          let start = 0;
          if(self.loadedFollowing() > 0) {
            start = self.loadedFollowing();
          } 
          let payload = { user_id: self.profileID, start: start};
          $.get('get_following.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //flag to indicate the questions have been fetched
                  self.followingFetched(true);
                  self.toggleLoading('following', false);
                  self.followingReset(data.message);
                }
              } else {
                self.toggleLoading('following', false);
              }
            }, "json"
          );
        };

        /* Followers */
        self.totalFollowers = ko.observable(json_payload.total_followers);
        self.followers = ko.observableArray([]);
        self.followersFetched = ko.observable(false);
        self.loadedFollowers = ko.observable(self.followers().length);
        self.followersHead = ko.computed(function(){
          return `${self.totalFollowers()} Follower${self.totalFollowers() > 1 ? 's' : ''}`;
        });
        self.followersTrack = ko.computed(function(){
          return `${self.loadedFollowers()} of ${self.totalFollowers()}`;
        });
        self.followersHidden = ko.observable(true);
        self.followersLoading = ko.observable(false);
        self.showFollowers = function() {
          if(self.followersFetched()){
            self.toggleLoading('follower', false);
          } else {
            self.loadFollowers();
          }
        };
        self.followersReset = function(data) {
          var mappedFollowers = data.users.map(user => new FollowersModel(user));
          //update the observable array
          if(self.followersCountChanged()) {
            self.followers(mappedFollowers);
            self.followersCountChanged(false);
          } else {
            self.followers.push(...mappedFollowers);
          }
          self.loadedFollowers(self.following().length);
          self.totalFollowers(data.total_followers);
        };
        self.loadFollowers = function(){
          self.toggleLoading('following', true);
          var start = 0;
          if(self.loadedFollowers() > 0 && self.followersFetched() ==  true) {
            start = self.loadedFollowers();
          } 
          var payload = { user_id: self.profileID, start: start};
          $.get('get_followers.php', payload, 
            function(data, status) {
              if(status == "success") {
                console.log(data);
                if(data.isErr == false) {
                  //flag to indicate the followers have been fetched
                  self.followersFetched(true);
                  self.toggleLoading('follower', false);
                  self.followersReset(data.message);
                }
              } else {
                self.toggleLoading('follower', false);
              }
            }, "json"
          );
        };

        /* Questions */
        self.questions = ko.observableArray([]);
        self.questionsFetched = ko.observable(false);
        self.loadedQuestions = ko.observable(self.questions().length);
        self.totalQuestions = ko.observable(json_payload.total_questions);
        self.questionHead = ko.computed(function(){
          return `${self.totalQuestions()} Question${self.totalQuestions() > 1 ? 's' : ''}`;
        });
        self.questionsTrack = ko.computed(function(){
          return `${self.loadedQuestions()} of ${self.totalQuestions()}`;
        });
        self.questionsHidden = ko.observable(true);
        self.questionsLoading = ko.observable(false);
        self.showQuestions = function() {
          if(self.questionsFetched){
            self.toggleLoading('question', false);
          } else {
            self.loadQuestions();
          }
        };

        /* Answers */
        self.answers = ko.observableArray([]);
        self.answersFetched = ko.observable(false);
        self.loadedAnswers = ko.observable(self.answers().length);
        self.totalAnswers = ko.observable(json_payload.total_answers);
        self.answersTrack = ko.computed(function(){
          return `${self.loadedAnswers()} of ${self.totalAnswers()}`;
        });
        self.answersHidden = ko.observable(true);
        self.answersLoading = ko.observable(true);
        self.answerHead = ko.computed(function(){
          return `${self.totalAnswers()} Answer${self.totalAnswers() > 1 ? 's' : ''}`;
        });
        self.answersHead = function() {
          return `${self.totalQuestions()} ${self.totalQuestions() > 1 ? 'Answers' : 'Answer'}`;
        };
        self.showAnswers = function() {
          if(self.answersFetched()){
            self.toggleLoading('answer', false);
          } else {
            self.loadAnswers();
          }
        };


        self.questionReset = function(data) {
          //convert each question to a question model and store in an array
          var mappedQuestions = data.questions.map(question => new QuestionModel(question));
          //update the observable array
          self.questions.push(...mappedQuestions);
          self.totalQuestions(data.total_questions);
        };
        self.loadQuestions = function(){
          self.toggleLoading('question', true);
          var start = 0;
          if(self.loadedQuestions() > 0) {
            start = self.loadedQuestions();
          } 
          var payload = { profile_id: self.profileID, start: start};
          $.get('profile_questions.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //flag to indicate the questions have been fetched
                  self.questionsFetched(true);
                  self.toggleLoading('question', false);
                  self.questionReset(data.message);
                }
              } else {
                self.toggleLoading('question', false);
              }
            }, "json"
          );
        };


        self.answerReset = function(data) {
          //convert each question to a question model and store in an array
          var mappedAnswers = data.answers.map(answer => new AnswerModel(answer));
          //update the observable array
          self.answers.push(...mappedAnswers);
          self.totalAnswers(data.total_answers);
          self.loadedAnswers(self.answers().length);
        };

        self.loadAnswers = function(viewMore = false){
          //if the view more  is clicked
          self.toggleLoading('answer', true, viewMore);
          var start = 0;
          if(self.loadedAnswers() > 0) {
            start = self.loadedAnswers();
          } 
          var payload = { profile_id: self.profileID, start: start};
          $.get('profile_answers.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //flag to indicate the answers have been fetched
                  self.answersFetched(true);
                  self.toggleLoading('answer', false);
                  self.answerReset(data.message);
                }
              } else {
                self.toggleLoading('answer', false);
              }
            }, "json"
          );
        };

        self.toggleLoading = function(type, is_loading, viewMore = false){
          self.loading(is_loading);
          switch (type) {
            case 'question':
              if(!viewMore) {
                self.questionsHidden(is_loading);
              }
              self.questionsLoading(is_loading);
              break;
            case 'answer':
              if(!viewMore) {
                self.answersHidden(is_loading);
              }
              self.answersLoading(is_loading);
              break;
            case 'following':
              if(!viewMore) {
                self.followingHidden(is_loading);
              }
              self.followingHidden(is_loading);
              break;
            case 'follower':
              if(!viewMore) {
                self.followersHidden(is_loading);
              }
              self.followersHidden(is_loading);
              break;
          }

          //if a block is being displayed, hide the rest
          if(is_loading == false) {
            switch(type) {
              case 'question':
                self.answersHidden(true);
                self.answersLoading(false);
                self.followingHidden(true);
                self.followingLoading(false);
                self.followersHidden(true);
                self.followersLoading(false);
                break;
              case 'answer':
                self.questionsHidden(true);
                self.questionsLoading(false);
                self.followingHidden(true);
                self.followingLoading(false);
                self.followersHidden(true);
                self.followersLoading(false);
                break;
              case 'following':
                self.questionsHidden(true);
                self.questionsLoading(false);
                self.answersHidden(true);
                self.answersLoading(false);
                self.followersHidden(true);
                self.followersLoading(false);
                break;
              case 'follower':
                self.questionsHidden(true);
                self.questionsLoading(false);
                self.answersHidden(true);
                self.answersLoading(false);
                self.followingHidden(true);
                self.followingLoading(false);
                break;
            }
          }
        };
        //Onload load the questions
        $(document).ready(function() {
          self.loadQuestions();
        });
      }
      var model = new AppViewModel();
      ko.applyBindings(model, document.getElementById("main_content"));


      function displayFeedback(msg) {
        //display feedback and remove any feedback span present
        $('.questionive-feedback').remove();
        var $feedback = $('body').append('<span class="positive-feedback"></span>');
        $('.positive-feedback').text(msg);
        setTimeout(function(){
          $('.positive-feedback').fadeOut(function(){
            $('.positive-feedback').remove();
          });
        }, 2000);
      }
    </script>
    <?php
      endif;
    ?>
  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>