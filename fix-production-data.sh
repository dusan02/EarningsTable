#!/bin/bash
# 🔧 Komplexný diagnostický a opravný skript pre produkciu
# Použitie: ./fix-production-data.sh [diagnose|reset-db|reset-cron|force-run|all]

set -e

# Farba pre výstup
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_DIR="/var/www/earnings-table"
CRON_DIR="$PROJECT_DIR/modules/cron"

# Funkcia na výpis hlavičky
print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

# 1. Diagnostika
diagnose() {
    print_header "📊 DIAGNOSTIKA SYSTÉMU"
    
    echo -e "${YELLOW}1. PM2 Status${NC}"
    pm2 list
    
    echo ""
    echo -e "${YELLOW}2. Earnings-cron Detailný Status${NC}"
    pm2 describe earnings-cron || echo -e "${RED}❌ earnings-cron nie je spustený${NC}"
    
    echo ""
    echo -e "${YELLOW}3. Posledných 50 riadkov z logov${NC}"
    pm2 logs earnings-cron --lines 50 --nostream 2>/dev/null | tail -50 || echo -e "${RED}❌ Žiadne logy${NC}"
    
    echo ""
    echo -e "${YELLOW}4. Posledné chyby${NC}"
    pm2 logs earnings-cron --err --lines 30 --nostream 2>/dev/null | tail -30 || echo "Žiadne chyby"
    
    echo ""
    echo -e "${YELLOW}5. Posledné cron ticky${NC}"
    pm2 logs earnings-cron --lines 500 --nostream 2>/dev/null | grep -i "\[CRON\] tick\|tick" | tail -10 || echo "Žiadne ticky"
    
    echo ""
    echo -e "${YELLOW}6. Posledné pipeline behy${NC}"
    pm2 logs earnings-cron --lines 500 --nostream 2>/dev/null | grep -i "pipeline\|starting\|completed\|failed" | tail -10 || echo "Žiadne pipeline behy"
    
    echo ""
    echo -e "${YELLOW}7. Overenie dát v databáze${NC}"
    cd "$CRON_DIR"
    npx tsx -e "
    import('./src/core/DatabaseManager.js').then(async ({ db }) => {
      try {
        const finhub = await db.getFinhubData();
        const polygon = await db.getPolygonData();
        const final = await db.getFinalReport();
        const withLogos = final.filter(r => r.logoUrl).length;
        const cronStatuses = await db.getAllCronStatuses();
        
        console.log('📊 FinhubData:', finhub.length, 'záznamov');
        console.log('📊 PolygonData:', polygon.length, 'záznamov');
        console.log('📊 FinalReport:', final.length, 'záznamov');
        console.log('🖼️  FinalReport s logami:', withLogos, 'z', final.length);
        console.log('');
        console.log('📋 Cron Statuses:');
        cronStatuses.forEach(s => {
          console.log('  -', s.name + ':', s.status, '(last run:', s.lastRunAt || 'never', ')');
        });
        
        if (final.length === 0) {
          console.log('');
          console.log('⚠️  VAROVANIE: FinalReport je prázdny!');
        }
        
        await db.disconnect();
      } catch (e) {
        console.error('❌ Error:', e.message);
        process.exit(1);
      }
    });
    " 2>/dev/null || echo -e "${RED}❌ Nepodarilo sa pripojiť k databáze${NC}"
    
    echo ""
    echo -e "${YELLOW}8. Aktuálny čas (NY)${NC}"
    TZ=America/New_York date
    
    echo ""
    echo -e "${YELLOW}9. Kontrola environment premenných${NC}"
    cd "$PROJECT_DIR"
    if [ -f .env ]; then
        echo "✅ .env súbor existuje"
        grep -q "FINNHUB_TOKEN" .env && echo "✅ FINNHUB_TOKEN je nastavený" || echo -e "${RED}❌ FINNHUB_TOKEN chýba${NC}"
        grep -q "POLYGON_API_KEY" .env && echo "✅ POLYGON_API_KEY je nastavený" || echo -e "${RED}❌ POLYGON_API_KEY chýba${NC}"
        grep -q "DATABASE_URL" .env && echo "✅ DATABASE_URL je nastavený" || echo -e "${RED}❌ DATABASE_URL chýba${NC}"
    else
        echo -e "${RED}❌ .env súbor neexistuje${NC}"
    fi
}

