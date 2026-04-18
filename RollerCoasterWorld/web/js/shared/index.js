// index.js — Lógica de la página de inicio (dashboard y landing)
// Carga datos desde api/php/index.php y los inyecta en el DOM.

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Anima un elemento numérico desde 0 hasta el valor objetivo.
 * @param {HTMLElement}  el      - Elemento cuyo textContent se anima
 * @param {number}       target  - Valor final numérico
 * @param {string}      [prefix=''] - Prefijo (p.ej. '+')
 */
function animateCount(el, target, prefix = '') {
    if (!el || isNaN(target)) return;

    const duration = 1500;
    const frames   = 30;
    const step     = Math.ceil(target / frames);
    let current    = 0;

    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = prefix + current.toLocaleString('es-ES');
        if (current >= target) clearInterval(timer);
    }, duration / frames);
}

/** Rellena un elemento por ID si existe */
function setEl(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value ?? '—';
}

// ── Inicialización ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    const isDashboard = !!document.getElementById('hero-username');
    const isLanding   = !!document.getElementById('landing-cnt-coasters');

    if (isDashboard) {
        initCarousel();
        loadDashboard();
        loadGlobalStats();
    } else if (isLanding) {
        loadLandingStats();
    }
});

// Nombre del usuario que se mantiene al rotar slides
let _heroUsername = '';

// ── Carrusel del hero ──────────────────────────────────────────────────────────
function initCarousel() {
    const slides   = document.querySelectorAll('.home-hero-slide');
    const dots     = document.querySelectorAll('.home-hero-dot');
    const btnPrev  = document.getElementById('hero-prev');
    const btnNext  = document.getElementById('hero-next');
    const hero     = document.getElementById('home-hero-carousel');

    if (!slides.length) return;

    let current = 0;
    let timer   = null;
    const INTERVAL = 4000;

    function goTo(idx) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (idx + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
        updateContent(slides[current]);
    }

    function updateContent(slide) {
        const titleEl = document.getElementById('hero-title');
        const subEl   = document.getElementById('hero-sub');
        const tagEl   = document.getElementById('hero-tag-text');
        const btn1    = document.getElementById('hero-btn1');
        const btn2    = document.getElementById('hero-btn2');

        if (tagEl)   tagEl.textContent = slide.dataset.tag || '';
        if (subEl)   subEl.textContent = slide.dataset.sub || '';
        if (titleEl) {
            titleEl.innerHTML = slide.dataset.title || '';
            // Reinyectar nombre si ya se cargó
            const uEl = document.getElementById('hero-username');
            if (uEl && _heroUsername) uEl.textContent = _heroUsername;
        }

        if (btn1 && slide.dataset.btn1Url) {
            btn1.href = slide.dataset.btn1Url;
            btn1.innerHTML = `<i class="fa-solid ${slide.dataset.btn1Icon || 'fa-link'}"></i> ${slide.dataset.btn1Label || ''}`;
        }
        if (btn2 && slide.dataset.btn2Url) {
            btn2.href = slide.dataset.btn2Url;
            btn2.innerHTML = `<i class="fa-solid ${slide.dataset.btn2Icon || 'fa-link'}"></i> ${slide.dataset.btn2Label || ''}`;
        }
    }

    function startAuto() {
        clearInterval(timer);   // Evita que se acumulen timers si se llama varias veces
        timer = setInterval(() => goTo(current + 1), INTERVAL);
    }
    function stopAuto()  { clearInterval(timer); }

    dots.forEach((dot, i) => dot.addEventListener('click', () => { stopAuto(); goTo(i); startAuto(); }));
    if (btnPrev) btnPrev.addEventListener('click', () => { stopAuto(); goTo(current - 1); startAuto(); });
    if (btnNext) btnNext.addEventListener('click', () => { stopAuto(); goTo(current + 1); startAuto(); });
    if (hero) {
        hero.addEventListener('mouseenter', stopAuto);
        hero.addEventListener('mouseleave', startAuto);
    }

    updateContent(slides[0]);
    startAuto();
}

