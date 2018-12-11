<?php
  /*
    needed query parameters for this page for now
    id - id of the tag
  */

  require_once('includes/generic_includes.php');

  $is_404 = false;
  $page_heading = "Oops! Tag not found :(";
  //if id query parameter doesn't exist, redirect to questions page
  if(isset($_GET['id']) && !empty($_GET['id'])) {
    $tag_id = trim($_GET['id']);

    //if id is numeric and greater than 0
    if (is_numeric($tag_id) && filter_var($tag_id, FILTER_VALIDATE_INT) != false && intval($tag_id) > 0) {
      $tag_id = intval($tag_id);
      //check if the tag exists and it is a country
      $stmt = $dbc->prepare("SELECT t.tag_name, c.alpha_code
        FROM country_details AS c
        JOIN tag AS t ON c.tag_id = t.tag_id
        JOIN tag_type AS tt ON t.tag_type_id = tt.tag_type_id
        WHERE t.tag_id = ? AND tt.name = 'forum'");
      $stmt->bind_param("i", $tag_id);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows == 1) {
       $stmt->bind_result($country_name, $country_alpha_code); 
       $stmt->fetch();
       $page_heading = $country_name;

        $query_string = "?id=$tag_id";
      } else {
        //tag does not exist
        $is_404 = true;
      }
      $stmt->free_result();
      $stmt->close();

    } else {
      $is_404 = true;
    }

  } else {
    //if id query parameter does not exist
    header("Location: tags.php");
    exit(); // Quit the script.
  }
  $page_title = $page_heading . " &ndash; ForaShare";
  $page = FORUM;
  require('includes/header.php');
