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

  var agendaList = document.getElementById('agendaList');
  if (agendaList) {
    fetch('data/agenda.json')
      .then(function (res) { return res.json(); })
      .then(function (datas) {
        agendaList.innerHTML = datas.map(function (item) {
          var info = document.createElement('div');
          var strong = document.createElement('strong');
          strong.textContent = item.cidade;
          var span = document.createElement('span');
          span.textContent = item.local;
          info.appendChild(strong);
          info.appendChild(span);
          var date = document.createElement('div');
          date.className = 'agenda-date';
          date.textContent = item.data;
          var wrap = document.createElement('div');
          wrap.className = 'agenda-item';
          wrap.appendChild(date);
          info.className = 'agenda-info';
          wrap.appendChild(info);
          return wrap.outerHTML;
        }).join('');
      })
      .catch(function () {
        agendaList.innerHTML = '<p style="text-align:center;">Agenda em atualização.</p>';
      });
  }

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
