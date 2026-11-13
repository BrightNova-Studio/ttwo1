import { api } from './api.js';
import { solve } from './pow-core.js';

/*
 * Invisible "are you a person" check. The server sends a small puzzle, the browser solves it in the background
 * (usually while the visitor is still reading the page, so they never wait), and the answer travels with the request.
 * See App\Support\Challenge for the server side.
 */
const ready = new Map(); // purpose -> { at, promise }
const FRESH_MS = 8 * 60 * 1000; // a bit less than the server's 10 minutes

function runInWorker(token, bits) {
  return new Promise((resolve, reject) => {
    let worker;
    try {
      worker = new Worker(new URL('./pow-worker.js', import.meta.url), { type: 'module' });
    } catch {
      solve(token, bits).then(resolve, reject); // no worker support: same work on the main thread
      return;
    }
    worker.onmessage = (e) => { worker.terminate(); e.data.error ? reject(new Error(e.data.error)) : resolve(e.data.nonce); };
    worker.onerror = () => { worker.terminate(); solve(token, bits).then(resolve, reject); };
    worker.postMessage({ token, bits });
  });
}

async function fetchAndSolve(purpose) {
  const ch = await api('GET', '/challenge?purpose=' + purpose);
  const nonce = await runInWorker(ch.token, ch.bits);
  return { pow_token: ch.token, pow_nonce: nonce };
}

/** Start solving ahead of time (call when the visitor opens the Create or Random tab). Safe to call repeatedly. */
export function prepare(purpose) {
  const cur = ready.get(purpose);
  if (cur && Date.now() - cur.at < FRESH_MS) return cur.promise;
  const entry = { at: Date.now(), promise: null };
  entry.promise = fetchAndSolve(purpose);
  entry.promise.catch(() => { if (ready.get(purpose) === entry) ready.delete(purpose); });
  ready.set(purpose, entry);
  return entry.promise;
}

const RETRY = new Set(['pow_stale', 'pow_used', 'pow_invalid', 'pow_required']);

/** Run `send(extraFields)` with a solved puzzle attached, getting a new one if the server asks for it. */
export async function withCheck(purpose, send, { onSlow } = {}) {
  let slow = null;
  if (onSlow) slow = setTimeout(onSlow, 1800);
  try {
    for (let attempt = 0; attempt < 4; attempt++) {
      const solution = await prepare(purpose);
      ready.delete(purpose); // every solution is single use
      const hp = document.querySelector('.hp')?.value || '';
      try {
        return await send({ ...solution, hp });
      } catch (err) {
        if (!RETRY.has(err.code) || attempt === 3) throw err;
      }
    }
  } finally {
    if (slow) clearTimeout(slow);
  }
  return null;
}
