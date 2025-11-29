#!/bin/bash
# Príkazy na stiahnutie zmien z GitHubu na SSH serveri

# ============================================
# SCENÁR 1: Ak už máte existujúci git repozitár
# ============================================

# 1. Prejsť do priečinka projektu
cd /var/www/earnings-table  # alebo váš priečinok

# 2. Skontrolovať aktuálny branch
git branch

# 3. Stiahnuť najnovšie zmeny z GitHubu
git fetch origin

# 4. Prepnúť sa na branch feat/skeleton-loading-etag (ak nie ste na ňom)
git checkout feat/skeleton-loading-etag

# 5. Stiahnuť a aplikovať zmeny
git pull origin feat/skeleton-loading-etag

# ALTERNATÍVA: Ak chcete stiahnuť všetky zmeny z aktuálneho branchu
# git pull

# ============================================
# SCENÁR 2: Ak ešte nemáte repozitár (prvé stiahnutie)
# ============================================

# 1. Klonovať repozitár
# git clone https://github.com/dusan02/EarningsTable.git

# 2. Prejsť do priečinka
# cd EarningsTable

# 3. Prepnúť sa na správny branch
# git checkout feat/skeleton-loading-etag

# ============================================
# SCENÁR 3: Ak chcete len konkrétny súbor
# ============================================

# 1. Stiahnuť konkrétny súbor pomocou curl
# curl -O https://raw.githubusercontent.com/dusan02/EarningsTable/feat/skeleton-loading-etag/modules/cron/src/main.ts

# ============================================
# OVERENIE: Skontrolovať, či sa zmeny stiahli
# ============================================

# Zobraziť posledné commity
git log --oneline -5

# Zobraziť zmeny v súbore
git show HEAD:modules/cron/src/main.ts | head -50

