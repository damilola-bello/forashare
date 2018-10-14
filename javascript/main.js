var lastWidth = 0;
const SEARCH_BAR_THRESHOLD = 880; //below this width hide the search bar
const SIDEBAR_TOGGLE_WIDTH = 650; //This width and below the sidebar is shrunk
const EMAIL_PATT = /^[A-Za-z0-9._%-]{1,64}@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/;
var searchActive = false;

$(document).ready(function(){
  //Initialize bootstrap tooltip
  $('[data-toggle="tooltip"]').tooltip({delay: {show: 500, hide: 100}});
  
  $('#searchbar_trigger').on('click', searchbarToggle);
  $('.sign-in').on('click', bannerLoginFormToggle);
  $('#options').on('click', optionsToggle);
  //sign up click
  $('.signup-form').on('submit', validateSignUp);
  //login click
  $('.login-form').on('submit', validateLoginPage);
  $('.password-toggle').on('click', togglePassword);
  
  //sidebar click
  $('.sidebar').on('click', preventBubbling);
  //more options click
  $('#more_options_popup').on('click', preventBubbling);
  
  
  $('#hamburger_box').click(togglePageNavigation);
  
  //Prevent click on login/sign up when active
  $('.reg-tab-active').on('click', function(e) {
    e.preventDefault();
  })
  $('body').on('click', bodyClick);
  
  $('#question_tags_dropdown .dropdown-item').on( 'click', toggleQuestionTagsDDL);
  
  $(window).resize(windowResize);  
  $('#page_search_input').focusin(function(){
    $('.search-forum-list').addClass('focus');
    
    searchActive = true;
    
    //blur the rest of the page when the search input is in focus
    $('.search-page-blur').fadeIn();
  });
  $('#page_search_input').focusout(function(){
    $('.search-forum-list').removeClass('focus');
  });
  //When the search forum changes
  $('.search-forum-list').on('change', changeSearchFlag);
  
  //Set the search flag on load
  changeSearchFlag();
  
  //Get the width of the browser on load and set lastWidth
  lastWidth = getWindowWidth();
});

function toggleQuestionTagsDDL(event) {
   var $target = $( event.currentTarget ),
       val = $target.attr('data-tag'),
       checked = $target.attr('data-tag-check'),
       $inp = $target.find('input'),
       idx;

   if (checked === '0') {
      setTimeout( function() { 
        $inp.prop( 'checked', true ); 
        $target.attr( 'data-tag-check', '1' ); 
      }, 0);
   } else if (checked === '1') {
      setTimeout( function() { 
        $inp.prop( 'checked', false ) ;
        $target.attr( 'data-tag-check', '0' ); 
      }, 0);
   }

   $( event.target ).blur();
      
   return false;
}

function preventBubbling(e) {
  //function to prevent event bubbling
  e.stopPropagation();
}

function optionsToggle(e) { 
  //if the click was generated from options pop up
  var optionsPopupClick = ($(e.target).closest('#more_options_popup').length === 1) ? true : false;
  $('#options').toggleClass('open-options');
  if($('#options').hasClass('open-options') && optionsPopupClick == false) {
    $('#more_options_popup').show();
    $('#options').addClass('banner-icon-active');
  } else if((!$('#options').hasClass('open-options')) && optionsPopupClick == false) {
    $('#more_options_popup').hide();
    $('#options').removeClass('banner-icon-active');
  }
  //If the hamburger nav is open, close it
  if($('#hamburger_box').hasClass('open-nav')) {
    sidebarHide();
  }
  //If searchbar is active, close it
  if($('#searchbar_trigger').hasClass('banner-icon-active')) {
    searchbarHide();
  }
  //prevent event bubbling
  preventBubbling(e);
}

function sidebarHide(){
  $('#hamburger_box').removeClass('open-nav');
  sidebarSlideUp();
}

function optionsHide(){
  $('#options').removeClass('open-options');
  $('#more_options_popup').hide();
  $('#options').removeClass('banner-icon-active');
}

