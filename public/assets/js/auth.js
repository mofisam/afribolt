// Auth form helpers: show/hide password, strength + length hint, match check, caps-lock warning.
(() => {
  document.querySelectorAll('[data-toggle-password]').forEach(btn => {
    const input = document.getElementById(btn.dataset.togglePassword);
    if (!input) return;
    btn.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Hide' : 'Show';
      btn.setAttribute('aria-pressed', String(show));
      btn.setAttribute('aria-label', (show ? 'Hide' : 'Show') + ' password');
    });
  });

  document.querySelectorAll('[data-caps-for]').forEach(note => {
    const input = document.getElementById(note.dataset.capsFor);
    if (!input) return;
    const check = e => { note.hidden = !(e.getModifierState && e.getModifierState('CapsLock')); };
    input.addEventListener('keydown', check); input.addEventListener('keyup', check);
    input.addEventListener('blur', () => { note.hidden = true; });
  });

  const pw = document.querySelector('[data-strength-input]');
  if (pw) {
    const min = Number(pw.dataset.min || 8);
    const bar = document.querySelector('[data-strength-bar]');
    const label = document.querySelector('[data-strength-text]');
    const levels = [
      { w: '20%', c: '#dc2626', t: 'Too short' }, { w: '40%', c: '#ea580c', t: 'Weak' },
      { w: '65%', c: '#ca8a04', t: 'Okay' }, { w: '85%', c: '#16a34a', t: 'Good' }, { w: '100%', c: '#15803d', t: 'Strong' },
    ];
    const update = () => {
      const v = pw.value;
      if (!v) { bar.style.width = '0'; label.textContent = 'At least ' + min + ' characters. A short phrase works well.'; label.className = 'hint'; return; }
      let s = 0;
      if (v.length >= min) s++;
      if (v.length >= 12) s++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
      if (/\d/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      const lvl = v.length < min ? levels[0] : levels[Math.min(Math.max(s - 1, 1), 4)];
      bar.style.width = lvl.w; bar.style.backgroundColor = lvl.c;
      label.textContent = v.length < min ? 'Too short: ' + (min - v.length) + ' more character' + (min - v.length === 1 ? '' : 's') + ' needed' : lvl.t;
      label.className = 'hint ' + (v.length < min ? 'bad' : 'ok');
    };
    pw.addEventListener('input', update); update();
  }

  const conf = document.querySelector('[data-match-input]');
  if (conf) {
    const target = document.getElementById(conf.dataset.matchInput);
    const msg = document.querySelector('[data-match-text]');
    const check = () => {
      if (!conf.value) { msg.textContent = ''; conf.classList.remove('invalid'); return; }
      const ok = conf.value === target.value;
      msg.textContent = ok ? 'Passwords match' : 'Passwords do not match yet';
      msg.className = 'hint ' + (ok ? 'ok' : 'bad');
      conf.classList.toggle('invalid', !ok);
    };
    conf.addEventListener('input', check); target.addEventListener('input', check);
  }
})();
