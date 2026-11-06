// Lesson player: tabs, mark complete, auto-complete on video end, resume position, quizzes, progress UI.
(() => {
  const root = document.getElementById('player');
  if (!root) return;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const base = root.dataset.base;                       // /learn/{slug}/{lessonId}
  const nextUrl = root.dataset.next || '';
  let done = root.dataset.done === '1';
  const post = (path, body) => fetch(base + path, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(body).toString(),
  }).then(r => r.json().then(j => ({ ok: r.ok, ...j })));

  // ---- progress UI ----
  const ring = document.querySelector('.pl-ring');
  const setSummary = s => {
    if (!s) return;
    if (ring) { ring.style.setProperty('--p', s.percent); ring.querySelector('span').textContent = s.percent + '%'; }
    document.querySelectorAll('[data-progress-text]').forEach(el => { el.textContent = s.done + ' of ' + s.total + ' complete'; });
    document.querySelectorAll('[data-progress-bar]').forEach(el => { el.style.width = s.percent + '%'; });
  };
  const markRow = (complete) => document.querySelector('.pl-lesson[aria-current="true"]')?.classList.toggle('done', complete);

  const btn = document.getElementById('complete-btn');
  const msg = document.getElementById('complete-msg');
  const render = () => { if (btn) { btn.textContent = done ? '\u2713 Completed (tap to undo)' : 'Mark as complete'; btn.classList.toggle('btn-primary', !done); btn.classList.toggle('btn-ghost', done); } };
  const setDone = async (value, advance) => {
    if (msg) msg.textContent = '';
    const r = await post('/complete', { done: value ? '1' : '0' });
    if (!r.ok) { if (msg) msg.textContent = r.message || 'Could not save. Try again.'; return false; }
    done = value; render(); markRow(done); setSummary(r.summary);
    if (r.certificate && done) {
      const box = document.getElementById('cert-banner'); if (box) { box.hidden = false; box.querySelector('a').href = root.dataset.certBase + r.certificate; }
    }
    if (done && advance && nextUrl) { location.href = nextUrl; }
    return true;
  };
  btn?.addEventListener('click', () => setDone(!done, false));
  document.getElementById('complete-next')?.addEventListener('click', async () => { if (done || await setDone(true, false)) { location.href = nextUrl; } });
  render();

  // ---- video: auto-complete + resume ----
  const video = document.getElementById('lesson-video');
  if (video) {
    const resumeAt = Number(video.dataset.pos || 0);
    video.addEventListener('loadedmetadata', () => { if (resumeAt > 5 && resumeAt < video.duration - 10) video.currentTime = resumeAt; });
    let last = 0;
    video.addEventListener('timeupdate', () => { const t = Math.floor(video.currentTime); if (t - last >= 10) { last = t; post('/progress', { seconds: t }); } });
    video.addEventListener('ended', () => { if (!done) setDone(true, false); });
  }
  const yt = document.getElementById('lesson-yt');
  if (yt) {
    window.onYouTubeIframeAPIReady = () => {
      new YT.Player('lesson-yt', { events: { onStateChange: e => { if (e.data === 0 && !done) setDone(true, false); } } });
    };
    const s = document.createElement('script'); s.src = 'https://www.youtube.com/iframe_api'; document.head.appendChild(s);
  }

  // ---- quiz ----
  const quiz = document.getElementById('quiz-form');
  if (quiz) {
    const out = document.getElementById('quiz-result');
    quiz.addEventListener('submit', async e => {
      e.preventDefault();
      const fd = new FormData(quiz);
      const body = {}; fd.forEach((v, k) => { if (k.startsWith('q[')) body[k] = v; });
      if (Object.keys(body).length < quiz.querySelectorAll('.quiz-q').length) { out.textContent = 'Please answer every question first.'; out.className = 'mt-4 font-bold text-red-700'; return; }
      const r = await post('/quiz', body);
      if (!r.ok) { out.textContent = r.message || 'Could not grade the quiz.'; return; }
      quiz.querySelectorAll('.quiz-q').forEach(q => {
        const id = q.dataset.q; const res = r.results[id]; if (!res) return;
        q.querySelectorAll('.quiz-opt').forEach(o => {
          const v = Number(o.querySelector('input').value);
          o.classList.remove('right', 'wrong');
          if (res.right.includes(v)) o.classList.add('right'); else if (o.querySelector('input').checked) o.classList.add('wrong');
        });
        const ex = q.querySelector('.quiz-ex'); if (ex) { ex.textContent = res.explanation || ''; ex.hidden = !res.explanation; }
      });
      out.textContent = 'You scored ' + r.score + '% (' + r.correct + ' of ' + r.total + '). ' + (r.passed ? 'Passed! Lesson complete.' : 'You need ' + root.dataset.pass + '% to pass. Review the answers and try again.');
      out.className = 'mt-4 font-bold ' + (r.passed ? 'text-green-700' : 'text-red-700');
      if (r.passed) { done = true; render(); markRow(true); setSummary(r.summary); if (r.certificate) { const b = document.getElementById('cert-banner'); if (b) { b.hidden = false; b.querySelector('a').href = root.dataset.certBase + r.certificate; } } }
    });
  }
})();
