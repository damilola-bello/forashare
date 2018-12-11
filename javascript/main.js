var lastWidth = 0;
const SEARCH_BAR_THRESHOLD = 880; //below this width hide the search bar
const SIDEBAR_TOGGLE_WIDTH = 650; //This width and below the sidebar is shrunk
const EMAIL_PATT = /^[A-Za-z0-9._%-]{1,64}@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/;
var searchActive = false;
var clickEl = '';
var searchResultsShown = false;
var defaultFont = 16;

$(document).ready(function() {
  /* EVENTS */
  
  //Initialize bootstrap tooltip
  $('[data-toggle="tooltip"]').tooltip({delay: {show: 500, hide: 100}});
  
  $('#searchbar_trigger').on('click', searchbarToggle);
  
  //sign up click
  $('.signup-form').on('submit', validateSignUp);
  //login click
  $('.login-form').on('submit', validateLoginPage);
  $('.password-toggle').on('click', togglePassword);
  
  //fontsize-toggler 
  $('.fontsize-toggler').on('click', toggleAdjustFontsize);

  //topic checker for ask queskion page
  $('.ask-topic-check .dropdown-item').on('click', toggleTopicsCheck);
  
  //track progress
  $('.progressable-input').on('keyup', updateProgress);
  $('.progressable-input').on('paste', updateProgress);
  
  //sidebar click
  $('.sidebar').on('click', preventBubbling);
  
  $('#hamburger_box').click(togglePageNavigation);
  
  $('.info-content-toggle').on('click', showHelp);
  $('.info-subcontent-title').on('click', showHelpSubcontent);

  $('.change-font').on('click', changeFontSize);
  
  //Prevent click on login/sign up when active
  $('.reg-tab-active').on('click', function(e) {
    e.preventDefault();
  });
  
  //toggle zoom image
  $('.question .question-image').on('click', toggleImageZoom);
  
  $('body').on('click', bodyClick);

  
  $(window).resize(windowResize);
  $('#page_search_input').on('click', function () {
    clickEl = 'page_search_input';
  });
  $('#page_search_input').focusin(function(){
    $('.search-forum-list').addClass('focus');
    $('#search_result_box_outer').show(function () {
      searchResultsShown = true;
    });
    searchActive = true;
    
    //blur the rest of the page when the search input is in focus
    $('.search-page-blur').fadeIn(function(){
      togglePageScroll(false);
    });
    
    clickEl = 'page_search_input';
  });
  $('#page_search_input').focusout(function(){
    if(clickEl != 'search_select_country') {
      $('.search-forum-list').removeClass('focus');
      //$('#search_result_box_outer').fadeOut();
    }
  });
  
  //Get the width of the browser on load and set lastWidth
  lastWidth = getWindowWidth();
  defaultFont = (lastWidth <= SEARCH_BAR_THRESHOLD ? 14 : 16);
  resetFontValues();
  
});

function togglePageScroll(scrollable) {
  let scrollValue = (scrollable) ? 'auto' : 'hidden';
  $('body').css('overflow-y', scrollValue);
}

//This function handles any click that occurs on the page
function bodyClick(e) {
  //if the search was clicked
  if(clickEl == 'page_search_input' || clickEl == 'search_select_country') {
    clickEl = '';
    return;
  }
  $('#search_result_box_outer').fadeOut(function () {
    searchResultsShown = false;
  });
  
  var $target = $(e.target);
  //if search is active
  if(searchActive === true) {
    //if the click was outside the search area, hide the page blur
    if($target.closest('#search_area').length <= 0) {
      $('.search-page-blur').fadeOut(function(){
        togglePageScroll(true);
      });
      //set the search to inactive
      searchActive = false;
    }
  }
  //if the hamburger box was clicked
  if(clickEl == 'hamburger_box') {
    clickEl = '';
    return;
  }
  //If the hamburger nav is open and the click was outside it, close it
  if($('#hamburger_box').hasClass('open-nav')) {
    sidebarHide();
  }
}

