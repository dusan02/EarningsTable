const https = require('https');
const http = require('http');

function fetchData(url) {
  return new Promise((resolve, reject) => {
    const client = url.startsWith('https') ? https : http;
    client.get(url, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(e);
        }
      });
    }).on('error', reject);
  });
}

async function checkSymbols() {
  try {
    const response = await fetchData('http://localhost:5555/api/final-report');
    const symbols = ['HBCP', 'SFST', 'AVBH', 'FLXS', 'LODE', 'NTZ', 'ENTO', 'ADN', 'BOKF', 'PFBC'];
    
    console.log('=== CHECKING SPECIFIC SYMBOLS ===');
    console.log(`Total records: ${response.count}`);
    
    for (const symbol of symbols) {
      const record = response.data.find(r => r.symbol === symbol);
      if (record) {
        console.log(`${symbol}: price=${record.price}, change=${record.change}, changePercent=${record.changePercent}`);
      } else {
        console.log(`${symbol}: NOT FOUND in FinalReport`);
      }
    }
  } catch (error) {
    console.error('Error:', error.message);
  }
}

checkSymbols();