// ── Dashboard (usuario logueado) ───────────────────────────────────────────────
async function loadDashboard() {
    try {
        const res  = await fetch(`${BASE_URL}/api/php/index.php?action=dashboard`, { credentials: 'include' });
        const data = await res.json();
        if (!data.success) return;

        const { user, stats, news } = data;

        // Hero
        _heroUsername = user.username;
        setEl('hero-username', user.username);

        // Perfil mini
        setEl('profile-name',     user.username);
        setEl('profile-location', user.location);
        setEl('fav-coaster',      user.favorite_coaster);

        // Avatar
        const avatarWrap = document.getElementById('profile-avatar-wrap');
        if (avatarWrap) {
            if (user.profile_image) {
                avatarWrap.innerHTML = `
                    <div class="home-profile-mini-avatar"
                         style="background-image:url('${user.profile_image}');background-size:cover;background-position:center;">
                    </div>`;
            } else {
                const initial = (user.username || '?').charAt(0).toUpperCase();
                avatarWrap.innerHTML = `
                    <div class="home-profile-mini-avatar d-flex justify-content-center align-items-center fw-bold fs-4 bg-success text-white">
                        ${initial}
                    </div>`;
            }
        }

        // Estadísticas personales
        setEl('stat-credits', stats.credits);
        setEl('stat-reviews', stats.reviews);
        setEl('stat-parks',   stats.parks_visited);
        setEl('stat-trips',   stats.trips);
        setEl('stat-friends', stats.friends);
        setEl('stat-photos',  stats.photos);

        // Noticias
        renderNews(news);

        // Imágenes del carrusel (BD → override de defaults)
        try {
            const crRes  = await fetch(BASE_URL + '/api/php/admin/admin_carousel.php?action=get', { credentials: 'include' });
            const crData = await crRes.json();
            if (crData.success) {
                crData.slides.forEach(function (s) {
                    if (s.image_url && typeof window.updateSingleCarouselSlide === 'function') {
                        window.updateSingleCarouselSlide(s.position, s.image_url);
                    } else if (s.image_url) {
                        var slides = document.querySelectorAll('.home-hero-slide');
                        var slide  = slides[s.position - 1];
                        if (slide) slide.style.backgroundImage = "url('" + s.image_url + "')";
                    }
                });
            }
        } catch (_) { /* silencioso */ }

    } catch (err) {
        console.error('Error cargando dashboard:', err);
    }
}

// ── Estadísticas globales (barra de contadores, dashboard) ────────────────────
async function loadGlobalStats() {
    try {
        const res  = await fetch(`${BASE_URL}/api/php/index.php?action=stats`);
        const data = await res.json();
        if (!data.success) return;

        const s = data;

        setTimeout(() => {
            animateCount(document.getElementById('cnt-users'),    s.users);
            animateCount(document.getElementById('cnt-coasters'), s.coasters);
            animateCount(document.getElementById('cnt-reviews'),  s.reviews);
            animateCount(document.getElementById('cnt-photos'),   s.photos);
            animateCount(document.getElementById('cnt-parks'),    s.parks);
        }, 300);

    } catch (err) {
        console.error('Error cargando stats globales:', err);
    }
}

// ── Estadísticas para la landing (guest) ──────────────────────────────────────
async function loadLandingStats() {
    try {
        const res  = await fetch(`${BASE_URL}/api/php/index.php?action=stats`);
        const data = await res.json();
        if (!data.success) return;

        const s = data;

        setTimeout(() => {
            // Mini stats del hero
            const elCoasters = document.getElementById('landing-cnt-coasters');
            const elParks    = document.getElementById('landing-cnt-parks');
            if (elCoasters) animateCount(elCoasters, s.coasters);
            if (elParks)    animateCount(elParks,    s.parks);

            // Grid de features (necesita el total de coasters)
            renderFeatures(s.coasters);
        }, 300);

    } catch (err) {
        console.error('Error cargando stats landing:', err);
    }
}

// ── Render: noticias ─────────────────────────────────────────────────────────

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    // Restar horas para compensar desfases horarios si es necesario, o comparar directamente
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        if (diffHours === 0) return 'Hace un momento';
        return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
    }
    if (diffDays === 1) return 'Hace 1 día';
    if (diffDays < 30) return `Hace ${diffDays} días`;

    const diffMonths = Math.floor(diffDays / 30);
    if (diffMonths === 1) return 'Hace 1 mes';
    if (diffMonths < 12) return `Hace ${diffMonths} meses`;

    const diffYears = Math.floor(diffDays / 365);
    if (diffYears === 1) return 'Hace 1 año';
    return `Hace ${diffYears} años`;
}

