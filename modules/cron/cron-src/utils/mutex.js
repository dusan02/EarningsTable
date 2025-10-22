export async function withMutex(key, fn) {
  console.log(`🔒 Acquiring lock: ${key}`);
  try {
    await fn();
  } finally {
    console.log(`🔓 Releasing lock: ${key}`);
  }
}
