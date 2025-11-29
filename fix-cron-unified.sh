#!/bin/bash
# Script na opravu cronu na produkcii - unified cron každých 5 minút

cd /var/www/earnings-table

# Backup súboru
cp modules/cron/src/main.ts modules/cron/src/main.ts.backup

# Nájsť začiatok cron sekcie a nahradiť ju unified cronom
# Najprv nájdeme riadok kde začína "if (!once) {"
# Potom nájdeme a nahradíme všetky cron sloty jedným unified cronom

# Zjednodušený prístup - použiť sed na nahradenie celého bloku
cat > /tmp/cron_fix.txt << 'EOF'
    // Unified cron: každých 5 minút počas celého dňa (okrem 03:00–03:05 pre reset)
    // Cron expression: každých 5 minút, ale preskočí 03:00 (kedy beží daily clear)
    const UNIFIED_CRON = '*/5 * * * 1-5';
    const UNIFIED_VALID = cron.validate(UNIFIED_CRON);
    if (!UNIFIED_VALID) console.error(`❌ Invalid cron expression: ${UNIFIED_CRON}`);
    cron.schedule(UNIFIED_CRON, async () => {
      const tickAt = isoNY();
      // Get current NY time for hour/minute check
      const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
      const hour = nowNY.getHours();
      const minute = nowNY.getMinutes();
      
      // Preskočiť 03:00 (kedy beží daily clear)
      if (hour === 3 && minute === 0) {
        console.log(`⏭️  [CRON] skipping tick @ ${tickAt} (NY) - daily clear time`);
        return;
      }
      
      console.log(`⏱️ [CRON] tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('unified-slot');
    }, { timezone: TZ });
    console.log(`✅ Unified pipeline scheduled @ ${UNIFIED_CRON} (NY, Mon–Fri, každých 5 min okrem 03:00) valid=${UNIFIED_VALID}`);
EOF

# Zložitejšie - musíme nájsť presný blok a nahradiť ho
# Najprv nájdeme riadok kde začíná EARLY_CRON
START_LINE=$(grep -n "Early slot:" modules/cron/src/main.ts | cut -d: -f1)
if [ -z "$START_LINE" ]; then
  echo "❌ Nenašiel som začiatok cron sekcie"
  exit 1
fi

# Nájdeme koniec EVENING_CRON sekcie (riadok pred Daily clear)
END_LINE=$(grep -n "Daily clear job" modules/cron/src/main.ts | cut -d: -f1)
if [ -z "$END_LINE" ]; then
  echo "❌ Nenašiel som koniec cron sekcie"
  exit 1
fi

# Vytvoríme nový súbor s nahradeným blokom
head -n $((START_LINE - 1)) modules/cron/src/main.ts > /tmp/main_new.ts
cat /tmp/cron_fix.txt >> /tmp/main_new.ts
tail -n +$((END_LINE)) modules/cron/src/main.ts >> /tmp/main_new.ts

# Presunúť nový súbor
mv /tmp/main_new.ts modules/cron/src/main.ts

echo "✅ Cron unified - reštartujem PM2..."
pm2 restart earnings-cron

echo "✅ Hotovo!"

