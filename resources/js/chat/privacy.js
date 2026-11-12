/**
 * Best-effort check for a private / incognito window. Browsers do not let a website
 * force or truly detect this, so the result only drives a friendly tip, never a block.
 * Returns 'private' | 'normal'.
 */
export async function privateWindowGuess() {
  try {
    if (navigator.storage && navigator.storage.estimate) {
      const { quota } = await navigator.storage.estimate();
      // Chromium private windows get a small, memory-based quota.
      if (quota && quota < 140 * 1024 * 1024) return 'private';
    }
  } catch { /* ignore */ }

  try {
    // Firefox private windows refuse IndexedDB.
    await new Promise((resolve, reject) => {
      const req = indexedDB.open('__probe__');
      req.onerror = () => reject(new Error('idb'));
      req.onsuccess = () => {
        req.result.close();
        indexedDB.deleteDatabase('__probe__');
        resolve();
      };
    });
  } catch {
    return 'private';
  }

  try {
    // Older Safari private mode: localStorage writes throw.
    const k = '__probe__';
    localStorage.setItem(k, '1');
    localStorage.removeItem(k);
  } catch {
    return 'private';
  }

  return 'normal';
}
