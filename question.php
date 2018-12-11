<?php
  /*
    needed query parameters for this page for now
    id - id of the question
  */

	require_once('includes/generic_includes.php');

  $is_404 = false;
  $page_heading = "Oops! Question not found :(";
  //if id query parameter doesn't exist, redirect to questions page
  if(isset($_GET['id']) && !empty($_GET['id'])) {
    $question_id = trim($_GET['id']);

    //if id is numeric and greater than 0
    if (is_numeric($question_id) && filter_var($question_id, FILTER_VALIDATE_INT) != false && intval($question_id) > 0) {
      $question_id = intval($question_id);
      //check if question exists
      $stmt = $dbc->prepare("SELECT COUNT(question_id) AS count, question_heading FROM question WHERE question_id = ?");
      $stmt->bind_param("i", $question_id);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($count, $heading);
      $stmt->fetch();
      $stmt->free_result();
      $stmt->close();
      //if question does not exists
      if(intval($count) != 1) {
        $is_404 = true;
      } else {
        $page_heading = $heading;

        $query_string = "?id=$question_id";
      }
    } else {
      $is_404 = true;
    }

  } else {
    //if id query parameter does not exist
    header("Location: questions.php");
    exit(); // Quit the script.
  }
  $page_title = $page_heading . " &ndash; ForaShare";
  $page = QUESTION;
  require('includes/header.php');
