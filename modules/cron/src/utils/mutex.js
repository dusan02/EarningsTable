// Simple in-process async mutex.
// Holds a per-key chain of promises so concurrent callers serialize.
const locks = new Map();

export async function withMutex(key, fn) {
  const prev = locks.get(key) || Promise.resolve();
  let release;
  const next = new Promise((resolve) => (release = resolve));
  locks.set(key, next);
  try {
    await prev;
    console.log(`🔒 Acquiring lock: ${key}`);
    return await fn();
  } finally {
    console.log(`🔓 Releasing lock: ${key}`);
    release();
    if (locks.get(key) === next) locks.delete(key);
  }
}
