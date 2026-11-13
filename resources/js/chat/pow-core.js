// Searches for a number such that SHA-256(token + ":" + number) starts with `bits` zero bits.
// Runs in a Web Worker (pow-worker.js) so the page never freezes; the same function is the fallback on the main thread.
const enc = new TextEncoder();

function leadingZeroBits(bytes) {
  let n = 0;
  for (let i = 0; i < bytes.length; i++) {
    if (bytes[i] === 0) { n += 8; continue; }
    return n + Math.clz32(bytes[i]) - 24;
  }
  return n;
}

export async function solve(token, bits, shouldStop = () => false) {
  const BATCH = 512;
  let n = Math.floor(Math.random() * 1e9);
  for (;;) {
    if (shouldStop()) return null;
    const nonces = Array.from({ length: BATCH }, (_, i) => n + i);
    const hashes = await Promise.all(nonces.map((x) => crypto.subtle.digest('SHA-256', enc.encode(token + ':' + x))));
    for (let i = 0; i < BATCH; i++) {
      if (leadingZeroBits(new Uint8Array(hashes[i])) >= bits) return String(nonces[i]);
    }
    n += BATCH;
  }
}
