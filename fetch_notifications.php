<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {

		require_once('includes/mysqli_connect.php');
    require_once('checkifloggedin.php');

		$errors = array();
		$return = array();

    if($loggedin) {
      $view_user_id = $_SESSION['user_id'];
      $start = 0;
      $is_mini = false;
      $limit_str = "";
      $order_by_str = " n.notification_time DESC ";

      $start_pattern = "/^([0-9]+)$/";
      $mini_pattern = "/^[1]$/";

      //validate the parameters
      if( (isset($_GET['start'])) ) {
        if(preg_match($start_pattern, $_GET['start'])) {
          $start = $_GET['start'];
          $limit_str = "LIMIT $start, 20";
          if(isset($_GET['date'])) {
            if($_GET['date'] == "asc") {
              $order_by_str = " n.notification_time ASC";
            } else {
              $errors [] = "Wrong parameters.";
            }
          }
        } else {
          $errors [] = " Wrong parameters.";
        }
      } else if(isset($_GET['mini'])) {
        if(preg_match($start_pattern, $_GET['mini'])) {
          $is_mini = true;
          $limit_str = "LIMIT 0, 5";
        } else {
          $errors [] = " Wrong parameters.";
        }
      }

      if(empty($errors)) {

        //used to calculate the date the notification was made
        include('includes/how_long.php');

        $notifications_array = array();
        $total_notifications = 0;

        //get the notifications count
        $stmt = $dbc->prepare("SELECT COUNT(notified_id)
          FROM notifications
          WHERE notified_id = ? ");
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($total_notifications);
        $stmt->fetch();

        //fetch the notifications
        $stmt = $dbc->prepare("SELECT n.notifier_id, u.username, n.type, n.seen, TIMESTAMPDIFF(SECOND, n.notification_time, NOW()), DATE_FORMAT(n.notification_time, '%b %e'), DATE_FORMAT(n.notification_time, 'at %h:%i %p'), DATE_FORMAT(n.notification_time, '%y'), DATE_FORMAT(NOW(), '%b %e'), DAY(n.notification_time), DAY(NOW()), n.question_id, n.answer_id, n.reply_id, n.is_like, n.text
          FROM notifications AS n
          JOIN user AS u ON n.notifier_id = u.user_id 
          WHERE notified_id = ?
          ORDER BY $order_by_str
          $limit_str");
	      $stmt->bind_param("i", $view_user_id);
	      $stmt->execute();
	      $stmt->store_result();

        $stmt->bind_result($notifier_id, $notifier_username, $notification_type, $is_seen, $seconds, $notification_date_open, $notification_date_close, $notification_year, $current_year, $notification_day, $today, $question_id, $answer_id, $reply_id, $is_like, $notification_text);
        while($stmt->fetch()) {

          switch ($notification_type) {
            case 'question_like':
              $notification_link = "question.php?id=$question_id";
              break;
            case 'answer_like':
            case 'answer':
              $notification_link = "question.php?id=$question_id&answer=$answer_id&uid=$notifier_id";
              break;
            case 'reply_like':
            case 'reply':
              $notification_link = "question.php?id=$question_id&answer=$answer_id&uid=$notifier_id";
              break;
          }

          switch ($notification_type) {
            case 'question_like':
            case 'answer_like':
            case 'reply_like':
              $notification_type = ($is_like == 0) ? "dislike" : "like";
              break;
            
            case 'answer':
            case 'reply':
              $notification_type = "answer";
              break;
          }

          //if the notification is not seen yet add the source query param to the link
          $notification_link = ($is_seen == false) ? ($notification_link . "&source=notification") : $notification_link;

          $notification_item = array(
            'notifier_username' => $notifier_username,
            'how_long' => calculate($seconds, $notification_date_open, $notification_date_close, $notification_year, $current_year, $notification_day, $today),
            'notification_text' => $notification_text,
            'notification_link' => $notification_link,
            'notification_type' => $notification_type,
            'seen' => ($is_seen == 0) ? false : true
          );
          array_push($notifications_array, $notification_item);
        }

        $pending_notification = false;
        if(!empty($notifications_array)) {
          //set all the user's notification to seen
          $stmt = $dbc->prepare("UPDATE notifications SET open = '1' WHERE notified_id = ? ");
          $stmt->bind_param("i", $view_user_id);
          $stmt->execute();
        }

        $return = array(
          "isErr" => false,
          "message" => array(
            'notifications' => $notifications_array,
            'pending_notification' => $pending_notification,
            'total_notifications' => $total_notifications
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