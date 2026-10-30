// Legal pages: highlight current section in the contents list. FAQ: live search. Print button.
(() => {
  const toc = document.querySelectorAll('.toc a[href^="#"]');
  const heads = [...document.querySelectorAll('.legal h2[id]')];
  if (toc.length && heads.length && 'IntersectionObserver' in window) {
    const mark = id => toc.forEach(a => a.setAttribute('aria-current', String(a.getAttribute('href') === '#' + id)));
    const io = new IntersectionObserver(entries => {
      const vis = entries.filter(e => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
      if (vis) mark(vis.target.id);
    }, { rootMargin: '-80px 0px -70% 0px' });
    heads.forEach(h => io.observe(h));
  }
  document.querySelectorAll('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));

  const search = document.getElementById('faq-search');
  if (search) {
    const items = [...document.querySelectorAll('.faq details')];
    const groups = [...document.querySelectorAll('[data-faq-group]')];
    const empty = document.getElementById('faq-empty');
    const count = document.getElementById('faq-count');
    const run = () => {
      const q = search.value.trim().toLowerCase();
      let shown = 0;
      items.forEach(d => {
        const hit = !q || d.textContent.toLowerCase().includes(q);
        d.hidden = !hit; if (hit) shown++;
        if (q && hit) d.open = true; else if (!q) d.open = false;
      });
      groups.forEach(g => { g.hidden = ![...g.querySelectorAll('details')].some(d => !d.hidden); });
      if (empty) empty.hidden = shown !== 0;
      if (count) count.textContent = q ? shown + ' result' + (shown === 1 ? '' : 's') : '';
    };
    search.addEventListener('input', run);
    if (location.hash) { const t = document.querySelector(location.hash + ' details, details' + location.hash); if (t) t.open = true; }
  }
})();
