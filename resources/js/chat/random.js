import { api } from './api.js';
import * as C from './crypto.js';
import { sleep } from './util.js';
import { withCheck } from './pow.js';

/**
 * Look for a stranger. Joins the waiting line, polls every couple of seconds, and calls
 * onMatched({ key, pass, peer_pub, pair }) when somebody else is found.
 */
export function startSearch({ topic, onWaiting, onMatched, onError }) {
  let stopped = false;
  let ticket = null;
  let pair = null;

  const handle = (s) => {
    if (s.status === 'matched') {
      stopped = true;
      onMatched({ key: s.key, pass: s.pass, peer_pub: s.peer_pub, pair });
    } else if (s.status === 'waiting') {
      onWaiting(s.looking || 1);
    } else {
      stopped = true;
      onError({ code: s.status });
    }
  };

  (async () => {
    try {
      pair = await C.genPair();
      const r = await withCheck('match', (extra) => api('POST', '/match/join', { topic, pub: pair.pub, adult: true, ...extra }));
      ticket = r.ticket;
      if (stopped) { // cancelled while the join request was in flight
        if (r.status !== 'matched') api('POST', '/match/cancel', { ticket }).catch(() => {});
        return;
      }
      handle(r);
      while (!stopped) {
        await sleep(2000);
        if (stopped) break;
        handle(await api('POST', '/match/poll', { ticket }));
      }
    } catch (e) {
      if (stopped) return;
      stopped = true;
      onError(e.status === 404 ? { code: 'gone' } : e);
    }
  })();

  return {
    cancel() {
      stopped = true;
      if (ticket) api('POST', '/match/cancel', { ticket }, { keepalive: true }).catch(() => {});
      ticket = null;
    },
  };
}
