<?php
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

    $errors = array();
    $return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];
      $id = 0;
      $new_country_id = "";

      $user_id_pattern = "/^([0-9]+)$/";

      //validate the parameters
      if( (isset($_GET['user_id']) && (preg_match($user_id_pattern, $_GET['user_id'])))  && (isset($_GET['country_id']) && is_string($_GET['country_id'])) ) {
        $id = intval($_GET['user_id']);

        //A user can only edit his/her profile country
        if ($id != $view_user_id) {
          $errors [] = "Wrong parameters.";
        }

        $new_country_id = $_GET['country_id'];

        //check if country_id is a country
        $stmt = $dbc->prepare("SELECT COUNT(t.tag_id)
          FROM tag AS t
          JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
          WHERE tt.name = 'forum' AND t.tag_id = ?
        ");
        $stmt->bind_param("i", $new_country_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($temp_count);
        $stmt->fetch();

          //if country exists
        if($temp_count == 1) {
          $stmt = $dbc->prepare("SELECT u.tag_id, 
            IF(u.last_country_change IS NOT NULL, DATEDIFF(NOW(), u.last_country_change), NULL)
            FROM user AS u
            WHERE u.user_id = ?
          ");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($old_country_id, $datediff);
          $stmt->fetch();

          //check if old_country_id and new_country_id are different
          if($old_country_id == $new_country_id) {
            //user cannot change the same country
            $errors [] = "Wrong parameters.";
          } else if($datediff != NULL && $datediff <= 90) {
            $errors [] = "The 90 days wait since your last country change isn't over yet.";
          }
        } else {
          $errors [] = "Wrong parameters.";
        }

      } else {
        $errors [] = " Wrong parameters.";
      }

      if(empty($errors)) {
        //update the user's country
        $stmt = $dbc->prepare("UPDATE user
          SET last_country_change = NOW(), tag_id = ?
          WHERE user_id = ?
        ");
        $stmt->bind_param("ii", $new_country_id, $id);
        $stmt->execute();

        //fetch the new country and the last country change from the user
        $stmt = $dbc->prepare("SELECT u.tag_id, t.tag_name, 
            IF(u.last_country_change IS NOT NULL, DATE_FORMAT(u.last_country_change, '%W, %M %e %Y'), NULL),
            IF(u.last_country_change IS NOT NULL, DATEDIFF(NOW(), u.last_country_change), NULL)
            FROM user AS u
            JOIN tag AS t ON u.tag_id = t.tag_id
            WHERE u.user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($new_country_id, $new_country_name, $last_country_change_date, $new_date_diff);
        $stmt->fetch();

        if($last_country_change_date != NULL) {
          $last_country_change_date .= (" ($new_date_diff day" . ($new_date_diff <= 1 ? "" : "s") . " ago)");
        }
        $return = array(
          "isErr" => false,
          "message" => array(
            "country_id" => $new_country_id,
            "country_name" => $new_country_name,
            "is_country_change_allowed" => (!is_null($new_date_diff) && $new_date_diff <= 90) ? 0 : 1,
            "last_country_change_date" => $last_country_change_date
          )
        );
      } else {
        $return = array("isErr" => true, "message" => $errors);
      }

    } else {
      $errors [] = "You need to sign in.";
      $return = array("isErr" => true, "message" => $errors);
    }

    // Close the database connection.
    mysqli_close($dbc);
    unset($dbc);
    echo json_encode($return);
  }
?>