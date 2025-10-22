export async function fetchWithTimeout(url: string, ms = 20000, init?: RequestInit) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), ms);
  try {
    return await fetch(url, { ...init, signal: ctrl.signal });
  } finally {
    clearTimeout(t);
  }
}

export async function withRetry<T>(fn: () => Promise<T>, tries = 3) {
  let err: any;
  for (let i = 0; i < tries; i++) {
    try { return await fn(); }
    catch (e) { err = e; await new Promise(r => setTimeout(r, 500 * (2 ** i))); }
  }
  throw err;
}
