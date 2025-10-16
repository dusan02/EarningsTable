-- CreateTable
CREATE TABLE "earnings_reports" (
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

-- CreateIndex
CREATE INDEX "earnings_reports_reportDate_idx" ON "earnings_reports"("reportDate");

-- CreateIndex
CREATE INDEX "earnings_reports_symbol_idx" ON "earnings_reports"("symbol");

-- CreateIndex
CREATE UNIQUE INDEX "earnings_reports_reportDate_symbol_key" ON "earnings_reports"("reportDate", "symbol");