# 2. Resetovanie databáze
reset_db() {
    print_header "🗑️  RESETOVANIE DATABÁZY"
    
    echo -e "${YELLOW}⚠️  Toto vymaže všetky dáta z databázy!${NC}"
    read -p "Naozaj chcete pokračovať? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        echo -e "${YELLOW}❌ Zrušené${NC}"
        return
    fi
    
    cd "$CRON_DIR"
    
    echo -e "${YELLOW}Vymazávam všetky tabuľky...${NC}"
    ALLOW_CLEAR=true npx tsx -e "
    import('./src/core/DatabaseManager.js').then(async ({ db }) => {
      try {
        await db.clearAllTables();
        console.log('✅ Databáza bola vymazaná');
        await db.disconnect();
      } catch (e) {
        console.error('❌ Error:', e.message);
        process.exit(1);
      }
    });
    " || echo -e "${RED}❌ Chyba pri vymazávaní databázy${NC}"
    
    echo ""
    echo -e "${GREEN}✅ Resetovanie databázy dokončené${NC}"
}

# 3. Resetovanie cronu
reset_cron() {
    print_header "🔄 RESETOVANIE CRONU"
    
    echo -e "${YELLOW}Reštartujem earnings-cron...${NC}"
    pm2 restart earnings-cron
    
    echo ""
    echo -e "${YELLOW}Čakám 3 sekundy...${NC}"
    sleep 3
    
    echo ""
    echo -e "${YELLOW}Status po reštarte:${NC}"
    pm2 status earnings-cron
    
    echo ""
    echo -e "${YELLOW}Posledných 20 riadkov logov:${NC}"
    pm2 logs earnings-cron --lines 20 --nostream
    
    echo ""
    echo -e "${GREEN}✅ Cron bol reštartovaný${NC}"
}

# 4. Manuálne spustenie pipeline
force_run() {
    print_header "🚀 MANUÁLNE SPUSTENIE PIPELINE"
    
    echo -e "${YELLOW}Spúšťam pipeline manuálne...${NC}"
    cd "$CRON_DIR"
    
    # Spustenie pipeline cez TypeScript
    npx tsx -e "
    import('./src/main.js').then(async (module) => {
      console.log('🚀 Spúšťam pipeline...');
      // Pipeline sa spustí automaticky pri importe
      setTimeout(() => {
        console.log('✅ Pipeline spustený');
        process.exit(0);
      }, 5000);
    }).catch(e => {
      console.error('❌ Error:', e.message);
      process.exit(1);
    });
    " || {
        echo -e "${YELLOW}Skúšam alternatívny spôsob...${NC}"
        cd "$CRON_DIR"
        npm run start || echo -e "${RED}❌ Nepodarilo sa spustiť pipeline${NC}"
    }
    
    echo ""
    echo -e "${GREEN}✅ Pipeline bol spustený${NC}"
    echo -e "${YELLOW}Pozrite si logy: pm2 logs earnings-cron${NC}"
}

# 5. Kompletný reset (všetko)
full_reset() {
    print_header "🔄 KOMPLETNÝ RESET"
    
    echo -e "${YELLOW}⚠️  Toto urobí kompletný reset:${NC}"
    echo "  1. Vymaže databázu"
    echo "  2. Reštartuje cron"
    echo "  3. Spustí pipeline manuálne"
    echo ""
    read -p "Naozaj chcete pokračovať? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        echo -e "${YELLOW}❌ Zrušené${NC}"
        return
    fi
    
    # Reset DB
    reset_db
    
    # Reset cron
    reset_cron
    
    # Force run
    echo ""
    echo -e "${YELLOW}Čakám 5 sekúnd pred manuálnym spustením...${NC}"
    sleep 5
    
    force_run
    
    echo ""
    echo -e "${GREEN}✅ Kompletný reset dokončený${NC}"
    echo ""
    echo -e "${YELLOW}Overte dáta:${NC}"
    echo "  ./fix-production-data.sh diagnose"
}

# Hlavná logika
cd "$PROJECT_DIR"

case "${1:-diagnose}" in
    diagnose)
        diagnose
        ;;
    reset-db)
        reset_db
        ;;
    reset-cron)
        reset_cron
        ;;
    force-run)
        force_run
        ;;
    all)
        full_reset
        ;;
    *)
        echo "Použitie: $0 [diagnose|reset-db|reset-cron|force-run|all]"
        echo ""
        echo "  diagnose   - Diagnostika systému (predvolené)"
        echo "  reset-db   - Vymazať všetky dáta z databázy"
        echo "  reset-cron - Reštartovať earnings-cron"
        echo "  force-run  - Manuálne spustiť pipeline"
        echo "  all        - Kompletný reset (všetko)"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✅ Hotovo${NC}"

