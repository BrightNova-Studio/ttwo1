import * as C from './crypto.js';
import { api, setToken } from './api.js';
import { icon } from './icons.js';
import { createEmojiPicker } from './emoji.js';
import { $, h, clamp, fmtTime, dayKey, dayLabel, emojiOnly, linkify, blip, unlockAudio, avatarHue, toast } from './util.js';

const QUICK_REACTIONS = ['❤️', '😂', '😮', '😢', '🙏', '👍', '🔥'];
const DISAPPEAR = [
  [0, 'Off'], [30, '30 s'], [300, '5 min'], [3600, '1 hour'], [86400, '24 hours'],
];
const IDLE_CHOICES = [[2, '2 min'], [5, '5 min'], [10, '10 min'], [30, '30 min'], [0, 'Never']];

export function startChat({ session, key, onExit }) {
  const S = {
    slot: session.slot,
    rev: 0,
    epoch: session.room.epoch,
    disappear: session.room.disappear,
    msgs: new Map(), // id -> message
    els: new Map(), // id -> element
    pending: new Map(), // client id -> optimistic message
    nick: '',
    peerNick: '',
    peerNickEnc: null,
    peerSessions: null,
    peerJoined: false,
    unread: 0,
    seenUpTo: 0,
    primed: false,
    fails: 0,
    viewOnce: false,
    replyTo: null,
    pickMode: 'compose',
    pickTarget: null,
    lastDay: null,
    lastMsgEl: null,
    destroyed: false,
    lastActivity: Date.now(),
    lastEsc: 0,
    settings: { conceal: true, sound: true, idleMinutes: 10 },
  };
  const ref = { kick: null };
  let menuEl = null;

  /* ---------------------------------------------------------------- shell */
  const app = $('#app');
  app.replaceChildren();
  app.hidden = false;
  app.classList.remove('concealed');

  const peerAvatar = h('div', { class: 'avatar' }, '?');
  const peerName = h('div', { class: 'peer-name' }, 'Your partner');
  const peerStatus = h('div', { class: 'peer-status' }, 'Waiting for them to join…');
  const offline = h('div', { class: 'offline-pill', hidden: true }, 'Reconnecting…');

  const header = h('header', { class: 'top' },
    h('div', { class: 'peer' }, peerAvatar, h('div', { class: 'peer-text' }, peerName, peerStatus)),
    h('div', { class: 'top-actions' },
      h('div', { class: 'enc-badge', title: 'Messages are encrypted on this device before they are sent. The server only sees scrambled data.' },
        h('span', { html: icon('lock') }), h('span', { class: 'enc-text' }, 'End-to-end encrypted')),
      h('button', { class: 'icon-btn', type: 'button', title: 'Hide now (press Esc twice)', 'aria-label': 'Hide now', onclick: () => exit('hidden'), html: icon('eyeoff') }),
      h('button', { class: 'icon-btn', type: 'button', title: 'Room settings', 'aria-label': 'Room settings', onclick: () => toggleDrawer(true), html: icon('sliders') })));

  const empty = h('div', { class: 'empty' },
    h('div', { class: 'empty-orb', html: icon('shield') }),
    h('h3', {}, 'Your private room is ready'),
    h('p', {}, 'Messages are encrypted before they leave this device. Nobody else, including us, can read them.'),
    session.room.ttl ? h('p', { class: 'room-life' },
      session.room.mode === 'temp' ? 'Temp room: ' : 'Long-term room: ',
      'it is deleted for good ',
      session.room.ttl >= 86400 ? Math.round(session.room.ttl / 86400) + ' days' : Math.round(session.room.ttl / 60) + ' minutes',
      ' after you are both away. Coming back resets the clock.') : null);

  const list = h('section', { class: 'list', 'aria-live': 'polite', 'aria-label': 'Messages' }, empty);
  const jump = h('button', { class: 'jump', type: 'button', hidden: true, onclick: () => scrollBottom(true) }, h('span', { html: icon('arrowdown') }), h('span', { class: 'jump-n' }));

  const replyBar = h('div', { class: 'replybar', hidden: true });
  const onceBar = h('div', { class: 'oncebar', hidden: true }, h('span', { html: icon('viewonce') }), 'View-once: it disappears after they open it');

  const ta = h('textarea', { class: 'input', rows: '1', placeholder: 'Write a private message', maxlength: '4000', autocomplete: 'off', autocapitalize: 'sentences', spellcheck: 'true', enterkeyhint: 'send', 'aria-label': 'Message' });
  const btnEmoji = h('button', { class: 'icon-btn round', type: 'button', title: 'Emoji', 'aria-label': 'Emoji', html: icon('smile') });
  const btnOnce = h('button', { class: 'icon-btn round', type: 'button', title: 'Send as view-once', 'aria-label': 'View once', html: icon('viewonce') });
  const btnSend = h('button', { class: 'send', type: 'button', title: 'Send', 'aria-label': 'Send', html: icon('send'), disabled: true });
  const picker = createEmojiPicker({ onPick: onPickEmoji });
  const emojiWrap = h('div', { class: 'emoji-wrap', hidden: true }, picker.el);

  const composer = h('footer', { class: 'composer' }, replyBar, onceBar, emojiWrap,
    h('div', { class: 'composer-row' }, btnOnce, h('div', { class: 'field' }, ta, btnEmoji), btnSend));

  const drawer = h('aside', { class: 'drawer', hidden: true, 'aria-label': 'Room settings' });
  const scrim = h('div', { class: 'scrim', hidden: true, onclick: () => toggleDrawer(false) });

  app.append(header, offline, h('div', { class: 'stage' }, list, jump), composer, scrim, drawer);

  /* -------------------------------------------------------------- helpers */
  const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 150;
  function scrollBottom(smooth = false) {
    list.scrollTo({ top: list.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    jump.hidden = true;
  }
  const touch = () => { S.lastActivity = Date.now(); unlockAudio(); };
  const updateTitle = () => { document.title = S.unread ? `(${S.unread}) 1two1` : '1two1'; };
  // Avatars are a small built-in set (emoji + colour) chosen on the device and sent encrypted, like nicknames.
  const AV_EMOJI = ['🦊','🐼','🐯','🦁','🐨','🐸','🦄','🐙','🦉','🐧','🐬','🦋','🌙','⭐','🔥','🌈','🍀','🌸','🎧','🎮','🚀','🛡️','💎','👻'];
  const AV_HUES = [250, 205, 165, 120, 40, 15, 340, 290];
  const cleanAvatar = (a) => (a && AV_EMOJI.includes(a.e) && Number.isFinite(a.h) ? { e: a.e, h: clamp(Math.round(a.h), 0, 360) } : null);
  function paintAvatar(el, name, av) {
    el.textContent = av ? av.e : (name[0] || '?').toUpperCase();
    el.style.setProperty('--h', av ? av.h : avatarHue(name));
  }
  const meName = () => S.nick || 'You';
  const theirName = () => S.peerNick || 'Your partner';

  function paintPeer(p) {
    const name = theirName();
    peerName.textContent = name;
    paintAvatar(peerAvatar, name, S.peerAvatar);
    peerAvatar.classList.toggle('on', !!p?.online);
    let status = 'Offline';
    if (!p?.joined) status = "Hasn't joined yet";
    else if (p.typing) status = 'typing…';
    else if (p.online) status = 'Online';
    peerStatus.textContent = status;
    peerStatus.classList.toggle('live', !!(p?.online));
    peerStatus.classList.toggle('typing', !!p?.typing);
  }

  function note(text, ico = 'info') {
    list.append(h('div', { class: 'note' }, h('span', { html: icon(ico) }), h('span', {}, text)));
    S.lastMsgEl = null;
    if (nearBottom()) scrollBottom();
  }

  function setOffline(on) { offline.hidden = !on; }

  /* ------------------------------------------------------------ rendering */
  const preview = (m) => (m.unsent ? 'Unsent message' : m.view_once ? 'View-once message' : (m.text || 'Message').slice(0, 90));

  function tickEl(m) {
    if (!m.mine) return null;
    if (m.state === 'sending') return h('span', { class: 'tick wait', title: 'Sending…', html: icon('clock') });
    if (m.state === 'failed') return h('span', { class: 'tick fail', title: 'Not sent' }, '!');
    if (m.seen) return h('span', { class: 'tick seen', title: 'Seen', html: icon('checks') });
    if (m.delivered) return h('span', { class: 'tick', title: 'Delivered', html: icon('checks') });
    return h('span', { class: 'tick', title: 'Sent', html: icon('check') });
  }

  function buildMsg(m) {
    const bubble = h('div', { class: 'bubble' });

    if (m.unsent) {
      bubble.classList.add('muted');
      bubble.append(h('span', { class: 'muted-txt' }, 'This message was unsent'));
    } else if (m.view_once) {
      bubble.classList.add('once');
      if (m.mine) {
        bubble.append(h('span', { class: 'once-ico', html: icon('viewonce') }),
          h('span', { class: 'once-txt' }, h('b', {}, 'View-once message'), h('small', {}, m.viewed ? 'Opened' : 'Not opened yet')));
      } else if (m.viewed) {
        bubble.classList.add('muted');
        bubble.append(h('span', { class: 'once-ico', html: icon('eye') }), h('span', { class: 'muted-txt' }, 'Opened'));
      } else {
        bubble.append(h('button', { class: 'once-open', type: 'button', onclick: () => openOnce(m) },
          h('span', { class: 'once-ico', html: icon('viewonce') }),
          h('span', { class: 'once-txt' }, h('b', {}, 'View-once message'), h('small', {}, 'Tap to open. It disappears after you close it.'))));
      }
    } else {
      if (m.reply_to) {
        const q = S.msgs.get(m.reply_to);
        bubble.append(h('button', { class: 'quote', type: 'button', title: 'Go to the original message', onclick: () => jumpTo(m.reply_to) },
          h('b', {}, q ? (q.mine ? meName() : theirName()) : 'Message'), h('span', {}, q ? preview(q) : 'Earlier message')));
      }
      if (m.text === null) bubble.append(h('span', { class: 'muted-txt' }, 'Could not decrypt. Is the secret phrase the same?'));
      else {
        if (emojiOnly(m.text)) bubble.classList.add('big');
        bubble.append(h('span', { class: 'txt' }, linkify(m.text)));
      }
    }

    const meta = h('span', { class: 'meta' },
      m.expires_at ? h('span', { class: 'ttl', title: 'Disappears automatically', html: icon('clock') }) : null,
      fmtTime(m.created_at), tickEl(m));

    const el = h('div', { class: 'msg ' + (m.mine ? 'mine' : 'theirs') + (m.state === 'sending' ? ' sending' : ''), data: { id: String(m.id), ts: String(m.created_at) } });
    const row = h('div', { class: 'row' }, bubble);
    if (!m.unsent) row.append(h('button', { class: 'more', type: 'button', 'aria-label': 'Message options', onclick: (e) => openMenu(m, e.currentTarget), html: icon('more') }));
    el.append(row);

    const foot = h('div', { class: 'foot' }, meta);
    if (m.state === 'failed') foot.prepend(h('button', { class: 'retry', type: 'button', onclick: () => m.retry && m.retry() }, 'Tap to retry'));
    el.append(foot);

    const chips = [];
    if (m.myReact) chips.push(h('button', { class: 'chip mine', type: 'button', title: 'Remove my reaction', onclick: () => react(m, m.myReact) }, m.myReact));
    if (m.theirReact) chips.push(h('span', { class: 'chip', title: theirName() }, m.theirReact));
    if (chips.length && !m.unsent) el.append(h('div', { class: 'chips' }, chips));

    el.addEventListener('contextmenu', (e) => { if (!m.unsent) { e.preventDefault(); openMenu(m, el.querySelector('.bubble')); } });
    let lp = null;
    el.addEventListener('pointerdown', (e) => {
      if (e.pointerType !== 'touch' || m.unsent) return;
      lp = setTimeout(() => openMenu(m, el.querySelector('.bubble')), 480);
    });
    ['pointerup', 'pointermove', 'pointercancel'].forEach((ev) => el.addEventListener(ev, () => clearTimeout(lp)));

    // WhatsApp-style: drag a message to the right to reply to it.
    if (!m.unsent && !m.view_once && typeof m.id === 'number') {
      const swipeIco = h('span', { class: 'swipe-ico', html: icon('reply') });
      el.prepend(swipeIco);
      let x0 = 0, y0 = 0, drag = false, armed = false, id = null;
      const reset = () => { drag = false; armed = false; id = null; el.classList.remove('swiping'); el.style.setProperty('--sx', '0px'); swipeIco.style.setProperty('--p', '0'); };
      el.addEventListener('pointerdown', (e) => {
        if (e.button > 0) return;
        x0 = e.clientX; y0 = e.clientY; id = e.pointerId; drag = false; armed = false;
      });
      el.addEventListener('pointermove', (e) => {
        if (id !== e.pointerId) return;
        const dx = e.clientX - x0, dy = e.clientY - y0;
        if (!drag) {
          if (Math.abs(dy) > 12 && Math.abs(dy) > Math.abs(dx)) { id = null; return; }
          if (dx > 14 && dx > Math.abs(dy) * 1.4) { drag = true; el.classList.add('swiping'); try { el.setPointerCapture(e.pointerId); } catch { /* ignore */ } }
          else return;
        }
        const d = clamp(dx, 0, 90);
        el.style.setProperty('--sx', d + 'px');
        swipeIco.style.setProperty('--p', String(clamp(d / 60, 0, 1)));
        if (d >= 60 && !armed) { armed = true; if (navigator.vibrate) navigator.vibrate(12); }
        if (d < 60) armed = false;
      });
      el.addEventListener('pointerup', (e) => {
        if (id !== e.pointerId) return;
        const fire = drag && armed;
        reset();
        if (fire) setReply(m);
      });
      el.addEventListener('pointercancel', reset);
    }
    return el;
  }

  function attach(m) {
    empty.remove();
    const dk = dayKey(m.created_at);
    if (S.lastDay !== dk) {
      list.append(h('div', { class: 'day' }, h('span', {}, dayLabel(m.created_at))));
      S.lastDay = dk;
      S.lastMsgEl = null;
    }
    const el = buildMsg(m);
    const prev = S.lastMsgEl;
    if (prev && prev.classList.contains('mine') === m.mine && Math.abs(m.created_at - Number(prev.dataset.ts)) < 300) {
      el.classList.add('cont');
      prev.classList.add('joined');
    }
    list.append(el);
    S.els.set(m.id, el);
    S.lastMsgEl = el;
  }

  function rerender(m) {
    const old = S.els.get(m.id);
    if (!old) return;
    const el = buildMsg(m);
    ['cont', 'joined'].forEach((c) => old.classList.contains(c) && el.classList.add(c));
    old.replaceWith(el);
    S.els.set(m.id, el);
    if (S.lastMsgEl === old) S.lastMsgEl = el;
  }

  function removeMsg(id) {
    const m = S.msgs.get(id);
    const el = S.els.get(id);
    S.msgs.delete(id);
    S.els.delete(id);
    if (el) {
      if (S.lastMsgEl === el) S.lastMsgEl = null;
      el.classList.add('leaving');
      setTimeout(() => el.remove(), 320);
    }
    if (m && S.replyTo && S.replyTo.id === id) setReply(null);
  }

  function wipeAll() {
    S.msgs.clear();
    S.els.clear();
    S.pending.clear();
    S.lastDay = null;
    S.lastMsgEl = null;
    S.seenUpTo = 0;
    list.replaceChildren(empty);
    setReply(null);
  }

  /* --------------------------------------------------------------- ingest */
  async function dec(blob) {
    if (!blob) return null;
    try { return await C.open(key, blob); } catch { return undefined; }
  }

  async function ingest(row) {
    const body = await dec(row.body_enc);
    const mineR = await dec(row.my_reaction);
    const theirR = await dec(row.their_reaction);
    const fields = {
      mine: row.mine,
      text: body === undefined ? null : body ? String(body.t ?? '') : null,
      view_once: row.view_once,
      viewed: row.viewed,
      unsent: row.unsent,
      reply_to: row.reply_to,
      myReact: mineR?.e || null,
      theirReact: theirR?.e || null,
      expires_at: row.expires_at,
      delivered: row.delivered,
      seen: row.seen,
      created_at: row.created_at,
      state: 'sent',
    };

    const existing = S.msgs.get(row.id);
    if (existing) {
      if (existing.view_once && existing.text === null) fields.text = null;
      Object.assign(existing, fields);
      rerender(existing);
      return;
    }

    // The server copy of a message we just sent replaces our optimistic bubble.
    if (row.mine && body?.c && S.pending.has(body.c)) {
      const tmp = S.pending.get(body.c);
      S.pending.delete(body.c);
      removeMsgInstant(tmp.id);
    }

    const m = { id: row.id, ...fields };
    if (m.view_once && row.mine) m.text = null;
    S.msgs.set(m.id, m);
    const stick = nearBottom();
    attach(m);

    if (!row.mine && S.primed) {
      if (S.settings.sound) blip('in');
      if (document.hidden || !document.hasFocus()) { S.unread++; updateTitle(); }
    }
    if (row.mine || stick) scrollBottom(S.primed);
    else { jump.hidden = false; $('.jump-n', jump).textContent = 'New messages'; }
  }

  function removeMsgInstant(id) {
    const el = S.els.get(id);
    S.msgs.delete(id);
    S.els.delete(id);
    if (el) {
      if (S.lastMsgEl === el) S.lastMsgEl = el.previousElementSibling?.classList?.contains('msg') ? el.previousElementSibling : null;
      el.remove();
    }
  }

  /* -------------------------------------------------------------- polling */
  function authFail(e) {
    if (e.status === 401 || e.status === 403) {
      exit(e.code === 'room_unavailable' ? 'room_unavailable' : e.code || 'session_ended', true);
      return true;
    }
    return false;
  }

  async function handlePeer(p) {
    if (p.nick_enc !== S.peerNickEnc) {
      S.peerNickEnc = p.nick_enc;
      const n = await dec(p.nick_enc);
      S.peerNick = (n && n.n) || '';
      S.peerAvatar = cleanAvatar(n && n.a);
    }
    if (S.peerSessions === null) S.peerSessions = p.sessions;
    else if (p.sessions > S.peerSessions) {
      S.peerSessions = p.sessions;
      note(`${theirName()} signed in again from a new session.`, 'shield');
    }
    if (p.joined && !S.peerJoined) {
      S.peerJoined = true;
      if (S.primed) note(`${theirName()} joined the room.`, 'lock');
    }
    paintPeer(p);
  }

  async function pollOnce() {
    const r = await api('GET', '/poll?since=' + S.rev);
    S.rev = r.rev;

    if (r.room.epoch !== S.epoch) {
      const mine = S.expectEpoch === r.room.epoch;
      S.epoch = r.room.epoch;
      wipeAll();
      S.unread = 0;
      updateTitle();
      note(mine ? 'You cleared the chat.' : 'The chat was cleared.', 'trash');
    }
    if (r.room.disappear !== S.disappear) {
      const first = S.disappear;
      S.disappear = r.room.disappear;
      if (S.primed && first !== undefined) note(r.room.disappear ? `Disappearing messages are on: ${labelFor(r.room.disappear)}.` : 'Disappearing messages are off.', 'clock');
      refreshDrawerChoice();
    }

    await handlePeer(r.peer);
    for (const row of r.messages) await ingest(row);
    S.primed = true;
    maybeSeen();
  }

  const labelFor = (s) => (DISAPPEAR.find((d) => d[0] === s) || [0, s + 's'])[1];

  async function loop() {
    while (!S.destroyed) {
      try {
        await pollOnce();
        S.fails = 0;
        setOffline(false);
      } catch (e) {
        if (S.destroyed || authFail(e)) return;
        S.fails++;
        if (S.fails >= 2) setOffline(true);
      }
      const quiet = Date.now() - S.lastActivity > 60000;
      let wait = document.hidden ? 4500 : quiet ? 3000 : 1300;
      if (S.fails) wait = Math.min(10000, 1500 * S.fails);
      await new Promise((resolve) => {
        const t = setTimeout(resolve, wait);
        ref.kick = () => { clearTimeout(t); resolve(); };
      });
    }
  }

  function maybeSeen() {
    if (document.hidden || !document.hasFocus() || !nearBottom()) return;
    let max = 0;
    for (const m of S.msgs.values()) if (!m.mine && typeof m.id === 'number' && m.id > max) max = m.id;
    if (max > S.seenUpTo) {
      S.seenUpTo = max;
      api('POST', '/messages/seen', { up_to: max }).catch(() => {});
    }
    if (S.unread) { S.unread = 0; updateTitle(); }
  }

  /* -------------------------------------------------------------- sending */
  function autosize() {
    ta.style.height = 'auto';
    ta.style.height = clamp(ta.scrollHeight, 24, 140) + 'px';
    btnSend.disabled = !ta.value.trim();
  }

  function jumpTo(id) {
    const target = S.els.get(id);
    if (!target) { toast('That message is no longer here.'); return; }
    target.scrollIntoView({ block: 'center', behavior: 'smooth' });
    target.classList.add('flash');
    setTimeout(() => target.classList.remove('flash'), 1300);
  }

  function setReply(m) {
    S.replyTo = m;
    replyBar.hidden = !m;
    replyBar.replaceChildren();
    if (m) {
      replyBar.append(
        h('span', { class: 'reply-ico', html: icon('reply') }),
        h('div', { class: 'reply-text' }, h('b', {}, 'Replying to ' + (m.mine ? meName() : theirName())), h('span', {}, preview(m))),
        h('button', { class: 'icon-btn', type: 'button', 'aria-label': 'Cancel reply', onclick: () => setReply(null), html: icon('x') }));
      ta.focus();
    }
  }

  function setOnceMode(on) {
    S.viewOnce = on;
    btnOnce.classList.toggle('on', on);
    onceBar.hidden = !on;
  }

  let typingAt = 0;
  function sendTyping() {
    if (Date.now() - typingAt < 2500 || !ta.value.trim()) return;
    typingAt = Date.now();
    api('POST', '/typing', {}).catch(() => {});
  }

  async function send() {
    const text = ta.value.replace(/\s+$/, '');
    if (!text.trim()) return;
    const cid = Math.random().toString(36).slice(2, 10);
    const tmp = {
      id: 't' + cid, mine: true, text: S.viewOnce ? null : text, view_once: S.viewOnce, viewed: false, unsent: false,
      reply_to: typeof S.replyTo?.id === 'number' ? S.replyTo.id : null, myReact: null, theirReact: null,
      expires_at: null, delivered: false, seen: false, created_at: Math.floor(Date.now() / 1000), state: 'sending',
    };
    S.pending.set(cid, tmp);
    S.msgs.set(tmp.id, tmp);
    attach(tmp);
    scrollBottom(true);
    ta.value = '';
    autosize();
    setReply(null);
    setOnceMode(false);
    hideEmoji();
    deliver(tmp, text, cid);
  }

  async function deliver(tmp, text, cid) {
    tmp.state = 'sending';
    rerender(tmp);
    try {
      const body_enc = await C.seal(key, { t: text, c: cid });
      const r = await api('POST', '/messages', { body_enc, view_once: tmp.view_once, reply_to: tmp.reply_to });
      if (S.msgs.get(tmp.id) === tmp) {
        const oldId = tmp.id;
        const el = S.els.get(oldId);
        S.msgs.delete(oldId);
        S.els.delete(oldId);
        tmp.id = r.message.id;
        tmp.created_at = r.message.created_at;
        tmp.expires_at = r.message.expires_at;
        tmp.state = 'sent';
        S.pending.delete(cid);
        S.msgs.set(tmp.id, tmp);
        S.els.set(tmp.id, el);
        rerender(tmp);
      }
      if (S.settings.sound) blip('out');
      ref.kick && ref.kick();
    } catch (e) {
      if (authFail(e)) return;
      tmp.state = 'failed';
      tmp.retry = () => deliver(tmp, text, cid);
      rerender(tmp);
    }
  }

  /* ----------------------------------------------------- message actions */
  async function react(m, emoji) {
    if (typeof m.id !== 'number') return;
    const remove = m.myReact === emoji;
    m.myReact = remove ? null : emoji;
    rerender(m);
    try {
      const enc = remove ? null : await C.seal(key, { e: emoji });
      await api('POST', `/messages/${m.id}/react`, { emoji_enc: enc });
      ref.kick && ref.kick();
    } catch (e) { authFail(e); }
  }

  async function unsend(m) {
    const ok = await confirmBox({ title: 'Unsend this message?', text: 'It will be removed for both of you.', yes: 'Unsend', danger: true });
    if (!ok) return;
    try {
      await api('DELETE', `/messages/${m.id}`);
      m.unsent = true;
      m.text = null;
      m.myReact = m.theirReact = null;
      rerender(m);
      ref.kick && ref.kick();
    } catch (e) { if (!authFail(e)) toast('Could not unsend that message.', 'warn'); }
  }

  async function openOnce(m) {
    if (m.opening || typeof m.id !== 'number') return;
    m.opening = true;
    try {
      const r = await api('POST', `/messages/${m.id}/view`, {});
      const p = await C.open(key, r.body_enc);
      m.viewed = true;
      rerender(m);
      showOnce(String(p.t ?? ''));
    } catch (e) {
      if (authFail(e)) return;
      m.viewed = true;
      rerender(m);
      toast('That message is no longer available.', 'warn');
    } finally { m.opening = false; }
  }

  function showOnce(text) {
    const secs = clamp(Math.round(6 + text.length / 14), 8, 60);
    let timer = null;
    const close = () => {
      clearTimeout(timer);
      card.textContent = '';
      ov.classList.remove('in');
      setTimeout(() => ov.remove(), 250);
      document.removeEventListener('visibilitychange', onVis);
    };
    const onVis = () => document.hidden && close();
    const card = h('div', { class: 'once-text' }, linkify(text));
    const ov = h('div', { class: 'overlay once-overlay' },
      h('div', { class: 'once-card' },
        h('div', { class: 'once-head' }, h('span', { html: icon('viewonce') }), h('b', {}, 'View once'), h('span', { class: 'once-time' }, secs + 's')),
        card,
        h('div', { class: 'once-bar' }, h('i', { vars: { '--dur': secs + 's' } })),
        h('p', { class: 'once-note' }, 'This will be gone when you close it. Screenshots cannot be blocked, so only share with people you trust.'),
        h('button', { class: 'btn primary', type: 'button', onclick: close }, 'Close and destroy')));
    document.body.append(ov);
    requestAnimationFrame(() => ov.classList.add('in'));
    timer = setTimeout(close, secs * 1000);
    document.addEventListener('visibilitychange', onVis);
  }

  /* ------------------------------------------------------------ popovers */
  function closeMenu() { if (menuEl) { menuEl.remove(); menuEl = null; } }

  function openMenu(m, anchor) {
    closeMenu();
    const canAct = typeof m.id === 'number';
    const bar = h('div', { class: 'react-bar' }, QUICK_REACTIONS.map((e) =>
      h('button', { class: 'react-btn' + (m.myReact === e ? ' on' : ''), type: 'button', onclick: () => { closeMenu(); react(m, e); } }, e)),
      h('button', { class: 'react-btn more-emoji', type: 'button', title: 'More', onclick: () => { closeMenu(); showEmoji('react', m); }, html: icon('plus') }));
    const items = [];
    if (!m.view_once) items.push(['reply', 'Reply', () => setReply(m)]);
    if (!m.view_once && m.text) items.push(['copy', 'Copy text', async () => { try { await navigator.clipboard.writeText(m.text); toast('Copied'); } catch { toast('Copy is blocked here.', 'warn'); } }]);
    if (m.mine && canAct) items.push(['trash', 'Unsend', () => unsend(m), true]);
    menuEl = h('div', { class: 'menu', role: 'menu' }, canAct ? bar : null,
      items.map(([ico, label, fn, danger]) =>
        h('button', { class: 'menu-item' + (danger ? ' danger' : ''), type: 'button', role: 'menuitem', onclick: () => { closeMenu(); fn(); } }, h('span', { html: icon(ico) }), label)));
    document.body.append(menuEl);
    const r = anchor.getBoundingClientRect();
    const mw = menuEl.offsetWidth;
    const mh = menuEl.offsetHeight;
    let left = m.mine ? r.right - mw : r.left;
    left = clamp(left, 8, window.innerWidth - mw - 8);
    let top = r.top - mh - 8;
    if (top < 8) top = Math.min(r.bottom + 8, window.innerHeight - mh - 8);
    menuEl.style.setProperty('--x', left + 'px');
    menuEl.style.setProperty('--y', top + 'px');
    requestAnimationFrame(() => menuEl && menuEl.classList.add('in'));
  }

  document.addEventListener('pointerdown', onDocDown, true);
  function onDocDown(e) {
    if (menuEl && !menuEl.contains(e.target) && !e.target.closest('.more')) closeMenu();
    if (!emojiWrap.hidden && !emojiWrap.contains(e.target) && !btnEmoji.contains(e.target) && !e.target.closest('.react-btn')) hideEmoji();
  }

  /* --------------------------------------------------------------- emoji */
  function showEmoji(mode, target) {
    S.pickMode = mode;
    S.pickTarget = target || null;
    emojiWrap.hidden = false;
    emojiWrap.classList.toggle('react-mode', mode === 'react');
    btnEmoji.classList.add('on');
    picker.prepare().then(() => matchMedia('(pointer:fine)').matches && picker.focus());
  }
  function hideEmoji() {
    emojiWrap.hidden = true;
    btnEmoji.classList.remove('on');
    S.pickMode = 'compose';
  }
  function onPickEmoji(e) {
    if (S.pickMode === 'react' && S.pickTarget) {
      react(S.pickTarget, e);
      hideEmoji();
      return;
    }
    const s = ta.selectionStart ?? ta.value.length;
    const t = ta.selectionEnd ?? ta.value.length;
    ta.setRangeText(e, s, t, 'end');
    ta.dispatchEvent(new Event('input'));
    ta.focus();
  }

  /* ------------------------------------------------------------- modals */
  function confirmBox({ title, text, yes = 'Confirm', danger = false }) {
    return new Promise((resolve) => {
      const done = (v) => { ov.classList.remove('in'); setTimeout(() => ov.remove(), 200); document.removeEventListener('keydown', onKey); resolve(v); };
      const onKey = (e) => e.key === 'Escape' && done(false);
      const ov = h('div', { class: 'overlay', role: 'dialog', 'aria-modal': 'true' },
        h('div', { class: 'dialog' }, h('h3', {}, title), h('p', {}, text),
          h('div', { class: 'dialog-actions' },
            h('button', { class: 'btn ghost', type: 'button', onclick: () => done(false) }, 'Cancel'),
            h('button', { class: 'btn ' + (danger ? 'danger' : 'primary'), type: 'button', onclick: () => done(true) }, yes))));
      document.body.append(ov);
      document.addEventListener('keydown', onKey);
      requestAnimationFrame(() => ov.classList.add('in'));
    });
  }

  /* -------------------------------------------------------------- drawer */
  let choiceBtns = [];
  function refreshDrawerChoice() {
    choiceBtns.forEach(([s, b]) => b.classList.toggle('on', s === S.disappear));
  }

  function toggle(label, hint, get, set) {
    const b = h('button', { class: 'switch', type: 'button', role: 'switch', 'aria-checked': String(get()) });
    b.addEventListener('click', () => { set(!get()); b.setAttribute('aria-checked', String(get())); });
    return h('div', { class: 'setting' }, h('div', { class: 'setting-text' }, h('b', {}, label), h('small', {}, hint)), b);
  }

  function buildDrawer() {
    const saveProfile = async (msg) => {
      try {
        await api('POST', '/profile', { nick_enc: (S.nick || S.avatar) ? await C.seal(key, { n: S.nick, a: S.avatar }) : null });
        toast(msg);
      } catch (e) { if (!authFail(e)) toast('Could not save that.', 'warn'); }
    };
    const preview = h('div', { class: 'avatar big' });
    const paintMine = () => paintAvatar(preview, S.nick || 'You', S.avatar);
    const pickEmoji = (e) => { S.avatar = { e, h: S.avatar ? S.avatar.h : AV_HUES[0] }; paintMine(); saveProfile('Picture saved'); };
    const pickHue = (hue) => { S.avatar = { e: S.avatar ? S.avatar.e : AV_EMOJI[0], h: hue }; paintMine(); saveProfile('Picture saved'); };
    const clearAvatar = () => { S.avatar = null; paintMine(); saveProfile('Picture removed'); };
    paintMine();
    const avatarSection = h('section', {},
      h('h4', {}, 'Your picture'),
      h('p', { class: 'hint' }, 'Pick an emoji and a colour. Your partner sees it next to your name. It is encrypted too, and no photo is ever uploaded.'),
      h('div', { class: 'av-row' }, preview, h('div', { class: 'av-hues' }, AV_HUES.map((hue) => h('button', { class: 'av-hue', type: 'button', 'aria-label': 'Colour', vars: { '--h': hue }, onclick: () => pickHue(hue) })))),
      h('div', { class: 'av-grid' }, AV_EMOJI.map((e) => h('button', { class: 'av-opt', type: 'button', onclick: () => pickEmoji(e) }, e))),
      h('button', { class: 'btn ghost sm', type: 'button', onclick: clearAvatar }, 'Use my initial'));

    const nickIn = h('input', { class: 'text-input', type: 'text', maxlength: '24', placeholder: 'Choose a nickname', value: S.nick, autocomplete: 'off', spellcheck: 'false', 'aria-label': 'Your nickname' });
    const saveNick = async () => {
      S.nick = nickIn.value.trim().slice(0, 24);
      paintMine();
      await saveProfile(S.nick ? 'Nickname saved' : 'Nickname removed');
    };
    nickIn.addEventListener('keydown', (e) => e.key === 'Enter' && saveNick());

    choiceBtns = DISAPPEAR.map(([s, label]) => {
      const b = h('button', { class: 'seg' + (s === S.disappear ? ' on' : ''), type: 'button', onclick: async () => {
        try { await api('POST', '/room/disappear', { seconds: s }); S.disappear = s; refreshDrawerChoice(); note(s ? `Disappearing messages are on: ${labelFor(s)}.` : 'Disappearing messages are off.', 'clock'); } catch (e) { if (!authFail(e)) toast('Could not change that.', 'warn'); }
      } }, label);
      return [s, b];
    });

    const idleSel = h('select', { class: 'select', 'aria-label': 'Auto sign-out' }, IDLE_CHOICES.map(([v, l]) => h('option', { value: String(v), selected: v === S.settings.idleMinutes }, l)));
    idleSel.addEventListener('change', () => { S.settings.idleMinutes = Number(idleSel.value); });

    drawer.replaceChildren(
      h('div', { class: 'drawer-head' }, h('h2', {}, 'Room settings'), h('button', { class: 'icon-btn', type: 'button', 'aria-label': 'Close', onclick: () => toggleDrawer(false), html: icon('x') })),
      h('div', { class: 'drawer-body' },
        h('section', {}, h('h4', {}, 'Your nickname'), h('p', { class: 'hint' }, `You are Member ${S.slot}. Your partner sees this name. It is encrypted too.`),
          h('div', { class: 'inline' }, nickIn, h('button', { class: 'btn primary sm', type: 'button', onclick: saveNick }, 'Save'))),
        avatarSection,
        h('section', {}, h('h4', {}, 'Disappearing messages'), h('p', { class: 'hint' }, 'New messages vanish for both of you after this time.'),
          h('div', { class: 'segs' }, choiceBtns.map(([, b]) => b))),
        h('section', {}, h('h4', {}, 'Privacy'),
          toggle('Blur when I look away', 'Hides the chat when this tab loses focus.', () => S.settings.conceal, (v) => { S.settings.conceal = v; if (!v) app.classList.remove('concealed'); }),
          toggle('Sounds', 'A soft tone for new messages.', () => S.settings.sound, (v) => { S.settings.sound = v; }),
          h('div', { class: 'setting' }, h('div', { class: 'setting-text' }, h('b', {}, 'Auto sign-out'), h('small', {}, 'After no activity. You will need your IDs again.')), idleSel),
          h('button', { class: 'btn ghost block', type: 'button', onclick: () => exit('hidden') }, h('span', { html: icon('eyeoff') }), 'Hide now (Esc Esc)')),
        h('section', {}, h('h4', {}, 'Conversation'),
          h('button', { class: 'btn danger-ghost block', type: 'button', onclick: clearChat }, h('span', { html: icon('trash') }), 'Clear chat for both'),
          h('button', { class: 'btn ghost block', type: 'button', onclick: () => exit('left') }, h('span', { html: icon('logout') }), 'Leave the room')),
        h('div', { class: 'enc-card' }, h('span', { html: icon('shield') }),
          h('p', {}, 'AES-256 encryption with a key made from your shared secret on this device. The server and the host only store scrambled data they cannot open.'))));
  }

  function toggleDrawer(open) {
    drawer.hidden = !open;
    scrim.hidden = !open;
    requestAnimationFrame(() => { drawer.classList.toggle('in', open); scrim.classList.toggle('in', open); });
  }

  async function clearChat() {
    const ok = await confirmBox({ title: 'Clear this chat?', text: 'Every message will be erased for both of you. This cannot be undone.', yes: 'Clear chat', danger: true });
    if (!ok) return;
    try {
      const r = await api('POST', '/room/clear', {});
      S.expectEpoch = r.epoch;
      toggleDrawer(false);
      ref.kick && ref.kick();
    } catch (e) { if (!authFail(e)) toast('Could not clear the chat.', 'warn'); }
  }

  /* ------------------------------------------------------------- lifecycle */
  const reasonsServerSide = new Set(['session_ended', 'session_expired', 'signed_out', 'room_unavailable']);

  function leaveNow() {
    api('POST', '/leave', {}, { keepalive: true }).catch(() => {});
  }

  function exit(reason, serverSide = false) {
    if (S.destroyed) return;
    S.destroyed = true;
    if (!serverSide && !reasonsServerSide.has(reason)) leaveNow();
    clearInterval(tickTimer);
    document.removeEventListener('pointerdown', onDocDown, true);
    window.removeEventListener('pagehide', leaveNow);
    ['keydown', 'pointerdown', 'pointermove', 'touchstart', 'wheel'].forEach((ev) => window.removeEventListener(ev, touch));
    window.removeEventListener('keydown', onKey, true);
    window.removeEventListener('blur', onBlur);
    window.removeEventListener('focus', onFocus);
    document.removeEventListener('visibilitychange', onVisibility);
    closeMenu();
    document.querySelectorAll('.overlay').forEach((o) => o.remove());
    S.msgs.clear();
    S.els.clear();
    S.pending.clear();
    app.replaceChildren();
    app.hidden = true;
    app.classList.remove('concealed');
    setToken(null);
    document.title = '1two1';
    onExit(reason);
  }

  function onKey(e) {
    touch();
    if (e.key === 'Escape') {
      const now = Date.now();
      if (now - S.lastEsc < 650) { exit('hidden'); return; }
      S.lastEsc = now;
      closeMenu();
      if (!emojiWrap.hidden) hideEmoji();
      else if (!drawer.hidden) toggleDrawer(false);
    }
  }

  function onBlur() { if (S.settings.conceal) app.classList.add('concealed'); }
  function onFocus() { app.classList.remove('concealed'); maybeSeen(); }
  function onVisibility() {
    if (document.hidden) { if (S.settings.conceal) app.classList.add('concealed'); }
    else { app.classList.remove('concealed'); maybeSeen(); ref.kick && ref.kick(); }
  }

  const tickTimer = setInterval(() => {
    const now = Date.now() / 1000;
    for (const m of [...S.msgs.values()]) if (m.expires_at && m.expires_at < now) removeMsg(m.id);
    const idle = S.settings.idleMinutes;
    if (idle > 0 && Date.now() - S.lastActivity > idle * 60000) exit('idle');
  }, 1000);

  /* ----------------------------------------------------------------- wire */
  ta.addEventListener('input', () => { autosize(); sendTyping(); });
  ta.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(); }
  });
  btnSend.addEventListener('click', send);
  btnEmoji.addEventListener('click', () => (emojiWrap.hidden ? showEmoji('compose') : hideEmoji()));
  btnOnce.addEventListener('click', () => setOnceMode(!S.viewOnce));
  list.addEventListener('scroll', () => { if (nearBottom()) { jump.hidden = true; maybeSeen(); } }, { passive: true });

  ['keydown', 'pointerdown', 'pointermove', 'touchstart', 'wheel'].forEach((ev) => window.addEventListener(ev, touch, { passive: true }));
  window.addEventListener('keydown', onKey, true);
  window.addEventListener('blur', onBlur);
  window.addEventListener('focus', onFocus);
  window.addEventListener('pagehide', leaveNow);
  document.addEventListener('visibilitychange', onVisibility);

  /* ----------------------------------------------------------------- start */
  (async () => {
    const mine = await dec(session.nick_enc);
    S.nick = (mine && mine.n) || '';
    S.avatar = cleanAvatar(mine && mine.a);
    buildDrawer();
    paintPeer(session.peer);
    updateTitle();
    ta.focus();
    loop();
  })();
}
