
// Prisma middleware - ochrana proti ms timestampom
prisma.$use(async (params, next) => {
  const forceDates = (obj) => {
    if (!obj || typeof obj !== 'object') return obj;
    const cast = (v) =>
      v instanceof Date ? v : (typeof v === 'number' ? new Date(v) :
      (typeof v === 'string' ? new Date(v) : v));
    if (obj.reportDate) obj.reportDate = cast(obj.reportDate);
    if (obj.snapshotDate) obj.snapshotDate = cast(obj.snapshotDate);
    if (obj.createdAt) obj.createdAt = cast(obj.createdAt);
    if (obj.updatedAt) obj.updatedAt = cast(obj.updatedAt);
    return obj;
  };

  if (params.model === 'FinalReport') {
    if (params.args?.data) params.args.data = forceDates(params.args.data);
    if (params.args?.create) params.args.create = forceDates(params.args.create);
    if (params.args?.update) params.args.update = forceDates(params.args.update);
  }
  return next(params);
});
