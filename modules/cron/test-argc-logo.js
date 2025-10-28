import axios from "axios";

async function testARGC() {
  const symbol = "ARGC";
  const token = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0";

  console.log(`Testing logo sources for ${symbol}:`);

  // Test Finnhub
  try {
    const finnhubUrl = `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${token}`;
    console.log(`\n1. Testing Finnhub: ${finnhubUrl}`);
    const finnhubResponse = await axios.get(finnhubUrl);
    console.log("Finnhub response:", finnhubResponse.data);
    if (finnhubResponse.data?.logo) {
      console.log("✅ Finnhub has logo:", finnhubResponse.data.logo);
    } else {
      console.log("❌ No Finnhub logo");
    }
  } catch (error) {
    console.log("❌ Finnhub error:", error.response?.status, error.message);
  }

  // Test Polygon
  try {
    const polygonUrl = `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX`;
    console.log(`\n2. Testing Polygon: ${polygonUrl}`);
    const polygonResponse = await axios.get(polygonUrl);
    console.log("Polygon response:", polygonResponse.data);
    if (polygonResponse.data?.results?.branding?.logo_url) {
      console.log(
        "✅ Polygon has logo:",
        polygonResponse.data.results.branding.logo_url
      );
    } else {
      console.log("❌ No Polygon logo");
    }
    if (polygonResponse.data?.results?.homepage_url) {
      console.log(
        "✅ Polygon has homepage:",
        polygonResponse.data.results.homepage_url
      );
    } else {
      console.log("❌ No Polygon homepage");
    }
  } catch (error) {
    console.log("❌ Polygon error:", error.response?.status, error.message);
  }

  // Test Yahoo Finance via Clearbit
  try {
    const yahooUrl = `https://logo.clearbit.com/finance.yahoo.com/quote/${symbol}`;
    console.log(`\n3. Testing Yahoo Finance: ${yahooUrl}`);
    const yahooResponse = await axios.get(yahooUrl);
    console.log("✅ Yahoo Finance logo accessible");
  } catch (error) {
    console.log(
      "❌ Yahoo Finance error:",
      error.response?.status,
      error.message
    );
  }
}

testARGC();

