// Shared UI: mobile menu, dismissible alerts, double-submit protection.
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  if (btn && menu) {
    const iconOpen = btn.querySelector('[data-icon-open]');
    const iconClose = btn.querySelector('[data-icon-close]');
    const set = (open) => {
      menu.hidden = !open;
      btn.setAttribute('aria-expanded', String(open));
      btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      if (iconOpen) iconOpen.style.display = open ? 'none' : '';
      if (iconClose) iconClose.style.display = open ? '' : 'none';
    };
    set(false);
    btn.addEventListener('click', () => set(menu.hidden));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !menu.hidden) { set(false); btn.focus(); } });
    document.addEventListener('click', e => { if (!menu.hidden && !menu.contains(e.target) && !btn.contains(e.target)) set(false); });
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => set(false)));
    window.matchMedia('(min-width: 768px)').addEventListener('change', e => { if (e.matches) set(false); });
  }
  document.querySelectorAll('[data-dismiss]').forEach(b =>
    b.addEventListener('click', () => b.closest('[role="alert"]')?.remove()));

  document.querySelectorAll('form').forEach(f => f.addEventListener('submit', () => {
    f.querySelectorAll('button[type="submit"]').forEach(b => { setTimeout(() => { b.disabled = true; }, 0); });
  }));
});
