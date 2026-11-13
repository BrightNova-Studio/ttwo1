import '../../css/chat.css';
import * as C from './crypto.js';
import { api, setToken } from './api.js';
import { startChat } from './chat.js';
import { startSearch } from './random.js';
import { icon } from './icons.js';
import { $, $$, h, toast, unlockAudio } from './util.js';

const gate = $('#gate');
const support = gate.dataset.support;
const steps = { 1: $('#step-1'), 2: $('#step-2'), 3: $('#step-3') };
const dots = $$('.steps li');
const note = $('#gate-note');
const SECRET_MIN = 5;
const SECRET_MAX = 40;
const glyphs = (s) => [...String(s)].length; // every character counts as one, spaces included

const S = { chatId: '', memberId: '', session: null };

/* ---------------------------------------------------------- support link */
$('#support-link').href = 'mailto:' + support + '?subject=' + encodeURIComponent('1two1: help with my room') +
  '&body=' + encodeURIComponent('Hello,\n\nI need help with 1two1.\n\n(Please do not send your secret phrase, Secret Room Key or Pass Codes.)\n\nThank you.');

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
// Secret Room Key = 2 letters + 4 digits (KR-4821). Pass Code = 6 digits (482-913).
const cleanChat = (v) => {
  const s = String(v).toUpperCase().replace(/[^A-Z0-9]/g, '');
  return (s.slice(0, 2).replace(/[^A-Z]/g, '') + s.slice(2).replace(/[^0-9]/g, '')).slice(0, 6);
};
const cleanMember = (v) => String(v).replace(/[^0-9]/g, '').slice(0, 6);
const groupChat = (v) => cleanChat(v).replace(/^(.{2})(?=.)/, '$1-');
const groupMember = (v) => cleanMember(v).replace(/^(.{3})(?=.)/, '$1-');
$('#chatId').addEventListener('input', (e) => { e.target.value = groupChat(e.target.value); });
$('#memberId').addEventListener('input', (e) => { e.target.value = groupMember(e.target.value); });

/* ----------------------------------------------------------------- steps */
/* ---------------------------------------------------------------- panes */
const panes = { join: $('#pane-join'), new: $('#pane-new'), rand: $('#pane-rand'), made: $('#pane-made') };
const tabs = { join: $('#tab-join'), new: $('#tab-new'), rand: $('#tab-rand') };
let search = null; // an active random-connect search, if any
function showPane(name, focus = true) {
  if (name !== 'rand' && search) stopSearching();
  Object.entries(panes).forEach(([k, p]) => { p.hidden = k !== name; });
  $('#tabs').hidden = name === 'made';
  Object.entries(tabs).forEach(([k, t]) => {
    const on = k === name;
    t.classList.toggle('on', on);
    t.setAttribute('aria-selected', String(on));
  });
  $$('.err').forEach((e) => { e.textContent = ''; });
  if (focus && name === 'join') setTimeout(() => $('input', $('.step.on', panes.join))?.focus(), 60);
}
tabs.join.addEventListener('click', () => showPane('join'));
tabs.new.addEventListener('click', () => showPane('new'));
tabs.rand.addEventListener('click', () => showPane('rand'));
$$('[data-ico]').forEach((el) => { el.innerHTML = icon(el.dataset.ico); });

