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


// Admin tables: "more options" (three dots) menu. Opens next to the button, closes on outside click, scroll or Escape; arrow keys move between items.
(() => {
  let openBtn = null;
  const menuOf = (btn) => document.getElementById(btn.getAttribute('aria-controls'));
  const close = (refocus) => {
    if (!openBtn) return;
    const m = menuOf(openBtn); if (m) m.hidden = true;
    openBtn.setAttribute('aria-expanded', 'false');
    if (refocus) openBtn.focus();
    openBtn = null;
  };
  const place = (btn, menu) => {
    menu.hidden = false;
    const r = btn.getBoundingClientRect(), mw = menu.offsetWidth, mh = menu.offsetHeight;
    let top = r.bottom + 4;
    if (top + mh > window.innerHeight - 8 && r.top - mh - 4 > 8) top = r.top - mh - 4; // flip above when near the bottom
    const left = Math.min(Math.max(8, r.right - mw), window.innerWidth - mw - 8);
    menu.style.top = top + 'px'; menu.style.left = left + 'px';
  };
  document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-menu-toggle]');
    if (t) {
      e.preventDefault();
      const same = openBtn === t; close(false);
      if (!same) { const m = menuOf(t); if (m) { place(t, m); t.setAttribute('aria-expanded', 'true'); openBtn = t; m.querySelector('[role="menuitem"]')?.focus(); } }
      return;
    }
    if (openBtn && !e.target.closest('.act-menu')) close(false);
  });
  document.addEventListener('keydown', (e) => {
    if (!openBtn) return;
    if (e.key === 'Escape') { e.preventDefault(); close(true); return; }
    if (e.key === 'Tab') { close(false); return; }
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      const items = [...menuOf(openBtn).querySelectorAll('[role="menuitem"]')];
      const i = items.indexOf(document.activeElement);
      items[e.key === 'ArrowDown' ? (i + 1) % items.length : (i - 1 + items.length) % items.length]?.focus();
    }
  });
  window.addEventListener('resize', () => close(false));
  window.addEventListener('scroll', () => close(false), true);
})();
