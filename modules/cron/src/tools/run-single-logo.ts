import 'dotenv/config';
import { fetchAndStoreLogo } from '../core/logoService.js';
import { db } from '../core/DatabaseManager.js';

async function main() {
  const sym = (process.argv[2] || '').toUpperCase();
  if (!sym) {
    console.error('Usage: tsx src/tools/run-single-logo.ts <SYMBOL>');
    process.exit(1);
  }
  console.log(`🖼️ Forcing logo fetch for ${sym}...`);
  const r = await fetchAndStoreLogo(sym);
  console.log('Result:', r);
  await db.batchUpdateLogoInfo([{ symbol: sym, logoUrl: r.logoUrl, logoSource: r.logoSource }]);
  await db.generateFinalReport();
  console.log('✅ Done.');
}

main().catch((e) => { console.error(e); process.exit(1); });


