import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

async function main() {
  console.log('--- DIAGNOSTICS START ---');

  // 1. Check Timezone / Date
  const now = new Date();
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/New_York',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).formatToParts(now);
  const y = parts.find(p => p.type === 'year')?.value;
  const m = parts.find(p => p.type === 'month')?.value;
  const d = parts.find(p => p.type === 'day')?.value;

  if (!y || !m || !d) {
     console.error('Failed to parse date parts', parts);
     return;
  }
  const nyDate = `${y}-${m}-${d}`;
  console.log(`Current Server Time (Local): ${now.toISOString()}`);
  console.log(`Calculated NY Date: ${nyDate}`);

  // 2. Check CronStatus
  console.log('\n--- Recent Cron Status ---');
  const recentCrons = await prisma.cronStatus.findMany({
    orderBy: { lastRunAt: 'desc' },
    take: 5
  });
  if (recentCrons.length === 0) {
    console.log('No CronStatus records found.');
  } else {
    console.table(recentCrons);
  }

  // 3. Check FinhubData for today
  console.log(`\n--- FinhubData for ${nyDate} ---`);
  const todayStart = new Date(`${nyDate}T00:00:00.000Z`);
  const countToday = await prisma.finhubData.count({
    where: {
      reportDate: todayStart
    }
  });
  console.log(`Count of records with reportDate = ${todayStart.toISOString()}: ${countToday}`);

  // 4. Check Latest FinhubData
  console.log('\n--- Latest FinhubData Entry ---');
  const latest = await prisma.finhubData.findFirst({
    orderBy: { reportDate: 'desc' }
  });
  if (latest) {
    console.log('Latest record:', latest);
  } else {
    console.log('No FinhubData found.');
  }

  console.log('--- DIAGNOSTICS END ---');
}

main()
  .catch(e => console.error(e))
  .finally(async () => {
    await prisma.$disconnect();
  });

