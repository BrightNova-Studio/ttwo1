import '../../css/chat.css';
import * as C from './crypto.js';
import { api, setToken } from './api.js';
import { startChat } from './chat.js';
import { privateWindowGuess } from './privacy.js';
import { icon } from './icons.js';
import { $, $$, h, unlockAudio } from './util.js';

const gate = $('#gate');
const support = gate.dataset.support;
const steps = { 1: $('#step-1'), 2: $('#step-2'), 3: $('#step-3') };
const dots = $$('.steps li');
const note = $('#gate-note');
const SECRET_MIN = 10;

const S = { chatId: '', memberId: '', session: null };

/* ---------------------------------------------------------- support link */
$('#support-link').href = 'mailto:' + support + '?subject=' + encodeURIComponent('1two1: request for a new chat ID') +
  '&body=' + encodeURIComponent('Hello,\n\nI would like a new private chat room (Chat ID and two Member IDs).\n\nThank you.');

/* ---------------------------------------------------- masked text inputs */
// A masked text field (instead of type=password) keeps browsers from offering to save these values.
const maskOk = CSS.supports && CSS.supports('-webkit-text-security', 'disc');
$$('.mask').forEach((i) => { if (!maskOk) { i.type = 'password'; i.autocomplete = 'new-password'; } });
$$('.eye').forEach((b) => {
  b.innerHTML = icon('eye');
  b.addEventListener('click', () => {
    const i = $('#' + b.dataset.reveal);
    const show = i.classList.toggle('shown');
    if (!maskOk) i.type = show ? 'text' : 'password';
    b.innerHTML = icon(show ? 'eyeoff' : 'eye');
  });
});

/* ------------------------------------------------------------ id helpers */
const clean = (v) => String(v).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 16);
const group = (v) => clean(v).replace(/(.{4})(?=.)/g, '$1-');
[$('#chatId'), $('#memberId')].forEach((i) => i.addEventListener('input', () => { i.value = group(i.value); }));

/* ----------------------------------------------------------------- steps */
function go(n, focus = true) {
  Object.entries(steps).forEach(([k, f]) => f.classList.toggle('on', Number(k) === n));
  dots.forEach((d, i) => d.classList.toggle('on', i < n));
  $$('.err').forEach((e) => { e.textContent = ''; });
  if (focus) setTimeout(() => $('input', steps[n]).focus(), 60);
}

function fail(form, msg) {
  const e = $('.err', form);
  e.textContent = msg;
  const card = $('.card');
  card.classList.remove('shake');
  void card.offsetWidth;
  card.classList.add('shake');
}

function busy(form, on, label) {
  const b = $('button[type=submit]', form);
  b.disabled = on;
  b.classList.toggle('loading', on);
  if (label) $('span', b).textContent = label;
}

$$('[data-back]').forEach((b) => b.addEventListener('click', () => {
  if (Number(b.dataset.back) === 1) { setToken(null); S.session = null; }
  go(Number(b.dataset.back));
}));

steps[1].addEventListener('submit', (e) => {
  e.preventDefault();
  S.chatId = clean($('#chatId').value);
  if (S.chatId.length < 16) return fail(steps[1], 'Enter your full 16-character Chat ID.');
  go(2);
});

steps[2].addEventListener('submit', async (e) => {
  e.preventDefault();
  S.memberId = clean($('#memberId').value);
  if (S.memberId.length < 16) return fail(steps[2], 'Enter your full 16-character Member ID.');
  busy(steps[2], true, 'Checking…');
  try {
    const r = await api('POST', '/enter', { chat_id: S.chatId, member_id: S.memberId });
    setToken(r.token);
    S.session = r;
    $('#secret-hint').textContent = r.key_check
      ? 'Enter the secret phrase you and your partner agreed on. It is checked on this device only.'
      : 'You are the first one in. Choose a secret phrase of at least ' + SECRET_MIN + ' characters and share it with your partner in private. It never leaves your device and cannot be recovered.';
    $('#step-3 .btn.primary span').textContent = r.key_check ? 'Unlock room' : 'Create secret & unlock';
    go(3);
  } catch (err) {
    fail(steps[2], err.status ? err.message : 'Cannot reach the server. Check your connection.');
  } finally {
    busy(steps[2], false, 'Continue');
  }
});

