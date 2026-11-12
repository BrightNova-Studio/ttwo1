// End-to-end encryption. The key is derived in this browser from the shared secret and never leaves it.
// The server only ever receives and stores the scrambled output of seal().

const enc = new TextEncoder();
const dec = new TextDecoder();

export const normalizeId = (s) => String(s || '').toUpperCase().replace(/[^A-Z0-9]/g, '');

const toB64u = (bytes) => {
  let s = '';
  for (let i = 0; i < bytes.length; i += 0x8000) s += String.fromCharCode(...bytes.subarray(i, i + 0x8000));
  return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};

const fromB64u = (str) => {
  const b = atob(String(str).replace(/-/g, '+').replace(/_/g, '/'));
  const out = new Uint8Array(b.length);
  for (let i = 0; i < b.length; i++) out[i] = b.charCodeAt(i);
  return out;
};

/** PBKDF2-SHA256 (600k rounds) -> non-extractable AES-256-GCM key. */
export async function deriveKey(secret, chatId) {
  const salt = enc.encode('1two1/v1/' + normalizeId(chatId));
  const base = await crypto.subtle.importKey('raw', enc.encode(String(secret).normalize('NFKC')), 'PBKDF2', false, ['deriveKey']);
  return crypto.subtle.deriveKey(
    { name: 'PBKDF2', salt, iterations: 600000, hash: 'SHA-256' },
    base,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt'],
  );
}

export async function seal(key, obj) {
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const ct = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, enc.encode(JSON.stringify(obj))));
  const out = new Uint8Array(12 + ct.length);
  out.set(iv);
  out.set(ct, 12);
  return toB64u(out);
}

export async function open(key, str) {
  const raw = fromB64u(str);
  const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv: raw.slice(0, 12) }, key, raw.slice(12));
  return JSON.parse(dec.decode(pt));
}

export const KEY_CHECK_VALUE = { k: '1two1-key-check-v1' };
