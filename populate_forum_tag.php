<?php
	require_once('includes/mysqli_connect.php');
  /*$stmt = $dbc->prepare("INSERT INTO tag (tag_id, tag_name, tag_image, is_toplevel, forum_id) VALUES (?, ?, ?, ?, ?)");
 	$default_tags = array("Culture" => "culture.png", "Education" => "education.png", "Others" => "others.png", "Politics" => "politics.png", "Religion" => "religion.png", "Sports" => "sports.png");
  $default = 1;
  $null = NULL;
  
	$query = "SELECT forum_id FROM forum ORDER BY forum_name ASC";
  $r = mysqli_query($dbc, $query);

  while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {

  	$forum_id = $row['forum_id'];
    foreach ($default_tags as $tag_name => $tag_image) {
	    $stmt->bind_param("sssss", $null, $tag_name, $tag_image, $default, $forum_id);
	    $stmt->execute();
    }
  }
  //Close the statement
  $stmt->close();
  unset($stmt);*/

  $stmt = $dbc->prepare("INSERT INTO tag (tag_id, tag_name, tag_image) VALUES (?, ?, ?)");
 	$default_tags = array("Culture" => "culture.png", "Education" => "education.png", "Others" => "others.png", "Politics" => "politics.png", "Religion" => "religion.png", "Sports" => "sports.png");
  $null = NULL;

  foreach ($default_tags as $tag_name => $tag_image) {
    $stmt->bind_param("sss", $null, $tag_name, $tag_image);
    $stmt->execute();
  }
  //Close the statement
  $stmt->close();
  unset($stmt);

  echo "Complete!";
?>