steps[3].addEventListener('submit', async (e) => {
  e.preventDefault();
  unlockAudio();
  const secret = $('#secret').value;
  const first = !S.session.key_check;
  if (first && secret.length < SECRET_MIN) return fail(steps[3], 'Use at least ' + SECRET_MIN + ' characters so it is hard to guess.');
  if (!secret) return fail(steps[3], 'Enter the secret phrase.');

  busy(steps[3], true, 'Securing…');
  try {
    // Let the button repaint before the (deliberately slow) key derivation starts.
    await new Promise((r) => setTimeout(r, 40));
    const key = await C.deriveKey(secret, S.chatId);

    let check = S.session.key_check;
    if (first) {
      const mine = await C.seal(key, C.KEY_CHECK_VALUE);
      try {
        await api('POST', '/key-check', { key_check: mine });
        check = mine;
      } catch (err) {
        if (err.status !== 409) throw err;
        check = err.data.key_check; // the partner got there first: verify against theirs
      }
    }

    try {
      const v = await C.open(key, check);
      if (v.k !== C.KEY_CHECK_VALUE.k) throw new Error('bad');
    } catch {
      return fail(steps[3], first
        ? 'Your partner already chose a secret phrase, and this is not it. Ask them for it.'
        : "That secret phrase doesn't match this room. Check it and try again.");
    }

    const session = S.session;
    // Scrub everything the form held before the chat starts.
    ['chatId', 'memberId', 'secret'].forEach((id) => { $('#' + id).value = ''; });
    S.chatId = S.memberId = '';
    S.session = null;
    gate.hidden = true;
    startChat({ session, key, onExit: onLeave });
  } catch (err) {
    fail(steps[3], err.status ? err.message : 'Something went wrong. Please try again.');
  } finally {
    busy(steps[3], false, first ? 'Create secret & unlock' : 'Unlock room');
  }
});

/* ------------------------------------------------------------ after exit */
const REASONS = {
  hidden: ['info', 'Hidden. Nothing from the chat is left on this screen.'],
  left: ['info', 'You left the room.'],
  idle: ['info', 'Signed out after a period of inactivity, to keep the chat private.'],
  session_ended: ['warn', 'Your session ended. If you did not do this, someone may have used your Member ID on another device. Tell your partner and contact support.'],
  session_expired: ['info', 'Your session timed out. Sign in again to continue.'],
  signed_out: ['info', 'Please sign in again.'],
  room_unavailable: ['warn', 'This room is not available right now. Contact support if this is unexpected.'],
};

function onLeave(reason) {
  setToken(null);
  S.session = null;
  ['chatId', 'memberId', 'secret'].forEach((id) => { $('#' + id).value = ''; });
  gate.hidden = false;
  const [kind, text] = REASONS[reason] || REASONS.left;
  note.className = 'gate-note ' + kind;
  note.textContent = text;
  note.hidden = false;
  go(1);
}

/* --------------------------------------------------- private window tip */
(async () => {
  const guess = await privateWindowGuess();
  const tip = $('#tip');
  const dismiss = () => {
    tip.classList.add('fade');
    setTimeout(() => { tip.hidden = true; }, 600);
  };
  const close = h('button', { class: 'tip-x', type: 'button', 'aria-label': 'Dismiss', onclick: dismiss, html: icon('x') });
  if (guess === 'private') {
    tip.className = 'tip ok';
    tip.replaceChildren(h('span', { class: 'tip-ico', html: icon('shield') }), h('span', {}, 'Private window detected. Good.'), close);
    tip.hidden = false;
    setTimeout(dismiss, 3500);
  } else {
    tip.className = 'tip warn';
    tip.replaceChildren(h('span', { class: 'tip-ico', html: icon('warn') }),
      h('span', {}, 'Tip: use a ', h('b', {}, 'Private / Incognito'), ' window so this browser keeps no history of your visit.'), close);
    tip.hidden = false;
  }
})();

setTimeout(() => $('#chatId').focus(), 120);