function renderNews(news) {
    const grid = document.getElementById('news-grid');
    if (!grid) return;

    if (!news || news.length === 0) {
        grid.innerHTML = `
            <div class="text-center text-muted p-5 bg-dark border rounded-0 w-100">
                <i class="fa-solid fa-newspaper fa-3x mb-3 opacity-25"></i>
                <p>No hay novedades publicadas por el momento.</p>
            </div>`;
        return;
    }

    const [featured, ...small] = news;

    const imgSrc = (item) => {
        if (!item.image_url) return '';
        return item.image_url.startsWith('http') ? item.image_url : BASE_URL + item.image_url;
    };

    const tagIcon = (tag) => tag === 'Destacado' ? 'fa-bolt' : 'fa-info-circle';

    const renderCard = (item, isBig) => {
        const cls = isBig ? 'big' : 'small';
        const img = item.image_url ? imgSrc(item) : '';
        const noPhotoCls = !img ? 'no-photo' : '';
        
        let bodyHtml = '';
        if (isBig) {
            bodyHtml = `
                <div class="home-news-tag">
                    <i class="fa-solid ${tagIcon(item.tag)} me-1"></i>${item.tag || 'Novedad'}
                </div>
                <div class="home-news-title">${item.title}</div>
                <div class="home-news-desc">${item.description}</div>
                <span class="home-news-read-more">
                    Leer más <i class="fa-solid fa-arrow-right" style="font-size:0.65rem;"></i>
                </span>
                <div class="home-news-date">
                    <i class="fa-regular fa-clock me-1"></i>${timeAgo(item.created_at)}
                </div>`;
        } else {
            bodyHtml = `
                <div class="home-news-tag">${item.tag || 'Info'}</div>
                <div class="home-news-title">${item.title}</div>
                <div class="home-news-desc">${item.description}</div>`;
        }

        return `
            <a href="#" class="home-news-card ${cls} ${noPhotoCls}" onclick="openNewsModal(this.dataset.news); return false;" data-news="${encodeURIComponent(JSON.stringify(item))}">
                ${img ? `<img src="${img}" class="home-news-img" alt="">` : ''}
                <div class="home-news-body ${!isBig ? 'd-flex flex-column justify-content-center' : ''}">
                    ${bodyHtml}
                </div>
            </a>`;
    };

    grid.innerHTML = renderCard(featured, true) + `<div class="home-news-small-col">${small.map(n => renderCard(n, false)).join('')}</div>`;
}

// ── Abrir Modal de Noticias ──────────────────────────────────────────────────
function openNewsModal(newsDataString) {
    if (!newsDataString) return;
    try {
        const item = JSON.parse(decodeURIComponent(newsDataString));
        
        document.getElementById('news-modal-title').textContent = item.title;
        document.getElementById('news-modal-desc').textContent = item.description;
        document.getElementById('news-modal-tag').textContent = item.tag || 'Novedad';
        document.getElementById('news-modal-date').textContent = timeAgo(item.created_at);
        
        const imgEl = document.getElementById('news-modal-img');
        if (item.image_url) {
            imgEl.src = item.image_url.startsWith('http') ? item.image_url : BASE_URL + item.image_url;
            imgEl.classList.remove('d-none');
        } else {
            imgEl.src = '';
            imgEl.classList.add('d-none');
        }
        
        // Link opcional
        let footer = document.querySelector('#news-modal .modal-footer');
        let oldLink = document.getElementById('news-modal-link-btn');
        if (oldLink) oldLink.remove();
        
        if (item.external_link && item.external_link.trim() !== '') {
            const btn = document.createElement('a');
            btn.id = 'news-modal-link-btn';
            btn.href = item.external_link;
            btn.target = '_blank';
            btn.className = 'btn btn-success fw-bold rounded-0 px-4 ms-auto';
            btn.innerHTML = 'Ver más <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>';
            footer.insertBefore(btn, footer.firstChild);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('news-modal'));
        modal.show();
    } catch (e) {
        console.error('Error al abrir la noticia', e);
    }
}

// ── Render: features grid (landing) ──────────────────────────────────────────
function renderFeatures(totalCoasters) {
    const grid = document.getElementById('features-grid');
    if (!grid) return;

    const count = totalCoasters ? totalCoasters.toLocaleString('es-ES') : '10.000';

    const features = [
        { icon: 'fa-database',         title: 'Base de datos completa',  desc: `Más de ${count} coasters indexados de todo el mundo con datos técnicos completos.` },
        { icon: 'fa-list-ol',          title: 'Tops personalizados',     desc: 'Crea y ordena tu ranking personal. Compáralo con el de la comunidad.' },
        { icon: 'fa-suitcase-rolling', title: 'Gestor de viajes',        desc: 'Planifica tus rutas por parques, calcula distancias y gestiona tus trips.' },
        { icon: 'fa-camera',           title: 'Galería de fotos',        desc: 'Sube fotos de tus visitas y descubre las de otros usuarios.' },
        { icon: 'fa-comments',         title: 'Foros especializados',    desc: 'Debate sobre coasters, parques, noticias y novedades del sector.' },
        { icon: 'fa-trophy',           title: 'Ranking global',          desc: 'Compite con otros enthusiasts y escala en el ranking de credits.' },
    ];

    grid.innerHTML = features.map(f => `
        <div class="col-sm-6 col-lg-4">
            <div class="landing-feature-card h-100 p-4 text-center rounded-0">
                <i class="fa-solid ${f.icon} fa-2x text-neon mb-3"></i>
                <h5 class="fw-bold mb-2 text-white">${f.title}</h5>
                <p class="text-muted small mb-0">${f.desc}</p>
            </div>
        </div>`).join('');
}
