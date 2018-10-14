<!-- SIDEBAR -->
<div class="sidebar">
  <nav class="sidebar-navigation" role="navigation">
    <ul class="sidebar-navlist">
      <!-- Display the home nav only when the user is logged in -->
      <?php
        if($loggedin == true) {
      ?>
        <li <?php if($page == HOME) { echo "class='active-nav'"; } ?>>
          <a href="./index.php">Home</a>
        </li>
      <?php
        }
      ?>
      <li <?php if($page == FORUM) { echo "class='active-nav'"; } ?>>
        <a href="./forums.php">Forums</a>
      </li>
      <li <?php if($page == TOPIC) { echo "class='active-nav'"; } ?>>
        <a href="./topics.php">Topics</a>
      </li>
      <li <?php if($page == USER) { echo "class='active-nav'"; } ?>>
        <a href="./users.php">Users</a>
      </li>
      <li <?php if($page == QUESTION) { echo "class='active-nav'"; } ?>>
        <a href="./questions.php">Questions</a>
      </li>
    </ul>
  </nav>
</div>
<!-- END OF SIDEBAR -->