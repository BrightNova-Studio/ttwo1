export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

/** Tiny safe DOM builder: text is always inserted as text, never as HTML. */
export function h(tag, props = {}, ...kids) {
  const el = document.createElement(tag);
  for (const [k, v] of Object.entries(props || {})) {
    if (v === undefined || v === null || v === false) continue;
    if (k === 'class') el.className = v;
    else if (k === 'vars') for (const [n, val] of Object.entries(v)) el.style.setProperty(n, val);
    else if (k === 'data') Object.assign(el.dataset, v);
    else if (k === 'html') el.innerHTML = v; // only ever used with static icon markup
    else if (k.startsWith('on')) el.addEventListener(k.slice(2).toLowerCase(), v);
    else el.setAttribute(k, v === true ? '' : v);
  }
  for (const kid of kids.flat(Infinity)) {
    if (kid === undefined || kid === null || kid === false) continue;
    el.append(kid.nodeType ? kid : document.createTextNode(String(kid)));
  }
  return el;
}

export const clamp = (n, a, b) => Math.min(b, Math.max(a, n));
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export function fmtTime(ts) {
  return new Date(ts * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export function dayKey(ts) {
  const d = new Date(ts * 1000);
  return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
}

export function dayLabel(ts) {
  const d = new Date(ts * 1000);
  const today = new Date();
  const yest = new Date(Date.now() - 86400000);
  if (dayKey(ts) === dayKey(today.getTime() / 1000)) return 'Today';
  if (dayKey(ts) === dayKey(yest.getTime() / 1000)) return 'Yesterday';
  return d.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

const seg = typeof Intl !== 'undefined' && Intl.Segmenter ? new Intl.Segmenter(undefined, { granularity: 'grapheme' }) : null;

/** 1-3 emoji and nothing else -> render big, without a bubble. */
export function emojiOnly(text) {
  const t = String(text || '').trim();
  if (!t || t.length > 40) return false;
  if (!/^[\p{Extended_Pictographic}\p{Emoji_Component}‍️\s]+$/u.test(t) || /^[\d#*\s]+$/.test(t)) return false;
  const count = seg ? [...seg.segment(t.replace(/\s/g, ''))].length : [...t].length;
  return count >= 1 && count <= 3;
}

const URL_RE = /https?:\/\/[^\s<>"']+/gi;

/** Text -> nodes, turning http(s) links into safe anchors. */
export function linkify(text) {
  const out = [];
  let last = 0;
  for (const m of text.matchAll(URL_RE)) {
    let url = m[0];
    const trail = url.match(/[.,;:!?)\]]+$/);
    if (trail) url = url.slice(0, -trail[0].length);
    if (m.index > last) out.push(text.slice(last, m.index));
    out.push(h('a', { href: url, target: '_blank', rel: 'noopener noreferrer nofollow' }, url));
    last = m.index + url.length;
  }
  if (last < text.length) out.push(text.slice(last));
  return out;
}

/* Soft notification tone, generated on the fly (no audio file to fetch). */
let ctx = null;
export function unlockAudio() {
  try {
    ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
    if (ctx.state === 'suspended') ctx.resume();
  } catch { /* no audio */ }
}
export function blip(kind = 'in') {
  if (!ctx || ctx.state !== 'running') return;
  const now = ctx.currentTime;
  const notes = kind === 'in' ? [660, 880] : [520];
  notes.forEach((f, i) => {
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'sine';
    o.frequency.value = f;
    g.gain.setValueAtTime(0.0001, now + i * 0.09);
    g.gain.exponentialRampToValueAtTime(0.06, now + i * 0.09 + 0.02);
    g.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.09 + 0.22);
    o.connect(g).connect(ctx.destination);
    o.start(now + i * 0.09);
    o.stop(now + i * 0.09 + 0.25);
  });
}

/** Stable pleasant gradient for an avatar from any string. */
export function avatarHue(str) {
  let n = 0;
  for (const c of String(str)) n = (n * 31 + c.charCodeAt(0)) % 360;
  return n;
}

export function toast(text, kind = 'info') {
  const root = $('#toasts');
  if (!root) return;
  const t = h('div', { class: 'toast toast-' + kind, role: 'status' }, text);
  root.append(t);
  requestAnimationFrame(() => t.classList.add('in'));
  setTimeout(() => {
    t.classList.remove('in');
    setTimeout(() => t.remove(), 300);
  }, 3600);
}
