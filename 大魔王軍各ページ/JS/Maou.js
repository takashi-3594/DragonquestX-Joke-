document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.getElementById('menuToggle');
  const sideMenu = document.getElementById('sideMenu');
  const closeMenu = document.getElementById('closeMenu');
  const overlay = document.querySelector('.overlay');

  // メニューを開く
  menuToggle.addEventListener('click', function() {
    sideMenu.classList.add('active');
    overlay.classList.add('active');
    menuToggle.classList.add('active');
  });

  // メニューを閉じる
  function closeSideMenu() {
    sideMenu.classList.remove('active');
    overlay.classList.remove('active');
    menuToggle.classList.remove('active');
  }

  // 閉じるボタンでメニューを閉じる
  closeMenu.addEventListener('click', closeSideMenu);

  // オーバーレイクリックでメニューを閉じる
  overlay.addEventListener('click', closeSideMenu);

  // ESCキーでメニューを閉じる
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sideMenu.classList.contains('active')) {
      closeSideMenu();
    }
  });
});

$(window).scroll(function() {
  if ($(document).scrollTop() > 150) {
    $('header').addClass('shrink');
  } else {
    $('header').removeClass('shrink');
  }
});