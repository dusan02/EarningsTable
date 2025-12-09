// Test API endpoint locally
const http = require('http');

const options = {
  hostname: 'localhost',
  port: 5555,
  path: '/api/final-report',
  method: 'GET',
  headers: {
    'Content-Type': 'application/json'
  }
};

console.log('🧪 Testing /api/final-report locally...');
console.log(`📍 URL: http://${options.hostname}:${options.port}${options.path}`);

const req = http.request(options, (res) => {
  let data = '';

  console.log(`📊 Status Code: ${res.statusCode}`);
  console.log(`📋 Headers:`, res.headers);

  res.on('data', (chunk) => {
    data += chunk;
  });

  res.on('end', () => {
    try {
      const json = JSON.parse(data);
      console.log('\n✅ Response received:');
      console.log(`   Success: ${json.success}`);
      console.log(`   Count: ${json.count || json.data?.length || 0}`);
      console.log(`   Timestamp: ${json.timestamp || 'N/A'}`);
      
      if (json.data && json.data.length > 0) {
        console.log(`\n📈 First 5 companies:`);
        json.data.slice(0, 5).forEach((item, idx) => {
          console.log(`   ${idx + 1}. ${item.symbol} - ${item.name || 'N/A'} - MarketCap: ${item.marketCap || 'N/A'}`);
        });
      } else {
        console.log('\n⚠️  No data returned!');
      }
      
      // Full response (truncated if too long)
      if (data.length > 1000) {
        console.log(`\n📄 Response preview (first 1000 chars):`);
        console.log(data.substring(0, 1000) + '...');
      } else {
        console.log(`\n📄 Full response:`);
        console.log(JSON.stringify(json, null, 2));
      }
    } catch (error) {
      console.error('❌ Error parsing JSON:', error.message);
      console.log('Raw response:', data.substring(0, 500));
    }
  });
});

req.on('error', (error) => {
  console.error('❌ Request error:', error.message);
  console.error('   Make sure the server is running on port 5555');
});

req.end();

