<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Questions &ndash; ForaShare';
  $page = QUESTION;
  include('includes/header.php');
?>
<!-- CONTAINER -->
<div class="container-fluid content-container">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="ask-question-link-box row">
      <a href="#" class="ask-question-link">Ask Question</button>
    </div>
    <div class="main-container">
      <!-- Displays the filter area to filter questions according to forum and tags -->
      <div id="question_filter" class="search-box-filter">
        <div id="question_forum_list_box" class="row filter-forum-list-box">
          <?php
            $query = "SELECT forum_name, alpha_code FROM forum ORDER BY forum_name ASC";
            $r = mysqli_query($dbc, $query);

            echo '<select class="form-control filter-forum-list">';
            echo '<option value="globe">All Forums</option>';
            while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
              $alpha_code = $row['alpha_code'];
              echo "<option value='$alpha_code'>" . $row['forum_name'] . "</option>";
            }
            echo '</select>';
          ?>
        </div>
        <div class="row filter-search-box">
          <div class="dropdown checkbox-dropdown-box" id="question_tags_dropdown">

            <a class="dropdown-toggle" href="#" id="questionTagLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span>tag</span>&nbsp;&nbsp;<i class="fas fa-tags"></i></a>

            <ul class="dropdown-menu dropdown-menu-right checkbox-dropdown" aria-labelledby="questionTagLink">
              <li class="dropdown-item" data-tag="religion" data-tag-check="1"><span class="question-tag-name" tabindex="-1"><input type="checkbox" checked="checked">&nbsp; Religion</span></li>
              <li class="dropdown-item" data-tag="culture" data-tag-check="0"><span class="question-tag-name" tabindex="-1"><input type="checkbox">&nbsp; Culture</span></li>
              <li class="dropdown-item" data-tag="politics" data-tag-check="0"><span class="question-tag-name" tabindex="-1"><input type="checkbox">&nbsp; Politics</span></li>
              <li class="dropdown-item" data-tag="education" data-tag-check="1"><span class="question-tag-name" tabindex="-1"><input type="checkbox" checked="checked">&nbsp; Education</span></li>
              <li class="dropdown-item" data-tag="sports" data-tag-check="0"><span class="question-tag-name" tabindex="-1"><input type="checkbox">&nbsp; Sports</span></li>
              <li class="dropdown-item" data-tag="others" data-tag-check="1"><span class="question-tag-name" tabindex="-1"><input type="checkbox" checked="checked">&nbsp; Others</span></li>
            </ul>
          </div>

          <button class="btn question-filter-btn"><i class="fas fa-search"></i></button>
        </div>
      </div>

      <!-- Question filter header -->
      <div id="question_filter_header" class="filter-header">
        <p class="filter-title">French Polynesia forum Questions</p>

        <div class="row filter-controls">
          <input type="text" class="form-control filter-input" placeholder="Filter Question">

          <div class="dropdown filter-order-dropdown" id="question_order_dropdown">
            <a class="dropdown-toggle" href="#" id="questionSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">points</a>
            <ul class="dropdown-menu dropdown-menu-right sort-dropdown" aria-labelledby="questionSortLink" id="question_sort">
              <li class="dropdown-item active">points</li>
              <li class="dropdown-item">oldest</li>
              <li class="dropdown-item">newest</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- List of Questions -->
      <div class="lists-box">
        <div class="question-preview">
          <span class="question-score positive-score">110</span>
          <div class="question-preview-content">
            <span class="question-preview-text">
              <a href="#">What is the difference between a saree and a normal drape gown? 
              Is Saree also only local to the indian culture or it is worn beyond the shores of Indian?</a>
            </span>
            <span class="question-preview-details">
              <span class="question-preview-answers"><strong>576</strong> Answers</span>
              <span class="question-preview-date">Asked May 30'16 11:32</span>
            </span>
          </div>
        </div>
        
        <div class="question-preview">
          <span class="question-score positive-score">1110</span>
          <div class="question-preview-content">
            <span class="question-preview-text">
              <a href="#">How are the transportation system like in Nigeria? I learnt it can be chaotic at time with the bad roads especially when it rains. If this is the case, what is the safests and most preferable mode of transportation in Nigeria?</a>
            </span>
            <span class="question-preview-details">
              <span class="question-preview-answers"><strong>126</strong> Answers</span>
              <span class="question-preview-date">Asked Dec 18 '18 07:00</span>
            </span>
          </div>
        </div>

        <div class="question-preview">
          <span class="question-score negative-score">-9</span>
          <div class="question-preview-content">
            <span class="question-preview-text">
              <a href="#">Which country is the safest in Africa?</a>
            </span>
            <span class="question-preview-details">
              <span class="question-preview-answers"><strong>126</strong> Answers</span>
              <span class="question-preview-date">Asked Feb 17 '18 17:19</span>
            </span>
          </div>
        </div>

      </div>

    </div>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>