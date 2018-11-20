<?php
	include('includes/generic_includes.php');
	//if the user is not logged in, redirect to questions page
	if($loggedin == false) {
    header("Location: questions.php");
    exit(); // Quit the script.
  }
  $page_title = 'Home &ndash; ForaShare';
  $page = HOME;
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
  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>