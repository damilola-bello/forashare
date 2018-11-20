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
      	$page_heading = "User " . ucfirst($username);
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
        $page_err_msg = "User not found.";
        include('includes/err404.php');
      else:
      	$post_count = 0;
      	$post_likes_count = 0;
      	$post_dislikes_count = 0;

      	$comment_count = 0;
      	$comment_likes_count = 0;
      	$comment_dislikes_count = 0;

      	$reply_count = 0;
      	$reply_likes_count = 0;
      	$reply_dislikes_count = 0;

      	$loaded_count = 0;

      	//get the user's info
      	$stmt = $dbc->prepare("SELECT u.username, u.info, YEAR(u.date_joined), MONTHNAME(u.date_joined), DAY(u.date_joined), u.profile_image, t.tag_name, t.tag_id FROM user AS u JOIN tag AS t ON u.tag_id = t.tag_id WHERE user_id = ?");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      $stmt->bind_result($username, $info, $year_joined, $month_joined, $day_joined, $profile_image, $user_forum_name, $user_forum_id);
	      $stmt->fetch();
	      $stmt->free_result();
	      $stmt->close();

	      //if no profile picture, set the default
	      $profile_image = ($profile_image == null) ? 'user_icon.png' : $profile_image;

	      //get the number of post the user has
	      $stmt = $dbc->prepare("SELECT COUNT(*) FROM post WHERE user_id = ?");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      $stmt->bind_result($post_count);
	      $stmt->fetch();
	      $stmt->free_result();
	      $stmt->close();

	      //get the number of comments the user has
	      $stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND parent_comment_id IS NULL");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      $stmt->bind_result($comment_count);
	      $stmt->fetch();
	      $stmt->free_result();
	      $stmt->close();

	      //get the number replies the user has
	      $stmt = $dbc->prepare("SELECT COUNT(*) FROM comment WHERE user_id = ? AND parent_comment_id IS NOT NULL");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      $stmt->bind_result($reply_count);
	      $stmt->fetch();
	      $stmt->free_result();
	      $stmt->close();

	      //get the posts likes/dislikes
	      $stmt = $dbc->prepare("SELECT COUNT(pl.is_like) AS count, if(pl.is_like = 0, 'dislike_count', 'like_count') AS islike FROM post AS p JOIN post_likes AS pl ON p.post_id = pl.post_id WHERE p.user_id = ? GROUP BY pl.is_like");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows > 0) {
            $stmt->bind_result($count, $type);
            
            while($stmt->fetch()) {
            	if($type == 'like_count') {
            		$post_likes_count = $count;
            	} else if($type == 'dislike_count') {
            		$post_dislikes_count = $count;
            	}
          }
        }
	      $stmt->free_result();
	      $stmt->close();

	      //get the comment likes/dislikes
	      $stmt = $dbc->prepare("SELECT COUNT(cl.is_like) AS count, if(cl.is_like = 0, 'dislike_count', 'like_count') AS islike FROM comment AS c JOIN comment_likes AS cl ON c.comment_id = cl.comment_id WHERE c.user_id = ? AND c.parent_comment_id IS NULL GROUP BY cl.is_like");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows > 0) {
            $stmt->bind_result($count, $type);
            
            while($stmt->fetch()) {
            	if($type == 'like_count') {
            		$comment_likes_count = $count;
            	} else if($type == 'dislike_count') {
            		$comment_dislikes_count = $count;
            	}
          }
        }
	      $stmt->free_result();
	      $stmt->close();

	      //get the replies likes/dislikes
	      $stmt = $dbc->prepare("SELECT COUNT(cl.is_like) AS count, if(cl.is_like = 0, 'dislike_count', 'like_count') AS islike FROM comment AS c JOIN comment_likes AS cl ON c.comment_id = cl.comment_id WHERE c.user_id = ? AND c.parent_comment_id IS NOT NULL GROUP BY cl.is_like");
	      $stmt->bind_param("d", $user_id);
	      $stmt->execute();
	      $stmt->store_result();
	      if($stmt->num_rows > 0) {
            $stmt->bind_result($count, $type);
            
            while($stmt->fetch()) {
            	if($type == 'like_count') {
            		$reply_likes_count = $count;
            	} else if($type == 'dislike_count') {
            		$reply_dislikes_count = $count;
            	}
          }
        }
	      $stmt->free_result();
	      $stmt->close();

	      //calculate the user's score
	      //get the posts score
	      $post_score = ($post_likes_count * 3) - ($post_dislikes_count * 3);
	      $comment_score = ($comment_likes_count * 3) - ($comment_dislikes_count * 3);
	      $reply_score = ($reply_likes_count * 3) - ($reply_dislikes_count * 3);

	      $profile_score = $post_score + $comment_score + $reply_score;
	      $profile_score_positive = ($profile_score > 0) ? true : false;


    ?>
    <div class="profile-outer-container">
      <div class="profile-intro">
        <div class="profile-name-pic">
          <div class="profile-pic-box">
            <img class="profile-pic" src="images/<?php echo $profile_image; ?>">
          </div>
          <div class="profile-credentials">
            <div class="profile-username">
              <h1 class="page-title">
                <span><?php echo ucfirst($username); ?></span>
                <span title="Profile Score" class="profile-score <?php if($profile_score_positive) { echo "positive"; } ?>"><?php echo $profile_score; ?></span>
              </h1>
            </div>
            <div class="profile-info">
              <span><?php echo $info; ?></span>
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
    						<span class="value"><a href="tag.php?id=<?php echo $user_forum_id; ?>"><?php echo "$user_forum_name"; ?></a></span>
    					</li>
    				</ul>
    			</section>
    			<section class="profile-breakdown-section">
    				<ul class="section-details">
    					<li>
    						<span class="count" data-bind="text: totalPosts"></span>
                <span data-bind="text: ' Question'+(totalPosts() > 1 ? 's' : '')"></span>
    					</li>
    					<li>
                <span class="count" data-bind="text: totalComments"></span>
    						<span data-bind="text: ' Answer'+(totalComments() > 1 ? 's' : '')"></span>
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
              <li class="feed-list-item" data-bind="css: {active: !postsHidden()}">
                <a href="#" data-bind="event: {click: showPosts}">
                  <span>Questions</span>
                  <span class="item-count" data-bind="text: totalPosts"></span>
                </a>
              </li>
              <li class="feed-list-item" data-bind="css: {active: !commentsHidden()}">
                <a href="#" data-bind="event: {click: showComments}">
                  <span>Answers</span>
                  <span class="item-count" data-bind="text: totalComments"></span>
                </a>
              </li>
              <li class="feed-list-item">
                <a href="#">
                  <span>Following</span>
                </a>
              </li>
              <li class="feed-list-item">
                <a href="#">
                  <span>Followers</span>
                </a>
              </li>
            </ul>
          </nav>
          <div class="feed-content">
            <div class="loading-box" title="Loading..." data-bind="css: {hide: !loading() }">
              <i class="fas fa-spinner loading-icon"></i>
            </div>
            <!-- Posts -->
            <div data-bind="if: !postsHidden()">
              <h3 data-bind="text: postHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-posts" data-bind=" foreach: posts ">
                <div class="post-preview">
                  <div class="post-heading-box">
                    <div class="post-meta">
                      <span data-bind="text: postScore, css: {positive: postScore > 1, negative: postScore <= 1, score: true}"></span>
                      <span data-bind="if: hasImage, attr: {title: 'This question has at least one image'}">
                        <span data-bind="html: imageIcon()"></span>
                      </span>
                    </div>
                    <div class="post-heading">
                      <a class="post-heading-text" data-bind="attr: {href: `question.php?id=${postID}`}, text: postHeading"></a>
                      <div class="post-tags-box" data-bind=" foreach: tags ">
                        <a data-bind="attr: { href: `tag.php?id=${tag_id}`, class: `${tag_type}-tag post-tag` }, text: tag_name"></a>
                      </div>
                    </div>
                  </div>
                  <ul class="attributes">
                    <li class="attribute">
                      <span class="count" data-bind="text: postLikes"></span>
                      <span data-bind="text: ` Like${postLikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li class="list-divider"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: postDislikes"></span>
                      <span data-bind="text: ` Dislike${postDislikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li class="list-divider"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: commentCount"></span>
                      <span data-bind="text: ` Answer${commentCount>1 ? 's' : ''}`"></span>
                    </li>
                  </ul>
                  <div data-bind="attr: {class: 'details'}">
                    <p>
                      <span class="text" data-bind="text: postBodyStart(postBody)"></span>
                      <span data-bind="if: postBodyIsMore() && !postMoreShown()">
                        <span data-bind="text: dittoText"></span>
                      </span>
                      <a href="#" data-bind="if: postBodyMore() && !postMoreShown(), event: {click: postShowMore}">
                        <span data-bind="text: moreText" class="more-text" title="Show More"></span>
                      </a>
                      <span data-bind="if: postBodyMore() && postMoreShown()">
                        <span class="text" data-bind="text: postBodyMore"></span>
                      </span>
                    </p>

                  </div>
                  <div>
                    <span class="when" data-bind=" text: ` &ndash; ${howLong}`"></span>
                  </div>
                </div>
              </div>
            </div>
            <!-- End of Post -->

            <!-- Comments -->
            <div data-bind="if: !commentsHidden()">
              <h3 data-bind="text: commentHead, attr: {class: 'feed-head'}"></h3>
              <div class="profile-posts" data-bind=" foreach: comments ">
                <div class="post-preview">
                  <div class="post-heading-box">
                    <div class="post-meta">
                      <span data-bind="text: commentScore, css: {positive: commentScore > 1, negative: commentScore <= 1, score: true}"></span>
                    </div>
                    <div class="post-heading">
                      <a class="post-heading-text" data-bind="attr: {href: `question.php?id=${postID}`}, text: postHeading"></a>
                      <div class="post-tags-box" data-bind=" foreach: tags ">
                        <a data-bind="attr: { href: `tag.php?id=${tag_id}`, class: `${tag_type}-tag post-tag` }, text: tag_name"></a>
                      </div>
                    </div>
                  </div>
                  <span data-bind="html: quote()"></span>
                  <ul class="attributes min">
                    <li class="attribute">
                      <span class="count" data-bind="text: commentLikes"></span>
                      <span data-bind="text: ` Like${commentLikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li class="list-divider"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: commentDislikes"></span>
                      <span data-bind="text: ` Dislike${commentDislikes>1 ? 's' : ''}`"></span>
                    </li>
                    <li class="list-divider"></li>
                    <li class="attribute">
                      <span class="count" data-bind="text: replyCount"></span>
                      <span data-bind="text: ` ${replyCount>1 ? 'Replies' : 'Reply'}`"></span>
                    </li>
                  </ul>
                  <div data-bind="attr: {class: 'details'}">
                    <p>
                      <span class="text" data-bind="text: commentBodyStart(commentBody)"></span>
                      <span data-bind="if: commentBodyIsMore() && !commentMoreShown()">
                        <span data-bind="text: dittoText"></span>
                      </span>
                      <a href="#" data-bind="if: commentBodyMore() && !commentMoreShown(), event: {click: commentShowMore}">
                        <span data-bind="text: moreText" class="more-text" title="Show More"></span>
                      </a>
                      <span data-bind="if: commentBodyMore() && commentMoreShown()">
                        <span class="text" data-bind="text: commentBodyMore"></span>
                      </span>
                    </p>

                  </div>
                  <div>
                    <span class="when" data-bind=" text: ` &ndash; ${howLong}`"></span>
                  </div>
                </div>
              </div>
              <div data-bind="if: loadedComments() < totalComments() && loadedComments() > 0">
                <div class="view-more-box">
                  <a href="#" data-bind="event: { click: loadComments.bind($data, true)  }, text: 'view more answers'" class="view-more view-more-big"></a>
                  <span class="track track-big" data-bind="text: commentsTrack()"></span>
                </div>          
              </div>
              <span data-bind="if: loadedComments() > 20 && loadedComments() == totalComments()">
                <span class="load-more-finished" data-bind="text: 'No more answers to show'"></span>
              </span>
            </div>
            <!-- End of Comments -->


          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      <?php
        $payload = array(
          'total_posts' => $post_count,
          'total_comments' => $comment_count,
          'total_replies' => $reply_count,
          'profile_id' => $user_id
        );
      	echo "var json_payload = " . json_encode($payload) . ";";
      ?>

      function quote() {
        return '<i class="fas fa-quote-left quote-icon"></i>';
      }
      function imageIcon() {
        return '<i class="fas fa-images images-icon"></i>';
      }

      function PostModel(data) {
        var self = this;
        self.postHeading = data.post_heading;
        self.postBody = data.post_body;
        self.postTags = data.post_tags;
        self.postScore = data.post_score;
        self.postLikes = data.post_likes_count;
        self.postDislikes = data.post_dislikes_count;
        self.postID = data.post_id;
        self.howLong = data.how_long;
        self.postDate = data.post_date;
        self.postUserName = data.post_user_name;
        self.postUserID = data.post_user_id;
        self.commentCount = data.post_comments_count;
        self.hasImage = data.post_has_image;
        self.postBodyIsMore = ko.observable(false);
        self.postBodyMore = ko.observable('');
        self.postMoreShown = ko.observable(false);
        self.dittoText = '...';
        self.moreText = '[more]';
        self.postShowMore = function() {
          self.postMoreShown(true);
        };
        self.postBodyStart = function(str) {
          var newStr = self.postBody;
          if(str.length > 210) {
            self.postBodyIsMore(true);
            //get the the first 197 characters
            newStr = str.substring(0, 197);
            //save the remainder
            self.postBodyMore(str.substring(197))
          } 
          return newStr;
        }
        self.tags = ko.observableArray(data.tags);
      }

      function CommentModel(data) {
        var self = this;
        self.postHeading = data.post_heading;
        self.commentBody = data.comment_body;
        self.postTags = data.post_tags;
        self.commentScore = data.comment_score;
        self.commentLikes = data.comment_likes_count;
        self.commentDislikes = data.comment_dislikes_count;
        self.commentID = data.comment_id;
        self.postID = data.post_id;
        self.howLong = data.how_long;
        self.commentDate = data.comment_date;
        self.replyCount = data.replies_count;
        self.hasImage = data.post_has_image;
        self.commentBodyIsMore = ko.observable(false);
        self.commentBodyMore = ko.observable('');
        self.commentMoreShown = ko.observable(false);
        self.dittoText = '...';
        self.moreText = '[more]';
        self.commentShowMore = function() {
          self.commentMoreShown(true);
        };
        self.commentBodyStart = function(str) {
          var newStr = self.commentBody;
          if(str.length > 210) {
            self.commentBodyIsMore(true);
            //get the the first 197 characters
            newStr = str.substring(0, 197);
            //save the remainder
            self.commentBodyMore(str.substring(197))
          } 
          return newStr;
        }
        self.tags = ko.observableArray(data.tags);
      }

      function AppViewModel() {
        var self = this;
        self.loading = ko.observable(false);

        /* Posts */
        self.posts = ko.observableArray([]);
        self.postsFetched = ko.observable(false);
        self.loadedPosts = ko.observable(self.posts().length);
        self.totalPosts = ko.observable(json_payload.total_posts);
        self.profileID = ko.observable(json_payload.profile_id);
        self.postHead = ko.computed(function(){
          return `${self.totalPosts()} Question${self.totalPosts() > 1 ? 's' : ''}`;
        });
        self.postsTrack = ko.computed(function(){
          return `${self.loadedPosts()} of ${self.totalPosts()}`;
        });
        self.postsHidden = ko.observable(true);
        self.postsHead = function() {
          return `${self.totalPosts()} ${self.totalPosts() > 1 ? 'Question' : 'Question'}`;
        };
        self.postsLoading = ko.observable(false);
        self.showPosts = function() {
          if(self.postsFetched){
            self.toggleLoading('post', false);
          } else {
            self.loadPosts();
          }
        };

        /* Comments */
        self.comments = ko.observableArray([]);
        self.commentsFetched = ko.observable(false);
        self.loadedComments = ko.observable(self.comments().length);
        self.totalComments = ko.observable(json_payload.total_comments);
        self.commentsTrack = ko.computed(function(){
          return `${self.loadedComments()} of ${self.totalComments()}`;
        });
        self.commentsHidden = ko.observable(true);
        self.commentsLoading = ko.observable(true);
        self.commentHead = ko.computed(function(){
          return `${self.totalComments()} Answer${self.totalComments() > 1 ? 's' : ''}`;
        });
        self.commentsHead = function() {
          return `${self.totalPosts()} ${self.totalPosts() > 1 ? 'Answers' : 'Answer'}`;
        };
        self.showComments = function() {
          if(self.commentsFetched()){
            self.toggleLoading('comment', false);
          } else {
            self.loadComments();
          }
        };




        self.postReset = function(data) {
          //convert each post to a post model and store in an array
          var mappedPosts = data.posts.map(post => new PostModel(post));
          //update the observable array
          self.posts.push(...mappedPosts);
          self.totalPosts(data.total_posts);
        };
        self.loadPosts = function(){
          self.toggleLoading('post', true);
          var start = 0;
          if(self.loadedPosts() > 0) {
            start = self.loadedPosts();
          } 
          var payload = { profile_id: self.profileID, start: start};
          $.get('profile_posts.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //flag to indicate the posts have been fetched
                  self.postsFetched(true);
                  self.toggleLoading('post', false);
                  self.postReset(data.message);
                }
              } else {
                self.toggleLoading('post', false);
              }
            }, "json"
          );
        };


        self.commentReset = function(data) {
          //convert each post to a post model and store in an array
          var mappedComments = data.comments.map(comment => new CommentModel(comment));
          //update the observable array
          self.comments.push(...mappedComments);
          self.totalComments(data.total_comments);
          self.loadedComments(self.comments().length);
        };

        self.loadComments = function(viewMore = false){
          //if the view more  is clicked
          self.toggleLoading('comment', true, viewMore);
          var start = 0;
          if(self.loadedComments() > 0) {
            start = self.loadedComments();
          } 
          var payload = { profile_id: self.profileID, start: start};
          $.get('profile_comments.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //flag to indicate the comments have been fetched
                  self.commentsFetched(true);
                  self.toggleLoading('comment', false);
                  self.commentReset(data.message);
                }
              } else {
                self.toggleLoading('comment', false);
              }
            }, "json"
          );
        };

        self.toggleLoading = function(type, is_loading, viewMore = false){
          self.loading(is_loading);
          switch (type) {
            case 'post':
              if(!viewMore) {
                self.postsHidden(is_loading);
              }
              self.postsLoading(is_loading);
              break;
            case 'comment':
              if(!viewMore) {
                self.commentsHidden(is_loading);
              }
              self.commentsLoading(is_loading);
              break;
          }

          //if a block is being displayed, hide the rest
          if(is_loading == false) {
            switch(type) {
              case 'post':
                self.commentsHidden(true);
                self.commentsLoading(false);
                break;
              case 'comment':
                self.postsHidden(true);
                self.postsLoading(false);
                break;
            }
          }
        };
        //Onload load the posts
        $(document).ready(function() {
          self.loadPosts();
        });
      }
      var model = new AppViewModel();
      ko.applyBindings(model);

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