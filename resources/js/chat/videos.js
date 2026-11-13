import { h } from './util.js';
import { icon } from './icons.js';

// How-to videos on the home page. Everything is served from our own site (no third-party player, no tracking).
let open = null;

function close() {
  if (!open) return;
  const { overlay, video, onKey, opener } = open;
  open = null;
  video.pause();
  video.removeAttribute('src');
  video.load(); // stop downloading
  document.removeEventListener('keydown', onKey);
  overlay.classList.remove('in');
  setTimeout(() => overlay.remove(), 200);
  document.body.classList.remove('has-modal');
  opener?.focus?.();
}

function play(el) {
  if (open) return;
  const { video: src, poster, captions, title, page } = el.dataset;
  const video = h('video', { controls: '', playsinline: '', preload: 'metadata', poster, 'aria-label': title });
  video.src = src;
  if (captions) {
    const track = h('track', { kind: 'captions', srclang: 'en', label: 'English', src: captions });
    video.append(track);
  }

  const failed = h('p', { class: 'vfail', hidden: true }, 'The video could not be played. ', h('a', { href: src }, 'Open it directly'), '.');
  video.addEventListener('error', () => { failed.hidden = false; });

  const overlay = h('div', { class: 'overlay vmodal', role: 'dialog', 'aria-modal': 'true', 'aria-label': title },
    h('div', { class: 'vbox' },
      h('div', { class: 'vhead' }, h('b', {}, title), h('button', { class: 'icon-btn', type: 'button', 'aria-label': 'Close video', onclick: close, html: icon('x') })),
      video, failed,
      h('div', { class: 'vfoot' }, h('a', { href: page }, 'Read the written guide'), h('span', {}, 'Captions: tap CC in the player'))));
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });

  const onKey = (e) => { if (e.key === 'Escape') close(); };
  document.addEventListener('keydown', onKey);
  document.body.append(overlay);
  document.body.classList.add('has-modal');
  requestAnimationFrame(() => overlay.classList.add('in'));
  open = { overlay, video, onKey, opener: el };
  video.play().catch(() => { /* the viewer presses play */ });
}

document.addEventListener('click', (e) => {
  const el = e.target.closest?.('[data-video]');
  if (!el) return;
  e.preventDefault();
  play(el);
});
