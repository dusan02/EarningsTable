// Prisma client setup for the API server.
// Loads the generated client from modules/shared (where `prisma generate`
// emits it), points the query-engine runtime at the right binary, and
// instantiates the client with the runtime DATABASE_URL.
const path = require("path");
const fs = require("fs");

let PrismaClient;
try {
  const sharedPrismaPath = path.resolve(
    __dirname,
    "..",
    "modules",
    "shared",
    "node_modules",
    "@prisma",
    "client"
  );

  if (fs.existsSync(sharedPrismaPath)) {
    PrismaClient = require(sharedPrismaPath).PrismaClient;
    console.log("[Prisma] ✅ Using client from modules/shared");
    console.log("[Prisma] Path:", sharedPrismaPath);
  } else {
    // Fallback to root node_modules (so we can start locally even when the
    // shared client hasn't been generated yet).
    PrismaClient = require("@prisma/client").PrismaClient;
    console.log("[Prisma] ⚠️ Shared Prisma client missing, using root @prisma/client");
  }
} catch (e) {
  console.error("[Prisma] ❌ Failed to load Prisma client:", e.message);
  throw new Error(
    "Prisma client not found. Try: npm install && npx prisma generate --schema=modules/database/prisma/schema.prisma"
  );
}

// Point Prisma at the correct query-engine binary for this platform.
const sharedPrismaRuntimePath = path.resolve(
  __dirname,
  "..",
  "modules",
  "shared",
  "node_modules",
  ".prisma",
  "client"
);
console.log("[Prisma] Checking runtime path:", sharedPrismaRuntimePath);
console.log("[Prisma] Runtime path exists:", fs.existsSync(sharedPrismaRuntimePath));

if (fs.existsSync(sharedPrismaRuntimePath)) {
  const files = fs.readdirSync(sharedPrismaRuntimePath);
  console.log("[Prisma] Runtime files:", files);

  const queryEngine = files.find(
    (f) =>
      f.includes("query_engine") &&
      (f.endsWith(".so.node") || f.endsWith(".dll.node"))
  );
  const schemaEngine = files.find((f) => {
    return (
      f.includes("schema-engine") &&
      !f.includes(".node") &&
      !f.endsWith(".js") &&
      !f.endsWith(".d.ts")
    );
  });

  if (queryEngine) {
    process.env.PRISMA_QUERY_ENGINE_LIBRARY = path.join(sharedPrismaRuntimePath, queryEngine);
    console.log("[Prisma] ✅ Runtime library set:", process.env.PRISMA_QUERY_ENGINE_LIBRARY);
  } else {
    console.log("[Prisma] ⚠️ Query engine not found in runtime files");
  }
  if (schemaEngine) {
    process.env.PRISMA_SCHEMA_ENGINE_BINARY = path.join(sharedPrismaRuntimePath, schemaEngine);
    console.log("[Prisma] ✅ Schema engine set:", process.env.PRISMA_SCHEMA_ENGINE_BINARY);
  } else {
    console.log("[Prisma] ⚠️ Schema engine not found in runtime files");
  }
} else {
  console.error("[Prisma] ❌ Runtime path does not exist:", sharedPrismaRuntimePath);
}

const prisma = new PrismaClient({
  datasources: {
    db: {
      url: process.env.DATABASE_URL,
    },
  },
});

module.exports = { prisma, PrismaClient };
