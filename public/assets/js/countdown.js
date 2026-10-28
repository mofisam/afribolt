// <div data-countdown="2026-11-02T09:00:00Z" data-server-now="...Z" data-reload-on-zero>
//   <span data-unit="days"></span> <span data-unit="hours"></span> ...
// Targets are UTC ISO strings from the server. Nothing is stored as "seconds remaining".
(() => {
  const pad = n => String(n).padStart(2, '0');
  document.querySelectorAll('[data-countdown]').forEach(el => {
    const target = Date.parse(el.dataset.countdown);
    // Correct for a wrong device clock using the server time printed at render time.
    const offset = el.dataset.serverNow ? Date.parse(el.dataset.serverNow) - Date.now() : 0;
    const units = {};
    el.querySelectorAll('[data-unit]').forEach(u => { units[u.dataset.unit] = u; });

    const tick = () => {
      let diff = Math.max(0, Math.floor((target - (Date.now() + offset)) / 1000));
      const d = Math.floor(diff / 86400); diff -= d * 86400;
      const h = Math.floor(diff / 3600);  diff -= h * 3600;
      const m = Math.floor(diff / 60);    const s = diff - m * 60;
      if (units.days) units.days.textContent = d;
      if (units.hours) units.hours.textContent = pad(h);
      if (units.minutes) units.minutes.textContent = pad(m);
      if (units.seconds) units.seconds.textContent = pad(s);
      if (target - (Date.now() + offset) <= 0) {
        clearInterval(timer);
        if ('reloadOnZero' in el.dataset) setTimeout(() => location.reload(), 1500);
      }
    };
    const timer = setInterval(tick, 1000);
    tick();
  });
})();
