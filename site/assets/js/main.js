var HERO_LOOP_START = 60;  // 1:00
var HERO_LOOP_END = 180;   // 3:00
var heroPlayer = null;

function onYouTubeIframeAPIReady() {
  var iframeEl = document.getElementById('heroPlayer');
  if (!iframeEl) return;
  heroPlayer = new YT.Player('heroPlayer', {
    events: {
      onReady: function (e) {
        e.target.playVideo();
        setInterval(function () {
          if (!heroPlayer || typeof heroPlayer.getCurrentTime !== 'function') return;
          if (heroPlayer.getCurrentTime() >= HERO_LOOP_END) {
            heroPlayer.seekTo(HERO_LOOP_START, true);
            heroPlayer.playVideo();
          }
        }, 500);
      },
      onStateChange: function (e) {
        if (e.data === YT.PlayerState.ENDED) {
          heroPlayer.seekTo(HERO_LOOP_START, true);
          heroPlayer.playVideo();
        }
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', function () {
  var navbar = document.getElementById('navbar');
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');

  function onScroll() {
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }
  window.addEventListener('scroll', onScroll);
  onScroll();

  navToggle.addEventListener('click', function () {
    navLinks.classList.toggle('open');
  });

  navLinks.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      navLinks.classList.remove('open');
    });
  });

  var yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  if ('IntersectionObserver' in window) {
    var revealItems = document.querySelectorAll('.timeline-item, .member-card');
    revealItems.forEach(function (el) { el.style.opacity = '0'; el.style.transform = 'translateY(16px)'; el.style.transition = 'opacity .6s ease, transform .6s ease'; });

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    revealItems.forEach(function (el) { observer.observe(el); });
  }
});
