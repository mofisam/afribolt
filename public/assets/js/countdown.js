// Live countdown for the Career Launch founding offer. Elements: <span data-countdown="2026-12-01T22:59:00+00:00">.
// The server decides the real price; when the timer hits zero we reload once so the page shows the new price.
(() => {
  const els = document.querySelectorAll('[data-countdown]');
  if (!els.length) return;
  const pad = n => String(n).padStart(2, '0');
  const tick = () => {
    const now = Date.now();
    let expired = null;
    els.forEach(el => {
      const end = Date.parse(el.dataset.countdown);
      if (isNaN(end)) return;
      const ms = end - now;
      if (ms <= 0) { expired = el.dataset.countdown; el.textContent = 'ended'; return; }
      const s = Math.floor(ms / 1000), d = Math.floor(s / 86400);
      el.textContent = (d > 0 ? d + 'd ' : '') + pad(Math.floor(s % 86400 / 3600)) + 'h ' + pad(Math.floor(s % 3600 / 60)) + 'm ' + pad(s % 60) + 's';
    });
    if (expired) {
      try { if (sessionStorage.getItem('cd-reloaded') === expired) return; sessionStorage.setItem('cd-reloaded', expired); } catch (e) {}
      setTimeout(() => location.reload(), 1200);
      return;
    }
    setTimeout(tick, 1000);
  };
  tick();
})();
