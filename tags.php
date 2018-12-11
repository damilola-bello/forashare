<?php
  include('includes/generic_includes.php');
  
  $page_title = 'Tags &ndash; ForaShare';
  $page = TAG;
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

      <div id="question_filter_header" class="filter-header">
        <h1 class="page-title filter-page-title">
          <span>Tags</span>
          <span data-bind="text: totalTags, attr: {class: 'filter-header-count'}"></span>
        </h1>
        <div class="row filter-controls" id="question_order_box">
          <input type="text" class="form-control filter-input" placeholder="Filter Tag" data-bind="value: filterText, valueUpdate:['afterkeydown','propertychange','input']">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="userSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="userSortLink">
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'name'}, event: { click: (sortType() != 'name') ? sortTags.bind($data, 'name') : null }">name</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'questions'}, event: { click: (sortType() != 'questions') ? sortTags.bind($data, 'questions') : null }">questions</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="users-box">
        <div class="loading-box" data-bind="css: { hide: tagsLoading() == false }, html: loadingHTML" title="loading..."></div>
        
        <div class="lists-box" id="users_list_grid" data-bind="foreach: tags">
          <div data-bind="attr: {class: 'preview row'}">
            <div data-bind="attr: {class: 'tag-preview'}">
              <a data-bind="attr: {class: 'question-tag filter-heading '+tag_type, href: `tag.php?id=${tag_id}`}, foreach: tag_name">
                <span class="heading-parts" data-bind="text: value, css: { 'marked-text': is_marked }"></span>
              </a>
              <span data-bind="attr: {class: 'tag-question-count'}, text: `${questions} Question${questions > 1 ? 's' : ''}`"></span>
            </div>
          </div>
        </div>
        <div data-bind="if: loadedTagsCount() < totalTags() && loadedTagsCount() > 0">
          <div class="view-more-box">
            <a href="#" data-bind="event: { click: loadTags.bind($data, false) }, text: 'view more tags'" class="view-more view-more-big"></a>
            <span class="track track-big" data-bind="text: tagTrack()"></span>
          </div>          
        </div>
        <span data-bind="if: loadedTagsCount() > 50 && loadedTagsCount() == totalTags()">
          <span class="load-more-finished" data-bind="text: 'No more tags to show'"></span>
        </span>
        <p data-bind="if: noTags() == true" class="filter-none">
          <span data-bind="text: 'No tag'"></span>
        </p>
      </div>
      <!-- List of Users -->

    </div>

    <script type="text/javascript">
      
      function AppViewModel() {
        var self = this;
        
        self.tags = ko.observableArray([]);
        self.tagsLoading = ko.observable(false);
        self.totalTags = ko.observable(0);
        self.sortType = ko.observable('questions');
        self.loadedTagsCount = ko.computed(function(){
          return self.tags().length;
        });
        self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
        self.tagTrack = ko.computed(function(){
          return `${self.loadedTagsCount()} of ${self.totalTags()}`;
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
              //call the load tags function to fetch the tags with the new search param
              self.loadTags(true);
            }, 500);


            //set the new length
            self.filterTextLength(currentLength);
          }
        });
        self.sortParam = ko.observable({});
        self.sortTags = function(type) {
          let types = ['name', 'questions'];
          // if the sort type is valid
          if(types.indexOf(type) != -1) {
            let param = {};
            if(type == 'name'){
              self.sortParam({name: 'asc'});
            } else if(type == 'questions') {
              self.sortParam({});
            }

            self.sortType(type);
            self.loadTags(true);
          }


        };
        self.noTags = ko.observable(false);
        self.loadTags = function (reset = true) {
          self.tagsLoading(true);

          var start = (reset) ? 0 : self.tags().length;

          var payload = { start: start, ...self.sortParam(), ...self.searchParam() };
          $.get('get_tags.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //update the observable array
                  if(reset == true) {
                    self.tags(data.message.tags);
                  } else {
                    self.tags.push(...data.message.tags);
                  }

                  if(self.tags().length <= 0) {
                    self.noTags(true);
                  } else {
                    self.noTags(false)
                  }
                  
                  self.totalTags(data.message.total_tags);
                  self.tagsLoading(false);
                }
              } else {
                self.tagsLoading(false);
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