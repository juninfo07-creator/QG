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
    var MESES = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
    var formatarData = function (iso, isoFim) {
      var p = (iso || '').split('-');
      if (p.length !== 3) return iso || '';
      var dia = parseInt(p[2], 10);
      var mes = MESES[parseInt(p[1], 10) - 1] || '';
      if (isoFim) {
        var pf = isoFim.split('-');
        if (pf.length === 3) {
          var diaFim = parseInt(pf[2], 10);
          var mesFim = MESES[parseInt(pf[1], 10) - 1] || '';
          return mes === mesFim ? (dia + '-' + diaFim + ' ' + mes) : (dia + ' ' + mes + ' - ' + diaFim + ' ' + mesFim);
        }
      }
      return dia + ' ' + mes;
    };

    fetch('api/eventos.php?_=' + Date.now())
      .then(function (res) { return res.json(); })
      .then(function (eventos) {
        agendaList.innerHTML = eventos.map(function (item) {
          var info = document.createElement('div');
          var strong = document.createElement('strong');
          strong.textContent = item.cidade;
          var span = document.createElement('span');
          span.textContent = item.local + (item.status === 'cancelado' ? ' (cancelado)' : item.status === 'a_confirmar' ? ' (a confirmar)' : '');
          info.appendChild(strong);
          info.appendChild(span);
          var date = document.createElement('div');
          date.className = 'agenda-date';
          date.textContent = formatarData(item.data, item.data_fim);
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
