<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Forums &ndash; ForaShare';
  $page = FORUM;
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

      <div id="forum_filter" class="search-box-filter">
        <div class="row filter-search-box">
          <div class="dropdown checkbox-dropdown-box" id="question_tags_dropdown">

            <a class="dropdown-toggle" href="#" id="regionsLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span>region</span></a>

            <ul class="dropdown-menu dropdown-menu-right checkbox-dropdown" aria-labelledby="regionsLink">
              <?php
                $query = "SELECT region_id, region_name FROM region ORDER BY region_name ASC";
                $r = mysqli_query($dbc, $query);
                while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                  $region_name = $row['region_name'];
                  $region_id = $row['region_id'];
                  echo "<li class='dropdown-item' data-tag='$region_id' data-tag-check='1'><span class='region-tag-name' tabindex='-1'><input type='checkbox' checked='checked'>&nbsp; $region_name</span></li>";
                }
              ?>
            </ul>
          </div>
          <button class="btn question-filter-btn"><i class="fas fa-search"></i></button>
        </div>
      </div>

      <!-- Question filter header -->
      <div class="filter-header">
        <p class="filter-title">All forums</p>
        <div class="row filter-controls">
          <input type="text" class="form-control filter-input" placeholder="Filter Forum">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle" href="#" id="forumSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">questions</a>
            <ul class="dropdown-menu dropdown-menu-right sort-dropdown" aria-labelledby="forumSortLink">
              <li class="dropdown-item active">questions</li>
              <li class="dropdown-item">users</li>
              <li class="dropdown-item">topics</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- List of Users -->
      <div class="lists-box" id="forums_list_grid">
        <?php
            $query = "SELECT forum_name, alpha_code FROM forum ORDER BY forum_name ASC";
            $r = mysqli_query($dbc, $query);

            while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
              $alpha_code = $row['alpha_code'];
              $forum_name = $row['forum_name'];

              echo "<div class='preview row'>";
              echo "<div class='preview-image-box'>";
              echo "<a href='#'><img src='images/forum-128/$alpha_code.png' class='preview-image'></a>";
              echo "</div>";
              echo "<div class='preview-details'>";
              echo "<a href='#' class='preview-name'>$forum_name</a>";

              echo "<span class='forum-preview-questions preview-count-box'>";
              echo "<span class='preview-count'>1,222,456</span>";
              echo "<span>Questions</span>";
              echo "</span>";

              echo "<span class='forum-preview-users preview-count-box'>";
              echo "<span class='preview-count'>22,456</span>";
              echo "<span>Users</span>";
              echo "</span>";

              echo "<span class='forum-preview-topics preview-count-box'>";
              echo "<span class='preview-count'>22,456</span>";
              echo "<span>Topics</span>";
              echo "</span>";

              echo "</div>";
              echo "</div>";
            }
          ?>
      </div>

    </div>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>