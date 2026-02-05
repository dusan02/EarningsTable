#!/bin/bash
# Deploy oprav dátumov a logov

cd /srv/EarningsTable && echo "═══════════════════════════════════════════════════════════" && echo "DEPLOY OPRAV DÁTUMOV A LOGOV" && echo "═══════════════════════════════════════════════════════════" && echo "" && echo "1. Git stash lokálne zmeny (ak existujú):" && git stash && echo "" && echo "2. Git pull najnovšie zmeny:" && git pull origin main && echo "" && echo "3. Reštart earnings-cron service:" && systemctl restart earnings-cron && echo "" && echo "4. Čakanie 10 sekúnd..." && sleep 10 && echo "" && echo "5. Kontrola statusu:" && systemctl status earnings-cron --no-pager -l | head -20 && echo "" && echo "6. Kontrola logov (posledných 20 riadkov):" && journalctl -u earnings-cron -n 20 --no-pager | tail -15 && echo "" && echo "✅ Deploy dokončený! Počkaj 5-10 minút a skontroluj, či sa dátumy aktualizujú."