function toggleTopicsCheck(event) {

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
    }, 0);
  } else if (checked === '1') {
    setTimeout( function() { 
      //uncheck checkbox
      $inp[0].checked = false;
      $target.attr( 'data-tag-check', '0' ); 
    }, 0);
  }

  var toggleBox = $target.closest('.form-group').find('.dropdown-toggle');
  //if the togglebox has err class and it belongs to askQuestionTagLink, reset it
  if(toggleBox.attr('id') == 'askQuestionTagLink' && toggleBox.hasClass('err')) {
    toggleBox.removeClass('err');
    toggleBox.closest('.form-group').find('.error-lbl').empty();
  }
  
  $( event.target ).blur();
      
  return false;
}

function toggleEligibilityInfo(input) {
  $info = $(input).closest('.question-eligibility-info-box').find('.eligibility-info').toggleClass('hide');
  if($info.hasClass('hide')) {
    $(input).attr('title', 'Show Info');
  } else {
    $(input).attr('title', 'Hide Info');
  }
}

function toggleImageZoom(e) {
  var $target = $(e.currentTarget);
  $target.toggleClass('min max');
  if($target.hasClass('min')) {
    $target.attr('title', 'Zoom In');
  } else {
    $target.attr('title', 'Zoom Out');
  }
}

function updateProgress(e) {
  var $target = $(e.currentTarget);
  //current length - trimmed
  var currentLength = $target.val().trim().length;
  //get last length 
  var lastLength = $target.attr('data-last-length');
  
  //remove error layer if any but dont detach event
  removeError(e, false);
  
  if(currentLength != lastLength) {
    //get the SVG
    var $SVG = $target.closest('.form-group').find('.progress-input-box');
    //get the progress-value circle
    var $progressValueCircle = $SVG.find('.progress-value');
    //get the char left span
    var $progressOverEl = $SVG.find('.progress-char-left');
    //get the stroke-dasharray
    var strokeDashArray = parseFloat($progressValueCircle.attr('stroke-dasharray'));
    //get the max-length
    var maxLength = $target.attr('data-max');
    var charLeft = maxLength - currentLength;
    //over
    if(currentLength > maxLength) {
      $progressValueCircle.attr('stroke-dashoffset', `0rem`);
      $progressValueCircle.removeClass('progress-warn').addClass('progress-over');
      percentDone = 1.0;
      $progressOverEl.removeClass('warn-value').text(charLeft);
    } else {
      $progressValueCircle.removeClass('progress-over');
      if(charLeft <= 10) {
        //show warning color(amber) if characters left is less than or equal to 10
        $progressOverEl.addClass('warn-value').text(charLeft);
        $progressValueCircle.addClass('progress-warn');
      } else {
        $progressValueCircle.removeClass('progress-warn')
        $progressOverEl.empty();
      }
      //get the percent
      var percentDone = currentLength/maxLength;
      //calculate the stroke-dashoffset
      var strokeDashOffset = parseFloat(strokeDashArray * (1 - percentDone));
      //update the stroke-dashoffset
      $progressValueCircle.attr('stroke-dashoffset', `${strokeDashOffset}rem`);
    }
    //set the title
    $SVG.attr('title', `[ ${currentLength} / ${maxLength} ]`);
    //update the length 
    $target.attr('data-last-length', currentLength);
  }
}

function setCookie(cname,cvalue,exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = `expires=${d.toGMTString()}`;
  document.cookie = `${cname}=${cvalue}; ${expires}; path=/`;
}

