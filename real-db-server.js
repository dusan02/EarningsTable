const express = require("express");
const cors = require("cors");
const sqlite3 = require("sqlite3").verbose();
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Database path
const dbPath = path.join(__dirname, "modules", "database", "prisma", "dev.db");

// API Routes
app.get("/api/final-report", async (req, res) => {
  try {
    console.log("📊 Fetching FinalReport data from SQLite database...");

    const db = new sqlite3.Database(dbPath, (err) => {
      if (err) {
        console.error("❌ Error opening database:", err.message);
        return res.status(500).json({
          success: false,
          error: "Database connection failed",
          message: err.message,
        });
      }
    });

    const query = `
      SELECT 
        id,
        symbol,
        name,
        size,
        marketCap,
        marketCapDiff,
        price,
        change,
        epsActual,
        epsEst,
        epsSurp,
        revActual,
        revEst,
        revSurp,
        createdAt,
        updatedAt
      FROM final_report 
      ORDER BY symbol ASC
    `;

    db.all(query, [], (err, rows) => {
      if (err) {
        console.error("❌ Error querying database:", err.message);
        res.status(500).json({
          success: false,
          error: "Database query failed",
          message: err.message,
        });
      } else {
        console.log(`✅ Found ${rows.length} records in FinalReport`);

        // Convert BigInt fields to strings for JSON serialization
        const serializedData = rows.map((row) => ({
          ...row,
          marketCap: row.marketCap ? row.marketCap.toString() : null,
          marketCapDiff: row.marketCapDiff
            ? row.marketCapDiff.toString()
            : null,
          revActual: row.revActual ? row.revActual.toString() : null,
          revEst: row.revEst ? row.revEst.toString() : null,
        }));

        res.json({
          success: true,
          data: serializedData,
          count: serializedData.length,
          timestamp: new Date().toISOString(),
        });
      }

      db.close((err) => {
        if (err) {
          console.error("❌ Error closing database:", err.message);
        }
      });
    });
  } catch (error) {
    console.error("❌ Error fetching FinalReport:", error);
    res.status(500).json({
      success: false,
      error: "Failed to fetch FinalReport data",
      message: error.message,
    });
  }
});

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    status: "healthy",
    timestamp: new Date().toISOString(),
    message: "Real database API server is running",
    database: dbPath,
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Real Database API Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(
    `   GET  /api/final-report - Get all FinalReport data from SQLite`
  );
  console.log(`   GET  /api/health - Health check`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
  console.log(`📝 Note: Using real SQLite database: ${dbPath}`);
});

// Graceful shutdown
process.on("SIGINT", async () => {
  console.log("\n🛑 Shutting down server...");
  process.exit(0);
});
