<div class="comment-box">
  <div class="loading-box hide comments-loading" title="Loading...">
    <i class="fas fa-spinner loading-icon"></i>
  </div>
  <?php
    if($c_a['total_comments'] == 0) {
  ?>
  <div class="no-post-comment">
    <p>This post has no comment yet.</p>
  </div>
  <?php
    } else {
  ?>

  <div class="comment-filter-details row">
    <p class="comment-count-text">
      <span class="comment-count"> <?php echo $post_comments_count; ?> Comment<?php if($post_comments_count > 1) echo "s"; ?> </span>
    </p>
    <div class="comment-filter">
      <div class="dropdown filter-order-dropdown" id="comment_order_dropdown">
        <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="commentSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">new</a>
        <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="commentSortLink" id="comment_sort">
          <li class="dropdown-item active">new</li>
          <li class="dropdown-item">point</li>
        </ul>
      </div>
    </div>
  </div>
  
  <div class="comments">
    <?php
      foreach ($c_a['comments'] as $c => $index) {
        //comment variables
        $comment_id = $c['comment_id'];
        $comment_text = $c['comment_text'];
        $comment_date = $c['comment_date'];
        $comment_likes_count = $c['comment_likes_count'];
        $comment_dislikes_count = $c['comment_dislikes_count'];
        $comment_replies_count = $c['comment_replies_count'];
        $comment_user_id = $c['comment_user_id'];
        $comment_user_username = $c['comment_user_username'];
        $comment_user_forum = $c['comment_user_forum'];
        $comment_user_profile_image = $c['comment_user_profile_image'];
        $comment_how_long = $c['comment_how_long'];
        $like_comment = $c['like_comment'];
        $dislike_comment = $c['dislike_comment'];
        $view_user_username = $c['view_user_username'];
        $view_user_profile_image = $c['view_user_profile_image'];
        $view_user_id = $c['view_user_id'];
    ?>
    <div class="comment post-section" data-comment-id="<?php echo $comment_id ?>">
      <div class="comment-user-icon-box icon-big-box">
        <?php
          if($comment_user_profile_image != null && !empty($comment_user_profile_image)) {
            echo "<a href='user.php?id=$comment_user_id><img class='post-user-img' alt='"."$comment_user_username's Profile Image"."' src='images/". trim($comment_user_profile_image) ."'></a>";
          } else {
            $icon_letter = strtoupper(substr($comment_user_username, 0, 1));
            $rand_color = $bg_colors[rand(0, count($bg_colors)-1)];
            echo "<a href='user.php?id=$comment_user_id' title='$comment_user_username'><span class='comment-user-icon $rand_color'>$icon_letter</span></a>";
          }
        ?>
      </div>
      <div class="comment-main-content">
        <div class="comment-controls">
          <ul class="comment-user-details">
            <li>
              <?php
                echo "<a title='$comment_user_username is a member of $comment_user_forum' href='user.php?id=$comment_user_id' class='user-name'>". ucfirst($comment_user_username) ."</a>";
              ?>
            </li>
            <li class="list-divider"></li>
            <li><span class="post-time" title="<?php echo $comment_date; ?>"><?php echo $comment_how_long; ?> ago</span></li>
          </ul>
          <div class="post-toggler-box">
            <a href="#" class="post-toggler collapse-icon" title="collapse" data-toggle="collapse"></a>
          </div>
        </div>
        <div class="comment-body post-body">
          <div class="comment-details">
            <p class="comment-details-text">
              <?php echo $comment_text; ?>
            </p>
            <ul class="comment-attributes">
              <li rel="popover" data-action-type="comment-like" class="comment-action comment-like <?php if($like_comment == true) { echo "active"; } ?>"  title="<?php echo set_title('Comment', 'like', $like_comment); ?>">
                <i class="far fa-thumbs-up icon"></i>
                <span class="react-count"><?php echo $comment_likes_count; ?></span>
              </li>
              <li class="list-divider"></li>
              <li rel="popover" data-action-type="comment-dislike" class="comment-action comment-dislike <?php if($dislike_comment == true) { echo "active"; } ?>"  title="<?php echo set_title('Comment', 'dislike', $dislike_comment); ?>">
                <i class="far fa-thumbs-down icon"></i>
                <span class="react-count"><?php echo $comment_dislikes_count; ?></span>
              </li>
              <?php
                if($eligible_to_comment) {
              ?>
                <li class="list-divider"></li>
                <li class="comment-action add-reply-trigger">
                  <span>Reply</span>
                </li>
              <?php
                }
              ?>
            </ul>
            <?php
              if($eligible_to_comment) {
            ?>
            <div class="reply-box">
              <div class="post-add-reply add-comment post-section hide">
                <div class="reply-user-icon-box icon-small-box">
                  <?php
                    if($view_user_profile_image != null && !empty($view_user_profile_image)) {

                    } else {
                      echo "<a href='user.php?id=$view_user_id'><span class='reply-user-icon $view_user_rand_color'>$view_user_icon_letter</span></a>";
                    }
                  ?>
                </div>
                <div class="add-reply-box">
                  <div class="form-group">
                    <textarea class="form-control post-input reply-input" placeholder="Add a reply..."></textarea>
                  </div>
                  <div class="form-group post-btn-box">
                    <button class="btn clear-btn ">Cancel</button>
                    <button class="btn reply-btn post-btn">Reply</button>
                  </div>
                </div>
              </div>
            </div>
            <?php
              }
            ?>
          </div>

          
        </div>

        
      </div>
    </div>

    <?php
        }

      //free the comment result
      $stmt_c->free_result();
    ?>

  </div>

  <?php
    }
  ?>
</div>