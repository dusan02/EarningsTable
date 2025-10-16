# Start unified server
Write-Host "🚀 Starting Earnings Table Server..." -ForegroundColor Green

# Set environment variables
$env:PORT = "5555"
$env:DATABASE_URL = "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"

# Start the server
node simple-server.js
