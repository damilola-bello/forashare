<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Countries &ndash; ForaShare';
  $page = FORUM;
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
    <div class="countries-outer-container">

      <div id="forum_filter" class="search-box-filter">
        <div class="row filter-search-box">
          <div class="dropdown checkbox-dropdown-box" id="question_tags_dropdown">

            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="regionsLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span>region</span></a>

            <ul class="dropdown-menu menu-filter dropdown-menu-right checkbox-dropdown" aria-labelledby="regionsLink" data-bind=" foreach: regions">
              <?php
                $query = "SELECT region_id, region_name FROM region ORDER BY region_name ASC";
                $r = mysqli_query($dbc, $query);
                $regions = array();
                while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                  $region = array(
                    'region_name' => $row['region_name'],
                    'region_id' => $row['region_id']
                  );
                  array_push($regions, $region);
                }
              ?>
              <li class='dropdown-item' data-bind="attr: {'data-tag': region_id}, event: {click: $parent.toggleRegionCheck}" data-tag-check='1'>
                <span class='region-tag-name' tabindex='-1'>
                  <input type='checkbox' checked='checked'>&nbsp; 
                  <span data-bind="text: region_name"></span>
                </span>
              </li>
            </ul>
          </div>
          <button class="btn question-filter-btn" data-bind="event: {click: (regionSearchEnabled() !== false) ? loadCountries.bind($data, true) : null }"><i class="fas fa-search"></i></button>
        </div>
      </div>

      <!-- Question filter header -->
      <div class="filter-header">
        <h1 class="page-title filter-page-title">
          <span data-bind="text: 'All Countries'"></span>
          <span data-bind="text: totalCountries, attr: {class: 'filter-header-count'}"></span>
        </h1>
        <div class="row filter-controls">
          <input type="text" class="form-control filter-input" data-bind="value: filterText, valueUpdate:['afterkeydown','propertychange','input']" placeholder="Filter Country">

          <div class="dropdown filter-order-dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="forumSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="forumSortLink">
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'name'}, event: { click: (sortType() != 'name') ? sortCountries.bind($data, 'name') : null }">name</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'question'}, event: { click: (sortType() != 'question') ? sortCountries.bind($data, 'question') : null }">question</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'user'}, event: { click: (sortType() != 'user') ? sortCountries.bind($data, 'user') : null }">user</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="countries-box">
        <div class="loading-box" data-bind="css: { hide: countriesLoading() == false }, html: loadingHTML" title="loading..."></div>
        <!-- List of Countries -->
        <div class="lists-box" id="forums_list_grid" data-bind=" foreach: countries">
          <div data-bind="attr: {class: 'preview row'}">
            <div data-bind="attr: {class: 'preview-image-box'}">
              <a data-bind="attr: {href: `country.php?id=${tag_id}`}"><img data-bind="attr: { src: 'images/forum-128/'+alpha_code+'.png', class: 'preview-image' } "></a>
            </div>
            <div data-bind="attr: {class: 'preview-details'}">
              <a data-bind="foreach: forum_name, attr: {href: `country.php?id=${tag_id}`, class: 'preview-name filter-heading'}">
                <span class="heading-parts" data-bind="text: value, css: { 'marked-text': is_marked }"></span>
              </a>
              <span class='forum-preview-questions preview-count-box'>
                <span class='preview-count' data-bind="text: questions"></span>
                <span data-bind="text: 'Questions'"></span>
              </span>
              <span class='forum-preview-users preview-count-box'>
                <span class='preview-count' data-bind="text: users"></span>
                <span data-bind="text: 'Users'"></span>
              </span>
            </div>
          </div>
        </div>
        <div data-bind="if: loadedCountriesCount() < totalCountries() && loadedCountriesCount() > 0">
          <div class="view-more-box">
            <a href="#" data-bind="event: { click: loadCountries.bind($data, false) }, text: 'view more countries'" class="view-more view-more-big"></a>
            <span class="track track-big" data-bind="text: countryTrack()"></span>
          </div>          
        </div>
        <span data-bind="if: loadedCountriesCount() > 50 && loadedCountriesCount() == totalCountries()">
          <span class="load-more-finished" data-bind="text: 'No more countries to show'"></span>
        </span>
        <p data-bind="if: noCountries() == true" class="filter-none">
          <span data-bind="text: 'No country'"></span>
        </p>
      </div>

    </div>

  </div>
