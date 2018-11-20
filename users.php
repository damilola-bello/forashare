<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Users &ndash; ForaShare';
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
    <div class="main-container">

      <div id="user_filter" class="search-box-filter">
        <div class="row filter-forum-list-box">
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
          <button class="btn question-filter-btn"><i class="fas fa-search"></i></button>
        </div>
      </div>

      <!-- Question filter header -->
      <div id="question_filter_header" class="filter-header">
        <p class="filter-title">French Polynesia forum Users</p>
        <div class="row filter-controls" id="question_order_box">
          <input type="text" class="form-control filter-input" placeholder="Filter User">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="userSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">scores</a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="userSortLink" id="user_sort">
              <li class="dropdown-item active">scores</li>
              <li class="dropdown-item">newer users</li>
              <li class="dropdown-item">older users</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- List of Users -->
      <div class="lists-box" id="users_list_grid">
        <div class="preview row">
          <div class="preview-image-box">
            <a href="#"><img src="images/a.png" class="preview-image"></a>
          </div>
          <div class="preview-details">
            <a href="#" class="user-preview-name">Quadrsd</a>
            <a href="#" class="user-preview-forum">Nigeria</a>
            <span class="user-preview-score">2,342</span>
          </div>
        </div>

        <div class="preview row">
          <div class="preview-image-box">
            <a href="#"><img src="images/c.png" class="preview-image"></a>
          </div>
          <div class="preview-details">
            <a href="#" class="user-preview-name">Damilola</a>
            <a href="#" class="user-preview-forum">Tanzania</a>
            <span class="user-preview-score">2342</span>
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