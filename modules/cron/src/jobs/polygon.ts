import { runPolygonJobFast } from '../polygon-fast.js';

export async function runPolygonJob(): Promise<void> {
  // Use the new fast implementation
  await runPolygonJobFast();
}
