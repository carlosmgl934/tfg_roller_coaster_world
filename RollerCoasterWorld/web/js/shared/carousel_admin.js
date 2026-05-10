// carousel_admin.js — Lógica del editor de imágenes del carrusel (solo admin)
// Se carga solo cuando $is_admin es true (condicional PHP en index.php)

(function () {
    'use strict';

    let _cropperInstance  = null;
    let _cropSlotPos      = null;
    let _cropAspectRatio  = 4;
    let _adminModal       = null;

    // ── Init ────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const editBtn = document.getElementById('carousel-edit-btn');
        if (!editBtn) return;

        editBtn.addEventListener('click', openAdminModal);

        const fileInput = document.getElementById('carousel-file-input');
        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (ev) { showCropper(ev.target.result); };
                reader.readAsDataURL(file);
                e.target.value = '';
            });
        }

        ['carousel-crop-cancel', 'carousel-crop-cancel2'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', hideCropper);
        });

        const confirmBtn = document.getElementById('carousel-crop-confirm');
        if (confirmBtn) confirmBtn.addEventListener('click', confirmCrop);
    });

    // ── Abrir modal y cargar slides ──────────────────────────────────────────
    async function openAdminModal() {
        try {
            const res  = await fetch(BASE_URL + '/api/php/admin/admin_carousel.php?action=get', { credentials: 'include' });
            const data = await res.json();
            renderSlots(data.success ? data.slides : []);
        } catch (_) {
            renderSlots([]);
        }

        if (!_adminModal) {
            _adminModal = new bootstrap.Modal(document.getElementById('carousel-admin-modal'));
        }
        _adminModal.show();
    }

    // ── Renderizar los 4 slots ───────────────────────────────────────────────
    function renderSlots(slides) {
        const grid = document.getElementById('carousel-slots-grid');
        if (!grid) return;

        const map = {};
        slides.forEach(function (s) { map[s.position] = s.image_url; });

        grid.innerHTML = '';
        for (let i = 1; i <= 4; i++) {
            const url = map[i] || null;
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6';

            const imgHtml = url
                ? '<img src="' + url + '" alt="Slide ' + i + '">'
                : '<div class="carousel-slot-empty"><i class="fa-solid fa-image"></i></div>';

            const clearBtn = url
                ? '<button class="btn btn-sm btn-outline-danger rounded-0 py-1 px-2"'
                  + ' data-pos="' + i + '" data-action="clear" title="Eliminar imagen">'
                  + '<i class="fa-solid fa-trash"></i></button>'
                : '';

            col.innerHTML =
                '<div class="carousel-slot-card">'
                +   '<span class="carousel-slot-label">Slide ' + i + '</span>'
                +   imgHtml
                +   '<div class="carousel-slot-actions">'
                +     '<button class="btn btn-sm btn-success rounded-0 py-1 px-2"'
                +       ' data-pos="' + i + '" data-action="pick" title="Cambiar imagen">'
                +       '<i class="fa-solid fa-camera"></i></button>'
                +     clearBtn
                +   '</div>'
                + '</div>';

            grid.appendChild(col);
        }

        // Delegated events (evita onclick inline con template literals)
        grid.addEventListener('click', handleSlotClick);
    }

    function handleSlotClick(e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const pos    = parseInt(btn.dataset.pos);
        const action = btn.dataset.action;
        if (action === 'pick')  pick(pos);
        if (action === 'clear') clearSlide(pos);
    }

    // ── Seleccionar imagen ───────────────────────────────────────────────────
    function pick(pos) {
        _cropSlotPos = pos;
        document.getElementById('carousel-file-input').click();
    }

    // ── Mostrar editor de recorte ────────────────────────────────────────────
    function showCropper(dataUrl) {
        document.getElementById('carousel-slots-view').classList.add('d-none');
        document.getElementById('carousel-cropper-view').classList.remove('d-none');

        const img = document.getElementById('carousel-crop-img');
        img.src = dataUrl;

        // Calcular aspect ratio real del carrusel
        const hero = document.getElementById('home-hero-carousel');
        _cropAspectRatio = hero ? (hero.offsetWidth / hero.offsetHeight) : 4;

        loadCropperJS(function () {
            if (_cropperInstance) { _cropperInstance.destroy(); _cropperInstance = null; }
            _cropperInstance = new Cropper(img, {
                aspectRatio:  _cropAspectRatio,
                viewMode:     1,
                guides:       true,
                autoCropArea: 0.95,
                background:   false,
            });
        });
    }

    // ── Ocultar editor de recorte ────────────────────────────────────────────
    function hideCropper() {
        document.getElementById('carousel-slots-view').classList.remove('d-none');
        document.getElementById('carousel-cropper-view').classList.add('d-none');
        if (_cropperInstance) { _cropperInstance.destroy(); _cropperInstance = null; }
    }

    // ── Confirmar recorte y subir ────────────────────────────────────────────
    function confirmCrop() {
        if (!_cropperInstance || !_cropSlotPos) return;

        const outputH = Math.round(1920 / _cropAspectRatio);
        const canvas  = _cropperInstance.getCroppedCanvas({ width: 1920, height: outputH });

        document.getElementById('carousel-cropper-view').classList.add('d-none');
        document.getElementById('carousel-upload-progress').classList.remove('d-none');

        canvas.toBlob(async function (blob) {
            try {
                const form = new FormData();
                form.append('file',   blob, 'carousel_slide_' + _cropSlotPos + '.webp');
                form.append('bucket', 'news-covers');
                form.append('path',   'carousel');

                const upRes  = await fetch(BASE_URL + '/api/php/upload.php', {
                    method: 'POST', 
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
                    },
                    body: form, 
                    credentials: 'include'
                });
                const upData = await upRes.json();

                if (!upData.success) {
                    alert('Error subiendo imagen: ' + (upData.error || 'Error desconocido'));
                    return;
                }

                const saveRes  = await fetch(BASE_URL + '/api/php/admin/admin_carousel.php?action=update', {
                        method: 'POST',
                        headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '', 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ position: _cropSlotPos, image_url: upData.url }),
                        credentials: 'include',
                    }
                );
                const saveData = await saveRes.json();

                if (saveData.success) {
                    updateSlideDOM(_cropSlotPos, upData.url);
                    hideCropper();
                    await openAdminModal();
                } else {
                    alert('Error guardando en BD: ' + (saveData.message || ''));
                }
            } catch (err) {
                alert('Error inesperado: ' + err.message);
            } finally {
                document.getElementById('carousel-upload-progress').classList.add('d-none');
            }
        }, 'image/webp', 0.87);
    }

    // ── Eliminar imagen de un slot ───────────────────────────────────────────
    async function clearSlide(pos) {
        if (!confirm('¿Eliminar la imagen del Slide ' + pos + '?')) return;

        const res  = await fetch(
            BASE_URL + '/api/php/admin/admin_carousel.php?action=clear&position=' + pos,
            { credentials: 'include' }
        );
        const data = await res.json();
        if (data.success) {
            updateSlideDOM(pos, null);
            await openAdminModal();
        }
    }

    // ── Actualizar el background del slide en el DOM ─────────────────────────
    window.updateSingleCarouselSlide = function (pos, imageUrl) {
        updateSlideDOM(pos, imageUrl);
    };

    function updateSlideDOM(pos, imageUrl) {
        const slides = document.querySelectorAll('.home-hero-slide');
        const slide  = slides[pos - 1];
        if (!slide) return;
        if (imageUrl) {
            slide.style.backgroundImage = "url('" + imageUrl + "')";
        } else {
            const def = slide.dataset.defaultImg;
            if (def) slide.style.backgroundImage = "url('" + def + "')";
        }
    }

    // ── Carga lazy de CropperJS desde CDN ───────────────────────────────────
    function loadCropperJS(cb) {
        if (window.Cropper) { cb(); return; }
        const link  = document.createElement('link');
        link.rel    = 'stylesheet';
        link.href   = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css';
        document.head.appendChild(link);

        const script   = document.createElement('script');
        script.src     = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js';
        script.onload  = cb;
        document.head.appendChild(script);
    }

})();
