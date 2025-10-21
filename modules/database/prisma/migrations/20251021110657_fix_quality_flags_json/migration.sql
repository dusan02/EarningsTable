/*
  Warnings:

  - You are about to alter the column `qualityFlags` on the `polygon_data` table. The data in that column could be lost. The data in that column will be cast from `String` to `Json`.

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
    "changeFromPrevClosePct" REAL,
    "changeFromOpenPct" REAL,
    "sessionRef" TEXT,
    "qualityFlags" JSONB,
    "change" REAL,
    "size" TEXT,
    "name" TEXT,
    "priceBoolean" BOOLEAN,
    "Boolean" BOOLEAN,
    "priceSource" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);
INSERT INTO "new_polygon_data" ("Boolean", "change", "changeFromOpenPct", "changeFromPrevClosePct", "createdAt", "id", "marketCap", "marketCapBoolean", "marketCapDiff", "name", "previousCloseAdj", "previousCloseRaw", "previousCloseSource", "previousMarketCap", "price", "priceBoolean", "priceSource", "qualityFlags", "sessionRef", "size", "symbol", "symbolBoolean", "updatedAt") SELECT "Boolean", "change", "changeFromOpenPct", "changeFromPrevClosePct", "createdAt", "id", "marketCap", "marketCapBoolean", "marketCapDiff", "name", "previousCloseAdj", "previousCloseRaw", "previousCloseSource", "previousMarketCap", "price", "priceBoolean", "priceSource", "qualityFlags", "sessionRef", "size", "symbol", "symbolBoolean", "updatedAt" FROM "polygon_data";
DROP TABLE "polygon_data";
ALTER TABLE "new_polygon_data" RENAME TO "polygon_data";
CREATE UNIQUE INDEX "polygon_data_symbol_key" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_symbol_idx" ON "polygon_data"("symbol");
CREATE INDEX "polygon_data_createdAt_idx" ON "polygon_data"("createdAt");
PRAGMA foreign_keys=ON;
PRAGMA defer_foreign_keys=OFF;
