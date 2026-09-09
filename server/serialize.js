// Serialize a Prisma FinalReport row into a JSON-safe object.
// BigInt fields -> string (preserves full precision; JSON.stringify would throw
// on BigInt). Use explicit `!= null` so a valid 0n is serialized as "0" instead
// of being dropped (0n is falsy in JS). Date fields -> ISO string or null.
//
// NOTE: this is API-layer serialization (raw precision-preserving strings), NOT
// the human formatter `formatBigInt` in modules/shared (which produces "$X.XB").
// They are intentionally separate.
function serializeFinalReport(item) {
  return {
    ...item,
    marketCap: item.marketCap != null ? item.marketCap.toString() : null,
    marketCapDiff: item.marketCapDiff != null ? item.marketCapDiff.toString() : null,
    revActual: item.revActual != null ? item.revActual.toString() : null,
    revEst: item.revEst != null ? item.revEst.toString() : null,
    reportDate: item.reportDate ? item.reportDate.toISOString() : null,
    snapshotDate: item.snapshotDate ? item.snapshotDate.toISOString() : null,
    createdAt: item.createdAt ? item.createdAt.toISOString() : null,
    updatedAt: item.updatedAt ? item.updatedAt.toISOString() : null,
    logoFetchedAt: item.logoFetchedAt ? item.logoFetchedAt.toISOString() : null,
  };
}

module.exports = { serializeFinalReport };
