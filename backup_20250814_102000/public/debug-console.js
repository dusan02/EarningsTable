// Debug script - spusti toto v konzole na stránke earnings-table-simple.html

console.log("🔍 DEBUG: Starting console debug...");

if (!window.__lastData || !window.__lastData.length) {
  console.log("🔍 DEBUG: No data loaded yet");
  console.log("🔍 DEBUG: window.__lastData:", window.__lastData);
  alert("No data loaded yet. Please wait for data to load first.");
} else {
  const rows = window.__lastData;
  console.log("🔍 DEBUG: Total rows:", rows.length);

  // Helper function
  function num(v) {
    if (v == null) return 0;
    if (typeof v === "number" && isFinite(v)) return v;
    const s = String(v)
      .replace(/[$,%\s,]/g, "")
      .trim();
    const m = s.match(/^(-?\d+(\.\d+)?)([KMBT])?$/i);
    if (!m) return parseFloat(s) || 0;
    const base = parseFloat(m[1]);
    const mult =
      { K: 1e3, M: 1e6, B: 1e9, T: 1e12 }[(m[3] || "").toUpperCase()] || 1;
    return base * mult;
  }

  // Top 10 market_cap_diff values
  const diffValues = rows
    .map((row) => ({
      ticker: row.ticker,
      diff: row.market_cap_diff,
      parsed: num(row.market_cap_diff),
    }))
    .filter((row) => row.parsed !== 0)
    .sort((a, b) => b.parsed - a.parsed)
    .slice(0, 10);

  console.log("🔍 DEBUG: Top 10 market_cap_diff values:");
  diffValues.forEach((row, i) => {
    console.log(`${i + 1}. ${row.ticker}: ${row.diff} (parsed: ${row.parsed})`);
  });

  // Top 10 market_cap values
  const marketCapValues = rows
    .map((row) => ({
      ticker: row.ticker,
      cap: row.market_cap,
      parsed: num(row.market_cap),
    }))
    .filter((row) => row.parsed !== 0)
    .sort((a, b) => b.parsed - a.parsed)
    .slice(0, 10);

  console.log("🔍 DEBUG: Top 10 market_cap values:");
  marketCapValues.forEach((row, i) => {
    console.log(`${i + 1}. ${row.ticker}: ${row.cap} (parsed: ${row.parsed})`);
  });

  // HYPR data
  const hypr = rows.find((row) => row.ticker === "HYPR");
  console.log("🔍 DEBUG: HYPR data:", hypr);

  // BABA data
  const baba = rows.find((row) => row.ticker === "BABA");
  console.log("🔍 DEBUG: BABA data:", baba);

  // Simulate calculateMetrics
  const diffVal = (r) => {
    if (!r) return 0;
    if (r.market_cap_diff != null) return num(r.market_cap_diff);
    if (r.market_cap_diff_billions != null)
      return num(r.market_cap_diff_billions) * 1e9;
    return 0;
  };

  let mcGainer = null,
    mcLoser = null;
  let maxDiff = -Infinity;
  let minDiff = Infinity;

  for (const r of rows) {
    const d = diffVal(r);
    if (d > maxDiff) {
      maxDiff = d;
      mcGainer = r;
      console.log(
        `🔍 DEBUG: New mcGainer: ${r.ticker} with diff=${d} (raw: ${r.market_cap_diff})`
      );
    }
    if (d < minDiff) {
      minDiff = d;
      mcLoser = r;
      console.log(
        `🔍 DEBUG: New mcLoser: ${r.ticker} with diff=${d} (raw: ${r.market_cap_diff})`
      );
    }
  }

  console.log("🔍 DEBUG: Final results:");
  console.log(`  mcGainer: ${mcGainer?.ticker} (diff: ${diffVal(mcGainer)})`);
  console.log(`  mcLoser: ${mcLoser?.ticker} (diff: ${diffVal(mcLoser)})`);

  alert(
    `DEBUG COMPLETE!\n\nCheck console for details.\n\nFinal results:\nmcGainer: ${
      mcGainer?.ticker
    } (diff: ${diffVal(mcGainer)})\nmcLoser: ${
      mcLoser?.ticker
    } (diff: ${diffVal(mcLoser)})`
  );
}
