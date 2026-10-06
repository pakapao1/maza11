// Shared behaviour for every page: theme toggle, mobile menu, back-to-top, delete confirmations.

const themeBtn = document.getElementById('themeBtn');
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (!themeBtn) return;
    themeBtn.textContent = theme === 'dark' ? '☀ Light' : '☾ Dark';
    themeBtn.setAttribute('aria-pressed', String(theme === 'dark'));
}
applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
themeBtn?.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try { localStorage.setItem('lv-theme', next); } catch (e) {}
});

const navToggle = document.getElementById('navToggle');
navToggle?.addEventListener('click', () => {
    const open = document.getElementById('navMenu').classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(open));
});

const toTop = document.getElementById('toTop');
if (toTop) {
    window.addEventListener('scroll', () => toTop.classList.toggle('show', window.scrollY > 600), { passive: true });
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// Any form with data-confirm asks before submitting (used for deletes).
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', e => {
        if (!confirm(form.dataset.confirm)) e.preventDefault();
    });
});
