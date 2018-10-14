<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Topics - ForaShare';
  $page = TOPIC;
  include('includes/header.php');
?>
<!-- CONTAINER -->
<div class="container-fluid content-container">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="main-container">

      <div id="question_filter" class="search-box-filter">
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

          <div class="dropdown filter-order-dropdown" id="user_order_dropdown">
            <a class="dropdown-toggle" href="#" id="questionOrderLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">scores</a>
            <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="questionOrderLink" id="question_order_options">
              <li class="dropdown-item active">scores</li>
              <li class="dropdown-item">newer users</li>
              <li class="dropdown-item">older users</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- List of Users -->
      <div class="lists-box" id="users_list_grid">
        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/a.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">Quadrsd</a>
            <a href="#" class="user-preview-forum">Nigeria</a>
            <span class="user-preview-score">2,342</span>
          </div>
        </div>

        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/c.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">Damilola</a>
            <a href="#" class="user-preview-forum">Tanzania</a>
            <span class="user-preview-score">2342</span>
          </div>
        </div>

        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/b.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">Pharell</a>
            <a href="#" class="user-preview-forum">Canada</a>
            <span class="user-preview-score">342</span>
          </div>
        </div>

        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/no_pic.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">Qnauf</a>
            <a href="#" class="user-preview-forum">Bulgaria</a>
            <span class="user-preview-score">221</span>
          </div>
        </div>

        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/b.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">William993_</a>
            <a href="#" class="user-preview-forum">Canada</a>
            <span class="user-preview-score">121</span>
          </div>
        </div>

        <div class="user-preview row">
          <div class="user-preview-image-box">
            <a href="#"><img src="images/no_pic.png" class="user-preview-image"></a>
          </div>
          <div class="user-preview-details">
            <a href="#" class="user-preview-name">Nassier</a>
            <a href="#" class="user-preview-forum">Democratic Republic of Congo</a>
            <span class="user-preview-score">77</span>
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