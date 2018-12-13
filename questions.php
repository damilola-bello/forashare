<?php
	include('includes/generic_includes.php');
	
  $page_title = 'Questions &ndash; ForaShare';
  $page = QUESTION;
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
    <div class="questions-outer-container">
      <!-- Displays the filter area to filter questions according to tags -->
      <div id="question_filter" class="search-box-filter">
        <div class="row filter-search-box">
          <div class="dropdown checkbox-dropdown-box" id="question_tags_dropdown">

            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="questionTagLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span>tags</span>&nbsp;&nbsp;<i class="fas fa-tags"></i></a>

            <ul class="dropdown-menu menu-filter dropdown-menu-right checkbox-dropdown tags-dropdown" aria-labelledby="questionTagLink" data-bind=" foreach: tags">
              <?php
                //Select the default tags
                $query = "SELECT tag_id, tag_name FROM tag AS t JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id WHERE tt.name = 'default_tag' ORDER BY t.tag_name ASC";
                $r = mysqli_query($dbc, $query);
                $tags = array(
                  array('tag_name' => "All",
                  'tag_id' => -1)
                );
                while ($row = mysqli_fetch_array($r, MYSQLI_ASSOC)) {
                  $tag_name = strtolower($row['tag_name']);
                  $tag_id = $row['tag_id'];
                  $tag = array(
                    'tag_name' => $tag_name,
                    'tag_id' => $tag_id
                  );
                  array_push($tags, $tag);
                }
              ?>
              <li class='dropdown-item' data-tag='$tag_name' data-tag-check='1' data-bind="event: {click: $parent.toggleTagsCheck}, css: { 'check-all': (tag_id == -1) }">
                <span class='ask-question-tag-name' tabindex='-1'>
                  <input type='checkbox' data-bind="attr: { value: tag_id}" checked="checked">
                  &nbsp;
                  <span data-bind="text: tag_name"></span>
                </span>
              </li>
            </ul>
          </div>

          <button class="btn question-filter-btn" data-bind="event: {click: (tagsSearchEnabled() !== false) ? loadQuestions.bind($data, true) : null }">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>

      <!-- Question filter header -->
      <div id="question_filter_header" class="filter-header">
        <h1 class="page-title filter-page-title">
          <span data-bind="text: 'Questions'"></span>
          <span data-bind="text: totalQuestions, attr: {class: 'filter-header-count'}"></span>
        </h1>

        <div class="row filter-controls">
          <input type="text" class="form-control filter-input" placeholder="Filter Question" data-bind="value: filterText, valueUpdate:['afterkeydown','propertychange','input']">

          <div class="dropdown filter-order-dropdown" id="question_order_dropdown">
            <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="questionSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
            <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="questionSortLink" id="question_sort">
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'answer'}, event: { click: (sortType() != 'answer') ? sortQuestions.bind($data, 'answer') : null }">answer</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'newest'}, event: { click: (sortType() != 'newest') ? sortQuestions.bind($data, 'newest') : null }">newest</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'oldest'}, event: { click: (sortType() != 'oldest') ? sortQuestions.bind($data, 'oldest') : null }">oldest</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'point'}, event: { click: (sortType() != 'point') ? sortQuestions.bind($data, 'point') : null }">point</li>
              <li class="dropdown-item" data-bind="css: {active: sortType() == 'unanswered'}, event: { click: (sortType() != 'unanswered') ? sortQuestions.bind($data, 'unanswered') : null }">unanswered</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="questions-box">
        <div class="loading-box" data-bind="css: { hide: questionsLoading() == false }, html: loadingHTML" title="loading..."></div>
        <!-- List of Questions -->
        <div class="lists-box" data-bind="foreach: questions">
          <div data-bind="attr: {class: 'question-preview'}">
            <div data-bind="attr: {class: 'question-preview-content'}">
              <div data-bind="attr: {class: 'question-preview-header'}">
                <span data-bind="attr: {class: `question-score ${(question_score > 0) ? 'positive' : ''}`}, text: question_score"></span>
                <span>
                  <a data-bind="attr: {class: 'question-heading-text filter-heading', href: `question.php?id=${question_id}`}, foreach: question_heading">
                    <span class="heading-parts" data-bind="text: value, css: { 'marked-text': is_marked }"></span>
                  </a>
                  <span class="preview-question-tags-box" data-bind=" foreach: tags ">
                    <a data-bind="attr: { href: `${tag_type == 'forum' ? 'country' : 'tag'}.php?id=${tag_id}`, class: `${tag_type}-tag question-tag` }, text: tag_name"></a>
                  </span>
                </span>
              </div>
              <div data-bind="attr: {class: 'question-preview-details'}">
                <span data-bind="attr: {class: 'question-preview-answers'}">
                  <strong><span data-bind="text: answers"></span></strong>&nbsp;
                  <span data-bind="text: 'Answers'"></span>
                </span>
                <span data-bind="attr: {class: 'question-preview-date'}, text: how_long"></span>
              </div>
            </div>
          </div>
        </div>
        <div data-bind="if: loadedQuestionsCount() < totalQuestions() && loadedQuestionsCount() > 0">
          <div class="view-more-box">
            <a href="#" data-bind="event: { click: loadQuestions.bind($data, false) }, text: 'view more questions'" class="view-more view-more-big"></a>
            <span class="track track-big" data-bind="text: questionTrack()"></span>
          </div>          
        </div>
        <span data-bind="if: loadedQuestionsCount() > 15 && loadedQuestionsCount() == totalQuestions()">
          <span class="load-more-finished" data-bind="text: 'No more questions to show'"></span>
        </span>
        <p data-bind="if: noQuestions() == true" class="filter-none">
          <span data-bind="text: 'No question'"></span>
        </p>
      </div>

    </div>

    <script type="text/javascript">
      <?php
        $a = array(
          'tags' => $tags
        );
        echo "var json_payload = " . json_encode($a) . ";";
      ?>

      function QuestionModel(data, filter) {
        var self = this;

        self.answers = data.answers;
        self.how_long = data.how_long;
        self.filter = filter;
        self.question_heading = data.question_heading;
        self.question_id = data.question_id;
        self.question_score = data.question_score;
        self.tags = data.tags;
      }



      function AppViewModel() {
        var self = this;

        self.tagsSearchEnabled = ko.observable(false);
        self.tags = json_payload.tags;
        self.questions = ko.observableArray([]);
        self.questionsLoading = ko.observable(false);
        self.totalQuestions = ko.observable(0);
        self.sortType = ko.observable('newest');
        self.allTags = new Set(
          json_payload.tags.filter(tag => (tag.tag_id != -1))
          .map(tag => tag.tag_id)
        );
        self.allTagsLength = self.allTags.size;
        self.sortTags = ko.observable(new Set(
          json_payload.tags.filter(tag => (tag.tag_id != -1))
          .map(tag => tag.tag_id)));
        self.loadedQuestionsCount = ko.computed(function(){
          return self.questions().length;
        });
        self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
        self.questionTrack = ko.computed(function(){
          return `${self.loadedQuestionsCount()} of ${self.totalQuestions()}`;
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
              //call the load questions function to fetch the questions with the new search param
              self.loadQuestions(true);
            }, 500);


            //set the new length
            self.filterTextLength(currentLength);
          }
        });
        self.toggleTagsCheck = function(koObj, event) {
          let tagID = koObj.tag_id;
          let all = (tagID == -1) ? true : false;
          let $target = $( event.currentTarget ),
             val = $target.attr('data-tag'),
             checked = $target.attr('data-tag-check'),
             $inp = $target.find('input'),
             idx;

          if (checked === '0') {
            setTimeout( function() {
              //check checkbox
              if(all) {
                $('.tags-dropdown input').each(function(){
                  this.checked = true;
                });
                $('.tags-dropdown .dropdown-item').each(function(){
                  $(this).attr( 'data-tag-check', '1' );
                });
                self.sortTags(self.allTags);
              } else {
                $inp[0].checked = true;
                $target.attr( 'data-tag-check', '1' ); 
                self.sortTags().add(tagID);

                if(self.sortTags().size == self.allTagsLength) {
                  $('.tags-dropdown .check-all').attr( 'data-tag-check', '1');
                  $('.tags-dropdown .check-all input')[0].checked = true;
                }
              }
            }, 0);
          } else if (checked === '1') {
            setTimeout( function() { 
              //uncheck checkbox
              if(all) {
                $('.tags-dropdown input').each(function(){
                  this.checked = false;
                });
                $('.tags-dropdown .dropdown-item').each(function(){
                  $(this).attr( 'data-tag-check', '0' );
                });
                self.sortTags(new Set());
              } else {
                $inp[0].checked = false;
                $target.attr( 'data-tag-check', '0' ); 
                self.sortTags().delete(tagID);

                $('.tags-dropdown .check-all').attr( 'data-tag-check', '0');
                $('.tags-dropdown .check-all input')[0].checked = false;
              }
            }, 0);
          }

          self.tagsSearchEnabled(true);
          
           $( event.target ).blur();
              
          event.stopPropagation();
        }
        self.sortParam = ko.observable({});
        self.sortQuestions = function(type) {
          let types = ['answer', 'newest', 'oldest', 'point', 'unanswered'];
          // if the sort type is valid
          if(types.indexOf(type) != -1) {
            let param = {};
            if(type == 'answer'){
              self.sortParam({answer: 'desc'});
            } else if(type == 'newest') {
              self.sortParam({});
            } else if(type == 'oldest') {
              self.sortParam({date: 'asc'});
            } else if(type == 'point') {
              self.sortParam({point: 'desc'});
            } else if(type == 'unanswered') {
              self.sortParam({unanswered: 1});
            }

            self.sortType(type);
            self.loadQuestions(true);
          }


        };
        self.noQuestions = ko.observable(false);
        self.createQuestionModel = function(questions, reset = false) {
          //convert each question to a question model and store in an array
          var mappedQuestions = questions.map(question => new QuestionModel(question, self.filterText()));

          //update the observable array
          if(reset == true) {
            self.questions(mappedQuestions);
            self.tagsSearchEnabled(false);
          } else {
            self.questions.push(...mappedQuestions);
          }

          if(self.questions().length <= 0) {
            self.noQuestions(true);
          } else {
            self.noQuestions(false)
          }
        };
        self.loadQuestions = function (reset = true) {
          self.questionsLoading(true);

          var start = (reset) ? 0 : self.questions().length;
          let tagParam='';
          let tagSetLen = self.sortTags().size-1;
          let counter = 0
          for(let tag of self.sortTags()) {
            tagParam += `${tag}${(counter !== tagSetLen) ? ',' : ''}`;
            counter++;
          }

          var payload = { start: start, ...self.sortParam(), ...self.searchParam(), tags: tagParam };
          $.get('get_questions.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  self.createQuestionModel(data.message.questions, reset);
                  
                  self.totalQuestions(data.message.total_questions);
                  self.questionsLoading(false);
                }
              } else {
                self.questionsLoading(false);
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

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>