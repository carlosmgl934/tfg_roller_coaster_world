/**
 * home.js — Animated counter logic for the index/home page stats bar
 */

function animateCount(el, target, suffix = '') {
    if (!el) return;
    const rawTarget = parseInt(target.toString().replace(/,/g, ''));
    if (isNaN(rawTarget)) return;

    let start = 0;
    const duration = 1500;
    const frames = 30;
    const step = Math.ceil(rawTarget / frames);

    const timer = setInterval(() => {
        start = Math.min(start + step, rawTarget);
        el.textContent = start.toLocaleString('es-ES') + suffix;
        if (start >= rawTarget) clearInterval(timer);
    }, duration / frames);
}

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const ids = ['cnt-users', 'cnt-coasters', 'cnt-reviews', 'cnt-photos', 'cnt-parks'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.textContent.trim() !== '—') {
                animateCount(el, el.textContent.trim());
            }
        });
    }, 300);
});
