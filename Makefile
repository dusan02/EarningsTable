# 🚀 EarningsTable Makefile
# Automatizácia bežných úloh

.PHONY: help test clean deploy backup ci-cd

# Default target
help:
	@echo "🚀 EarningsTable - Available commands:"
	@echo ""
	@echo "📋 Development:"
	@echo "  make test          - Spusti všetky testy"
	@echo "  make test-db       - Test databázového pripojenia"
	@echo "  make test-api      - Test API funkcionality"
	@echo "  make ci-cd         - Spusti CI/CD pipeline lokálne"
	@echo ""
	@echo "🧹 Maintenance:"
	@echo "  make clean         - Vyčisti cache a logy"
	@echo "  make backup        - Vytvor zálohu"
	@echo "  make clear-tables  - Vyčisti databázové tabuľky"
	@echo ""
	@echo "🚀 Deployment:"
	@echo "  make deploy        - Nasadenie na server"
	@echo "  make sync          - Synchronizácia s htdocs"
	@echo "  make rollback      - Rollback na predchádzajúcu verziu"
	@echo ""
	@echo "🔍 Code Quality:"
	@echo "  make stan          - PHPStan analýza"
	@echo "  make cs            - CodeSniffer kontrola"
	@echo "  make quality       - Kompletná kontrola kvality"
	@echo ""
	@echo "🔐 Security:"
	@echo "  make setup-env     - Nastavenie environment"
	@echo "  make test-env      - Test environment premenných"
	@echo "  make test-rate     - Test rate limiting"
	@echo "  make monitor-api   - Monitor API limitov"
	@echo "  make test-sql      - Test SQL injection protection"
	@echo "  make test-log      - Test logging & monitoring"
	@echo "  make test-headers  - Test security headers"

# Test commands
test:
	@echo "🧪 Spúšťam všetky testy..."
	@php Tests/test-db.php
	@php Tests/test-path.php
	@php Tests/check_tickers.php

test-db:
	@echo "🗄️ Testujem databázové pripojenie..."
	@php Tests/test-db.php

test-api:
	@echo "🌐 Testujem API..."
	@php Tests/test_api.php

# Maintenance commands
clean:
	@echo "🧹 Čistím cache a logy..."
	@rm -rf logs/*.log
	@rm -rf storage/*.cache
	@echo "✅ Vyčistené!"

backup:
	@echo "💾 Vytváram zálohu..."
	@mkdir -p archive/backup_$(shell date +%Y%m%d_%H%M%S)
	@cp -r config/ archive/backup_$(shell date +%Y%m%d_%H%M%S)/
	@echo "✅ Záloha vytvorená!"

clear-tables:
	@echo "🗑️ Čistím databázové tabuľky..."
	@php scripts/clear_tables.php

# Deployment commands
deploy:
	@echo "🚀 Nasadzujem na server..."
	@cd deploy && ./sync_to_htdocs.bat

sync:
	@echo "🔄 Synchronizujem s htdocs..."
	@cd deploy && ./sync_to_htdocs.bat

# CI/CD commands
ci-cd:
	@echo "🚀 Spúšťam CI/CD pipeline lokálne..."
	@echo "🧪 1. Spúšťam testy..."
	@make test
	@echo "🔍 2. Kontrola kvality kódu..."
	@make quality
	@echo "📦 3. Vytváram deployment package..."
	@composer deploy
	@echo "✅ CI/CD pipeline dokončený úspešne!"

rollback:
	@echo "🚨 Spúšťam rollback..."
	@chmod +x deploy/rollback.sh
	@./deploy/rollback.sh

# Code quality commands
stan:
	@echo "🔍 PHPStan analýza..."
	@composer stan

cs:
	@echo "🎨 CodeSniffer kontrola..."
	@composer cs

quality:
	@echo "🔍 Kompletná kontrola kvality..."
	@make stan
	@make cs
	@echo "📝 Kontrola dokumentácie..."
	@if [ -f "README.md" ] && [ -d "docs" ] && [ -f "Tests/README.md" ]; then \
		echo "✅ Dokumentácia je kompletná"; \
	else \
		echo "❌ Chýbajúce súbory dokumentácie"; \
		exit 1; \
	fi

# Security commands
setup-env:
	@echo "🔐 Nastavujem environment..."
	@php scripts/setup_env.php

test-env:
	@echo "🧪 Testujem environment premenné..."
	@php Tests/test_env.php

test-rate:
	@echo "🚦 Testujem rate limiting..."
	@php Tests/test_rate_limiting.php

monitor-api:
	@echo "📊 Monitorujem API limity..."
	@php scripts/monitor_api_limits.php

test-sql:
	@echo "🔍 Testujem SQL injection protection..."
	@php Tests/test_sql_injection.php

test-log:
	@echo "📝 Testujem logging & monitoring..."
	@php Tests/test_logging_monitoring.php

test-headers:
	@echo "🌐 Testujem security headers..."
	@php Tests/test_security_headers.php

monitor-security:
	@echo "🔒 Monitorujem bezpečnosť..."
	@php scripts/monitor_security.php