function togglePageNavigation(e) {
  $('#hamburger_box').toggleClass('open-nav');
  if($('#hamburger_box').hasClass('open-nav')) {
    sidebarSlideDown();
  } else {
    sidebarSlideUp();
  }
  //If the options drop down is open, close it
  if($('#options').hasClass('open-options')) {
    optionsHide();
  }
  //If searchbar is active, close it
  if($('#searchbar_trigger').hasClass('banner-icon-active')) {
    searchbarHide();
  }
  //prevent bubbling
  preventBubbling(e);
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

function changeSearchFlag() {
  //Get the value of the selected forum
  var forumAlphaCode = $(".search-forum-list option:selected").val();
  $(".banner-search-image").removeClass('current-forum');
  $(`*[data-code="${forumAlphaCode}"]`).addClass('current-forum');
}

//This function handles any click that occurs on the page
function bodyClick(e) {
  var $target = $(e.target);
  //if search is active
  if(searchActive === true) {
    //if the click was outside the search area, hide the page blur
    if($target.closest('#search_area').length <= 0) {
      $('.search-page-blur').fadeOut();
      //set the search to inactive
      searchActive = false;
    }
  }
  
  //If the hamburger nav is open and the click was outside it, close it
  if($('#hamburger_box').hasClass('open-nav')) {
    sidebarHide();
  }
  //If the options drop down is open and the click was outside it, close it
  if($('#options').hasClass('open-options')) {
    optionsHide();
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
  var username_patt = /^([a-zA-Z][a-zA-Z0-9_]{5,24})$/;///^[a-zA-Z0-9_]{6,25}$/;
  var password_patt = /^([0-9]+[a-zA-Z_%*]+|[a-zA-Z_%*]+[0-9]+)[0-9a-zA-Z_%*]*$/;///^[A-Za-z0-9._%*$]{6,20}$/;
  var errors = [];
  //var result = patt.test(str);
  
  //Username validation
  if(username.length == 0) {
    errors.push('Username cannot be empty.');
  } else {
    if(username.length < 6 || username.length > 25) {
      errors.push('Username can only have 6-25 characters.');
    } else if(username_patt.test(username) === false) {
      errors.push('Username must start with an alphabet and must contain numbers and alphabets. Underscore(_) is optional.');
    }
  }
  
  //forum validation
  if(forum.length == 0 || forum === 'empty') {
    errors.push('Choose a forum to belong to.');
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

function hideLoginPanel() {
  $('#sign_in_popup').removeClass('open-signin-popup');
  $('#banner_signin').removeClass('open');
  $('.login-icon').removeClass('fa-caret-up').addClass('fa-caret-down');
}

function showLoginPanel() {
  $('#sign_in_popup').addClass('open-signin-popup');
  $('#banner_signin').addClass('open');
  $('.login-icon').removeClass('fa-caret-down').addClass('fa-caret-up');
  $('#email_banner_login').focus();
}

function bannerLoginFormToggle (e) {
  if($('#sign_in_popup').hasClass('open-signin-popup')) {
    hideLoginPanel();
  } else {
    showLoginPanel();
  }
  e.preventDefault();
}

function searchbarHide() {
  //Close the search bar
  $('#searchbar_trigger').removeClass('banner-icon-active');
  $('#searchbar_trigger').attr('data-original-title', 'Open Search');
  $('#search_area').fadeOut();
  $('.search-page-blur').fadeOut();
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
    //If the options drop down is open and the click was outside it, close it
    if($('#options').hasClass('open-options')) {
      optionsHide();
    }
  }
  //Prevent event bubbling
  preventBubbling(e);
}

//Gets the current width of the browser
function getWindowWidth() {
  return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
}
function windowResize() {
  var currentWidth = getWindowWidth();
  
  var changeWidth = false;
  //If the last width had the sidebar hidden, unhide it
  if(lastWidth <= SIDEBAR_TOGGLE_WIDTH && currentWidth > SIDEBAR_TOGGLE_WIDTH) {
    $('.sidebar').removeAttr("style");
    $('#hamburger_box').removeClass('open-nav');
    changeWidth = true;
  }
  if(lastWidth < SEARCH_BAR_THRESHOLD && currentWidth >= SEARCH_BAR_THRESHOLD) {
    $('#search_area').removeAttr("style");
    $('#searchbar_trigger').removeClass('banner-icon-active');
    
    $('#searchbar_trigger').attr('data-original-title', 'Open Search');
    
    changeWidth = true;
  } else if(currentWidth < SEARCH_BAR_THRESHOLD) {
    changeWidth = true;
  }
  
  if(changeWidth) {
    lastWidth = currentWidth;
  }
}