function go(n, focus = true) {
  showPane('join', false);
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

/** Sign in with S.chatId / S.memberId, then ask for the secret phrase. */
async function signIn() {
  const r = await api('POST', '/enter', { chat_id: S.chatId, member_id: S.memberId });
  setToken(r.token);
  S.session = r;
  paintPhraseStep();
  go(3);
}

/**
 * The secret phrase is optional. The first person in chooses: type one (then the partner must enter the same),
 * or leave it empty (then the partner just walks in). Three cases are shown here.
 */
function phraseMode() {
  const r = S.session;
  if (!r.key_check) return 'choose'; // first one in
  return r.phrase === false ? 'none' : 'enter'; // partner: nothing to type, or must type the phrase
}

function paintPhraseStep() {
  const mode = phraseMode();
  const input = $('#secret');
  const hint = $('#secret-hint');
  const label = $('#secret-label');
  $('#secret-field').hidden = mode === 'none';
  input.value = '';
  label.hidden = mode === 'none';
  if (mode === 'choose') {
    label.innerHTML = 'Secret phrase <span class="opt">optional</span>';
    input.placeholder = 'Leave empty to skip';
    hint.textContent = 'Add a phrase of ' + SECRET_MIN + ' to ' + SECRET_MAX + ' characters (spaces count as one) and your partner must type the same one to get in. It never leaves your device and cannot be recovered. Leave it empty if you would rather not use one.';
  } else if (mode === 'enter') {
    label.innerHTML = 'Secret phrase';
    input.placeholder = 'The phrase you agreed on';
    hint.textContent = 'Your partner set a secret phrase for this room. Enter it exactly. It is checked on this device only.';
  } else {
    hint.textContent = 'No secret phrase was set for this room, so you can go straight in. Messages are still encrypted.';
  }
  syncPhraseButton();
}

function syncPhraseButton() {
  const mode = phraseMode();
  const typed = $('#secret').value.length > 0;
  const text = mode === 'choose' ? (typed ? 'Create phrase & enter' : 'Enter without a phrase') : mode === 'none' ? 'Enter room' : 'Unlock room';
  $('#step-3 .btn.primary span').textContent = text;
  $('#step-3 .btn.primary').dataset.label = text;
}
$('#secret').addEventListener('input', syncPhraseButton);

steps[1].addEventListener('submit', (e) => {
  e.preventDefault();
  S.chatId = cleanChat($('#chatId').value);
  if (!/^[A-Z]{2}[0-9]{4}$/.test(S.chatId)) return fail(steps[1], 'Enter your Secret Room Key: two letters and four digits, like KR-4821.');
  go(2);
});

steps[2].addEventListener('submit', async (e) => {
  e.preventDefault();
  S.memberId = cleanMember($('#memberId').value);
  if (S.memberId.length < 6) return fail(steps[2], 'Enter your 6-digit Pass Code.');
  busy(steps[2], true, 'Checking…');
  try {
    await signIn();
  } catch (err) {
    fail(steps[2], err.status ? err.message : 'Cannot reach the server. Check your connection.');
  } finally {
    busy(steps[2], false, 'Continue');
  }
});

steps[3].addEventListener('submit', async (e) => {
  e.preventDefault();
  unlockAudio();
  const mode = phraseMode();
  const first = mode === 'choose';
  const typed = $('#secret').value;
  if (mode === 'enter' && !typed) return fail(steps[3], 'Enter the secret phrase your partner set.');
  if (typed && glyphs(typed) > SECRET_MAX) return fail(steps[3], 'The secret phrase can be at most ' + SECRET_MAX + ' characters.');
  if (first && typed && glyphs(typed) < SECRET_MIN) return fail(steps[3], 'Use ' + SECRET_MIN + ' to ' + SECRET_MAX + ' characters (spaces count), or leave it empty to skip.');
  // No phrase anywhere: a fixed value stands in. Messages are still encrypted, but the key is not secret.
  const usePhrase = mode === 'none' ? false : !!typed;
  const secret = usePhrase ? typed : C.NO_PHRASE;
  const label = $('#step-3 .btn.primary').dataset.label || 'Enter room';

  busy(steps[3], true, 'Securing…');
  try {
    // Let the button repaint before the (deliberately slow) key derivation starts.
    await new Promise((r) => setTimeout(r, 40));
    const key = await C.deriveKey(secret, S.chatId);

    let check = S.session.key_check;
    if (first) {
      const mine = await C.seal(key, C.KEY_CHECK_VALUE);
      try {
        await api('POST', '/key-check', { key_check: mine, phrase: usePhrase });
        check = mine;
        S.session.phrase = usePhrase;
      } catch (err) {
        if (err.status !== 409) throw err;
        check = err.data.key_check; // the partner got there first: verify against theirs
        S.session.phrase = err.data.phrase !== false;
      }
    }

    try {
      const v = await C.open(key, check);
      if (v.k !== C.KEY_CHECK_VALUE.k) throw new Error('bad');
    } catch {
      if (first) {
        // The partner chose differently a moment earlier: show the right form instead of a dead end.
        S.session.key_check = check;
        paintPhraseStep();
        return fail(steps[3], S.session.phrase
          ? 'Your partner just set a secret phrase for this room. Enter it to continue.'
          : 'Your partner chose no secret phrase for this room. Press Enter room to continue.');
      }
      return fail(steps[3], "That secret phrase doesn't match this room. Check it and try again.");
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
    busy(steps[3], false, $('#step-3 .btn.primary').dataset.label || label);
  }
});

/* ------------------------------------------------------- random connect */
const randForm = $('#rand-form');
const randWait = $('#rand-wait');
let topic = 'any';
$$('.topic', randForm).forEach((b) => b.addEventListener('click', () => {
  topic = b.dataset.topic;
  $$('.topic', randForm).forEach((o) => {
    o.classList.toggle('on', o === b);
    o.setAttribute('aria-checked', String(o === b));
  });
}));
$('#rand-adult').addEventListener('change', (e) => { $('#rand-go').disabled = !e.target.checked; });

function showSearching(on) {
  randForm.hidden = on;
  randWait.hidden = !on;
  $('#tabs').hidden = on;
}

function stopSearching() {
  if (search) search.cancel();
  search = null;
  showSearching(false);
}

$('#rand-cancel').addEventListener('click', () => { stopSearching(); showPane('rand', false); });
window.addEventListener('pagehide', () => { if (search) search.cancel(); });

randForm.addEventListener('submit', (e) => {
  e.preventDefault();
  if (!$('#rand-adult').checked) return fail(randForm, 'Please confirm that you are 18 or older.');
  $('.err', randForm).textContent = '';
  unlockAudio();
  $('#rand-title').textContent = 'Looking for someone…';
  $('#rand-count').textContent = '';
  showSearching(true);
  search = startSearch({
    topic,
    onWaiting: (n) => { $('#rand-count').textContent = n > 1 ? n + ' people are looking right now' : 'You are in line. Waiting for someone else to look…'; },
    onMatched: (m) => { search = null; connectRandom(m); },
    onError: (err) => {
      search = null;
      showSearching(false);
      const msg = err.code === 'timeout' ? 'No one else was around just now. Try again in a moment.'
        : err.code === 'gone' ? 'The search ended. Please try again.'
        : err.status ? err.message : 'Cannot reach the server. Check your connection.';
      fail(randForm, msg);
    },
  });
});

/** Matched: sign in to the fresh room, agree the secret with the stranger's browser, open the chat. */
async function connectRandom(m) {
  $('#rand-title').textContent = 'Found someone! Securing the chat…';
  $('#rand-count').textContent = '';
  try {
    const session = await api('POST', '/enter', { chat_id: cleanChat(m.key), member_id: cleanMember(m.pass) });
    setToken(session.token);
    const secret = await C.sharedSecret(m.pair.priv, m.peer_pub);
    const safety = await C.safetyCode(m.pair.pub, m.peer_pub);
    const key = await C.deriveKey(secret, cleanChat(m.key));

    let check = session.key_check;
    if (!check) {
      const mine = await C.seal(key, C.KEY_CHECK_VALUE);
      try {
        await api('POST', '/key-check', { key_check: mine });
        check = mine;
      } catch (err) {
        if (err.status !== 409) throw err;
        check = err.data.key_check;
      }
    }
    const v = await C.open(key, check);
    if (v.k !== C.KEY_CHECK_VALUE.k) throw new Error('mismatch');

    gate.hidden = true;
    showSearching(false);
    startChat({ session, key, onExit: onLeave, random: { safety } });
  } catch {
    setToken(null);
    showSearching(false);
    fail(randForm, 'We could not secure that chat. Please try again.');
  }
}

/* ------------------------------------------------------- create a room */
const MODE_TEXT = {
  temp: 'Best for a quick, private talk. Once you are both offline a 30-minute clock starts, and if neither of you comes back the room and every message are deleted for good.',
  long: 'Best for a room you keep using. It stays until nobody has been in it for 30 days. Every visit starts the 30 days again.',
};
let mode = 'temp';
const newForm = $('#new-form');
function setMode(m) {
  mode = m;
  $$('.mode', newForm).forEach((b) => {
    const on = b.dataset.mode === m;
    b.classList.toggle('on', on);
    b.setAttribute('aria-checked', String(on));
  });
  $('#mode-desc').textContent = MODE_TEXT[m];
}
$$('.mode', newForm).forEach((b) => b.addEventListener('click', () => setMode(b.dataset.mode)));
setMode('temp');

const dur = (s) => (s >= 86400 ? Math.round(s / 86400) + ' days' : Math.round(s / 60) + ' minutes');
let made = null;

newForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  busy(newForm, true, 'Creating…');
  try {
    made = await api('POST', '/rooms', { mode });
    $('#mk-key').textContent = made.key;
    $('#mk-you').textContent = made.you;
    $('#mk-partner').textContent = made.partner;
    $('#made-chip').textContent = made.mode === 'temp' ? 'Temp room' : 'Long-term room';
    $('#made-line').textContent = 'Deleted ' + dur(made.seconds) + ' after you both leave.';
    showPane('made', false);
  } catch (err) {
    fail(newForm, err.status ? err.message : 'Cannot reach the server. Check your connection.');
  } finally {
    busy(newForm, false, 'Create my room');
  }
});

