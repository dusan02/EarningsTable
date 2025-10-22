export function getNYMidnight(dateUTC = new Date()): Date {
  const ny = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/New_York",
    year: "numeric", 
    month: "2-digit", 
    day: "2-digit",
  }).format(dateUTC);
  return new Date(`${ny}T00:00:00.000-05:00`);
}

export function validateNotFuture(date: Date, maxFutureHours = 24): boolean {
  const nowNY = getNYMidnight(new Date());
  const maxFuture = new Date(nowNY.getTime() + (maxFutureHours * 60 * 60 * 1000));
  return date.getTime() <= maxFuture.getTime();
}