?>
<!-- CONTAINER -->
<div class="container-fluid content-container" id="main_content">
  <?php
    require('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="create-link-box row">
      <a href="ask.php" class="create-link">Ask Question</a>
    </div>
    <?php
      if($is_404):
        $page_err_msg = "Question not found.";
        require('includes/err404.php');

      else:
        //variable to determine if the user can add a answer to the question
        $eligible_to_answer = false;

        $answer_focus_id = null;
        $answer_focus_user_id = null;
        //check if the page should display a particular answer
        if (isset($_GET['answer']) && isset($_GET['uid'])) {
          $temp_answer = $_GET['answer'];
          $temp_user_id = $_GET['uid'];
          if((filter_var($temp_answer, FILTER_VALIDATE_INT) === 0 || filter_var($temp_answer, FILTER_VALIDATE_INT)) && (filter_var($temp_user_id, FILTER_VALIDATE_INT) === 0 || filter_var($temp_user_id, FILTER_VALIDATE_INT))) {
            $answer_focus_id = intval($_GET['answer']);
            $answer_focus_user_id = intval($_GET['uid']);
          }
        }
    ?>
    <div class="question-outer-container">

      <!-- Question Container -->
      <div class="question" data-question-id="<?php echo $question_id; ?>">
        <!-- Question Header -->
        <div class="question-header">
          <h1 class="question-heading page-title">
            <span><?php echo $heading; ?>
              <span title="Score" class="question-score" data-bind="text: questionScore, css: {positive: questionScore() > 0 }"></span>  
            </span>
          </h1>
          <div class="question-tags">
            <i class="fas fa-tag question-tag-icon"></i>
            <?php
              $question_tags = array();
              //load the tags sorted by the forum first, followed by default tags then custom tags 
              $stmt = $dbc->prepare("SELECT t.tag_id, t.tag_name, tt.name FROM tag_associations AS ta JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE ta.question_id = ? ORDER BY FIELD (tt.name, 'forum', 'default_tag', 'custom_tag')");
              $stmt->bind_param("d", $question_id);
              $stmt->execute();
              $stmt->store_result();
              $stmt->bind_result($temp_tag_id, $temp_tag_name, $temp_tag_type_name);
              while ($stmt->fetch()) {
                array_push($question_tags, $temp_tag_id);
                $css = '';
                if($temp_tag_type_name == 'forum'){
                  $css .= 'forum-tag';
                  //get the forum id for the question
                  $question_forum_id = $temp_tag_id;
                } else if($temp_tag_type_name == 'custom_tag') {
                  $css .= 'custom-tag';
                }
                $temp_page = ($temp_tag_type_name == 'forum') ? 'country' : 'tag';
                echo "<a href='$temp_page.php?id=$temp_tag_id' class='question-tag $css'>" . strtolower($temp_tag_name) . "</a>";
                unset($temp_page);
              }
              $stmt->free_result();
              $stmt->close();
              unset($stmt);
            ?>
          </div>
        </div>

        <!-- Question Body -->
        <div class="question-box">
          <?php
            //get question content, attributes(likes/dislikes/answers count) and user who questioned it
            $stmt = $dbc->prepare("SELECT q.question_text, q.question_date, q.likes, q.dislikes, q.points, q.answers, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, q.question_date, NOW()) AS seconds, DATE_FORMAT(q.question_date, '%M %e'), DATE_FORMAT(q.question_date, ' at %h:%i%p'), YEAR(q.question_date), YEAR(NOW()), DAY(q.question_date), DAY(NOW()) FROM question AS q JOIN user AS u ON q.user_id = u.user_id JOIN tag AS t ON u.tag_id = t.tag_id WHERE question_id = ?");
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($question_text, $question_date, $question_likes_count, $question_dislikes_count, $question_score, $question_answers_count, $question_user_id, $username, $user_img, $question_owner_forum, $seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_day, $today);
            $stmt->fetch();
            $stmt->free_result();
            $stmt->close();
            unset($stmt);

          ?>
          <div class="question-main question-section">
            <div class="question-user-icon-box icon-big-box">
              <?php
              //initialize the user view values
                $view_user_id = null;
                $view_user_profile_image = null;
                $view_user_username = null;

                include('includes/how_long.php');
                include('includes/question_action_title.php');
                //print user icon
                echo "<a href='user.php?id=$question_user_id'><img class='question-user-img' alt='$username Profile Image' src='images/". (empty(trim($user_img)) ? 'user_icon.png' : trim($user_img)) ."'/></a>";
                
              ?>
            </div>
            <div class="question-main-content">
              <ul class="question-user-details">
                <li>
                  <?php
                    $title = ( ($loggedin == true && $_SESSION['user_id'] == $question_user_id) ? 'Go to your Profile' : $username.' is a member of '. $question_owner_forum);
                    echo "<a title='$title' href='user.php?id=$question_user_id' class='user-name'>". ucfirst($username) ."</a>";
                  ?>
                </li>
                <li class="list-divider"></li>
                <li><span class="question-time" title="<?php echo $question_date; ?>"><?php echo calculate($seconds, $question_date_open, $question_date_close, $question_year, $current_year, $question_date, $today); ?></span></li>
              </ul>
              <div class="question-details">
                <p class="question-details-text"><?php echo htmlspecialchars($question_text, ENT_QUOTES); ?></p>
                <?php
                  //get the question images
                  $stmt = $dbc->prepare("SELECT image_name FROM question_images WHERE question_id = ?");
                  $stmt->bind_param("i", $question_id);
                  $stmt->execute();
                  $stmt->store_result();
                  if($stmt->num_rows > 0) {
                    echo "<div class='question-image-box'>";
                    $stmt->bind_result($image_name);
                    
                    while($stmt->fetch()) {
                      echo "<img class='min question-image' title='Zoom In' src='images/question/". trim($image_name) ."' alt='Image for Question - $heading'>";
                    }
                    echo "</div>";

                    //unset the variable
                    unset($image_name);
                  }
                  $stmt->free_result();
                  $stmt->close();
                ?>
                <?php
                  //flags to know if the user has previously liked/disliked a question
                  $like_question = false;
                  $dislike_question = false;
                  $is_question_saved = false;

                ?>
                <ul class="question-attributes">
                  <?php
                  if($loggedin) {
                    
                    $view_user_id = $_SESSION['user_id'];

                    $stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
                    $stmt->bind_param("i", $view_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    $stmt->bind_result($user_forum_id);
                    $stmt->fetch();
                    $stmt->free_result();

                    //the user is only eligiblie if he belong to the country of question or he is the owner of the question
                    if($user_forum_id == $question_forum_id || $view_user_id == $question_user_id) {
                      $eligible_to_answer = true;
                    }

                    //check if the view user has saved the question before
                    $stmt = $dbc->prepare("SELECT question_id FROM saved_question WHERE question_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $question_id, $view_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if($stmt->num_rows == 1) {
                      $is_question_saved = true;
                    }
                    $stmt->free_result();

                    //check if the view user has ever liked or disliked the question
                    $stmt = $dbc->prepare("SELECT is_like FROM question_likes WHERE question_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $question_id, $view_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if($stmt->num_rows == 1) {
                      $stmt->bind_result($is_like);
                      $stmt->fetch();

                      if($is_like == 1) {
                        $like_question = true;
                      } else if($is_like == 0) {
                        $dislike_question = true;
                      }
                    }
                    $stmt->free_result();
                    $stmt->close();
                  ?>
                  <li data-action-type="question-like" class="question-action question-like" data-bind="css: { active: questionLiked() == true, loading: questionLikeLoading() }, attr: {title: (viewUserID == questionUserID)? '' : questionLikeTitle(), 'data-content': (viewUserID == questionUserID) ? 'You cannot like your own Question' : '' }, event: { click: (viewUserID == questionUserID) ? popoverLikeDislike : sendLikeDislike.bind($data, 1) }">
                    <i class="far fa-thumbs-up icon" data-bind="css: {hide: questionLikeLoading() }"></i>
                    <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !questionLikeLoading() }"></i>
                    <span class="react-count" data-bind="text: questionLikesCount"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li data-action-type="question-like" class="question-action question-like" data-bind="css: { active: questionDisliked() == true, loading: questionDislikeLoading() }, attr: {title: (viewUserID == questionUserID)? '' : questionDislikeTitle(), 'data-content': (viewUserID == questionUserID) ? 'You cannot dislike your own Question' : '' }, event: { click: (viewUserID == questionUserID) ? popoverLikeDislike : sendLikeDislike.bind($data, 0) }">
                    <i class="far fa-thumbs-down icon" data-bind="css: {hide: questionDislikeLoading() }"></i>
                    <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !questionDislikeLoading() }"></i>
                    <span class="react-count" data-bind="text: questionDislikesCount"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li rel="popover" data-action-type="question-save" class="question-action question-save" data-bind="css: {active: isQuestionSaved() }, event: {click: saveQuestion}, attr: {title: (isQuestionSaved() ? 'Unsave this Question' : 'I like this Question')}">
                    <i class="far fa-bookmark icon"></i>
                  </li>
                <?php
                  unset($is_like);
                  } else {
                ?>
                  <li class="question-action-ineligible">
                    <span class="react-count" data-bind="text: questionLikesCount"></span>
                    <span data-bind="text: ' Like'+(questionLikesCount()>1 ? 's' : '')"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li class="question-action-ineligible">
                    <span class="react-count" data-bind="text: questionDislikesCount"></span>
                    <span data-bind="text: ' Dislike'+(questionDislikesCount()>1 ? 's' : '')"></span>
                  </li>
                <?php
                  }
                ?>
                </ul>

                <?php

                  if($eligible_to_answer) {
                ?>
                <div class="add-answer-trigger-box">
                  <span class="add-answer-trigger" data-bind="css: { active: answerBoxOpen() == true }, event: { click: showAnswerBox }">Add answer</span>
                </div>
                <?php
                  }
                ?>
              </div>
            </div>
          </div>

          <?php
            if($eligible_to_answer) {
          ?>
          <div class="question-add-answer add-answer question-section" data-bind="css: { hide: answerBoxOpen() == false }">
            <div class="question-user-icon-box icon-big-box">
              <?php
                $stmt = $dbc->prepare("SELECT username, profile_image FROM user WHERE user_id = ?");
                $stmt->bind_param("i", $view_user_id);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($view_user_username, $view_user_profile_image);
                $stmt->fetch();
                $stmt->free_result();
                $stmt->close();
                unset($stmt);

                $view_use_has_profile_picture = false;

                ?>
                <a href="user.php?id=<?php echo $view_user_id; ?>" title="You">
                  <?php
                    $view_user_profile_image = ($view_user_profile_image == null || empty($view_user_profile_image)) ? 'user_icon.png' : trim($view_user_profile_image);
                    echo "<img class='question-user-img' alt='$view_user_username' src='images/". $view_user_profile_image ."' />";
                  ?>
                </a>
            </div>
            <div class="add-answer-box">
              <div class="form-group question-input-box">
                <textarea class="form-control question-input answer-input" data-bind="value: answerInputText, valueUpdate:['afterkeydown','propertychange','input'], hasFocus: answerBoxOpen()" rows="6" placeholder="Add an answer..."></textarea>
                <span class="question-input-count-box" data-bind="text: answerInputTextCount, css: { over: answerInputTextExceeded() }"></span>
              </div>
              <div class="form-group question-btn-box">
                <button class="btn clear-btn" data-bind="event: { click: hideAnswerBox } ">Cancel</button>
                <button class="btn answer-btn question-btn" data-bind="event: {click: makeAnswer}, css: { disabled: answerBtnDisabled() }">Answer</button>
              </div>
            </div>
          </div>
          <?php
            } else if($eligible_to_answer == false && $loggedin == true) {
               //if the user is logged in but cannot answer due to being ineligible
          ?>
              <div class="question-eligibility-info-box">
                <div class="info-icon-box">
                  <div class="info-icon-box-inner" title="Show Info" onclick="toggleEligibilityInfo(this)">
                    <i class="fas fa-info-circle info-icon"></i>
                  </div>
                </div>
                <p class="eligibility-info hide">
                  <span class="main-info info-text">You can't add an answer or reply to this question because you don't belong to the country the question was directed to.</span>
                  <span class="sub-info info-text">The only time you can answer or reply to a question if you don't belong to the country the question was directed to is when you are the owner of the question.</span>
                </p>
              </div>
          <?php
            }
          ?>

          <div class="question-answers">
            <div class="question-answers-header">
              <span>Answers</span>
            </div>
            <div class="answer-box">
              <div class="loading-box hide answers-loading" title="Loading..." data-bind="css: { hide: answersLoading() == false }">
                <i class="fas fa-spinner loading-icon"></i>
              </div>
              <div class="no-question-answer" data-bind="if: totalAnswers() != null && totalAnswers() <= 0">
                <p>This Question has no answer yet.</p>
              </div>

              <div class="answer-filter-details row">
                <p class="answer-count-text" data-bind="if: totalAnswers() > 0">
                  <span class="answer-count" data-bind="text: getTotalAnswers()"></span>
                </p>
                <div class="answer-filter" data-bind="if: totalAnswers() > 1">
                  <div class="dropdown filter-order-dropdown" id="answer_order_dropdown">
                    <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="answerSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortAnswerType"></a>
                    <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="answerSortLink" id="answer_sort">
                      <li class="dropdown-item" data-bind="css: { active: sortAnswerType() == 'newest' }, event: { click: (sortAnswerType() != 'newest') ? sortAnswers.bind($data, 'newest') : null }">newest</li>
                      <li class="dropdown-item" data-bind="css: { active: sortAnswerType() == 'oldest' }, event: { click: (sortAnswerType() != 'oldest') ? sortAnswers.bind($data, 'oldest') : null }">oldest</li>
                      <li class="dropdown-item" data-bind="css: { active: sortAnswerType() == 'score' }, event: { click: (sortAnswerType() != 'score') ? sortAnswers.bind($data, 'score') : null }">score</li>
                    </ul>
                  </div>
                </div>
              </div>
              
              

              <div class="answers" data-bind=" foreach: answers">

                <div class="answer" data-bind="css: { answerFocus: ($parent.answerFocusID == id)}">
                  <div data-bind="css: { collapsed: answerHidden() && ($parent.answerFocusID != id), owner: is_owner, focus: replyInputFocused() }" class="answer-wrap question-section">
                    <div class="answer-user-icon-box icon-big-box" data-bind="css: { collapsed: answerHidden() == true, owner: is_owner }">
                      <a data-bind="attr: { href: 'user.php?id=' + user_id, title: user_username }"><img data-bind="attr: {src: 'images/'+user_profile_image }"/></a>
                    </div>
                    <div class="answer-main-content">
                      <div class="answer-controls">
                        <ul class="answer-user-details">
                          <li>
                            <a class="user-name" data-bind="attr: { title: nameTitle(), href: 'user.php?id=' + user_id }, css: {questionOwner: ($parent.questionUserID == user_id), focusUser: ($parent.answerFocusUserID == user_id && $parent.questionUserID != user_id)}, text: user_username"></a>
                          </li>
                          <li class="list-divider"></li>
                          <li>
                            <span class="answer-time" data-bind="attr: { title: date }, text: how_long"></span>
                          </li>
                        </ul>
                        <div class="question-toggler-box">
                          <a href="#" class="question-toggler" data-bind="attr: { title: (answerHidden() == false ? 'collapse' : 'expand')}, css: answerHidden() == false ? 'collapse-icon' : 'expand-icon', event: {click : toggleAnswer }"></a>
                        </div>
                      </div>
                      <div class="answer-body question-body">
                        <div class="answer-details" data-bind="if: answerHidden() == false">
                          <p class="answer-details-text" data-bind="attr: { class: 'answer-details-text' }, text: text">
                          </p>

                          <ul class="answer-attributes">
                          <?php
                            if($eligible_to_answer) {
                          ?>
                            <li class="answer-action answer-like" data-bind="event: { click: (view_user_id == user_id) ? popoverLikeDislike : sendLikeDislike.bind($data,1) }, css: { active: answerLiked() == true, loading: answerLikeLoading() }, attr: { title: (view_user_id == user_id) ? '' : answerLikeTitle(), 'data-content': (view_user_id == user_id) ? 'You cannot like your own Answer' : ''}" data-action-type="answer-like">
                              <i class="far fa-thumbs-up icon" data-bind="css: {hide: answerLikeLoading() }"></i>
                              <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !answerLikeLoading() }"></i>
                              <span class="react-count" data-bind="text: answerLikesCount"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li rel="popover" class="answer-action answer-dislike" data-bind="css: { active: answerDisliked() == true, loading: answerDislikeLoading() }, attr: { title: (view_user_id == user_id) ? '' : answerDislikeTitle(), 'data-content': (view_user_id == user_id) ? 'You cannot dislike your own Answer' : ''}, event: { click: (view_user_id == user_id) ? popoverLikeDislike : sendLikeDislike.bind($data,0) }" data-action-type="answer-dislike">
                              <i class="far fa-thumbs-down icon" data-bind="css: {hide: answerDislikeLoading() }"></i>
                              <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !answerDislikeLoading() }"></i>
                              <span class="react-count" data-bind="text: answerDislikesCount"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li class="answer-action add-reply-trigger" data-bind="event: { click: showReplyBox }, css: {active: replyBoxOpen() } ">
                              <span>Reply</span>
                            </li>
                          <?php
                            } else {
                          ?>
                            <li class="answer-action-ineligible">
                              <span class="react-count" data-bind="text: answerLikesCount"></span>
                              <span data-bind="text: ' Like'+(answerLikesCount()>1 ? 's' : '')"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li class="answer-action-ineligible">
                              <span class="react-count" data-bind="text: answerDislikesCount"></span>
                              <span data-bind="text: ' Dislike'+(answerDislikesCount()>1 ? 's' : '')"></span>
                            </li>
                          <?php
                            }
                          ?>
                          </ul>
                          <?php
                            if($eligible_to_answer) {
                          ?>
                          <div class="question-add-reply add-answer question-section" data-bind="css: { hide: replyBoxOpen() == false }">
                            <div class="reply-user-icon-box icon-small-box">
                              <a data-bind="attr: { href: 'user.php?id=' + view_user_id }" title="You"><img data-bind="attr: {src: 'images/'+view_user_profile_image }"/></a>
                            </div>
                            <div class="add-reply-box">
                              <div class="form-group">
                                <textarea class="form-control question-input reply-input" placeholder="Add a reply..." data-bind="value: replyInputText, valueUpdate:['afterkeydown','propertychange','input'], hasFocus: replyInputFocused(), event: { blur: replyInputRemoveFocus(), focus: replyInputAddFocus}"></textarea>
                                <span class="reply-input-count">
                                  <span class="reply-input-count-text" data-bind="text: replyInputTextCount, css: { over: replyInputTextExceeded() }"></span>
                                </span>
                              </div>
                              <div class="form-group question-btn-box">
                                <button class="btn clear-btn " data-bind="event: { click: hideReplyBox } ">Cancel</button>
                                <button class="btn reply-btn question-btn" data-bind="event: { click: makeReply }, css: { disabled: replyBtnDisabled() }">Reply</button>
                              </div>
                            </div>
                          </div>
                          <?php
                            }
                          ?>
                          <div class="reply-box">
                            <div class="loading-box replies-loading" data-bind="css: { hide: repliesLoading() == false }" title="loading..."></div>
                            <div class="replies-count" data-bind="if: totalReplies() > 0">
                              <a href="#" class="replies-count-text" data-bind="css: repliesHidden() == true ? 'collapsed' : 'expanded', event: {click: loadReply }, text: replyCountDisplay()"></a>
                            </div>
                            <div class="replies" data-bind="if: totalReplies() > 0 && repliesHidden() == false">
                              <div data-bind="foreach: replies">
                                <div class="reply">
                                  <div class="reply-wrap question-section" data-bind="css: { collapsed: replyHidden() == true, owner: is_owner }">
                                    <div class="reply-user-icon-box icon-small-box">
                                      <a data-bind="attr: { href: 'user.php?id='+user_id }">
                                        <img data-bind="attr: { src: 'images/'+user_profile_image }">
                                      </a>
                                    </div>
                                    <div class="reply-main-content">
                                      <div class="reply-controls">
                                        <ul class="reply-user-details">
                                          <li>
                                            <a data-bind="attr:{ href: 'user.php?id='+user_id, title: nameTitle() }, text:user_username" class="user-name"></a>
                                          </li>
                                          <li class="list-divider"></li>
                                          <li>
                                            <span class="reply-time" data-bind="attr: {title: date}, text: how_long"></span>
                                          </li>
                                        </ul>
                                        <div class="question-toggler-box">
                                          <a href="#" class="question-toggler" data-bind="attr: { title: (replyHidden() == false ? 'collapse' : 'expand')}, css: replyHidden() == false ? 'collapse-icon' : 'expand-icon', event: {click : toggleReply }"></a>
                                        </div>
                                      </div>
                                      <div class="reply-details" data-bind="if: replyHidden() == false">
                                        <p class="reply-details-text" data-bind="text: text">
                                        </p>
                                        <ul class="reply-attributes">
                                        <?php
                                          if($eligible_to_answer) {
                                        ?>
                                          <li rel="popover" class="reply-action reply-like" data-bind="css: { active: replyLiked() == true, loading: replyLikeLoading() }, attr: {title: (view_user_id == user_id) ? '' : replyLikeTitle(), 'data-content': (view_user_id == user_id) ? 'You cannot like your own Reply' : ''}, event: { click: (view_user_id == user_id) ? popoverLikeDislike : sendLikeDislike.bind($data,1) } " data-action-type="reply-like">
                                            <i class="far fa-thumbs-up icon" data-bind="css: {hide: replyLikeLoading() }"></i>
                                            <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !replyLikeLoading() }"></i>
                                            <span class="react-count" data-bind="text: replyLikesCount"></span>
                                          </li>
                                          <li class="list-divider"></li>
                                          <li rel="popover" class="reply-action reply-dislike" data-bind="css: { active: replyDisliked() == true, loading: replyDislikeLoading() }, attr: {title: (view_user_id == user_id) ? '' : replyDislikeTitle(), 'data-content': (view_user_id == user_id) ? 'You cannot dislike your own Reply' : ''}, event: { click: (view_user_id == user_id) ? popoverLikeDislike : sendLikeDislike.bind($data,0) }" data-action-type="reply-dislike">
                                            <i class="far fa-thumbs-down icon" data-bind="css: {hide: replyDislikeLoading() }"></i>
                                            <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !replyDislikeLoading() }"></i>
                                            <span class="react-count" data-bind="text: replyDislikesCount"></span>
                                          </li>
                                        <?php
                                          } else {
                                        ?>
                                          <li class="reply-action-ineligible">
                                            <span class="react-count" data-bind="text: replyLikesCount"></span>
                                            <span data-bind="text: ' Like'+(replyLikesCount()>1 ? 's' : '')"></span>
                                          </li>
                                          <li class="list-divider"></li>
                                          <li class="reply-action-ineligible">
                                            <span class="react-count" data-bind="text:replyDislikesCount"></span>
                                            <span data-bind="text: ' Dislike'+(replyDislikesCount()>1 ? 's' : '')"></span>
                                          </li>
                                        <?php
                                          }
                                        ?>
                                        </ul>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div data-bind="if: loadedRepliesCount() < totalReplies() && loadedRepliesCount() > 0 && repliesHidden() == false">
                              <div class="view-more-box">
                                <a href="#" data-bind="event: { click: fetchReply }, text: 'view more replies'" class="view-more view-more-small"></a>
                                <span class="track track-small" data-bind="text: replyTrack()"></span>
                              </div>          
                            </div>
                            <span data-bind="if: loadedRepliesCount() > 10 && loadedRepliesCount() == totalReplies() && repliesHidden() == false">
                              <span class="load-more-finished" data-bind="text: 'No more replies to show'"></span>
                            </span>
                          </div>
                        </div>
                      </div>

                      
                    </div>
                  </div> 
                </div>

              </div>

              <div class="view-more-box" data-bind="if: ((loadedAnswersCount() < totalAnswers() && loadedAnswersCount() > 0) || (showAllAnswersOption() == true && totalAnswers() > 1))">
                <a data-bind="event: { click: fetchAnswers }, text: ((showAllAnswersOption() == true) ? 'view all answers' : 'view more answers')" href="#" class="view-more view-more-big"></a>
                <span class="track track-big" data-bind="text: answerTrack()"></span>
              </div>
              <span class="load-more-finished" data-bind="if: loadedAnswersCount() > 10 && loadedAnswersCount() == totalAnswers()">
                No more answers to show
              </span>
            </div>

          </div>

        </div>
        
      </div>


      <script type="text/javascript">
        <?php
          $answer_count = 0;
          //get the count of the answers to the question
          $stmt = $dbc->prepare("SELECT COUNT(*) FROM answer WHERE question_id = ? AND parent_answer_id IS NULL");
          $stmt->bind_param("i", $question_id);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($answer_count);
          $stmt->fetch();
          $stmt->free_result();
          $stmt->close();

          //empty payload on load
          $a = array(
            'view_user_username' => $view_user_username,
            'view_user_profile_image' => $view_user_profile_image,
            'view_user_id' => $view_user_id,
            'eligible_to_answer' => $eligible_to_answer,
            'loggedin' => $loggedin,
            'question_id' => $question_id,
            'question_user_id' => $question_user_id,
            'question_liked' => $like_question,
            'question_disliked' => $dislike_question,
            'question_likes_count' => $question_likes_count,
            'question_dislikes_count' => $question_dislikes_count,
            'question_score' => $question_score,
            'is_question_saved' => $is_question_saved,
            'answer_focus_id' => $answer_focus_id,
            'answer_focus_user_id' => $answer_focus_user_id,
            'answer_focus_shown' => ($answer_focus_id == null ? false : true),
            'total_answers' => null,//$question_answers_count,
            'answers' => array()
          );

          //conert payload to json
          echo "var json_payload = " . json_encode($a) . ";";
        ?>
        var model;

        function popoverLikeDislike(self, e) {
          $(e.currentTarget).popover({
            placement: 'auto',
            trigger: 'focus'
          });
          $(e.currentTarget).popover('show');
          setTimeout(()=> $(e.currentTarget).popover('hide'), 2200);
        }

        //function to decode the characters and print them as text
        function htmlDecode( html ) {
            let a = document.createElement( 'a' ); a.innerHTML = html;
            return a.textContent;
        };

        function replyModel(data) {
          var self = this;
          self.date = data.reply_date;
          self.replyLikesCount = ko.observable(data.reply_likes_count);
          self.replyDislikesCount = ko.observable(data.reply_dislikes_count);
          self.replyLiked = ko.observable(data.reply_liked);
          self.replyDisliked = ko.observable(data.reply_disliked);
          self.replyLikeTitle = ko.computed(function () {
            return (self.replyLiked()) ? 'Remove like' : 'I like this Reply';
          });
          self.replyDislikeTitle = ko.computed(function () {
            return (self.replyDisliked()) ? 'Remove dislike' : 'I dislike this Reply';
          });
          self.replyLikeLoading = ko.observable(false);
          self.replyDislikeLoading = ko.observable(false);
          self.sendLikeDislike = function(is_like) {
            //don't process if clicked twice or more in succession
            if((is_like == 0 && self.replyDislikeLoading() == true) || (is_like == 1 && self.replyLikeLoading() == true)) {
              return;
            }
            var payload = { is_like: is_like, question_id: self.question_id, answer_id: self.answer_id, reply_id: self.id};
            $.get('like_reply.php', payload, 
              function(data, status) {
                (is_like) ? self.replyLikeLoading(true) : self.replyDislikeLoading(true);
                if(status == "success") {
                  if(data.isErr == false) {
                    self.replyLikesCount(data.message.reply_likes_count); 
                    self.replyDislikesCount(data.message.reply_dislikes_count); 
                    self.replyLiked(data.message.reply_liked); 
                    self.replyDisliked(data.message.reply_disliked); 
                    (is_like) ? self.replyLikeLoading(false) : self.replyDislikeLoading(false);
                  }
                } else {
                  (is_like) ? self.replyLikeLoading(false) : self.replyDislikeLoading(false);
                }
              }, "json");
          };
          self.how_long = data.reply_how_long;
          self.id = data.reply_id;
          self.answer_id = data.answer_id;
          self.question_id = data.question_id;
          self.text = htmlDecode(data.reply_text);
          self.user_forum = data.reply_user_forum;
          self.user_id = data.reply_user_id;
          self.view_user_id = data.view_user_id;
          self.user_profile_image = data.reply_user_profile_image;
          self.user_username = data.reply_user_username;
          self.is_owner = (self.view_user_id == self.user_id);
          self.replyHidden = ko.observable(false);
          self.nameTitle = function(){
            var title = '';
            if(self.is_owner) {
              title = 'Go to your Profile';
            } else {
              title = `${self.user_username} is a member of ${self.user_forum}`;
            }
            return title;
          }
          self.toggleReply = function() { self.replyHidden(!self.replyHidden()); };
        }

        function answerModel(data) {
          var self = this;
          self.date = data.answer_date;
          self.answerLikesCount = ko.observable(data.answer_likes_count);
          self.answerDislikesCount = ko.observable(data.answer_dislikes_count);
          self.how_long = data.answer_how_long;
          self.id = data.answer_id;
          self.totalReplies = ko.observable(data.total_replies);
          self.loadedRepliesCount = ko.observable(data.loaded_replies);
          self.text = htmlDecode(data.answer_text);
          self.user_forum = data.answer_user_forum;
          self.user_id = data.answer_user_id;
          self.user_profile_image = data.answer_user_profile_image;
          self.user_username = data.answer_user_username;
          self.answerDisliked = ko.observable(data.answer_disliked);
          self.answerLiked = ko.observable(data.answer_liked);
          self.question_id = data.question_id;
          self.view_user_id = data.view_user_id;
          self.view_user_profile_image = data.view_user_profile_image;
          self.repliesHidden = ko.observable(true);
          self.replyInputText = ko.observable('');
          self.replyCountDisplay = ko.computed(function() {
            if(self.totalReplies() == null) {
              return;
            }
            var is_plural = (self.totalReplies() > 1) ? true : false;
            return `${self.repliesHidden() ? 'Show' : 'Hide' }  ${self.totalReplies()} ${(is_plural) ? 'Replies' : 'Reply'}`;
          });
          self.replyInputRemoveFocus = function() {
            self.replyInputFocused(false);
          };
          self.replyInputAddFocus = function() {
            if(self.replyInputFocused() == false)
              self.replyInputFocused(true);
          };
          self.answerLikeLoading = ko.observable(false);
          self.answerDislikeLoading = ko.observable(false);
          self.answerLikeTitle = ko.computed(function () {
              return (self.answerLiked()) ? 'Remove like' : 'I like this Answer';
          });
          self.answerDislikeTitle = ko.computed(function () {
              return (self.answerDisliked()) ? 'Remove dislike' : 'I dislike this Answer';
          });
          self.sendLikeDislike = function(is_like) {
            //don't process if clicked twice or more in succession
            if((is_like == 0 && self.answerDislikeLoading() == true) || (is_like == 1 && self.answerLikeLoading() == true)) {
              return;
            }
            var payload = { is_like: is_like, question_id: self.question_id, answer_id: self.id};
            $.get('like_answer.php', payload, 
              function(data, status) {
                (is_like) ? self.answerLikeLoading(true) : self.answerDislikeLoading(true);
                if(status == "success") {
                  if(data.isErr == false) {
                    self.answerLikesCount(data.message.answer_likes_count); 
                    self.answerDislikesCount(data.message.answer_dislikes_count); 
                    self.answerLiked(data.message.answer_liked); 
                    self.answerDisliked(data.message.answer_disliked); 
                    (is_like) ? self.answerLikeLoading(false) : self.answerDislikeLoading(false);
                  }
                } else {
                  (is_like) ? self.answerLikeLoading(false) : self.answerDislikeLoading(false);
                }
              }, "json");
          };
          self.is_owner = data.is_owner;
          self.nameTitle = function(){
            var title = '';
            if(self.is_owner) {
              title = 'Go to your Profile';
            } else {
              title = `${self.user_username} is a member of ${self.user_forum}`;
            }
            return title;
          }
          self.replyTrack = ko.computed(function(){
            return `${self.loadedRepliesCount()} of ${self.totalReplies()}`;
          });
          self.replyBtnDisabled = ko.observable(true);
          self.replyInputTextExceeded = ko.observable(false);
          self.replyInputTextCount = ko.computed(function() {
            var len = self.replyInputText().trim().length;
            if(len > 0 && len <= 1000) {
              self.replyBtnDisabled(false);
            } else {
              self.replyBtnDisabled(true);
            }
            if(len > 1000) {
              self.replyInputTextExceeded(true);
            } else {
              self.replyInputTextExceeded(false)
            }
            return `${len}/1000`;
          });
          self.repliesLoading = ko.observable(false);
          self.replyBoxOpen = ko.observable(false);
          self.replyInputFocused = ko.observable(false);
          self.showReplyBox = function() {
            if(self.replyBoxOpen()) {
              return;
            }
            //open the reply box
            self.replyBoxOpen(true);
            //set focus to the reply input
            self.replyInputFocused(true);
          }
          self.hideReplyBox = function() {
            //close the reply box
            self.replyBoxOpen(false);
          }

          self.clearReplyBox = function() {
            //empty the reply input
            self.replyInputText('');
            self.replyBoxOpen(false);
          };

          self.fetchReply = function() {
            self.repliesLoading(true);
            var limit = 10;
            if(self.loadedRepliesCount() != undefined || self.loadedRepliesCount() != null) {
              limit = self.loadedRepliesCount() + 10;
            } 
            var payload = { question_id: self.question_id, answer_id: self.id, limit: limit };
            $.get('load_more_replies.php', payload, 
            function(data, status) {
              var dataObj = JSON.parse(data);
              if(status == "success") {
                if(dataObj.isErr == false) {
                  self.repliesHidden(false);
                  self.reset(dataObj.message);
                  self.repliesLoading(false);
                }
              } else {
                self.repliesLoading(false);
              }
            });
          }

          self.loadReply = function() {
            if(self.repliesHidden()) {
              if(self.loadedRepliesCount() > 0) {
                self.repliesHidden(false)
              } else {
                self.fetchReply();
              }
            } else {
              self.repliesHidden(true);
            }
          };

          self.reset = function(data) {
            //convert each reply into a reply Model and store in an array
            var mappedReplies = data.replies.map(reply => new replyModel(reply));
            self.replies(mappedReplies);
            self.totalReplies(data.total_replies);
            self.loadedRepliesCount(data.loaded_replies);
          };

          self.answerHidden = ko.observable(false);
          self.toggleAnswer = function() { self.answerHidden(!self.answerHidden()); };
          
          self.makeReply = function() {
            
            //return if button is disabled
            if (self.replyBtnDisabled() || self.replyInputTextExceeded()) {
              return;
            }
            
            
            var body = self.replyInputText();

            //show the loading box
            self.repliesLoading(true);
            
            var question_payload = { question_id: self.question_id, answer_id: self.id, body: body }

            $.post('make_reply.php', question_payload, function(data, status) {
              if(status == "success") {
                var dataObj = JSON.parse(data);
                self.clearReplyBox();
                
                if(dataObj.isErr === true) {
                  //displayError(msg, $errorBox);         
                } else if(dataObj.isErr === false) {
                  
                  var json_payload = dataObj.message;
                  self.reset(json_payload);

                  //display feedback and remove any feedback span present
                  $('.questionive-feedback').remove();
                  var $feedback = $('body').append('<span class="positive-feedback"></span>');
                  $('.positive-feedback').text('Reply Added!');
                  setTimeout(function(){
                    $('.positive-feedback').fadeOut(function(){
                      $('.positive-feedback').remove();
                    });
                  }, 2200);

                  self.repliesLoading(false);
                }
              } else {
                self.repliesLoading(false);
              }
            });
          };

          self.replies = ko.observableArray('');
          self.questionActionTitle = function (type, is_like, liked) {
            var str = '';
            if(liked) {
              str = `Remove ${is_like}`;
            } else {
              str = `I ${is_like} this ${type}`;
            }
            return str;
          };

        }

        var sortTypesAvailable = ['newest', 'oldest', 'score'];

        function AppViewModel() {
          var self = this;
          //flag to store the sort type
          self.sortAnswerType = ko.observable('newest');

          //convert each answer to a model and store in an array
          var mappedAnswers = json_payload.answers.map(answer => new answerModel(answer));
          self.answers = ko.observableArray(mappedAnswers);
          self.answerFocusID = json_payload.answer_focus_id;
          self.answerFocusUserID = json_payload.answer_focus_user_id;
          self.answerFocusShown = ko.observable(json_payload.answer_focus_shown);
          self.isQuestionSaved = ko.observable(json_payload.is_question_saved);
          self.totalAnswers = ko.observable(json_payload.total_answers);
          self.loadedAnswersCount = ko.observable(self.answers().length);
          self.getTotalAnswers = ko.computed(function(){
            var len = self.totalAnswers();
            return `${len} Answer${(len > 1) ? 's' :''}`;
          });
          self.viewUserID = json_payload.view_user_id;
          self.questionID = json_payload.question_id;
          self.viewUserProfileImage = json_payload.view_user_profile_image;
          self.eligibleToAnswer = json_payload.eligible_to_answer;
          self.isLoggedIn = json_payload.is_loggedin;
          self.answerTrack = ko.computed(function(){
            return `${self.loadedAnswersCount()} of ${self.totalAnswers()}`;
          });
          self.questionLikesCount = ko.observable(json_payload.question_likes_count);
          self.questionDislikesCount = ko.observable(json_payload.question_dislikes_count);
          self.questionScore = ko.observable(json_payload.question_score);
          self.questionUserID = json_payload.question_user_id;
          self.questionLiked = ko.observable(json_payload.question_liked);
          self.questionDisliked = ko.observable(json_payload.question_disliked);
          self.questionLikeTitle = ko.computed(function () {
              return (self.questionLiked()) ? 'Remove like' : 'I like this Question';
          });
          self.questionDislikeTitle = ko.computed(function () {
              return (self.questionDisliked()) ? 'Remove dislike' : 'I dislike this Question';
          });

          self.reset = function (data, emptyAnswer = false) {
            var payload = data;
            //convert each answer into a answer Model and store in an array
            var mappedAnswers = data.answers.map(answer => new answerModel(answer));
            if(emptyAnswer){ self.answers([]); };
            self.answers.push(...mappedAnswers);
            self.totalAnswers(payload.total_answers);
            self.loadedAnswersCount(self.answers().length);
          };

          self.answersLoading = ko.observable(false);
          self.answerInputText = ko.observable('');
          self.answerBoxOpen = ko.observable(false);
          self.answerBtnDisabled = ko.observable(true);
          self.answerInputTextExceeded = ko.observable(false);
          self.showAllAnswersOption = ko.observable(false);
          self.answerInputTextCount = ko.computed(function() {
            var len = self.answerInputText().trim().length;
            if(len > 0 && len <= 1000) {
              self.answerBtnDisabled(false);
            } else {
              self.answerBtnDisabled(true);
            }
            if(len > 1000) {
              self.answerInputTextExceeded(true);
            } else {
              self.answerInputTextExceeded(false)
            }
            return `${len}/1000`;
          });

          self.clearAnswerBox = function() {
            //empty the reply input
            self.answerInputText('');
            self.answerBoxOpen(false);
          };
          self.hideAnswerBox = function() {
            //close the answer box
            self.answerBoxOpen(false);
          }
          self.showAnswerBox = function() {
            if(self.answerBoxOpen()) {
              return;
            }
            //open the answer box
            self.answerBoxOpen(true);
          }
          self.makeAnswer = function () {
            //return if button is disabled
            if (self.answerBtnDisabled() || self.answerInputTextExceeded()) {
              return;
            }

            //show the loading box
            self.answersLoading(true);
            
            //question parameters
            var questionID = self.questionID;
            var body = self.answerInputText().trim();

            $.post('make_answer.php', {body: body, id: questionID}, function(data, status) {
              if(status == "success") {
                console.log(data);
                var dataObj = JSON.parse(data);
                var msg = dataObj.message;
                
                if(dataObj.isErr === true) {
                  //displayError(msg, $errorBox);         
                } else if(dataObj.isErr === false) {
                  //clear and hide the answer box
                  self.clearAnswerBox();
                  
                  self.reset(msg, true);
                  displayFeedback('Answer Added!');

                  //reset the answer type to newest
                  self.sortAnswerType('newest');
                  
                }
                self.answersLoading(false);
              } else {
                self.answersLoading(false);
              }
            });
          };
          self.questionLikeLoading = ko.observable(false);
          self.questionDislikeLoading = ko.observable(false);
          self.saveQuestion = function() {
            var payload = { question_id: self.questionID};
            $.get('save_question.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    self.isQuestionSaved(data.message.is_question_saved);
                    if(self.isQuestionSaved() == true) {
                      displayFeedback('Question Saved!');
                    }
                  }
                } 
            }, "json");
          };
          self.sendLikeDislike = function(is_like) {
            //don't process if clicked twice or more in succession
            if((is_like == 0 && self.questionDislikeLoading() == true) || (is_like == 1 && self.questionLikeLoading() == true)) {
              return;
            }
            var payload = { is_like: is_like, question_id: self.questionID};
            $.get('like_question.php', payload, 
              function(data, status) {
                (is_like) ? self.questionLikeLoading(true) : self.questionDislikeLoading(true);
                if(status == "success") {
                  if(data.isErr == false) {
                    self.questionLikesCount(data.message.question_likes_count); 
                    self.questionDislikesCount(data.message.question_dislikes_count); 
                    self.questionLiked(data.message.question_liked); 
                    self.questionDisliked(data.message.question_disliked);
                    self.questionScore(data.message.question_score);
                    (is_like) ? self.questionLikeLoading(false) : self.questionDislikeLoading(false);
                  }
                } else {
                  (is_like) ? self.questionLikeLoading(false) : self.questionDislikeLoading(false);
                }
            }, "json");
          };

          self.fetchAnswers = function() {
            var resetAnswers = false;
            if(self.answerFocusShown()) {
              //if only a particular answer was shown before
              self.answerFocusShown(false);
              self.showAllAnswersOption(false);
              resetAnswers = true;
            }
            self.reloadAnswers(resetAnswers);
          }

          //sort answers
          self.sortAnswers = function(type) {
            if(self.answerFocusShown()) {
              return;
            }
            //validate the type
            type = type.toLowerCase(type);
            type = sortTypesAvailable.indexOf(type) != -1 ? type : 'newest';
            self.sortAnswerType(type);
            self.reloadAnswers(true);
          };

          self.reloadAnswers = function (resetAnswers = true) {
            self.answersLoading(true);
            let type = self.sortAnswerType();

            var start = 0;
            if(self.loadedAnswersCount() > 0 && resetAnswers == false) {
              start = self.loadedAnswersCount();
            } 
            var payload = {};


            if(self.answerFocusShown() == false){
              payload = { question_id: self.questionID, start: start, sortType: type};
            } else {
              payload = { question_id: self.questionID, ansID: self.answerFocusID, sortType: type };
              //if the user sees a particular answer, there should be an option to see all the comments normally
              self.showAllAnswersOption(true);
            }
            $.get('load_more_answers.php', payload, 
              function(data, status) {
                console.log(data);
                if(status == "success") {
                  if(data.isErr == false) {
                    //reset the answers if aparticular answer was previously shown
                    self.reset(data.message, resetAnswers);  
                  }

                  self.answersLoading(false);
                } else {
                  self.answersLoading(false);
                }
            }, "json");
          };

          //Onload load the answers
          $(document).ready(function() {
            self.reloadAnswers(true);  
          });

        }
        model = new AppViewModel();
        /*model.answer.subscribe(function(newValue) {
            console.log("The person's new name is " , newValue);
        });*/
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

      <!-- Related Questions -->
      <div class="related">
        <p class="related-header">Related Questions</p>
        <ul class="related-questions-box">
          <?php
            $question_tags_id = implode(',', $question_tags);
            //Get related Questions
            $stmt = $dbc->prepare("SELECT DISTINCT question_heading, q.question_id FROM question AS q
            JOIN tag_associations AS ta ON q.question_id = ta.question_id 
            WHERE ( question_heading LIKE CONCAT('%',?,'%') OR ta.tag_id IN ($question_tags_id) ) AND q.question_id <> ? LIMIT 10");
            $stmt->bind_param("si", $heading, $question_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($related_question_heading, $related_question_id);
            while($stmt->fetch()) {
              ?>
              <li><a href="<?php echo "question.php?id=$related_question_id" ?>" class="related-link"><?php echo $related_question_heading; ?></a></li>
              <?php
            }
            $stmt->free_result();
          ?>
        </ul>
      </div>
    </div>
    <?php
      endif;
    ?>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>