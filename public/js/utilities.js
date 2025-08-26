/**
 * 🛠️ UTILITIES - Common JavaScript functions for Earnings Dashboard
 * Extracted from dashboard-fixed.html for better code organization
 */

/**
 * Calculate surprise percentage between actual and estimate values
 * @param {number|string|null} actual - Actual value
 * @param {number|string|null} estimate - Estimated value
 * @returns {number} Surprise percentage or NaN if invalid
 */
function calculateSurprise(actual, estimate) {
  if (
    actual === null ||
    estimate === null ||
    actual === undefined ||
    estimate === undefined
  )
    return NaN;
  const actualNum = parseFloat(actual);
  const estimateNum = parseFloat(estimate);
  if (isNaN(actualNum) || isNaN(estimateNum) || estimateNum === 0) return NaN;
  return ((actualNum - estimateNum) / Math.abs(estimateNum)) * 100;
}

/**
 * Robust numeric comparison with null handling for sorting
 * @param {Object} a - First object
 * @param {Object} b - Second object
 * @param {string} direction - Sort direction ('asc' or 'desc')
 * @param {string} field - Field name to compare
 * @returns {number} Comparison result
 */
function cmpNumericWithNullLast(a, b, direction, field) {
  const aVal = a[field];
  const bVal = b[field];

  // Check if values are null, undefined, empty, or zero
  const aIsNull =
    aVal === null || aVal === undefined || aVal === "" || aVal === 0;
  const bIsNull =
    bVal === null || bVal === undefined || bVal === "" || bVal === 0;

  // If both are null, they're equal
  if (aIsNull && bIsNull) return 0;

  // If only one is null, null goes to end
  if (aIsNull) return 1;
  if (bIsNull) return -1;

  // Both have values, parse and compare
  const numA = parseFloat(aVal);
  const numB = parseFloat(bVal);

  // Check if parsing failed
  if (isNaN(numA) && isNaN(numB)) return 0;
  if (isNaN(numA)) return 1;
  if (isNaN(numB)) return -1;

  return direction === "asc" ? numA - numB : numB - numA;
}

/**
 * Get short company name (first 20 characters)
 * @param {string} companyName - Full company name
 * @returns {string} Shortened company name
 */
function getShortCompanyName(companyName) {
  if (!companyName) return "-";
  return companyName.length > 20
    ? companyName.substring(0, 20) + "..."
    : companyName;
}

/**
 * Get size class for CSS styling
 * @param {string} size - Company size (Large, Mid, Small)
 * @returns {string} CSS class name
 */
function getSizeClass(size) {
  if (!size) return "size-unknown";
  return "size-" + size.toLowerCase();
}

/**
 * Get CSS class for price change styling
 * @param {number} changePercent - Price change percentage
 * @returns {string} CSS class name
 */
function getPriceChangeClass(changePercent) {
  if (!changePercent || isNaN(changePercent)) return "price-neutral";
  const num = parseFloat(changePercent);
  if (isNaN(num)) return "price-neutral";
  return num > 0 ? "price-up" : num < 0 ? "price-down" : "price-neutral";
}

/**
 * Get CSS class for surprise styling
 * @param {number} actual - Actual value
 * @param {number} estimate - Estimated value
 * @returns {string} CSS class name
 */
function getSurpriseClass(actual, estimate) {
  const surprise = calculateSurprise(actual, estimate);
  if (isNaN(surprise)) return "price-neutral";
  return surprise > 0
    ? "price-up"
    : surprise < 0
    ? "price-down"
    : "price-neutral";
}

/**
 * Get CSS class for market cap difference styling
 * @param {number} diff - Market cap difference
 * @returns {string} CSS class name
 */
function getDiffClass(diff) {
  if (!diff || isNaN(diff)) return "price-neutral";
  const num = parseFloat(diff);
  if (isNaN(num)) return "price-neutral";
  return num > 0 ? "price-up" : num < 0 ? "price-down" : "price-neutral";
}

/**
 * Format currency values
 * @param {number} value - Value to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(value) {
  if (!value || isNaN(value)) return "-";
  const num = parseFloat(value);
  if (isNaN(num)) return "-";
  if (num >= 1e12) return "$" + (num / 1e12).toFixed(1) + "T";
  if (num >= 1e9) return "$" + (num / 1e9).toFixed(1) + "B";
  if (num >= 1e6) return "$" + (num / 1e6).toFixed(1) + "M";
  if (num >= 1e3) return "$" + (num / 1e3).toFixed(1) + "K";
  return "$" + num.toFixed(0);
}

/**
 * Format price values
 * @param {number} price - Price to format
 * @returns {string} Formatted price string
 */
function formatPrice(price) {
  if (!price || isNaN(price)) return "-";
  return "$" + parseFloat(price).toFixed(2);
}

/**
 * Format EPS values
 * @param {number} eps - EPS value to format
 * @returns {string} Formatted EPS string
 */
function formatEPS(eps) {
  if (!eps || isNaN(eps)) return "-";
  return parseFloat(eps).toFixed(2);
}

/**
 * Format price change percentage
 * @param {number} changePercent - Price change percentage
 * @returns {string} Formatted change string
 */
function formatPriceChange(changePercent) {
  if (!changePercent || isNaN(changePercent)) return "-";
  const num = parseFloat(changePercent);
  if (isNaN(num)) return "-";
  const sign = num > 0 ? "+" : "";
  return sign + num.toFixed(2) + "%";
}

/**
 * Format market cap difference
 * @param {number} diff - Market cap difference
 * @returns {string} Formatted difference string
 */
function formatMarketCapDiff(diff) {
  if (!diff || isNaN(diff)) return "-";
  const num = parseFloat(diff);
  if (isNaN(num)) return "-";

  // Použijeme absolútnu hodnotu pre formátovanie
  const absNum = Math.abs(num);
  let formattedValue;

  if (absNum >= 1e12) {
    formattedValue = "$" + (absNum / 1e12).toFixed(1) + "T";
  } else if (absNum >= 1e9) {
    formattedValue = "$" + (absNum / 1e9).toFixed(1) + "B";
  } else if (absNum >= 1e6) {
    formattedValue = "$" + (absNum / 1e6).toFixed(1) + "M";
  } else if (absNum >= 1e3) {
    formattedValue = "$" + (absNum / 1e3).toFixed(1) + "K";
  } else {
    formattedValue = "$" + absNum.toFixed(0);
  }

  // Pridáme znamienko na začiatok
  const sign = num > 0 ? "+" : num < 0 ? "-" : "";
  return sign + formattedValue;
}

/**
 * Format surprise percentage
 * @param {number} actual - Actual value
 * @param {number} estimate - Estimated value
 * @returns {string} Formatted surprise string
 */
function formatSurprise(actual, estimate) {
  const surprise = calculateSurprise(actual, estimate);
  if (isNaN(surprise)) return "-";
  const sign = surprise > 0 ? "+" : "";
  return sign + surprise.toFixed(1) + "%";
}
