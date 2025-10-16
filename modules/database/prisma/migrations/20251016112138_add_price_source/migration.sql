/*
  Warnings:

  - You are about to drop the column `currentPrice` on the `polygon_data` table. All the data in the column will be lost.
  - You are about to drop the column `lastUpdated` on the `polygon_data` table. All the data in the column will be lost.
  - You are about to drop the column `volume` on the `polygon_data` table. All the data in the column will be lost.

*/
-- AlterTable
ALTER TABLE "finnhub_data" ADD COLUMN "logoFetchedAt" DATETIME;
ALTER TABLE "finnhub_data" ADD COLUMN "logoSource" TEXT;
ALTER TABLE "finnhub_data" ADD COLUMN "logoUrl" TEXT;

-- CreateTable
CREATE TABLE "final_report" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "symbol" TEXT NOT NULL,
    "name" TEXT,
    "size" TEXT,
    "marketCap" BIGINT,
    "marketCapDiff" BIGINT,
    "price" REAL,
    "change" REAL,
    "epsActual" REAL,
    "epsEst" REAL,
    "epsSurp" REAL,
    "revActual" BIGINT,
    "revEst" BIGINT,
    "revSurp" REAL,
    "logoUrl" TEXT,
    "logoSource" TEXT,
    "logoFetchedAt" DATETIME,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);

-- RedefineTables
PRAGMA defer_foreign_keys=ON;
PRAGMA foreign_keys=OFF;
CREATE TABLE "new_polygon_data" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "symbol" TEXT NOT NULL,
    "symbolBoolean" BOOLEAN,
    "marketCap" BIGINT,
    "previousMarketCap" BIGINT,
    "marketCapDiff" BIGINT,
    "marketCapBoolean" BOOLEAN,
    "price" REAL,
    "previousClose" REAL,
    "change" REAL,
    "size" TEXT,
    "name" TEXT,
    "priceBoolean" BOOLEAN,
    "Boolean" BOOLEAN,
    "priceSource" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);
INSERT INTO "new_polygon_data" ("createdAt", "id", "marketCap", "previousClose", "symbol", "updatedAt") SELECT "createdAt", "id", "marketCap", "previousClose", "symbol", "updatedAt" FROM "polygon_data";
DROP TABLE "polygon_data";
ALTER TABLE "new_polygon_data" RENAME TO "polygon_data";
CREATE UNIQUE INDEX "polygon_data_symbol_key" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_symbol_idx" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_createdAt_idx" ON "polygon_data"("createdAt");
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;

-- CreateIndex
CREATE UNIQUE INDEX "final_report_symbol_key" ON "final_report"("symbol");

-- CreateIndex
CREATE INDEX "final_report_symbol_idx" ON "final_report"("symbol");

-- CreateIndex
CREATE INDEX "final_report_createdAt_idx" ON "final_report"("createdAt");

-- CreateIndex
CREATE INDEX "finnhub_data_reportDate_symbol_idx" ON "finnhub_data"("reportDate", "symbol");
