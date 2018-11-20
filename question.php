<?php
  /*
    needed query parameters for this page for now
    id - id of the post
  */

	include('includes/generic_includes.php');

  $is_404 = false;
  $page_heading = "Oops! Post not found :(";
  //if id query parameter doesn't exist, redirect to questions page
  if(isset($_GET['id']) && !empty($_GET['id'])) {
    $post_id = trim($_GET['id']);

    //if id is numeric and greater than 0
    if (is_numeric($post_id) && filter_var($post_id, FILTER_VALIDATE_INT) != false && intval($post_id) > 0) {
      $post_id = intval($post_id);
      //check if post exists
      $stmt = $dbc->prepare("SELECT COUNT(*) AS count, post_heading FROM post WHERE post_id = ?");
      $stmt->bind_param("d", $post_id);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($count, $heading);
      $stmt->fetch();
      $stmt->free_result();
      $stmt->close();
      //if post does not exists
      if(intval($count) != 1) {
        $is_404 = true;
      } else {
        $page_heading = $heading;

        $query_string = "?id=$post_id";
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
  include('includes/header.php');
?>
<!-- CONTAINER -->
<div class="container-fluid content-container">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="create-link-box row">
      <a href="ask.php" class="create-link">Ask Question</a>
    </div>
    <?php
      if($is_404):
        $page_err_msg = "Post not found.";
        include('includes/err404.php');

      else:
        //variable to determine if the user can add a comment to the post
        $eligible_to_comment = false;
    ?>
    <div class="post-outer-container">

      <!-- Post Container -->
      <div class="post" data-post-id="<?php echo $post_id; ?>">
        <!-- Post Header -->
        <div class="post-header">
          <h1 class="post-heading page-title">
            <span><?php echo $heading; ?>
              <span title="Score" class="post-score" data-bind="text: postScore, css: {positive: postScore() > 0 }"></span>  
            </span>
          </h1>
          <div class="post-tags">
            <i class="fas fa-tag post-tag-icon"></i>
            <?php
              //load the tags sorted by the forum first, followed by default tags then custom tags 
              $stmt = $dbc->prepare("SELECT t.tag_id, t.tag_name, tt.name FROM tag_associations AS ta JOIN tag AS t ON ta.tag_id = t.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE ta.post_id = ? ORDER BY FIELD (tt.name, 'forum', 'default_tag', 'custom_tag')");
              $stmt->bind_param("d", $post_id);
              $stmt->execute();
              $stmt->store_result();
              $stmt->bind_result($temp_tag_id, $temp_tag_name, $temp_tag_type_name);
              while ($stmt->fetch()) {
                $css = '';
                if($temp_tag_type_name == 'forum'){
                  $css .= 'forum-tag';
                  //get the forum id for the post
                  $post_forum_id = $temp_tag_id;
                } else if($temp_tag_type_name == 'custom_tag') {
                  $css .= 'custom-tag';
                }
                echo "<a href='tag.php?id=$temp_tag_id' class='post-tag $css'>" . strtolower($temp_tag_name) . "</a>";
              }
              $stmt->free_result();
              $stmt->close();
              unset($stmt);
            ?>
          </div>
        </div>

        <!-- Post Body -->
        <div class="post-box">
          <?php
            //get post content, attributes(likes/dislikes/comments count) and user who posted it
            $stmt = $dbc->prepare("SELECT p.post_text, p.post_date, p.likes, p.dislikes, p.comments, u.user_id, u.username, u.profile_image, t.tag_name, TIMESTAMPDIFF(SECOND, p.post_date, NOW()) AS seconds, DATE_FORMAT(p.post_date, '%M %e'), DATE_FORMAT(p.post_date, ' at %h:%i %p'), YEAR(p.post_date), YEAR(NOW()), DAY(p.post_date), DAY(NOW()) FROM post AS p JOIN user AS u ON p.user_id = u.user_id JOIN tag AS t ON u.tag_id = t.tag_id WHERE post_id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($post_text, $post_date, $post_likes_count, $post_dislikes_count, $post_comments_count, $post_user_id, $username, $user_img, $post_owner_forum, $seconds, $post_date_open, $post_date_close, $post_year, $current_year, $post_day, $today);
            $stmt->fetch();
            $stmt->free_result();
            $stmt->close();
            unset($stmt);

          ?>
          <div class="post-main post-section">
            <div class="post-user-icon-box icon-big-box">
              <?php
              //initialize the user view values
                $view_user_id = null;
                $view_user_profile_image = null;
                $view_user_username = null;

                include('includes/how_long.php');
                include('includes/post_action_title.php');
                //print user icon
                echo "<a href='user.php?id=$post_user_id'><img class='post-user-img' alt='$username Profile Image' src='images/". (empty(trim($user_img)) ? 'user_icon.png' : trim($user_img)) ."'/></a>";
                
              ?>
            </div>
            <div class="post-main-content">
              <ul class="post-user-details">
                <li>
                  <?php
                    $title = ( ($loggedin == true && $_SESSION['user_id'] == $post_user_id) ? 'Go to your Profile' : $username.' is a member of '. $post_owner_forum);
                    echo "<a title='$title' href='user.php?id=$post_user_id' class='user-name'>". ucfirst($username) ."</a>";
                  ?>
                </li>
                <li class="list-divider"></li>
                <li><span class="post-time" title="<?php echo $post_date; ?>"><?php echo calculate($seconds, $post_date_open, $post_date_close, $post_year, $current_year, $post_date, $today); ?></span></li>
              </ul>
              <div class="post-details">
                <p class="post-details-text"><?php echo htmlspecialchars($post_text, ENT_QUOTES); ?></p>
                <?php
                  //get the post images
                  $stmt = $dbc->prepare("SELECT image_name FROM post_images WHERE post_id = ?");
                  $stmt->bind_param("i", $post_id);
                  $stmt->execute();
                  $stmt->store_result();
                  if($stmt->num_rows > 0) {
                    echo "<div class='post-image-box'>";
                    $stmt->bind_result($image_name);
                    
                    while($stmt->fetch()) {
                      echo "<img class='min post-image' title='Zoom In' src='images/question/". trim($image_name) ."' alt='Image for Post - $heading'>";
                    }
                    echo "</div>";

                    //unset the variable
                    unset($image_name);
                  }
                  $stmt->free_result();
                  $stmt->close();
                ?>
                <?php
                  //flags to know if the user has previously liked/disliked a post
                  $like_post = false;
                  $dislike_post = false;
                  $is_post_saved = false;

                ?>
                <ul class="post-attributes">
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

                    //the user is only eligiblie if he belong to the country of post or he is the owner of the post
                    if($user_forum_id == $post_forum_id || $view_user_id == $post_user_id) {
                      $eligible_to_comment = true;
                    }

                    //check if the view user has saved the post before
                    $stmt = $dbc->prepare("SELECT post_id FROM saved_post WHERE post_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $post_id, $view_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if($stmt->num_rows == 1) {
                      $is_post_saved = true;
                    }
                    $stmt->free_result();

                    //check if the view user has ever liked or disliked the post
                    $stmt = $dbc->prepare("SELECT is_like FROM post_likes WHERE post_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $post_id, $view_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if($stmt->num_rows == 1) {
                      $stmt->bind_result($is_like);
                      $stmt->fetch();

                      if($is_like == 1) {
                        $like_post = true;
                      } else if($is_like == 0) {
                        $dislike_post = true;
                      }
                    }
                    $stmt->free_result();
                    $stmt->close();
                  ?>
                  <li rel="popover" data-action-type="post-like" class="post-action post-like" data-bind="css: { active: postLiked() == true, loading: postLikeLoading() }, attr: {title: postLikeTitle()}, event: { click: function() { sendLikeDislike(1) } }">
                    <i class="far fa-thumbs-up icon" data-bind="css: {hide: postLikeLoading() }"></i>
                    <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !postLikeLoading() }"></i>
                    <span class="react-count" data-bind="text: postLikesCount"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li rel="popover" data-action-type="post-dislike" class="post-action post-dislike" data-bind="css: { active: postDisliked() == true, loading: postDislikeLoading() }, attr: {title: postDislikeTitle()}, event: { click: function() { sendLikeDislike(0) } }">
                    <i class="far fa-thumbs-down icon" data-bind="css: {hide: postDislikeLoading() }"></i>
                    <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !postDislikeLoading() }"></i>
                    <span class="react-count" data-bind="text: postDislikesCount"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li rel="popover" data-action-type="post-save" class="post-action post-save" data-bind="css: {active: isPostSaved() }, event: {click: savePost}, attr: {title: (isPostSaved() ? 'Unsave this Question' : 'I like this Question')}">
                    <i class="far fa-bookmark icon"></i>
                  </li>
                <?php
                  unset($is_like);
                  } else {
                ?>
                  <li class="post-action-ineligible">
                    <span class="react-count" data-bind="text: postLikesCount"></span>
                    <span data-bind="text: ' Like'+(postLikesCount()>1 ? 's' : '')"></span>
                  </li>
                  <li class="list-divider"></li>
                  <li class="post-action-ineligible">
                    <span class="react-count" data-bind="text: postDislikesCount"></span>
                    <span data-bind="text: ' Dislike'+(postDislikesCount()>1 ? 's' : '')"></span>
                  </li>
                <?php
                  }
                ?>
                </ul>

                <?php

                  if($eligible_to_comment) {
                ?>
                <div class="add-comment-trigger-box">
                  <span class="add-comment-trigger" data-bind="css: { active: commentBoxOpen() == true }, event: { click: showCommentBox }">Add answer</span>
                </div>
                <?php
                  }
                ?>
              </div>
            </div>
          </div>

          <?php
            if($eligible_to_comment) {
          ?>
          <div class="post-add-comment add-comment post-section" data-bind="css: { hide: commentBoxOpen() == false }">
            <div class="post-user-icon-box icon-big-box">
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
                    echo "<img class='post-user-img' alt='$view_user_username' src='images/". $view_user_profile_image ."' />";
                  ?>
                </a>
            </div>
            <div class="add-comment-box">
              <div class="form-group post-input-box">
                <textarea class="form-control post-input comment-input" data-bind="value: commentInputText, valueUpdate:['afterkeydown','propertychange','input'], hasFocus: commentBoxOpen()" rows="6" placeholder="Add an answer..."></textarea>
                <span class="post-input-count-box" data-bind="text: commentInputTextCount, css: { over: commentInputTextExceeded() }"></span>
              </div>
              <div class="form-group post-btn-box">
                <button class="btn clear-btn" data-bind="event: { click: hideCommentBox } ">Cancel</button>
                <button class="btn comment-btn post-btn" data-bind="event: {click: makeComment}, css: { disabled: commentBtnDisabled() }">Answer</button>
              </div>
            </div>
          </div>
          <?php
            } else if($eligible_to_comment == false && $loggedin == true) {
               //if the user is logged in but cannot comment due to being ineligible
          ?>
              <div class="post-eligibility-info-box">
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

          <div class="post-comments">
            <div class="post-comments-header">
              <span>Answers</span>
            </div>
            <div class="comment-box">
              <div class="loading-box hide comments-loading" title="Loading..." data-bind="css: { hide: commentsLoading() == false }">
                <i class="fas fa-spinner loading-icon"></i>
              </div>
              <div class="no-post-comment" data-bind="if: totalComments() != null && totalComments() <= 0">
                <p>This Question has no answer yet.</p>
              </div>

              <div class="comment-filter-details row">
                <p class="comment-count-text" data-bind="if: totalComments() > 0">
                  <span class="comment-count" data-bind="text: getTotalComments()"></span>
                </p>
                <div class="comment-filter" data-bind="if: totalComments() > 1">
                  <div class="dropdown filter-order-dropdown" id="comment_order_dropdown">
                    <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="commentSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">new</a>
                    <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="commentSortLink" id="comment_sort">
                      <li class="dropdown-item active">new</li>
                      <li class="dropdown-item">point</li>
                    </ul>
                  </div>
                </div>
              </div>
              
              

              <div class="comments" data-bind=" foreach: comments">

                <div class="comment">
                  <div data-bind="css: { collapsed: commentHidden() == true, owner: is_owner, focus: replyInputFocused() }" class="comment-wrap post-section">
                    <div class="comment-user-icon-box icon-big-box" data-bind="css: { collapsed: commentHidden() == true, owner: is_owner }">
                      <a data-bind="attr: { href: 'user.php?id=' + user_id, title: user_username }"><img data-bind="attr: {src: 'images/'+user_profile_image }"/></a>
                    </div>
                    <div class="comment-main-content">
                      <div class="comment-controls">
                        <ul class="comment-user-details">
                          <li>
                            <a data-bind="attr: { title: nameTitle(), href: 'user.php?id=' + user_id, class: 'user-name' }, html: user_username"></a>
                          </li>
                          <li class="list-divider"></li>
                          <li>
                            <span class="comment-time" data-bind="attr: { title: date }, html: how_long"></span>
                          </li>
                        </ul>
                        <div class="post-toggler-box">
                          <a href="#" class="post-toggler" data-bind="attr: { title: (commentHidden() == false ? 'collapse' : 'expand')}, css: commentHidden() == false ? 'collapse-icon' : 'expand-icon', event: {click : toggleComment }"></a>
                        </div>
                      </div>
                      <div class="comment-body post-body">
                        <div class="comment-details" data-bind="if: commentHidden() == false">
                          <p class="comment-details-text" data-bind="attr: { class: 'comment-details-text' }, html: text">
                          </p>

                          <ul class="comment-attributes">
                          <?php
                            if($eligible_to_comment) {
                          ?>
                            <li rel="popover" class="comment-action comment-like" data-bind="css: { active: commentLiked() == true, loading: commentLikeLoading() }, attr: {title: commentLikeTitle()}, event: { click: function() { sendLikeDislike(1) } }" data-action-type="comment-like">
                              <i class="far fa-thumbs-up icon" data-bind="css: {hide: commentLikeLoading() }"></i>
                              <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !commentLikeLoading() }"></i>
                              <span class="react-count" data-bind="text: commentLikesCount"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li rel="popover" class="comment-action comment-dislike" data-bind="css: { active: commentDisliked() == true, loading: commentDislikeLoading() }, attr: {title: commentDislikeTitle()}, event: { click: function() { sendLikeDislike(0) } }" data-action-type="comment-dislike">
                              <i class="far fa-thumbs-down icon" data-bind="css: {hide: commentDislikeLoading() }"></i>
                              <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !commentDislikeLoading() }"></i>
                              <span class="react-count" data-bind="text: commentDislikesCount"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li class="comment-action add-reply-trigger" data-bind="event: { click: showReplyBox }, css: {active: replyBoxOpen() } ">
                              <span>Reply</span>
                            </li>
                          <?php
                            } else {
                          ?>
                            <li class="comment-action-ineligible">
                              <span class="react-count" data-bind="text: commentLikesCount"></span>
                              <span data-bind="text: ' Like'+(commentLikesCount()>1 ? 's' : '')"></span>
                            </li>
                            <li class="list-divider"></li>
                            <li class="comment-action-ineligible">
                              <span class="react-count" data-bind="text: commentDislikesCount"></span>
                              <span data-bind="text: ' Dislike'+(commentDislikesCount()>1 ? 's' : '')"></span>
                            </li>
                          <?php
                            }
                          ?>
                          </ul>
                          <?php
                            if($eligible_to_comment) {
                          ?>
                          <div class="post-add-reply add-comment post-section" data-bind="css: { hide: replyBoxOpen() == false }">
                            <div class="reply-user-icon-box icon-small-box">
                              <a data-bind="attr: { href: 'user.php?id=' + view_user_id }" title="You"><img data-bind="attr: {src: 'images/'+view_user_profile_image }"/></a>
                            </div>
                            <div class="add-reply-box">
                              <div class="form-group">
                                <textarea class="form-control post-input reply-input" placeholder="Add a reply..." data-bind="value: replyInputText, valueUpdate:['afterkeydown','propertychange','input'], hasFocus: replyInputFocused(), event: { blur: replyInputRemoveFocus(), focus: replyInputAddFocus}"></textarea>
                                <span class="reply-input-count">
                                  <span class="reply-input-count-text" data-bind="text: replyInputTextCount, css: { over: replyInputTextExceeded() }"></span>
                                </span>
                              </div>
                              <div class="form-group post-btn-box">
                                <button class="btn clear-btn " data-bind="event: { click: hideReplyBox } ">Cancel</button>
                                <button class="btn reply-btn post-btn" data-bind="event: { click: makeReply }, css: { disabled: replyBtnDisabled() }">Reply</button>
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
                                  <div class="reply-wrap post-section" data-bind="css: { collapsed: replyHidden() == true, owner: is_owner }">
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
                                        <div class="post-toggler-box">
                                          <a href="#" class="post-toggler" data-bind="attr: { title: (replyHidden() == false ? 'collapse' : 'expand')}, css: replyHidden() == false ? 'collapse-icon' : 'expand-icon', event: {click : toggleReply }"></a>
                                        </div>
                                      </div>
                                      <div class="reply-details" data-bind="if: replyHidden() == false">
                                        <p class="reply-details-text" data-bind="text: text">
                                        </p>
                                        <ul class="reply-attributes">
                                        <?php
                                          if($eligible_to_comment) {
                                        ?>
                                          <li rel="popover" class="reply-action reply-like" data-bind="css: { active: replyLiked() == true, loading: replyLikeLoading() }, attr: {title: replyLikeTitle()}, event: { click: function() { sendLikeDislike(1) } }" data-action-type="reply-like">
                                            <i class="far fa-thumbs-up icon" data-bind="css: {hide: replyLikeLoading() }"></i>
                                            <i class="fas fa-spinner loading-icon" data-bind="css: {hide: !replyLikeLoading() }"></i>
                                            <span class="react-count" data-bind="text: replyLikesCount"></span>
                                          </li>
                                          <li class="list-divider"></li>
                                          <li rel="popover" class="reply-action reply-dislike" data-bind="css: { active: replyDisliked() == true, loading: replyDislikeLoading() }, attr: {title: replyDislikeTitle()}, event: { click: function() { sendLikeDislike(0) } }" data-action-type="reply-dislike">
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

              <div class="view-more-box" data-bind="if: loadedCommentsCount() < totalComments() && loadedCommentsCount() > 0">
                <a data-bind="event: { click: reloadComments }" href="#" class="view-more view-more-big">view more answers</a>
                <span class="track track-big" data-bind="text: commentTrack()"></span>
              </div>
              <span class="load-more-finished" data-bind="if: loadedCommentsCount() > 10 && loadedCommentsCount() == totalComments()">
                No more answers to show
              </span>
            </div>

          </div>

        </div>
        
      </div>


      <script type="text/javascript">
        <?php
          $comment_count = 0;
          //get the count of the comments to the post
          $stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE post_id = ? AND parent_comment_id IS NULL");
          $stmt->bind_param("i", $post_id);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($comment_count);
          $stmt->fetch();
          $stmt->free_result();
          $stmt->close();

          //empty payload on load
          $a = array(
            'view_user_username' => $view_user_username,
            'view_user_profile_image' => $view_user_profile_image,
            'view_user_id' => $view_user_id,
            'eligible_to_comment' => $eligible_to_comment,
            'loggedin' => $loggedin,
            'post_id' => $post_id,
            'post_liked' => $like_post,
            'post_disliked' => $dislike_post,
            'post_likes_count' => $post_likes_count,
            'post_dislikes_count' => $post_dislikes_count,
            'is_post_saved' => $is_post_saved,
            'total_comments' => null,//$post_comments_count,
            'comments' => array()
          );

          //conert payload to json
          echo "var json_payload = " . json_encode($a) . ";";
        ?>
        var model;

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
            var payload = { is_like: is_like, post_id: self.post_id, comment_id: self.comment_id, reply_id: self.id};
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
          self.comment_id = data.comment_id;
          self.post_id = data.post_id;
          self.text = data.reply_text;
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

        function commentModel(data) {
          var self = this;
          self.date = data.comment_date;
          self.commentLikesCount = ko.observable(data.comment_likes_count);
          self.commentDislikesCount = ko.observable(data.comment_dislikes_count);
          self.how_long = data.comment_how_long;
          self.id = data.comment_id;
          self.totalReplies = ko.observable(data.total_replies);
          self.loadedRepliesCount = ko.observable(data.loaded_replies);
          self.text = data.comment_text;
          self.user_forum = data.comment_user_forum;
          self.user_id = data.comment_user_id;
          self.user_profile_image = data.comment_user_profile_image;
          self.user_username = data.comment_user_username;
          self.commentDisliked = ko.observable(data.comment_disliked);
          self.commentLiked = ko.observable(data.comment_liked);
          self.post_id = data.post_id;
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
          self.commentLikeLoading = ko.observable(false);
          self.commentDislikeLoading = ko.observable(false);
          self.commentLikeTitle = ko.computed(function () {
              return (self.commentLiked()) ? 'Remove like' : 'I like this Answer';
          });
          self.commentDislikeTitle = ko.computed(function () {
              return (self.commentDisliked()) ? 'Remove dislike' : 'I dislike this Answer';
          });
          self.sendLikeDislike = function(is_like) {
            //don't process if clicked twice or more in succession
            if((is_like == 0 && self.commentDislikeLoading() == true) || (is_like == 1 && self.commentLikeLoading() == true)) {
              return;
            }
            var payload = { is_like: is_like, post_id: self.post_id, comment_id: self.id};
            $.get('like_comment.php', payload, 
              function(data, status) {
                (is_like) ? self.commentLikeLoading(true) : self.commentDislikeLoading(true);
                if(status == "success") {
                  if(data.isErr == false) {
                    self.commentLikesCount(data.message.comment_likes_count); 
                    self.commentDislikesCount(data.message.comment_dislikes_count); 
                    self.commentLiked(data.message.comment_liked); 
                    self.commentDisliked(data.message.comment_disliked); 
                    (is_like) ? self.commentLikeLoading(false) : self.commentDislikeLoading(false);
                  }
                } else {
                  (is_like) ? self.commentLikeLoading(false) : self.commentDislikeLoading(false);
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
            var payload = { post_id: self.post_id, comment_id: self.id, limit: limit };
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

          self.commentHidden = ko.observable(false);
          self.toggleComment = function() { self.commentHidden(!self.commentHidden()); };
          
          self.makeReply = function() {
            
            //return if button is disabled
            if (self.replyBtnDisabled() || self.replyInputTextExceeded()) {
              return;
            }
            
            
            var body = self.replyInputText();

            //show the loading box
            self.repliesLoading(true);
            
            var post_payload = { post_id: self.post_id, comment_id: self.id, body: body }

            $.post('make_reply.php', post_payload, function(data, status) {
              if(status == "success") {
                var dataObj = JSON.parse(data);
                self.clearReplyBox();
                
                if(dataObj.isErr === true) {
                  //displayError(msg, $errorBox);         
                } else if(dataObj.isErr === false) {
                  
                  var json_payload = dataObj.message;
                  self.reset(json_payload);

                  //display feedback and remove any feedback span present
                  $('.postive-feedback').remove();
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
          self.postActionTitle = function (type, is_like, liked) {
            var str = '';
            if(liked) {
              str = `Remove ${is_like}`;
            } else {
              str = `I ${is_like} this ${type}`;
            }
            return str;
          };

        }

        function AppViewModel() {
          var self = this;
          //convert each comment to a model and store in an array
          var mappedComments = json_payload.comments.map(comment => new commentModel(comment));
          self.comments = ko.observableArray(mappedComments);
          self.bgColor = function (char) {
            var colors = ["gray", "maroon", "olive", "teal", "fucshia", "purple", ""];
            //convert the alphabet to ASCII value
            var value = Number(char.toLowerCase().charCodeAt(0));
            //ASCII a-z is 97-122
            var color = "";
            //I used true in the swutch case because js uses the value passed in the switch case as the basis to compare, in this case any case that matches 'true'
            switch (true) {
              case value <= 105:
                color = "gray";
                break;
              case value <= 109:
                color = "maroon";
                break;
              case value <= 113:
                color = "olive";
                break;
              case value <= 117:
                color = "teal";
                break;
              case value <= 121:
                color = "fucshia";
                break;
              case value <= 124:
                color = "purple";
                break;
              
              default:
                color = "purple";
                break;
            }
            return color;
          };
          self.isPostSaved = ko.observable(json_payload.is_post_saved);
          self.totalComments = ko.observable(json_payload.total_comments);
          self.loadedCommentsCount = ko.observable(self.comments().length);
          self.getTotalComments = ko.computed(function(){
            var len = self.totalComments();
            return `${len} Answer${(len > 1) ? 's' :''}`;
          });
          self.viewUserID = json_payload.view_user_id;
          self.postID = json_payload.post_id;
          self.viewUserProfileImage = json_payload.view_user_profile_image;
          self.eligibleToComment = json_payload.eligible_to_comment;
          self.isLoggedIn = json_payload.is_loggedin;
          self.commentTrack = ko.computed(function(){
            return `${self.loadedCommentsCount()} of ${self.totalComments()}`;
          });
          self.postLikesCount = ko.observable(json_payload.post_likes_count);
          self.postDislikesCount = ko.observable(json_payload.post_dislikes_count);
          self.postScore = ko.computed(function(){
            return (self.postLikesCount() * 3) - (self.postDislikesCount() * 3);
          });
          self.postLiked = ko.observable(json_payload.post_liked);
          self.postDisliked = ko.observable(json_payload.post_disliked);
          self.postLikeTitle = ko.computed(function () {
              return (self.postLiked()) ? 'Remove like' : 'I like this Question';
          });
          self.postDislikeTitle = ko.computed(function () {
              return (self.postDisliked()) ? 'Remove dislike' : 'I dislike this Question';
          });

          self.reset = function (data, emptyComment = false) {
            var payload = data;
            //convert each comment into a comment Model and store in an array
            var mappedComments = data.comments.map(comment => new commentModel(comment));
            if(emptyComment){ self.comments([]); };
            self.comments.push(...mappedComments);
            self.totalComments(payload.total_comments);
            self.loadedCommentsCount(self.comments().length);
          };

          self.commentsLoading = ko.observable(false);
          self.commentInputText = ko.observable('');
          self.commentBoxOpen = ko.observable(false);
          self.commentBtnDisabled = ko.observable(true);
          self.commentInputTextExceeded = ko.observable(false);
          self.commentInputTextCount = ko.computed(function() {
            var len = self.commentInputText().trim().length;
            if(len > 0 && len <= 1000) {
              self.commentBtnDisabled(false);
            } else {
              self.commentBtnDisabled(true);
            }
            if(len > 1000) {
              self.commentInputTextExceeded(true);
            } else {
              self.commentInputTextExceeded(false)
            }
            return `${len}/1000`;
          });

          self.clearCommentBox = function() {
            //empty the reply input
            self.commentInputText('');
            self.commentBoxOpen(false);
          };
          self.hideCommentBox = function() {
            //close the comment box
            self.commentBoxOpen(false);
          }
          self.showCommentBox = function() {
            if(self.commentBoxOpen()) {
              return;
            }
            //open the comment box
            self.commentBoxOpen(true);
          }
          self.makeComment = function () {
            //return if button is disabled
            if (self.commentBtnDisabled() || self.commentInputTextExceeded()) {
              return;
            }

            //show the loading box
            self.commentsLoading(true);
            
            //post parameters
            var postID = self.postID;
            var body = self.commentInputText().trim();

            $.post('make_comment.php', {body: body, id: postID}, function(data, status) {
              if(status == "success") {
                var dataObj = JSON.parse(data);
                var msg = dataObj.message;
                
                if(dataObj.isErr === true) {
                  //displayError(msg, $errorBox);         
                } else if(dataObj.isErr === false) {
                  //clear and hide the comment box
                  self.clearCommentBox();
                  
                  self.reset(msg, true);
                  displayFeedback('Comment Added!');
                  
                }
                self.commentsLoading(false);
              } else {
                self.commentsLoading(false);
              }
            });
          };
          self.postLikeLoading = ko.observable(false);
          self.postDislikeLoading = ko.observable(false);
          self.savePost = function() {
            var payload = { post_id: self.postID};
            $.get('save_post.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    self.isPostSaved(data.message.is_post_saved);
                    if(self.isPostSaved() == true) {
                      displayFeedback('Post Saved!');
                    }
                  }
                } 
            }, "json");
          };
          self.sendLikeDislike = function(is_like) {
            //don't process if clicked twice or more in succession
            if((is_like == 0 && self.postDislikeLoading() == true) || (is_like == 1 && self.postLikeLoading() == true)) {
              return;
            }
            var payload = { is_like: is_like, post_id: self.postID};
            $.get('like_post.php', payload, 
              function(data, status) {
                (is_like) ? self.postLikeLoading(true) : self.postDislikeLoading(true);
                if(status == "success") {
                  if(data.isErr == false) {
                    self.postLikesCount(data.message.post_likes_count); 
                    self.postDislikesCount(data.message.post_dislikes_count); 
                    self.postLiked(data.message.post_liked); 
                    self.postDisliked(data.message.post_disliked);
                    (is_like) ? self.postLikeLoading(false) : self.postDislikeLoading(false);
                  }
                } else {
                  (is_like) ? self.postLikeLoading(false) : self.postDislikeLoading(false);
                }
            }, "json");
          };

          self.reloadComments = function () {
            self.commentsLoading(true);
            var start = 0;
            if(self.loadedCommentsCount() != undefined || self.loadedCommentsCount() != null) {
              start = self.loadedCommentsCount();
            } 
            var payload = { post_id: self.postID, start: start};
            $.get('load_more_comments.php', payload, 
              function(data, status) {
                if(status == "success") {
                  if(data.isErr == false) {
                    self.reset(data.message);
                  }
                  self.commentsLoading(false);
                } else {
                  self.commentsLoading(false);
                }
            }, "json");
          };

          //Onload load the comments
          $(document).ready(function() {
            self.reloadComments();
          });

        }
        model = new AppViewModel();
        /*model.comment.subscribe(function(newValue) {
            console.log("The person's new name is " , newValue);
        });*/
        ko.applyBindings(model);

        function displayFeedback(msg) {
          //display feedback and remove any feedback span present
          $('.postive-feedback').remove();
          var $feedback = $('body').append('<span class="positive-feedback"></span>');
          $('.positive-feedback').text(msg);
          setTimeout(function(){
            $('.positive-feedback').fadeOut(function(){
              $('.positive-feedback').remove();
            });
          }, 2000);
        }

      </script>

      <!-- Related Posts -->
      <div class="related">
        <p class="related-header">Related Questions</p>
        <ul class="related-posts-box">
          <li><a href="#" class="related-link">What is the difference between foor loop and while Loop?</a></li>
          <li><a href="#" class="related-link">Why is for loop so common?</a></li>
          <li><a href="#" class="related-link">Why is Africa in the center of the world?</a></li>
          <li><a href="#" class="related-link">Does it snow in Africa much?</a></li>
          <li><a href="#" class="related-link">What is a Safari?</a></li>
          <li><a href="#" class="related-link">How much does it cost to travel around the world (Globe trotting)?</a></li>
          <li><a href="#" class="related-link">Does it cost much to smile more?</a></li>
          <li><a href="#" class="related-link">WHO what does that stand for in African parlance?</a></li>
          <li><a href="#" class="related-link">How cold does it get during monsoon season in India?</a></li>
          <li><a href="#" class="related-link">WHy are there hdfefb?</a></li>

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