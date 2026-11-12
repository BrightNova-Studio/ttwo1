import { h } from './util.js';

// Self-hosted emoji picker. Nothing is stored in the browser: "recent" lives in memory only.
const GROUPS = [
  { id: 'recent', icon: '🕘', name: 'Recent' },
  { id: 0, icon: '😀', name: 'Smileys & emotion' },
  { id: 1, icon: '👋', name: 'People & body' },
  { id: 3, icon: '🐻', name: 'Animals & nature' },
  { id: 4, icon: '🍔', name: 'Food & drink' },
  { id: 5, icon: '✈️', name: 'Travel & places' },
  { id: 6, icon: '⚽', name: 'Activities' },
  { id: 7, icon: '💡', name: 'Objects' },
  { id: 8, icon: '❤️', name: 'Symbols' },
  { id: 9, icon: '🏳️', name: 'Flags' },
];

let dataPromise = null;
const loadData = () => (dataPromise ||= fetch('/emoji/emoji.json').then((r) => r.json()));

const recent = [];

export function createEmojiPicker({ onPick }) {
  let data = [];
  let active = 1;
  const root = h('div', { class: 'emoji-picker', role: 'dialog', 'aria-label': 'Emoji picker' });
  const search = h('input', { class: 'emoji-search', type: 'search', placeholder: 'Search emoji', autocomplete: 'off', spellcheck: 'false', 'aria-label': 'Search emoji' });
  const tabs = h('div', { class: 'emoji-tabs', role: 'tablist' });
  const grid = h('div', { class: 'emoji-grid' });
  const label = h('div', { class: 'emoji-label' });

  GROUPS.forEach((g) => {
    tabs.append(h('button', { class: 'emoji-tab', type: 'button', role: 'tab', title: g.name, data: { g: String(g.id) }, onclick: () => { search.value = ''; setGroup(g.id); } }, g.icon));
  });

  root.append(h('div', { class: 'emoji-head' }, search), tabs, label, grid);

  function paint(list, title) {
    label.textContent = title;
    grid.replaceChildren(...list.slice(0, 400).map((d) => h('button', { class: 'emoji-btn', type: 'button', title: d.n, data: { e: d.e } }, d.e)));
    if (!list.length) grid.append(h('div', { class: 'emoji-empty' }, 'No emoji found'));
    grid.scrollTop = 0;
  }

  function setGroup(id) {
    active = id;
    root.querySelectorAll('.emoji-tab').forEach((t) => t.classList.toggle('on', t.dataset.g === String(id)));
    if (id === 'recent') {
      paint(recent.map((e) => data.find((d) => d.e === e) || { e, n: e }), 'Recently used');
      return;
    }
    paint(data.filter((d) => d.g === id), GROUPS.find((g) => g.id === id).name);
  }

  grid.addEventListener('click', (ev) => {
    const b = ev.target.closest('.emoji-btn');
    if (!b) return;
    const e = b.dataset.e;
    const i = recent.indexOf(e);
    if (i >= 0) recent.splice(i, 1);
    recent.unshift(e);
    recent.length = Math.min(recent.length, 32);
    onPick(e);
  });

  search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    if (!q) return setGroup(active);
    const terms = q.split(/\s+/);
    paint(data.filter((d) => terms.every((t) => (d.n + ' ' + d.k).toLowerCase().includes(t))), 'Results');
  });

  return {
    el: root,
    async prepare() {
      if (data.length) return;
      try {
        data = await loadData();
        setGroup(recent.length ? 'recent' : 1);
      } catch {
        label.textContent = 'Could not load emoji';
      }
    },
    focus() { search.focus({ preventScroll: true }); },
  };
}
