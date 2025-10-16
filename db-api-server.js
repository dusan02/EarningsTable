const express = require("express");
const cors = require("cors");
const sqlite3 = require("sqlite3").verbose();
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3002;

// Middleware
app.use(cors());
app.use(express.json());

// Database path
const dbPath = path.join(__dirname, "modules", "database", "prisma", "dev.db");

// Helper function to get all data from a table
function getAllFromTable(tableName) {
  return new Promise((resolve, reject) => {
    const db = new sqlite3.Database(dbPath, (err) => {
      if (err) {
        reject(err);
        return;
      }
    });

    const query = `SELECT * FROM ${tableName} ORDER BY id DESC LIMIT 100`;

    db.all(query, [], (err, rows) => {
      if (err) {
        reject(err);
      } else {
        resolve(rows);
      }
      db.close();
    });
  });
}

// API Routes
app.get("/api/finalReport", async (req, res) => {
  try {
    console.log("📊 Fetching FinalReport data...");
    const data = await getAllFromTable("final_report");

    // Convert BigInt to string for JSON serialization
    const serializedData = data.map((item) => ({
      ...item,
      marketCap: item.marketCap ? item.marketCap.toString() : null,
      marketCapDiff: item.marketCapDiff ? item.marketCapDiff.toString() : null,
      revActual: item.revActual ? item.revActual.toString() : null,
      revEst: item.revEst ? item.revEst.toString() : null,
    }));

    res.json(serializedData);
  } catch (error) {
    console.error("❌ Error fetching FinalReport:", error);
    res.status(500).json({ error: error.message });
  }
});

app.get("/api/finnhub", async (req, res) => {
  try {
    console.log("📊 Fetching Finnhub data...");
    const data = await getAllFromTable("finnhub_data");
    res.json(data);
  } catch (error) {
    console.error("❌ Error fetching Finnhub:", error);
    res.status(500).json({ error: error.message });
  }
});

app.get("/api/polygonData", async (req, res) => {
  try {
    console.log("📊 Fetching Polygon data...");
    const data = await getAllFromTable("polygon_data");
    res.json(data);
  } catch (error) {
    console.error("❌ Error fetching Polygon:", error);
    res.status(500).json({ error: error.message });
  }
});

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    status: "healthy",
    timestamp: new Date().toISOString(),
    message: "Database API server is running",
    database: dbPath,
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Database API Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(`   GET  /api/finalReport - Get FinalReport data`);
  console.log(`   GET  /api/finnhub - Get Finnhub data`);
  console.log(`   GET  /api/polygonData - Get Polygon data`);
  console.log(`   GET  /api/health - Health check`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
  console.log(`📝 Note: Using SQLite database: ${dbPath}`);
});

// Graceful shutdown
process.on("SIGINT", async () => {
  console.log("\n🛑 Shutting down server...");
  process.exit(0);
});
