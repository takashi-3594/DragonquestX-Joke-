$(window).scroll(function() {
  if ($(document).scrollTop() > 150) {
    $('header').addClass('shrink');
  } else {
    $('header').removeClass('shrink');
  }
});