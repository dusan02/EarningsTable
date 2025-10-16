-- CreateTable
CREATE TABLE "cron_status" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "jobType" TEXT NOT NULL,
    "lastRunAt" DATETIME NOT NULL,
    "status" TEXT NOT NULL,
    "recordsProcessed" INTEGER,
    "errorMessage" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" DATETIME NOT NULL
);

-- CreateIndex
CREATE UNIQUE INDEX "cron_status_jobType_key" ON "cron_status"("jobType");

-- CreateIndex
CREATE INDEX "cron_status_jobType_idx" ON "cron_status"("jobType");

-- CreateIndex
CREATE INDEX "cron_status_lastRunAt_idx" ON "cron_status"("lastRunAt");
