<?php
	include('includes/generic_includes.php');
	//if the user is not logged in, redirect to questions page
	if($loggedin == false) {
    header("Location: questions.php");
    exit(); // Quit the script.
  }
  $page_title = 'Notifications &ndash; ForaShare';
  $page = "Notifications";
  include('includes/header.php');
     
?>
<!-- CONTAINER -->
<div class="container-fluid content-container" id="main_content">
  <?php
    include('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="create-link-box row">
      <a href="ask.php" class="create-link">Ask Question</a>
    </div>
    <div class="notifications-outer-container">
      
      <!-- Question filter header -->
      <div class="filter-header">
        <h1 class="page-title filter-page-title">
          <span data-bind="text: 'Notifications'"></span>
        </h1>

        <div class="row notification-filter">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="notificationSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="notificationSortLink">
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'newest'}, event: { click: (sortType() != 'newest') ? sortNotifications.bind($data, 'newest') : null }">newest</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'oldest'}, event: { click: (sortType() != 'oldest') ? sortNotifications.bind($data, 'oldest') : null }">oldest</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="notifications-box">
        <div class="loading-box" data-bind="css: { hide: notificationsLoading() == false }, html: loadingHTML" title="loading..."></div>
        <!-- List of Notifications -->
        <ul data-bind=" foreach: notifications">
	        <li data-bind=" attr: { class: 'notification-list-item' }">
	          <a data-bind=" attr: { href: `${notification_link}` }, css: { 'not-seen': (seen == false)}" class="notification-list-item-link">
	            <span data-bind=" attr: { class: 'notifcation-text' }">
	              <span data-bind=" attr: { class: 'name' }, text: notifier_username"></span>
	              <span data-bind=" text: notification_text"></span>
	            </span>
	            <span data-bind=" attr: { class: 'notification-details' }">
	              <i data-bind=" css: notificationIconClass(notification_type)" class="notification-type-icon"></i>
	              <span data-bind="attr: { class: 'notification-time' }, text: how_long"></span>
	            </span>
	          </a>
	        </li>
	      </ul>
        <div data-bind="if: loadedNotificationsCount() < totalNotifications() && loadedNotificationsCount() > 0">
          <div class="view-more-box">
            <a href="#" data-bind="event: { click: fetchNotifications.bind($data, false) }, text: 'view more notifications'" class="view-more view-more-big"></a>
          </div>          
        </div>
        <span data-bind="if: loadedNotificationsCount() > 15 && loadedNotificationsCount() == totalNotifications()">
          <span class="load-more-finished" data-bind="text: 'No more notifications to show'"></span>
        </span>
        <p data-bind="if: noNotifications() == true" class="filter-none">
          <span data-bind="text: 'No notification'"></span>
        </p>
      </div>

      <script type="text/javascript">
      	<?php
          $a = array(
            'pending_notification' => $pending_notification,
            'original_page_title' => $original_page_title
          );
          echo "var json_notifcation_page_payload = " . json_encode($a) . ";";
          unset($a);
        ?>
      	function AppViewModel() {
	      	self.sortParam = ko.observable({});
	      	self.sortType = ko.observable('newest');
	      	self.totalNotifications = ko.observable(0);
	      	self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
	      	self.pending_notification = ko.observable(json_notifcation_page_payload.pending_notification);
	      	self.pageTitle = json_notifcation_page_payload.original_page_title;
	        self.sortNotifications = function(type) {
	          let types = ['newest', 'oldest'];
	          // if the sort type is valid
	          if(types.indexOf(type) != -1) {
	            let param = {};
	            if(type == 'newest') {
	              self.sortParam({});
	            } else if(type == 'oldest') {
	              self.sortParam({date: 'asc'});
	            }

	            self.sortType(type);
	            self.fetchNotifications(true);
	          }
	        };
	        self.notificationIconClass = function(type) {
            let str = "";
            if(type == "like") {
              str = "far fa-thumbs-up like";
            } else if(type == "dislike") {
              str = "far fa-thumbs-down dislike";
            } else if(type == "answer") {
              str = "fas fa-comments answer";
            }
            return str;
          };
	        self.notificationsLoading = ko.observable(false);
	        self.notifications = ko.observableArray([]);
          self.noNotifications = ko.observable(false);
          self.loadedNotificationsCount = ko.computed(function(){
	          return self.notifications().length;
	        });
	        self.fetchNotifications = function (reset = true) {
	          self.notificationsLoading(true);

	          let start = (reset) ? 0 : self.notifications().length;

	          var payload = { start: start, ...self.sortParam() };
	          $.get('fetch_notifications.php', payload, 
	            function(data, status) {
	              if(status == "success") {
	                if(data.isErr == false) {
	                  
	                  self.totalNotifications(data.message.total_notifications);
                    self.notificationsLoading(false);
                    let notificationsJSON = data.message.notifications;
                    if(reset) {
                    	self.notifications(notificationsJSON);
                    } else {
                    	self.notifications.push(...notificationsJSON);
                    }
                    if(self.notifications().length == 0) {
                      self.noNotifications(true);
                    }
                    self.pending_notification(data.message.pending_notification);
                    //reset the page title if there are no pending notifications
                    if(self.pending_notification() == false) {
                      document.title = self.pageTitle.replace("&ndash;", "\u2013");
                      //this removes the red color from the notifications icon on the navbar
                      TopBarModel.pending_notification(false);
                    }
	                }
	              } else {
	                self.notificationsLoading(false);
	              }
	          }, "json");
	        };

	        

	        //Onload load the notifications
	        $(document).ready(function() {
	          self.fetchNotifications();
	        });
      	}

        var model = new AppViewModel();
      	ko.applyBindings(model, document.getElementById("main_content"));
      </script>

    </div>
  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>