function removeCookie(cname) {
  document.cookie = `${cname}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

function changeFontSize (input) {
  let $inputEl = $(input.currentTarget);

  if($inputEl.hasClass('disabled')) {
    return;
  }
  //get the font-size
  let type = $inputEl.attr('data-font');

  if(type == "add") {
    fontSize = (fontSize < 22) ? ++fontSize : fontSize;
    if(fontSize >= 22) {
      $inputEl.addClass('disabled');
    } else {
      $('.font-subtract').removeClass('disabled');
    }
  } else if(type == "subtract") {
    fontSize = (fontSize > 13) ? --fontSize : fontSize;
    if(fontSize <= 13) {
      $inputEl.addClass('disabled');
    } else {
      $('.font-add').removeClass('disabled');
    }
  }

  isDefaultFont = 0;
  setFontPercentage(fontSize, isDefaultFont);


  $(":root").css('font-size', `${fontSize}px`);

  //set the cookie to the new font size
  setCookie('font', fontSize, 30);
}

function setFontPercentage (font, defaultFontUsed) {
  let str = '';
  if (!defaultFontUsed) {
    str = `${100 + ((font - defaultFont) * 10)}%`;
  } else {
    str = "100%";
  }
  fontSize = font;
  $('body .font-percentage').text(`${str}`);
}

function toggleAdjustFontsize(e){
  var $target = $(e.currentTarget);
  var $box = $target.closest('.fontsize-adjust');
  //if open, close it
  if($box.hasClass('open')) {
    slideLeftOrRight(0, -160, $box, true);
  } else {
    slideLeftOrRight(-160, 0, $box, true);
  }
}

function resetFont(input) {
  //remove the font-size inline style
  $(":root").css('font-size', '');
  //get the default font-size
  var size = parseInt($(":root").css('font-size'));

  isDefaultFont = 1;

  setFontPercentage(size, isDefaultFont);

  //set the font size
  fontSize = size;

  $('.change-font').removeClass('disabled')
  

  //remove the font cookie
  removeCookie('font');
  
  //prevent default
  return false;
}

function slideLeftOrRight(start, end, $element, isLeft) {
  var minus = (start < end) ? false : true;
  var pos = (isLeft) ? 'left' : 'right';
  //duration is 1 second
  //get the difference between the two points
  var diff = Math.abs(start - end);
  var bits = diff/70;//call the interval every 1ms; 70 times in total.
  var installment = start;
  
  var slide = setInterval(function() {
    //add or subtract the increment/decrement
    installment = (minus) ? installment -= bits : installment += bits;
    //update DOM
    $element.css(pos, installment);
    //check if complete
    if((minus && installment <= end) || (!minus && installment >= end)) {
      clearInterval(slide);
      $element.css(pos, end);
      
      //toggle 'open' class
      $element.toggleClass('open');
    }
  }, 1)
}

function clearImgUpload(e) {
  var $target = $(e);
  var imgWrapper = $target.closest('.image-upload-preview');
  //remove the image
  imgWrapper.find('.upload-preview-image').empty();
  //empty the file input value
  imgWrapper.find('.upload-image').val('');
  //change the prompt text
  imgWrapper.find('.upload-prompt-text').text('Select Image');
  //if image 1 is removed, and image 2 is empty, hide image 2 or if image 2 is removed, hide it
  if(imgWrapper.attr('id') === 'ask_img_1_box' && $('#ask_img_2').val().length == 0 || (imgWrapper.attr('id') === 'ask_img_2_box')) {
    $('#ask_img_2_box').addClass('hide');
  }
}

function togglePhotoBox(el) {
  //find the box, then find the toggle switch
  var $switch = $(el).closest('.add-photos-lbl').find('.switch input[type="checkbox"]');
  var checkboxEl = $switch[0];
  checkboxEl.checked = !checkboxEl.checked;
  addPhotosToggle(checkboxEl);
}

function addPhotosToggle (e) {
  if(e.checked) {
    $('#image_upload_box').removeClass('hide').css('display', 'flex');
  } else {
    $('#image_upload_box').addClass('hide');
  }
}

function uploadQuestionImg(input) {
  var imgURL = '';
  if (input.files && input.files[0]) {
    var imageType = input.files[0].type;
    if(!(imageType === 'image/jpeg' || imageType === 'image/png' || imageType === 'image/gif')) {
      //wrong file type
      alert('Wrong Image type.\nYou can only upload a jpeg, png or gif image.');
      $(input).val('');
      return;
    }
    
    var imageSize = input.files[0].size;
    if(imageSize > 1048576) {
      //image more than 1MB is too large
      alert('Image too large.\nImage can not be more than 1MB in size.');
      $(input).val('');
      return;
    }
    
    var reader = new FileReader();
    reader.onload = function (e) {
      //image data
      imgURL = e.target.result;
      //find the img tag
      $target = $(input);
      var imgWrapper = $target.closest('.image-upload-preview').find('.upload-preview-image');
      imgWrapper.empty().append(`<img src="${imgURL}" class="question-upload-img"><span class="delete-img-upload" onclick="clearImgUpload(this);">&times;</span>`);
      
      //change the name of the buttton
      $target.siblings('.upload-prompt-text').text('Change');
      
      //if the first image is uploaded, display the second one
      if($target.attr('id') === 'ask_img_1') {
        $('#ask_img_2_box').removeClass('hide');
      }
      
    };
    //get the image data
    reader.readAsDataURL(input.files[0]);
  }
}

function showHelpSubcontent(e) {
  var $target = $(e.currentTarget);
  $target.toggleClass('open');
}

function showHelp(e) {
  var $target = $(e.currentTarget);
  $target.toggleClass('open');
}

function askQuestionSubmit() {
  //validate form before submission
  //reset the eroor messages
  var $errorBox = $('#ask_question_form').find('.form-error');
  
  $errorBox.empty();
  $('.error-lbl').empty();
  //get form elements
  var headingEl = $('#ask_question_form').find('.question-heading').removeClass('err')[0];
  var heading = headingEl.value.trim();
  
  var bodyEl = $('#ask_question_form').find('.question-body').removeClass('err')[0];
  var body = bodyEl.value.trim();
  
  var forumEl = $('#ask_question_form').find('.question-forum').removeClass('err')[0];
  var forum = forumEl.value.trim();
  
  var imageBox = $('#ask_question_form .image-upload-box').removeClass('err');
  var image1 = imageBox.find('#ask_img_1_box input[type="file"]')[0].files[0];
  var image2 = imageBox.find('#ask_img_2_box input[type="file"]')[0].files[0];
  
  var tagsBox = $('.dropdown-toggle');
  var tags = $('#ask_question_form').find('.checkbox-dropdown .question-tags');
  var tag_ids = [];
  
  var errCount = 0;
  
  //heading validation
  if(heading.length < 5 || heading.length > 100) {
    var txt = '';
    if(heading.length <= 0) {
      txt = 'Question Heading cannot be empty.';
    } else if(heading.length < 5) {
      txt = 'Question Heading too short.';
    } else {
      txt = 'Question heading cannot be more than 100 characters.';
    }
    $('.question-heading-err').text(txt);
    $(headingEl).addClass('err');
    
    errCount++;
  }
  
  //body validation
  if(body.length < 10 || body.length > 1500) {
    var txt = '';
    if(body.length <= 0) {
      txt = 'Question body cannot be empty.'; 
    } else if (body.length < 10) {
      txt = 'Question body too short.';
    } else {
      txt = 'Question body cannot be more than 1500 characters.';
    }
    $('.question-body-err').text(txt);
    $(bodyEl).addClass('err');
    errCount++;
  }
  
  //forum validation
  if(forum == "empty") {
    $('.question-forum-err').text("Select a country to question to.");
    $(forumEl).addClass('err');
    //add event so when the input changes, the error disappers
    $(forumEl).on('change', removeError);
    errCount++;
  }
  
  //tags validation
  for(var i = 0; i < tags.length; i++) {
    //store the names of the tags
    if(tags[i].checked === true) {
      tag_ids.push(tags[i].value);
    }
  }
  if(tag_ids.length == 0) {
    $('.question-tag-err').text("You must attach at least one topic to your question.");
    tagsBox.addClass('err');
    errCount++;
  } else if(tag_ids.length > 3) {
    $('.question-tag-err').text("You can't attach more than three topics to your question.");
    tagsBox.addClass('err');
    errCount++;
  }
  
  //images validation
  
  //if there are no errors
  if(errCount == 0) {
    $('.ask-question-loading').removeClass('hide');
    
    var formData = new FormData();
    formData.append('heading', heading);
    formData.append('body', body);
    formData.append('forum', forum);
    formData.append('tags', JSON.stringify(tag_ids));
    formData.append('image1', image1);
    formData.append('image2', image2);
    $.ajax({
      url: "make_question.php",
      data: formData,
      processData: false,
      contentType: false,
      type: 'POST',
      success: function(data) {
        var dataObj = JSON.parse(data);
        var msg = dataObj.message;
        
        if(dataObj.isErr === true) {
          //displayError(msg, $errorBox);         
        } else if(dataObj.isErr === false) {
          window.location.replace( `${window.location.protocol}//${window.location.host}/${window.location.hostname == "localhost" ? 'forashare' : ''}/question.php?id=${msg}` );
        }
        $('.ask-question-loading').addClass('hide');
      },
      error(xhr,status,error) {
        /*var errArr = ["An error occured."];
        displayError(errArr, $errorBox);*/
        $('.ask-question-loading').addClass('hide');
      }
    });
  }
}

