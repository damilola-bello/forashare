<!-- SIDEBAR -->
<div class="sidebar">
  <nav class="sidebar-navigation" role="navigation">
    <ul class="sidebar-navlist">
      <!-- Display the home nav only when the user is logged in -->
      <?php
        if($loggedin == true) {
      ?>
        <li class="home-nav<?php if($page == HOME) { echo ' active-nav'; } ?>">
          <a href="./index.php">Home</a>
        </li>
      <?php
        }
      ?>
      <li class="countries-nav<?php if($page == FORUM) { echo ' active-nav'; } ?>">
        <a href="./countries.php">Countries</a>
      </li>
      <li class="tags-nav<?php if($page == TAG) { echo ' active-nav'; } ?>">
        <a href="./tags.php">Tags</a>
      </li>
      <li class="users-nav<?php if($page == USER) { echo ' active-nav'; } ?>">
        <a href="./users.php">Users</a>
      </li>
      <li class="questions-nav<?php if($page == QUESTION) { echo ' active-nav'; } ?>">
        <a href="./questions.php">Questions</a>
      </li>
    </ul>
  </nav>
</div>
<!-- END OF SIDEBAR -->