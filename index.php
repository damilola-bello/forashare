<?php
	include('includes/generic_includes.php');
	//if the user is not logged in, redirect to questions page
	if($loggedin == false) {
    header("Location: questions.php");
    exit(); // Quit the script.
  }
  $page_title = 'Home &ndash; ForaShare';
  $page = HOME;
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
      
      <!-- Question filter header -->
      <div id="question_filter_header" class="filter-header">
        <h1 class="page-title filter-page-title">
          <span data-bind="text: 'Questions'"></span>
          <span data-bind="text: totalQuestions, attr: {class: 'filter-header-count'}"></span>
          <span class="page-title-info" data-bind="text: '[Questions are based on the tags and users you follow]'"></span>
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
              <span data-bind="attr: { class: 'asked-by' }">
                <span data-bind="text: 'asked by '"></span>
                <a data-bind="attr: { href: `user.php?id=${question_user_id}`, class: 'asked-by-link' }, text: question_username"></a>
              </span>
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
        <div data-bind=" if: questionsFetched() == true">
          <div data-bind="attr: { class: 'home-feed-info-box' }">
            <span>
              <span data-bind="text: 'To see more questions, check the '"></span>
              <a href="questions.php" data-bind="text: 'complete list of questions'"></a>
              <span data-bind="text: ', or '"></span>
              <a href="tags.php" data-bind="text: 'all tags'"></a> 
              <span data-bind="text: 'available on ForaShare.'"></span>
            </span>
          </div>
        </div>
      </div>


    </div>

    <script type="text/javascript">

      function QuestionModel(data, filter) {
        var self = this;

        self.answers = data.answers;
        self.how_long = data.how_long;
        self.filter = filter;
        self.question_heading = data.question_heading;
        self.question_id = data.question_id;
        self.question_score = data.question_score;
        self.question_user_id = data.question_user_id;
        self.question_username = data.question_username;
        self.tags = data.tags;
      }



      function AppViewModel() {
        var self = this;

        self.tagsSearchEnabled = ko.observable(false);
        self.questions = ko.observableArray([]);
        self.questionsLoading = ko.observable(false);
        self.questionsFetched = ko.observable(false);
        self.totalQuestions = ko.observable(0);
        self.sortType = ko.observable('newest');
        self.loadingHTML = '<i class="fas fa-spinner loading-icon"></i>';
        self.loadedQuestionsCount = ko.computed(function(){
          return self.questions().length;
        });
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

          var payload = { start: start, ...self.sortParam(), ...self.searchParam() };
          $.get('home_feeds.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  self.createQuestionModel(data.message.questions, reset);
                  
                  self.totalQuestions(data.message.total_questions);
                  self.questionsLoading(false);

                  self.questionsFetched(true);
                }
              } else {
                self.questionsLoading(false);
              }
          }, "json");
        };
        
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