async function copyText(text, done) {
  try {
    await navigator.clipboard.writeText(text);
    toast(done || 'Copied', 'ok');
  } catch {
    toast('Copy is blocked here. Select the text and copy it by hand.', 'warn');
  }
}
$$('[data-copy-from]').forEach((b) => b.addEventListener('click', () => copyText($('#' + b.dataset.copyFrom).textContent)));

$('#copy-invite').addEventListener('click', () => {
  if (!made) return;
  copyText(
    'You are invited to a private 1two1 room (' + (made.mode === 'temp' ? 'Temp' : 'Long term') + ').\n\n' +
    'Secret Room Key: ' + made.key + '\n' +
    'Your Pass Code: ' + made.partner + '\n\n' +
    'Open ' + location.origin + ' in a Private / Incognito window and choose "Join a room".\n' +
    'If I set a secret phrase for the room, I will tell you it separately.',
    'Invite copied. If you set a secret phrase, share it another way.');
});

$('#enter-made').addEventListener('click', async () => {
  if (!made) return;
  const btn = $('#enter-made');
  btn.disabled = true;
  S.chatId = cleanChat(made.key);
  S.memberId = cleanMember(made.you);
  try {
    await signIn();
    made = null;
    ['mk-key', 'mk-you', 'mk-partner'].forEach((id) => { $('#' + id).textContent = ''; });
  } catch (err) {
    toast(err.status ? err.message : 'Cannot reach the server. Check your connection.', 'warn');
  } finally {
    btn.disabled = false;
  }
});

