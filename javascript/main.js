var lastWidth = 0;
const SEARCHBARTHRESHOLD = 880; //below this width hide the search bar
var searchActive = false;

$(document).ready(function(){
  //Initialize bootstrap tooltip
  $('[data-toggle="tooltip"]').tooltip();
  
  $('body').on('click', bodyClick);
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
  
  //If the sign in pop up form is visible and there is a click outside of the form, close the form.
  if(!$('#sign_in_popup').hasClass('toggle_') && !$target.closest('#sign_in_popup').length && !$target.closest('.sign-in').length) {
    hideLoginPanel();
  }
  
  if($target.closest('#searchbar_trigger').length > 0) {
    searchbarToggle();
  } else if($target.closest('.sign-in').length > 0) {
    bannerLoginFormToggle(e);
  }
}

function hideLoginPanel() {
  $('#sign_in_popup').addClass('toggle_');
  $('.login-icon').removeClass('fa-caret-up').addClass('fa-caret-down');
}

function showLoginPanel() {
  $('#sign_in_popup').removeClass('toggle_');
  $('.login-icon').removeClass('fa-caret-down').addClass('fa-caret-up');
}

function bannerLoginFormToggle (e) {
  if($('#sign_in_popup').hasClass('toggle_')) {
    showLoginPanel();
  } else {
    hideLoginPanel();
  }
  e.preventDefault();
}

function searchbarToggle() {
  if($('#searchbar_trigger').hasClass('banner_icon_active')) {
    $('#searchbar_trigger').removeClass('banner_icon_active');
    $('#searchbar_trigger').attr('data-original-title', 'Open Search');
    $('#search_area').fadeOut();
  } else {
    $('#searchbar_trigger').addClass('banner_icon_active');
    $('#search_area').css('display', 'flex');
    $('#searchbar_trigger').attr('data-original-title', 'Close Search');
    //Set focus to the search box
    $('#page_search_input').focus();
  }
}

function getWindowWidth() {
  return $(window).width();
}
function windowResize() {
  var currentWidth = getWindowWidth();
  
  if(lastWidth < SEARCHBARTHRESHOLD && currentWidth >= SEARCHBARTHRESHOLD) {
    $('#search_area').removeAttr("style");
    $('#searchbar_trigger').removeClass('banner_icon_active');
    
    $('#searchbar_trigger').attr('data-original-title', 'Open Search');
    
    lastWidth = currentWidth;
  } else if(currentWidth < SEARCHBARTHRESHOLD) {
    lastWidth = currentWidth;
  }
}