function displayError(errorArray, $errorBox) {
  var ul = "<ul>";
  for(var i = 0; i < errorArray.length; i++) {
    ul += `<li>${errorArray[i]}</li>`;
  }
  ul += '</ul>';

  //append the error(s) to the error box
  $errorBox.append(ul);
}

function removeError(e, detach = true) {
  var wrapper = $(e.currentTarget);
  if(wrapper.hasClass('err')) {
    //remove the error class
    wrapper.removeClass('err');
    //empty the error message
    wrapper.closest('.form-group').find('.error-lbl').empty();
    
    //detach the event
    if(detach) {
      $(this).off(e.type.trim());
    }
  }
}


function preventBubbling(e) {
  //function to prevent event bubbling
  e.stopPropagation();
}

function sidebarHide(){
  $('#hamburger_box').removeClass('open-nav');
  sidebarSlideUp();
}

function togglePageNavigation(e) {
  $('#hamburger_box').toggleClass('open-nav');
  if($('#hamburger_box').hasClass('open-nav')) {
    sidebarSlideDown();
    clickEl = 'hamburger_box';
  } else {
    sidebarSlideUp();
  }
  //If searchbar is active, close it
  if($('#searchbar_trigger').hasClass('banner-icon-active')) {
    searchbarHide();
  }
}