?>
<!-- CONTAINER -->
<div class="container-fluid content-container" id="main_content">
  <?php
    require('includes/sidebar.php');
  ?>
  <div class="main-content">
    <div class="create-link-box row">
      <a href="ask.php" class="create-link">Ask Question</a>
    </div>
    <?php
      if($is_404):
        $page_err_msg = "Question not found.";
        require('includes/err404.php');

      else:
        
    ?>
    <div id="tag_filter_header" class="filter-header">
      <h1 class="page-title filter-page-title">
      	<img data-bind="attr: { src: `images/forum-32/${alpha_code}.png`}">
        <span data-bind="text: countryName"></span>
        <span data-bind="text: totalQuestions, attr: { class: 'filter-header-count'}"></span>
      </h1>
      <div class="row filter-controls" id="question_order_box">
        <input type="text" class="form-control filter-input" placeholder="Filter Question" data-bind="value: filterText, valueUpdate:['afterkeydown','propertychange','input']">

        <div class="dropdown filter-order-dropdown">
          <a class="dropdown-toggle filter-dropdown-toggle" href="#" id="tagSortLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bind="text: sortType"></a>
          <ul class="dropdown-menu menu-filter dropdown-menu-right sort-dropdown" aria-labelledby="tagSortLink" id="tag_sort">
            <li class="dropdown-item" data-bind="css: {active: sortType() == 'newest'}, event: { click: (sortType() != 'newest') ? sortQuestions.bind($data, 'newest') : null }">newest</li>
            <li class="dropdown-item" data-bind="css: {active: sortType() == 'oldest'}, event: { click: (sortType() != 'oldest') ? sortQuestions.bind($data, 'oldest') : null }">oldest</li>
            <li class="dropdown-item" data-bind="css: {active: sortType() == 'score'}, event: { click: (sortType() != 'score') ? sortQuestions.bind($data, 'score') : null }">score</li>
            <li class="dropdown-item" data-bind="css: {active: sortType() == 'unanswered'}, event: { click: (sortType() != 'unanswered') ? sortQuestions.bind($data, 'unanswered') : null }">unanswered</li>
          </ul>
        </div>
      </div>
    </div>
    <div class="tag-content-box">
      <div class="loading-box" data-bind="css: { hide: questionsLoading() == false }, html: loadingHTML" title="loading..."></div>
      <!-- Questions -->
      <div class="tag-questions" data-bind=" foreach: questions ">
        <div data-bind="attr: {class:'question-preview'}">
          <div class="question-heading-box">
            <div class="question-meta">
              <span data-bind="text: question_score, css: {positive: question_score > 0, negative: question_score <= 0, score: true}"></span>
              <span data-bind="if: has_image, attr: {title: 'This question has at least one image'}">
                <span data-bind="html: imageIcon()"></span>
              </span>
            </div>
            <div class="question-heading">
              <a data-bind="attr: {href: `question.php?id=${question_id}`, class: 'question-heading-text filter-heading'}, foreach: question_heading">
              	<span class="heading-parts" data-bind="text: value, css: { 'marked-text': is_marked }"></span>
              </a>
              <div class="question-tags-box" data-bind=" foreach: tags ">
                <a data-bind="attr: { href: `${tag_type == 'forum' ? 'country' : 'tag'}.php?id=${tag_id}`, class: `${tag_type}-tag question-tag` }, text: tag_name"></a>
              </div>
            </div>
          </div>
          <ul class="attributes">
            <li class="attribute">
              <span class="count" data-bind="text: question_likes"></span>
              <span data-bind="text: ` Like${question_likes>1 ? 's' : ''}`"></span>
            </li>
            <li data-bind="attr: {class: 'list-divider'}"></li>
            <li class="attribute">
              <span class="count" data-bind="text: question_dislikes"></span>
              <span data-bind="text: ` Dislike${question_dislikes>1 ? 's' : ''}`"></span>
            </li>
            <li data-bind="attr: {class: 'list-divider'}"></li>
            <li class="attribute">
              <span class="count" data-bind="text: answers"></span>
              <span data-bind="text: ` Answer${answers>1 ? 's' : ''}`"></span>
            </li>
          </ul>
          <div data-bind="attr: {class: 'details'}">
            <p>
              <span class="text" data-bind="text: question_text"></span>
            </p>

          </div>
          <div>
            <span class="when" data-bind=" text: ` &ndash; ${how_long}`"></span>
          </div>
        </div>
      </div>
      <!-- End of Question -->
      <div data-bind="if: loadedQuestionsCount() < totalQuestions() && loadedQuestionsCount() > 0">
        <div class="view-more-box">
          <a href="#" data-bind="event: { click: loadQuestions.bind($data, false) }, text: 'view more questions'" class="view-more view-more-big"></a>
          <span class="track track-big" data-bind="text: questionTrack()"></span>
        </div>          
      </div>
      <span data-bind="if: loadedQuestionsCount() > 10 && loadedQuestionsCount() == totalQuestions()">
        <span class="load-more-finished" data-bind="text: 'No more questions to show'"></span>
      </span>
      <p data-bind="if: noQuestions() == true" class="filter-none">
        <span data-bind="text: 'No question'"></span>
      </p>
    </div>
    <script type="text/javascript">
      <?php
        $a = array(
          'tag_id' => $tag_id,
          'country_name' => $country_name,
          'alpha_code' => $country_alpha_code
        );
        echo "var json_payload = " . json_encode($a) . ";";
      ?>

      function imageIcon() {
        return '<i class="fas fa-images images-icon"></i>';
      }

      function QuestionModel(data) {
        var self = this;

        self.answers = data.answers;
        self.how_long = data.how_long;
        self.question_heading = data.question_heading;
        self.question_id = data.question_id;
        self.question_score = data.question_score;
        self.question_likes = data.question_likes;
        self.question_dislikes = data.question_dislikes;
        self.question_text = data.question_text;
        self.has_image = data.has_image;
        self.tags = data.tags;
      }

      function AppViewModel() {
        var self = this;

        self.countryName = json_payload.country_name;
        self.countryID = json_payload.tag_id;
        self.alpha_code = json_payload.alpha_code;

        self.questions = ko.observableArray([]);
        self.questionsLoading = ko.observable(false);
        self.totalQuestions = ko.observable(0);
        self.sortType = ko.observable('newest');
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
        self.sortParam = ko.observable({});
        self.countryIDParam = ko.observable({});
        self.sortQuestions = function(type) {
          let types = ['newest', 'oldest', 'score', 'unanswered'];
          // if the sort type is valid
          if(types.indexOf(type) != -1) {
            let param = {};
            if(type == 'newest'){
              self.sortParam({});
            } else if(type == 'oldest') {
              self.sortParam({date: 'asc'});
            } else if(type == 'score') {
              self.sortParam({score: 'desc'});
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
          var mappedQuestions = questions.map(question => new QuestionModel(question));

          //update the observable array
          if(reset == true) {
            self.questions(mappedQuestions);
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

          var payload = { start: start, country_id: self.countryID, ...self.sortParam(), ...self.searchParam() };
          console.log(payload);
          $.get('get_country_questions.php', payload, 
            function(data, status) {
              if(status == "success") {
                if(data.isErr == false) {
                  //update the observable array
                  self.createQuestionModel(data.message.questions, reset);
                  
                  self.totalQuestions(data.message.total_questions);
                  self.questionsLoading(false);
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
    <?php
      endif;
    ?>

  </div>
</div>
<!-- END OF CONTAINER -->
<?php
  include('includes/footer.php');
?>