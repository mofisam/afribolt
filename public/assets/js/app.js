// Shared UI: mobile menu, dismissible alerts, double-submit protection.
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  if (btn && menu) {
    btn.addEventListener('click', () => {
      const open = menu.classList.toggle('hidden') === false;
      btn.setAttribute('aria-expanded', String(open));
    });
  }
  document.querySelectorAll('[data-dismiss]').forEach(b =>
    b.addEventListener('click', () => b.closest('[role="alert"]')?.remove()));

  document.querySelectorAll('form').forEach(f => f.addEventListener('submit', () => {
    f.querySelectorAll('button[type="submit"]').forEach(b => { setTimeout(() => { b.disabled = true; }, 0); });
  }));
});