function sidebarSlideDown() {
  $('.sidebar').show();
}

function sidebarSlideUp() {
  $('.sidebar').hide();
}

function togglePassword(e) {
  var $target = $(e.target);
  var $form = $target.closest('form');
  
  if($target.hasClass('fa-eye')) { 
    //Show password
    $form.find('.password-input').attr('type', 'text');
    //Change the eye icon
    $target.toggleClass('fa-eye-slash fa-eye').attr('title', 'Hide Password');
  } else {
    //Hide password
    $form.find('.password-input').attr('type', 'password');
    //Change the eye icon
    $target.toggleClass('fa-eye-slash fa-eye').attr('title', 'Show Password');
  }
}

function validateLoginPage(e) {
  //get the form the button belongs to
  var $form = $(e.target).closest('form');
  //find the error container
  var $errorBox = $form.find('.form-error');
  var errors = [];
  
  //get the values of all the fields
  var email = $form.find('.email-login').val().trim();
  var password = $form.find('.password-login').val().trim();
  
  //email validation
  if(email.length == 0) {
    errors.push('Email cannot be empty.');
  } else if(EMAIL_PATT.test(email) === false) {
    errors.push('Enter a valid email address.');
  }
  
  //password validation
  if(password.length == 0) {
    errors.push('Enter a password.');
  }
  
  if(errors.length != 0) {
    //print errors
    printError(errors, e, $errorBox);
  }
}

function validateSignUp(e) {
  //If the sign up button is clicked
  
  //get the form the button belongs to
  var $form = $(e.target).closest('form');
  //find the error container
  var $errorBox = $form.find('.form-error');
  
  //get the values of all the fields
  var username = $('#username_signup').val().trim();
  var forum = $('#forum_signup').val().trim();
  var email = $('#email_signup').val().trim();
  var password1 = $('#password1_signup').val().trim();
  var password2 = $('#password2_signup').val().trim();
  
  //username must start with a capital letter and must contain numbers and characters
  var username_patt = /^([a-zA-Z][a-zA-Z0-9_]{2,24})$/;
  var password_patt = /^([0-9]+[a-zA-Z_%*]+|[a-zA-Z_%*]+[0-9]+)[0-9a-zA-Z_%*]*$/;
  var errors = [];
  //var result = patt.test(str);
  
  //Username validation
  if(username.length == 0) {
    errors.push('Username cannot be empty.');
  } else {
    if(username.length < 3 || username.length > 25) {
      errors.push('Username can only have 3-25 characters.');
    } else if(username_patt.test(username) === false) {
      errors.push('Username must start with an alphabet and must contain numbers and alphabets. Underscore(_) is optional.');
    }
  }
  
  //forum validation
  if(forum.length == 0 || forum === 'empty') {
    errors.push('Choose a country to belong to.');
  }
  
  //email validation
  if(email.length == 0) {
    errors.push('Email cannot be empty.');
  } else if(EMAIL_PATT.test(email) === false) {
    errors.push('Enter a valid email address.');
  }
  
  //password validation
  if(password1.length == 0 || password2.length == 0) {
    errors.push('Enter a password in both fields.');
  } else {
    if(password1 !== password2) {
      errors.push('Passwords don\'t match.');
    } else if(password1.length < 6 || password1.length > 20) {
      errors.push('Password must have 6-20 characters.');
    }
    else if(password_patt.test(password1) === false) { //only passowrd1 is used because at this point password1 is equals to password2
      errors.push('Password must contain numbers and alphabets. Special characters like _*% are allowed.');
    }
  }
  
  if(errors.length != 0) {
    //print errors
    printError(errors, e, $errorBox);
  }
}

