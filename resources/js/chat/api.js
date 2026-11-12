// The sign-in token lives only in this variable: not in a cookie, not in storage.
let token = null;
export const setToken = (t) => { token = t; };
export const hasToken = () => !!token;

export async function api(method, path, body, opts = {}) {
  const headers = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (token) headers.Authorization = 'Bearer ' + token;

  const res = await fetch('/api' + path, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
    cache: 'no-store',
    credentials: 'omit',
    referrerPolicy: 'no-referrer',
    keepalive: !!opts.keepalive,
    signal: opts.signal,
  });

  let data = null;
  try { data = await res.json(); } catch { /* empty body */ }

  if (!res.ok) {
    const err = new Error((data && data.message) || 'Something went wrong.');
    err.status = res.status;
    err.code = data && data.error;
    err.data = data;
    throw err;
  }
  return data;
}
