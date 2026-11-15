// Lesson form: show only the fields that belong to the chosen lesson type (elements carry data-for="video article ...").
(() => {
  const type = document.getElementById('lesson_type');
  if (!type) return;
  const sync = () => {
    document.querySelectorAll('[data-for]').forEach(el => {
      el.hidden = !el.dataset.for.split(/\s+/).includes(type.value);
    });
  };
  type.addEventListener('change', sync);
  sync();
})();
