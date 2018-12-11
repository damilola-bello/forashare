<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Users &ndash; ForaShare';
  $page = USER;
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
    <div class="users-outer-container">

      <div id="user_filter" class="search-box-filter">
        <div class="row filter-forum-list-box">
          <?php
            $query = "SELECT t.tag_name AS tag_name, t.tag_id AS tag_id FROM tag AS t JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'forum' ORDER BY t.tag_name ASC";
            $r = mysqli_query($dbc, $query);
            $countries = array(
              array(
              'tag_name' => "All",
              'tag_id' => 0)
            );
            while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
              $country = array(
                'tag_name' => $row['tag_name'],
                'tag_id' => $row['tag_id']
              );
              array_push($countries, $country);
            }
          ?>
          <select class="form-control filter-forum-list" data-bind="options: countries, optionsText: 'tag_name', optionsValue: 'tag_id', event: {change: changeCountryName}">
          </select>
        </div>
      </div>


      <div id="question_filter_header" class="filter-header">
        <h1 class="page-title filter-page-title">
          <span data-bind="text: countryName"></span>
          <span>Users</span>
          <span data-bind="text: totalUsers, attr: {class: 'filter-header-count'}"></span>
        </h1>
        <div class="row filter-controls" id="question_order_box">
          <input type="text" class="form-control filter-input" placeholder="Filter User" data-bind="value: filterText, valueUpdate:['afterkeydown','propertychange','input']">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="userSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="userSortLink" id="user_sort">
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'score'}, event: { click: (sortType() != 'score') ? sortUsers.bind($data, 'score') : null }">score</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'new users'}, event: { click: (sortType() != 'new users') ? sortUsers.bind($data, 'new users') : null }">new users</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'old users'}, event: { click: (sortType() != 'old users') ? sortUsers.bind($data, 'old users') : null }">old users</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="users-box">
        <div class="loading-box" data-bind="css: { hide: usersLoading() == false }, html: loadingHTML" title="loading..."></div>
        
        <div class="lists-box" id="users_list_grid" data-bind="foreach: users">
          <div data-bind="attr: {class: 'preview row'}">
            <div data-bind="attr: {class: 'preview-image-box'}">
              <a data-bind="attr: {href: `user.php?id=${user_id}`}">
                <img data-bind="attr: {src:`images/${profile_image}`, alt: `${username}'s profile image`, class: 'preview-image'}">
              </a>
            </div>
            <div data-bind="attr: {class: 'preview-details'}">
              <a data-bind="attr: {class: 'user-preview-name detail filter-heading', href: `user.php?id=${user_id}`}, foreach: username">
                <span class="heading-parts" data-bind="text: value, css: { 'marked-text': is_marked }"></span>
              </a>
              <a data-bind="attr: {class: 'user-preview-country detail', href: `country.php?id=${country_id}`}, text: country_name"></a>
              <span data-bind="attr: {class: 'user-preview-score detail', title: 'User\'s Score' }, text: profile_score"></span>
              <span data-bind="attr: {class: 'user-preview-date-joined detail'}, text: `Joined ${how_long}`"></span>
            </div>
          </div>
        </div>
        <div data-bind="if: loadedUsersCount() < totalUsers() && loadedUsersCount() > 0">
          <div class="view-more-box">
            <a href="#" data-bind="event: { click: loadUsers.bind($data, false) }, text: 'view more users'" class="view-more view-more-big"></a>
            <span class="track track-big" data-bind="text: userTrack()"></span>
          </div>          
        </div>
        <span data-bind="if: loadedUsersCount() > 50 && loadedUsersCount() == totalUsers()">
          <span class="load-more-finished" data-bind="text: 'No more users to show'"></span>
        </span>
        <p data-bind="if: noUsers() == true" class="filter-none">
          <span data-bind="text: 'No user'"></span>
        </p>
      </div>
      <!-- List of Users -->

    </div>

    <script type="text/javascript">
      <?php
        $a = array(
          'countries' => $countries
        );
        echo "var json_payload = " . json_encode($a) . ";";
      ?>

      function CountryModel(data) {
        var self = this;

        self.tag_name = data.tag_name;
        self.tag_id = data.tag_id;
      }

      function AppViewModel() {
        var self = this;

        let mappedCountries = json_payload.countries.map(country => new CountryModel(country));
        self.countries = mappedCountries;
        self.countryName = ko.observable('All');
        
        self.users = ko.observableArray([]);
        self.usersLoading = ko.observable(false);
        self.totalUsers = ko.observable(0);
        self.sortType = ko.observable('score');
        self.loadedUsersCount = ko.computed(function(){
          return self.users().length;
        });
        self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
        self.userTrack = ko.computed(function(){
          return `${self.loadedUsersCount()} of ${self.totalUsers()}`;
        });
        self.searchParam = ko.observable({});
        self.filterText = ko.observable('');
        self.filterTextLength = ko.observable(0);
        var timeOut = 0;
        self.filterTextSearch = ko.computed(function () {
          let currentLength = self.filterText().trim().length;
          //if the filter string changes
          if(currentLength !== self.filterTextLength) {
            //clear the timeout
            clearTimeout(timeOut);
            timeOut = setTimeout(() => {
              self.searchParam({q: self.filterText().trim()});
              //call the load users function to fetch the users with the new search param
              self.loadUsers(true);
            }, 500);


            //set the new length
            self.filterTextLength(currentLength);
          }
        });
        self.sortParam = ko.observable({});
        self.countryIDParam = ko.observable({});
        self.sortUsers = function(type) {
          let types = ['new users', 'old users', 'score'];
          // if the sort type is valid
          if(types.indexOf(type) != -1) {
            let param = {};
            if(type == 'new users'){
              self.sortParam({date_joined: 'desc'});
            } else if(type == 'old users') {
              self.sortParam({date_joined: 'asc'});
            } else if(type == 'score') {
              self.sortParam({});
            }

            self.sortType(type);
            self.loadUsers(true);
          }


        };
        var countryDDLTimeOut = 0;
        self.changeCountryName = function(koObj, e) {
          let select = e.currentTarget;
          let selectedText = select.options[select.selectedIndex].text;
          let selectedValue = select.value;
          let param = (Number(selectedValue) > 0) ? {country: selectedValue} : {};
          self.countryIDParam(param);
          if(Object.keys(self.countryIDParam().length !== 0)) {
            //clear the timeout
            clearTimeout(countryDDLTimeOut);
            countryDDLTimeOut = setTimeout(() => {
              //call the load users function
              self.loadUsers(true);
              self.countryName(selectedText);
            }, 500)
          }
        }
        self.noUsers = ko.observable(false);
        self.loadUsers = function (reset = true) {
          self.usersLoading(true);

          var start = (reset) ? 0 : self.users().length;

          var payload = { start: start, ...self.sortParam(), ...self.countryIDParam(), ...self.searchParam() };
          console.log(payload);
          $.get('get_users.php', payload, 
            function(data, status) {
              console.log(data);
              if(status == "success") {
                if(data.isErr == false) {
                  //update the observable array
                  if(reset == true) {
                    self.users(data.message.users);
                  } else {
                    self.users.push(...data.message.users);
                  }

                  if(self.users().length <= 0) {
                    self.noUsers(true);
                  } else {
                    self.noUsers(false)
                  }
                  
                  self.totalUsers(data.message.total_users);
                  self.usersLoading(false);
                }
              } else {
                self.usersLoading(false);
              }
          }, "json");
        };
        
        //Onload, load the users
        $(document).ready(function() {
          //self.loadCountries(true);  
        });
      }
      var model = new AppViewModel();
      ko.applyBindings(model, document.getElementById("main_content"));
    </script>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>