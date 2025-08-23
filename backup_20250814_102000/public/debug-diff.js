// Funkcia na vytiahnutie MAX hodnoty zo stĺpca DIFF
function getMaxDiff() {
    console.log('🔍 Hľadám MAX hodnotu zo stĺpca DIFF...');
    
    // Skontroluj či máme dáta
    if (!window.__lastData || !Array.isArray(window.__lastData)) {
        console.error('❌ Žiadne dáta na analýzu! Skús najprv načítať stránku.');
        return null;
    }
    
    console.log(`📊 Analyzujem ${window.__lastData.length} riadkov...`);
    
    let maxDiff = -Infinity;
    let maxDiffRow = null;
    
    // Prejdi všetky riadky a nájdi MAX diff
    window.__lastData.forEach((row, index) => {
        const diff = parseFloat(row.market_cap_diff) || 0;
        console.log(`${index + 1}. ${row.ticker}: diff = ${diff} (${row.market_cap_diff})`);
        
        if (diff > maxDiff) {
            maxDiff = diff;
            maxDiffRow = row;
            console.log(`🏆 Nový MAX: ${row.ticker} s diff = ${diff}`);
        }
    });
    
    // Výsledok
    console.log('='.repeat(50));
    console.log('🏆 VÝSLEDOK - MAX DIFF:');
    console.log(`Ticker: ${maxDiffRow?.ticker}`);
    console.log(`Diff: ${maxDiffRow?.market_cap_diff}`);
    console.log(`Číselná hodnota: ${maxDiff}`);
    console.log(`Market Cap: ${maxDiffRow?.market_cap}`);
    console.log(`Company: ${maxDiffRow?.company_name}`);
    console.log('='.repeat(50));
    
    return {
        ticker: maxDiffRow?.ticker,
        diff: maxDiffRow?.market_cap_diff,
        diffValue: maxDiff,
        marketCap: maxDiffRow?.market_cap,
        company: maxDiffRow?.company_name,
        row: maxDiffRow
    };
}

// Funkcia na vytiahnutie MIN hodnoty zo stĺpca DIFF
function getMinDiff() {
    console.log('🔍 Hľadám MIN hodnotu zo stĺpca DIFF...');
    
    if (!window.__lastData || !Array.isArray(window.__lastData)) {
        console.error('❌ Žiadne dáta na analýzu! Skús najprv načítať stránku.');
        return null;
    }
    
    let minDiff = Infinity;
    let minDiffRow = null;
    
    window.__lastData.forEach((row, index) => {
        const diff = parseFloat(row.market_cap_diff) || 0;
        
        if (diff < minDiff) {
            minDiff = diff;
            minDiffRow = row;
            console.log(`📉 Nový MIN: ${row.ticker} s diff = ${diff}`);
        }
    });
    
    console.log('='.repeat(50));
    console.log('📉 VÝSLEDOK - MIN DIFF:');
    console.log(`Ticker: ${minDiffRow?.ticker}`);
    console.log(`Diff: ${minDiffRow?.market_cap_diff}`);
    console.log(`Číselná hodnota: ${minDiff}`);
    console.log('='.repeat(50));
    
    return {
        ticker: minDiffRow?.ticker,
        diff: minDiffRow?.market_cap_diff,
        diffValue: minDiff,
        marketCap: minDiffRow?.market_cap,
        company: minDiffRow?.company_name,
        row: minDiffRow
    };
}

// Funkcia na porovnanie všetkých diff hodnôt
function compareAllDiffs() {
    console.log('🔍 Porovnávam všetky DIFF hodnoty...');
    
    if (!window.__lastData || !Array.isArray(window.__lastData)) {
        console.error('❌ Žiadne dáta na analýzu!');
        return;
    }
    
    // Vytvor pole s diff hodnotami
    const diffs = window.__lastData
        .map(row => ({
            ticker: row.ticker,
            diff: parseFloat(row.market_cap_diff) || 0,
            rawDiff: row.market_cap_diff,
            marketCap: row.market_cap
        }))
        .filter(item => item.diff !== 0) // Filtruj nenulové hodnoty
        .sort((a, b) => b.diff - a.diff); // Zoraď zostupne
    
    console.log('📊 TOP 10 najväčších DIFF hodnôt:');
    diffs.slice(0, 10).forEach((item, index) => {
        console.log(`${index + 1}. ${item.ticker}: ${item.rawDiff} (${item.diff})`);
    });
    
    console.log('📊 TOP 10 najmenších DIFF hodnôt:');
    diffs.slice(-10).reverse().forEach((item, index) => {
        console.log(`${index + 1}. ${item.ticker}: ${item.rawDiff} (${item.diff})`);
    });
    
    return diffs;
}

// Funkcia na test konkrétneho tickera
function testTicker(ticker) {
    console.log(`🔍 Testujem ticker: ${ticker}`);
    
    if (!window.__lastData || !Array.isArray(window.__lastData)) {
        console.error('❌ Žiadne dáta na analýzu!');
        return;
    }
    
    const row = window.__lastData.find(r => r.ticker === ticker);
    
    if (!row) {
        console.error(`❌ Ticker ${ticker} sa nenašiel!`);
        return;
    }
    
    console.log('📊 Dáta pre', ticker + ':');
    console.log(`Market Cap: ${row.market_cap}`);
    console.log(`Diff: ${row.market_cap_diff}`);
    console.log(`Diff (číselne): ${parseFloat(row.market_cap_diff) || 0}`);
    console.log(`Company: ${row.company_name}`);
    console.log(`Price Change: ${row.price_change_percent}%`);
    
    return row;
}

// Export funkcií pre použitie v konzole
console.log('🚀 Debug funkcie načítané!');
console.log('Použite:');
console.log('- getMaxDiff() - nájde MAX diff hodnotu');
console.log('- getMinDiff() - nájde MIN diff hodnotu');
console.log('- compareAllDiffs() - porovná všetky diff hodnoty');
console.log('- testTicker("BABA") - test konkrétneho tickera');