function printError(errors, e, $errorBox) {
  //empty the error container
  $errorBox.empty();
  
  var ul = '<ul>';
  for(var i=0; i<errors.length; i++) {
    ul += `<li>${errors[i]}</li>`;
  }
  ul += '</li>';
  $errorBox.append(ul);
  //Prevent the form submission
  e.preventDefault();
}

function searchbarHide() {
  //Close the search bar
  $('#searchbar_trigger').removeClass('banner-icon-active');
  $('#searchbar_trigger').attr('data-original-title', 'Open Search');
  $('#search_area').fadeOut();
  $('.search-page-blur').fadeOut(function(){
    togglePageScroll(true);
  });
  $('#search_result_box_outer').fadeOut(function () {
    searchResultsShown = false;
  });
  //set the search to inactive
  searchActive = false;
}

function searchbarToggle(e) {
  if($('#searchbar_trigger').hasClass('banner-icon-active')) {
    searchbarHide();
  } else {
    //Open the search bar
    $('#searchbar_trigger').addClass('banner-icon-active');
    $('#search_area').css('display', 'flex');
    $('#searchbar_trigger').attr('data-original-title', 'Close Search');
    //Set focus to the search box
    $('#page_search_input').focus();
    
    //If the hamburger nav is open and the click was outside it, close it
    if($('#hamburger_box').hasClass('open-nav')) {
      sidebarHide();
    }
  }
  
}

//Gets the current width of the browser
function getWindowWidth() {
  return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
}
function windowResize() {
  var currentWidth = getWindowWidth();
  defaultFont = (currentWidth <= SEARCH_BAR_THRESHOLD ? 14 : 16);
  
  var changeWidth = false;
  //If the last width had the sidebar hidden, unhide it
  if(lastWidth <= SIDEBAR_TOGGLE_WIDTH && currentWidth > SIDEBAR_TOGGLE_WIDTH) {
    $('.sidebar').removeAttr("style");
    $('#hamburger_box').removeClass('open-nav');
    changeWidth = true;
  } 
  //console.log("---", lastWidth, SEARCH_BAR_THRESHOLD, currentWidth);
  if(lastWidth < SEARCH_BAR_THRESHOLD && currentWidth >= SEARCH_BAR_THRESHOLD) {
    $('#search_area').removeAttr("style");
    $('#searchbar_trigger').removeClass('banner-icon-active');
    
    $('#searchbar_trigger').attr('data-original-title', 'Open Search');

    resetFontValues();

    changeWidth = true;
  } else if(currentWidth <= SEARCH_BAR_THRESHOLD) {
    if(lastWidth >= SEARCH_BAR_THRESHOLD && searchResultsShown == true) {
      $('#searchbar_trigger').click();
    }
    resetFontValues();
    changeWidth = true;
  }
  
  if(changeWidth) {
    lastWidth = currentWidth;
  }
}

function resetFontValues() {
  if(!isDefaultFont) {
    setFontPercentage(fontSize, isDefaultFont);
  } else {
    let tempFont = parseInt($(":root").css('font-size'));
    let currentFont = (tempFont != defaultFont) ? tempFont : defaultFont;
    setFontPercentage(currentFont, isDefaultFont);
  }
}