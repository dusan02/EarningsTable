import { runPolygonJobFast } from '../polygon-fast.js';

export async function runPolygonJob(symbols?: string[]): Promise<void> {
  // Use the new fast implementation
  await runPolygonJobFast(symbols);
}
