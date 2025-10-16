/*
  Warnings:

  - You are about to drop the `earnings_reports` table. If the table is not empty, all the data it contains will be lost.

*/
-- DropTable
PRAGMA foreign_keys=off;
DROP TABLE "earnings_reports";
PRAGMA foreign_keys=on;

-- CreateTable
CREATE TABLE "finnhub_data" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "reportDate" DATETIME NOT NULL,
    "symbol" TEXT NOT NULL,
    "epsActual" REAL,
    "epsEstimate" REAL,
    "revenueActual" BIGINT,
    "revenueEstimate" BIGINT,
    "hour" TEXT,
    "quarter" INTEGER,
    "year" INTEGER,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);

-- CreateTable
CREATE TABLE "polygon_data" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "symbol" TEXT NOT NULL,
    "marketCap" BIGINT,
    "currentPrice" REAL,
    "previousClose" REAL,
    "volume" BIGINT,
    "lastUpdated" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);

-- CreateIndex
CREATE INDEX "finnhub_data_reportDate_idx" ON "finnhub_data"("reportDate");

-- CreateIndex
CREATE INDEX "finnhub_data_symbol_idx" ON "finnhub_data"("symbol");

-- CreateIndex
CREATE UNIQUE INDEX "finnhub_data_reportDate_symbol_key" ON "finnhub_data"("reportDate", "symbol");

-- CreateIndex
CREATE INDEX "polygon_data_symbol_idx" ON "polygon_data"("symbol");

-- CreateIndex
CREATE INDEX "polygon_data_lastUpdated_idx" ON "polygon_data"("lastUpdated");

-- CreateIndex
CREATE UNIQUE INDEX "polygon_data_symbol_key" ON "polygon_data"("symbol");
