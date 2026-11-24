// ══════════════════════════════════════════════
// Loja Gamer — script.js
// ══════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {

  // ── Tema claro/escuro ────────────────────────
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon   = document.getElementById('themeIcon');
  const themeLabel  = document.getElementById('themeLabel');

  // Lê o tema salvo (o inline script já aplicou no <html>,
  // aqui só sincronizamos o ícone)
  function syncThemeButton(theme) {
    if (!themeIcon || !themeLabel) return;
    if (theme === 'light') {
      themeIcon.className  = 'fa-solid fa-moon';
      themeLabel.textContent = 'Escuro';
    } else {
      themeIcon.className  = 'fa-solid fa-sun';
      themeLabel.textContent = 'Claro';
    }
  }

  const savedTheme = localStorage.getItem('loja_theme') || 'dark';
  syncThemeButton(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next    = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('loja_theme', next);
      syncThemeButton(next);
    });
  }

  // ── Notificações ─────────────────────────────
  const notifBtn      = document.getElementById('notifBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifBadge    = document.getElementById('notifBadge');

  if (notifBtn && notifDropdown) {
    let notifOpen = false;

    function loadNotifications() {
      fetch('/api_notifications.php')
        .then(r => r.json())
        .then(data => {
          if (data.count > 0) {
            notifBadge.textContent = data.count > 9 ? '9+' : data.count;
            notifBadge.style.display = 'flex';
          } else {
            notifBadge.style.display = 'none';
          }
          if (!notifOpen) return;

          if (!data.items.length) {
            notifDropdown.innerHTML = `<div class="notif-header"><h4>Notificações</h4></div>
              <div class="notif-empty"><i class="fa-regular fa-bell" style="font-size:1.5rem;display:block;margin-bottom:8px;color:#2e2c45"></i>Sem notificações</div>`;
            return;
          }

          let html = `<div class="notif-header">
            <h4>Notificações</h4>
            <button class="notif-mark-all" onclick="markAllRead()">Marcar tudo lido</button>
          </div><div class="notif-list">`;

          data.items.forEach(n => {
            const bg = n.color + '18';
            html += `<a href="${n.url||'#'}" class="notif-item${n.is_read?'':' unread'}"
                        onclick="markRead(${n.id})">
              <div class="notif-icon" style="background:${bg};color:${n.color}">
                <i class="fa-solid ${n.icon}"></i></div>
              <div class="notif-body">
                <span class="notif-title">${n.title}</span>
                <span class="notif-msg">${n.message}</span>
              </div>
              <span class="notif-time">${n.time}</span>
            </a>`;
          });
          html += `</div><div class="notif-footer"><a href="/minha_conta.php?tab=pedidos">Ver todos →</a></div>`;
          notifDropdown.innerHTML = html;
        });
    }

    window.markRead = function(id) {
      fetch('/api_notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id})
        .then(()=>loadNotifications());
    };
    window.markAllRead = function() {
      fetch('/api_notifications.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id=0'})
        .then(()=>loadNotifications());
    };

    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      notifOpen = !notifOpen;
      notifDropdown.style.display = notifOpen ? 'block' : 'none';
      if (notifOpen) loadNotifications();
    });

    document.addEventListener('click', e => {
      if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifOpen = false;
        notifDropdown.style.display = 'none';
      }
    });

    loadNotifications();
    setInterval(loadNotifications, 60000);
  }

  // ── Autocomplete de busca ────────────────────
  const searchInput = document.querySelector('.header-search input[name="q"]');
  const searchForm  = document.querySelector('.header-search form');

  if (searchInput) {
    let acTimer = null;
    let acDropdown = null;
    let acActive = -1;

    function removeDropdown() {
      if (acDropdown) { acDropdown.remove(); acDropdown = null; acActive = -1; }
    }

    function buildDropdown(results, q) {
      removeDropdown();
      acDropdown = document.createElement('div');
      acDropdown.className = 'autocomplete-dropdown';

      if (!results.length) {
        acDropdown.innerHTML = `<div class="ac-empty">Nenhum resultado para "<strong>${q}</strong>"</div>`;
      } else {
        let html = '';
        const cats = results.filter(r => r.type === 'category');
        const prods = results.filter(r => r.type === 'product');

        if (cats.length) {
          html += '<div class="ac-section-label">Categorias</div>';
          cats.forEach(r => {
            html += `<a href="${r.url}" class="ac-item">
              <div class="ac-icon"><i class="fa-solid fa-folder"></i></div>
              <div class="ac-text"><span class="ac-name">${r.label}</span></div>
            </a>`;
          });
        }
        if (prods.length) {
          html += '<div class="ac-section-label">Produtos</div>';
          prods.forEach(r => {
            const BASE = document.querySelector('meta[name="base-url"]')?.content || '';
            html += `<a href="${r.url}" class="ac-item">
              <img src="${BASE}/${r.img}" alt="${r.label}" class="ac-img" loading="lazy">
              <div class="ac-text">
                <span class="ac-name">${r.label}</span>
                <span class="ac-sub">${r.sub}</span>
              </div>
              <span class="ac-price">${r.price}</span>
            </a>`;
          });
        }
        html += `<div class="ac-footer" onclick="document.querySelector('.header-search form').submit()">
          Ver todos os resultados para "<strong>${q}</strong>" →
        </div>`;
        acDropdown.innerHTML = html;
      }

      searchInput.parentElement.appendChild(acDropdown);
    }

    searchInput.addEventListener('input', function () {
      const q = this.value.trim();
      clearTimeout(acTimer);
      if (q.length < 2) { removeDropdown(); return; }
      acTimer = setTimeout(() => {
        fetch('/api_search.php?q=' + encodeURIComponent(q))
          .then(r => r.json())
          .then(data => buildDropdown(data, q));
      }, 220);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (!acDropdown) return;
      const items = acDropdown.querySelectorAll('.ac-item');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        acActive = Math.min(acActive + 1, items.length - 1);
        items.forEach((el,i) => el.classList.toggle('active', i === acActive));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        acActive = Math.max(acActive - 1, -1);
        items.forEach((el,i) => el.classList.toggle('active', i === acActive));
      } else if (e.key === 'Enter' && acActive >= 0) {
        e.preventDefault();
        items[acActive].click();
      } else if (e.key === 'Escape') {
        removeDropdown();
      }
    });

    document.addEventListener('click', e => {
      if (!searchInput.contains(e.target) && (!acDropdown || !acDropdown.contains(e.target))) {
        removeDropdown();
      }
    });
  }

  // ── Menu hamburguer ──────────────────────────
  const menuToggle = document.getElementById('menuToggle');
  const mainMenu   = document.getElementById('mainMenu');

  if (menuToggle && mainMenu) {
    menuToggle.addEventListener('click', function () {
      mainMenu.classList.toggle('open');
      const isOpen = mainMenu.classList.contains('open');
      menuToggle.setAttribute('aria-expanded', isOpen);
      menuToggle.querySelector('i').className = isOpen
        ? 'fa-solid fa-xmark'
        : 'fa-solid fa-bars';
    });

    // Fecha o menu ao clicar em um link
    mainMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mainMenu.classList.remove('open');
        menuToggle.querySelector('i').className = 'fa-solid fa-bars';
      });
    });

    // Fecha ao clicar fora
    document.addEventListener('click', function (e) {
      if (!menuToggle.contains(e.target) && !mainMenu.contains(e.target)) {
        mainMenu.classList.remove('open');
        menuToggle.querySelector('i').className = 'fa-solid fa-bars';
      }
    });
  }

  // ── Toast Notification ───────────────────────
  window.showToast = function (message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icon = type === 'success' ? 'fa-circle-check'
               : type === 'error'   ? 'fa-circle-xmark'
               : 'fa-circle-info';

    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => toast.classList.add('toast-show'), 10);
    setTimeout(() => {
      toast.classList.remove('toast-show');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  };

  function createToastContainer() {
    const c = document.createElement('div');
    c.id = 'toast-container';
    document.body.appendChild(c);
    return c;
  }

  // ── Swiper: Carrossel de Banners ─────────────
  if (document.querySelector('.mySwiperBanner')) {
    new Swiper('.mySwiperBanner', {
      slidesPerView: 1,
      loop: true,
      autoplay: { delay: 5000, disableOnInteraction: false },
      pagination: { el: '.mySwiperBanner .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.mySwiperBanner .swiper-button-next',
        prevEl: '.mySwiperBanner .swiper-button-prev',
      },
    });
  }

  if (document.querySelector('.mySwiperBanner2')) {
    new Swiper('.mySwiperBanner2', {
      slidesPerView: 1,
      loop: true,
      autoplay: { delay: 5500, disableOnInteraction: false },
      pagination: { el: '.mySwiperBanner2 .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.mySwiperBanner2 .swiper-button-next',
        prevEl: '.mySwiperBanner2 .swiper-button-prev',
      },
    });
  }

  // ── Swiper: Carrossel de Banners ─────────────
  if (document.querySelector('.mySwiperBannerHero')) {
    new Swiper('.mySwiperBannerHero', {
      slidesPerView: 1,
      loop: true,
      effect: 'fade',
      fadeEffect: { crossFade: true },
      autoplay: { delay: 5000, disableOnInteraction: false },
      pagination: {
        el: '.banner-hero-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.banner-hero-next',
        prevEl: '.banner-hero-prev',
      },
      keyboard: { enabled: true },
    });
  }

  if (document.querySelector('.mySwiperBannerStrip')) {
    new Swiper('.mySwiperBannerStrip', {
      slidesPerView: 1,
      spaceBetween: 16,
      loop: true,
      autoplay: { delay: 6000, disableOnInteraction: false },
      pagination: {
        el: '.strip-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.strip-next',
        prevEl: '.strip-prev',
      },
      breakpoints: {
        600: { slidesPerView: 2, spaceBetween: 16 },
      },
    });
  }

  // ── Swiper: Carrossel A ──────────────────────
  if (document.querySelector('.mySwiperA')) {
    new Swiper('.mySwiperA', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: true,
      autoplay: { delay: 3000, disableOnInteraction: false },
      pagination: { el: '.mySwiperA .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.mySwiperA .swiper-button-next',
        prevEl: '.mySwiperA .swiper-button-prev',
      },
      breakpoints: {
        480:  { slidesPerView: 2 },
        768:  { slidesPerView: 3 },
        1024: { slidesPerView: 4 },
      },
    });
  }

  // ── Swiper: Carrossel B ──────────────────────
  if (document.querySelector('.mySwiperB')) {
    new Swiper('.mySwiperB', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: true,
      autoplay: { delay: 4000, disableOnInteraction: false },
      pagination: { el: '.mySwiperB .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.mySwiperB .swiper-button-next',
        prevEl: '.mySwiperB .swiper-button-prev',
      },
      breakpoints: {
        480:  { slidesPerView: 2 },
        768:  { slidesPerView: 3 },
        1024: { slidesPerView: 4 },
      },
    });
  }

  // ── Swiper: Produtos Relacionados ────────────
  if (document.querySelector('.mySwiperRelated')) {
    new Swiper('.mySwiperRelated', {
      slidesPerView: 1,
      spaceBetween: 20,
      loop: true,
      autoplay: { delay: 3500, disableOnInteraction: false },
      pagination: { el: '.mySwiperRelated .swiper-pagination', clickable: true },
      navigation: {
        nextEl: '.mySwiperRelated .swiper-button-next',
        prevEl: '.mySwiperRelated .swiper-button-prev',
      },
      breakpoints: {
        480:  { slidesPerView: 2 },
        768:  { slidesPerView: 3 },
        1024: { slidesPerView: 4 },
      },
    });
  }

  // ── Lazy loading com efeito ──────────────────
  document.querySelectorAll('img[loading="lazy"]').forEach(img => {
    img.addEventListener('load', () => img.classList.add('loaded'));
  });

});
