import { solve } from './pow-core.js';

self.onmessage = async (e) => {
  const { token, bits } = e.data;
  try {
    self.postMessage({ nonce: await solve(token, bits) });
  } catch (err) {
    self.postMessage({ error: String(err) });
  }
};