/* ------------------------------------------------------------ after exit */
const REASONS = {
  hidden: ['info', 'Hidden. Nothing from the chat is left on this screen.'],
  left: ['info', 'You left the room.'],
  idle: ['info', 'Signed out after a period of inactivity, to keep the chat private.'],
  session_ended: ['warn', 'Your session ended. If you did not do this, someone may have used your Pass Code on another device. Tell your partner and contact support.'],
  session_expired: ['info', 'Your session timed out. Sign in again to continue.'],
  signed_out: ['info', 'Please sign in again.'],
  partner_left: ['info', 'The chat ended. Whenever you like, you can look for someone new.'],
  ended: ['info', 'You ended the chat. Whenever you like, you can look for someone new.'],
  room_unavailable: ['warn', 'This room is not available. It may have expired or been closed. You can create a new one.'],
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
// A website cannot reliably tell whether it is in a private window, so we always show the same short advice.
(() => {
  const tip = $('#tip');
  const dismiss = () => {
    tip.classList.add('fade');
    setTimeout(() => { tip.hidden = true; }, 600);
  };
  tip.className = 'tip info';
  tip.replaceChildren(
    h('span', { class: 'tip-ico', html: icon('shield') }),
    h('span', {}, 'For better security, open this in ', h('b', {}, 'Incognito / Private'), ' mode.'),
    h('button', { class: 'tip-x', type: 'button', 'aria-label': 'Dismiss', onclick: dismiss, html: icon('x') }));
  tip.hidden = false;
})();

setTimeout(() => $('#chatId').focus(), 120);
