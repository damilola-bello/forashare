<?php
	include('includes/generic_includes.php');
	//if the user is not logged in, redirect to questions page
	if($loggedin == false) {
    header("Location: login.php");
    exit(); // Quit the script.
  }
  $page_title = 'Ask Question &ndash; ForaShare';
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
      <a href="#" class="create-link">Create Tag</a>
    </div>

    <div class="page-header">
      <h1 class="page-title">New Question</h1>
    </div>

    <div id="ask_question_box">
      <div class="info-section">
        <div class="info-section-box">
          <div class="info-content-toggle open">
          <span>Help</span>
        </div>
        <div class="info-content">
      
          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Heading</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Required.</span><br>The heading is a preview of your question, it is what users will see. Make it short, concise and straight to the point.</p>
            </div>
          </div>

          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Question</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Required.</span><br>What you want to ask.</p>
            </div>
          </div>

          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Photo(s)</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Optional.</span> The picture(s) must be either in gif, jpeg or png format.<br><span class="max">You can upload a maximum of two pictures. Each picture with a max size of 1MB.</span></p>
            </div>
          </div>

          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Country</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Required.</span><br>You can only post a question to a country at a time.</p>
            </div>
          </div>

          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Topics</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Required.</span><br>Again, this forum is issues-based and the predefined topics are the talking points we want our users to focus on. The topics are also useful when searching for questions so try to attach topic(s) that your question falls under.<br><span class="max">You must attach at least one topic to your question, with a maximum of three.</span></p>
            </div>
          </div>

          <div class="subcontent">
            <div class="info-subcontent-title">
              <span class="info-subcontent-title-text">Custom Tags</span>
            </div>
            <div class="info-subcontent">
              <p><span class="optional">Optional.</span> You can choose to post your question under a user-defined tags. This streamlines your question and makes it more specific. It is advisable to add at least a custom tag to your question.<br><span class="max">You can add a maximum of three custom tags to your question.</span></p>
            </div>
          </div>
        </div>  
        </div>
        
      </div>

      <!-- Form for Posting a Question -->

      <div id="ask_question_form">
        <div class="loading-box ask-question-loading hide" title="Loading...">
          <i class="fas fa-spinner loading-icon"></i>
        </div>
        <div class="form-group form-error"></div>
        <div class="form-group">
          <div class="form-label-box">
            <span class="form-lbl">Question Heading <span class="important-star" title="Required Field">*</span></span>
            <div class="progress-input-box">
              <span class="progress-char-left"></span>
              <svg class="progress-input">
                <circle class="progress-meter" />
                <circle class="progress-value" stroke-dasharray="3.77rem" stroke-dashoffset="3.77rem" />
              </svg>
            </div>
          </div>
          <input type="text" name="question_heading" class="form-control question-heading progressable-input" placeholder="E.g. What is Nigerian Pounded yam?" data-max="100" data-last-length="0">
          <span class="question-heading-err error-lbl"></span>
        </div>

        <div class="form-group">
          <div class="form-label-box">
            <span class="form-lbl">Question <span class="important-star" title="Required Field">*</span></span>
            <div class="progress-input-box">
              <span class="progress-char-left"></span>
              <svg class="progress-input">
                <circle class="progress-meter" />
                <circle class="progress-value" stroke-dasharray="3.77rem" stroke-dashoffset="3.77rem" />
              </svg>
            </div>
          </div>
          <textarea name="question_body" rows="7" data-max="1500" data-last-length="0" class="form-control question-body progressable-input" placeholder="Type in your question here."></textarea>
          <span class="question-body-err error-lbl"></span>
        </div>
        
        <div class="form-group">
          <div class="form-label-box">
            <span class="form-lbl">Country<span class="important-star" title="Required Field">*</span> </span>
            <i class="far fa-question-circle label-info" data-toggle="tooltip" data-placement="top" title="Choose a country you want your question to be posted to"></i>
          </div>
          <?php
            $user_id = $_SESSION['user_id'];
            //get the user's forum
            $stmt = $dbc->prepare("SELECT tag_id FROM user WHERE user_id = ?");
            $stmt->bind_param("d", $user_id);
            $stmt->execute();
            $stmt->bind_result($user_forum_id);
            $stmt->fetch();
            $stmt->close();

            $query = "SELECT t.tag_name, t.tag_id, f.alpha_code FROM forum_details AS f JOIN tag AS t ON t.tag_id = f.tag_id JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'forum' ORDER BY t.tag_name ASC";
            $r = mysqli_query($dbc, $query);

            echo '<select class="form-control question-forum" name="question_forum">';
            echo '<option value="empty">-- Choose Country --</option>';
            while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
              $alpha_code = $row['alpha_code'];
              $id = $row['tag_id'];
              echo "<option value='$alpha_code'" . (($id == $user_forum_id) ? ' selected' : ''). ">" . $row['tag_name'] . "</option>"; 
            }
            echo '</select>';
          ?>
          <span class="question-forum-err error-lbl"></span>
        </div>

        <div class="form-group">
          <div class="row_">
            <div class="dropdown checkbox-dropdown-box" id="ask_question_tags_dropdown">
              <a class="dropdown-toggle form-control" href="#" id="askQuestionTagLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span>topics</span>&nbsp;&nbsp;<i class="fas fa-tags"></i>
              </a>

              <ul class="dropdown-menu menu-filter dropdown-menu-left checkbox-dropdown" aria-labelledby="askQuestionTagLink">
                <?php
                  //Select the default tags
                  $query = "SELECT tag_id, tag_name FROM tag AS t JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'default_tag' ORDER BY t.tag_name ASC";
                  $r = mysqli_query($dbc, $query);
                  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                    $tag_name = strtolower($row['tag_name']);
                    $tag_id = $row['tag_id'];
                    echo "<li class='dropdown-item' data-tag='$tag_name' data-tag-check='0'><span class='ask-question-tag-name' tabindex='-1'><input class='question-tags' value='$tag_id' type='checkbox'>&nbsp; $tag_name</span></li>";
                  }
                ?>
              </ul>
            </div>
            <div class="form-label-box">
              <i class="far fa-question-circle label-info" data-toggle="tooltip" data-placement="top" title="Add at least one tag to your question"></i>
            </div>
            <span class="important-star" title="Required Field">*</span>
          </div>
          <span class="question-tag-err error-lbl"></span>
        </div>

        <div class="form-group">
          <div class="d-flex justify-content-between align-items-center add-photos-lbl">
            <span class="form-lbl add-photos-toggler" onclick="togglePhotoBox(this);">
              <span>
                <span>Add Photos</span>
                <i class="far fa-image image-icon"></i>
              </span>
            </span>
            <label class="switch">
              <input type="checkbox" onchange="addPhotosToggle(this);">
              <span class="slider"></span>
            </label>
          </div>
          <div class="image-upload-box hide" id="image_upload_box">
            <div class="image-upload-preview" id="ask_img_1_box">
              <div class="upload-preview-image">
              </div>
              <span class="upload-btn">
                <input type="file" name="img1_ask" class="upload-image" id="ask_img_1" accept="image/png, image/jpeg, image/gif" value="" onchange="uploadQuestionImg(this);">
                <small class="upload-prompt-text">Select Image</small>
              </span>
            </div>

            <div class="image-upload-preview hide" id="ask_img_2_box">
              <div class="upload-preview-image">
              </div>
              <span class="upload-btn">
                <input type="file" name="img1_ask" class="upload-image" id="ask_img_2" accept="image/png, image/jpeg, image/gif" value="" onchange="uploadQuestionImg(this);">
                <small class="upload-prompt-text">Select Image</small>
              </span>
            </div>
          </div>
        </div>

        <div class="form-group">
          <span class="form-lbl">Add Custom Tags</span>
          <div class="add-topic-box">
            <input type="text" placeholder="Find Custom Tag" class="form-control">
            <div></div>
          </div>
        </div>

        <div class="form-group d-flex justify-content-end">
          <button class="btn submit-btn" onclick="askQuestionSubmit();">Post</button>
        </div>

      </div>      
    </div>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>