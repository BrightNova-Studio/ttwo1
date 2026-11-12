// Console helpers. Inline handlers are blocked by the page's Content-Security-Policy, so wire them here.
document.addEventListener('click', async (e) => {
  const copy = e.target.closest('[data-copy]');
  if (copy) {
    try {
      await navigator.clipboard.writeText(copy.dataset.copy);
      const old = copy.textContent;
      copy.textContent = 'Copied';
      setTimeout(() => { copy.textContent = old; }, 1400);
    } catch {
      copy.textContent = 'Copy blocked';
    }
    return;
  }
  if (e.target.closest('[data-close]')) {
    const ov = document.getElementById('cred');
    if (ov) ov.remove();
  }
});

const filter = document.getElementById('room-filter');
if (filter) {
  filter.addEventListener('input', () => {
    const q = filter.value.trim().toLowerCase();
    document.querySelectorAll('.room').forEach((r) => {
      r.hidden = q !== '' && !(r.dataset.search || '').includes(q);
    });
  });
}

document.addEventListener('submit', (e) => {
  const msg = e.target.dataset && e.target.dataset.confirm;
  if (msg && !window.confirm(msg)) e.preventDefault();
});
