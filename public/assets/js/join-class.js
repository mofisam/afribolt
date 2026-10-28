// "Join class" buttons. The window check here is UX only - the server re-checks eligibility
// and is the only thing that ever returns the meeting URL.
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  document.querySelectorAll('[data-join-session]').forEach(btn => {
    const start = Date.parse(btn.dataset.starts) - (Number(btn.dataset.early || 10) * 60000);
    const end = Date.parse(btn.dataset.ends);
    const offset = btn.dataset.serverNow ? Date.parse(btn.dataset.serverNow) - Date.now() : 0;
    const msg = btn.parentElement.querySelector('[data-join-msg]');

    const refresh = () => {
      const now = Date.now() + offset;
      btn.disabled = !(now >= start && now <= end);
    };
    refresh();
    setInterval(refresh, 15000);

    btn.addEventListener('click', async () => {
      btn.disabled = true;
      if (msg) msg.textContent = '';
      try {
        const res = await fetch(btn.dataset.endpoint, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        const data = await res.json();
        if (data.ok && data.url) {
          window.open(data.url, '_blank', 'noopener');
          if (msg && data.passcode) { msg.className = 'text-sm text-slate-700'; msg.textContent = 'Meeting passcode: ' + data.passcode; }
        }
        else if (msg) { msg.textContent = data.message || 'Unable to join right now.'; }
      } catch (e) {
        if (msg) msg.textContent = 'Network error. Please try again.';
      } finally { refresh(); }
    });
  });
})();
