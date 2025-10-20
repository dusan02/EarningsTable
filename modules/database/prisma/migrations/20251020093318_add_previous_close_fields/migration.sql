/*
  Warnings:

  - You are about to drop the column `previousClose` on the `polygon_data` table. All the data in the column will be lost.

*/
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
    "previousCloseRaw" REAL,
    "previousCloseAdj" REAL,
    "previousCloseSource" TEXT,
    "change" REAL,
    "size" TEXT,
    "name" TEXT,
    "priceBoolean" BOOLEAN,
    "Boolean" BOOLEAN,
    "priceSource" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);
INSERT INTO "new_polygon_data" ("Boolean", "change", "createdAt", "id", "marketCap", "marketCapBoolean", "marketCapDiff", "name", "previousMarketCap", "price", "priceBoolean", "priceSource", "size", "symbol", "symbolBoolean", "updatedAt") SELECT "Boolean", "change", "createdAt", "id", "marketCap", "marketCapBoolean", "marketCapDiff", "name", "previousMarketCap", "price", "priceBoolean", "priceSource", "size", "symbol", "symbolBoolean", "updatedAt" FROM "polygon_data";
DROP TABLE "polygon_data";
ALTER TABLE "new_polygon_data" RENAME TO "polygon_data";
CREATE UNIQUE INDEX "polygon_data_symbol_key" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_symbol_idx" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_createdAt_idx" ON "polygon_data"("createdAt");
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;
