// Admin sidebar: mobile drawer + desktop collapse-to-icons (remembered in localStorage).
(() => {
  const sidebar = document.getElementById('adm-sidebar');
  if (!sidebar) return;
  const overlay = document.querySelector('[data-adm-overlay]');
  const openBtns = document.querySelectorAll('[data-adm-open]');
  const closeBtns = document.querySelectorAll('[data-adm-close]');
  let lastFocus = null;

  const setOpen = (open) => {
    sidebar.dataset.open = String(open);
    if (overlay) overlay.hidden = !open;
    openBtns.forEach(b => b.setAttribute('aria-expanded', String(open)));
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) { lastFocus = document.activeElement; sidebar.querySelector('a, button')?.focus(); }
    else if (lastFocus) { lastFocus.focus(); lastFocus = null; }
  };
  openBtns.forEach(b => b.addEventListener('click', () => setOpen(true)));
  closeBtns.forEach(b => b.addEventListener('click', () => setOpen(false)));
  overlay?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && sidebar.dataset.open === 'true') setOpen(false); });
  sidebar.querySelectorAll('a.adm-link').forEach(a => a.addEventListener('click', () => { if (sidebar.dataset.open === 'true') setOpen(false); }));

  const collapseBtn = document.querySelector('[data-adm-collapse]');
  collapseBtn?.addEventListener('click', () => {
    const on = document.documentElement.classList.toggle('adm-collapsed');
    collapseBtn.setAttribute('aria-pressed', String(on));
    collapseBtn.setAttribute('aria-label', on ? 'Expand sidebar' : 'Collapse sidebar');
    try { localStorage.setItem('adm-collapsed', on ? '1' : '0'); } catch (e) {}
  });
  if (collapseBtn && document.documentElement.classList.contains('adm-collapsed')) {
    collapseBtn.setAttribute('aria-pressed', 'true'); collapseBtn.setAttribute('aria-label', 'Expand sidebar');
  }
})();