</div>
<script type="text/javascript">
  <?php
    $a = array(
      'regions' => $regions,
      'countries' => []
    );
    echo "var json_payload = " . json_encode($a) . ";";
  ?>

  function AppViewModel() {
    var self = this;

    self.regionSearchEnabled = ko.observable(false);
    self.regions = json_payload.regions;
    self.countries = ko.observableArray(json_payload.countries);
    self.countriesLoading = ko.observable(false);
    self.totalCountries = ko.observable(0);
    self.sortType = ko.observable('question');
    self.sortRegions = ko.observable(new Set(json_payload.regions.map(region => region.region_name.toLowerCase())));
    self.loadedCountriesCount = ko.computed(function(){
      return self.countries().length;
    });
    self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
    self.countryTrack = ko.computed(function(){
      return `${self.loadedCountriesCount()} of ${self.totalCountries()}`;
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
          //call the load countries function to fetch the countries with the new search param
          self.loadCountries(true);
        }, 500);


        //set the new length
        self.filterTextLength(currentLength);
      }
    });
    self.toggleRegionCheck = function(koObj, event) {
      let regionName = koObj.region_name.toLowerCase();

      let $target = $( event.currentTarget ),
         val = $target.attr('data-tag'),
         checked = $target.attr('data-tag-check'),
         $inp = $target.find('input'),
         idx;

      if (checked === '0') {
        setTimeout( function() {
          //check checkbox
          $inp[0].checked = true;
          $target.attr( 'data-tag-check', '1' ); 
          self.sortRegions().add(regionName);
        }, 0);
      } else if (checked === '1') {
        setTimeout( function() { 
          //uncheck checkbox
          $inp[0].checked = false;
          $target.attr( 'data-tag-check', '0' ); 
          self.sortRegions().delete(regionName);
        }, 0);
      }

      self.regionSearchEnabled(true);
      
       $( event.target ).blur();
          
      event.stopPropagation();
    }
    self.sortParam = ko.observable({});
    self.sortCountries = function(type) {
      let types = ['name', 'user', 'question'];
      // if the sort type is valid
      if(types.indexOf(type) != -1) {
        let param = {};
        if(type == 'user'){
          self.sortParam({user: 'desc'});
        } else if(type == 'name') {
          self.sortParam({name: 'desc'});
        } else if(type == 'question') {
          self.sortParam({});
        }

        self.sortType(type);
        self.loadCountries(true);
      }


    };
    self.noCountries = ko.observable(false);
    self.loadCountries = function (reset = true) {
      self.countriesLoading(true);

      var start = (reset) ? 0 : self.countries().length;
      let regionParam='';
      let regionSetLen = self.sortRegions().size-1;
      let counter = 0
      for(let region of self.sortRegions()) {
        regionParam += `${region}${(counter !== regionSetLen) ? ',' : ''}`;
        counter++;
      }

      var payload = { start: start, ...self.sortParam(), ...self.searchParam(), regions: regionParam };
      $.get('get_countries.php', payload, 
        function(data, status) {
          if(status == "success") {
            if(data.isErr == false) {
              if(reset == true) {
                self.countries(data.message.countries);
                self.regionSearchEnabled(false);
              } else {
                self.countries.push(...data.message.countries);
              }
              self.totalCountries(data.message.total_countries);
              if(self.countries().length <= 0) {
                self.noCountries(true);
              } else {
                self.noCountries(false)
              }
            }
            self.countriesLoading(false);
          } else {
            self.countriesLoading(false);
          }
      }, "json");
    };
    
    //Onload, load the questions
    $(document).ready(function() {
      //self.loadCountries(true);  
    });
  }
  var model = new AppViewModel();
  ko.applyBindings(model, document.getElementById("main_content"));

</script>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>