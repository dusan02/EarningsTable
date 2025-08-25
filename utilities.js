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
    if (actual === null || estimate === null || actual === undefined || estimate === undefined) return NaN;
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
    const numA = parseFloat(a[field]) || null;
    const numB = parseFloat(b[field]) || null;
    
    if (numA === null && numB === null) return 0;
    if (numA === null) return 1;      // null values go to end
    if (numB === null) return -1;     // null values go to end
    
    return direction === 'asc' ? numA - numB : numB - numA;
}

/**
 * Get short company name (first 20 characters)
 * @param {string} companyName - Full company name
 * @returns {string} Shortened company name
 */
function getShortCompanyName(companyName) {
    if (!companyName) return '-';
    return companyName.length > 20 ? companyName.substring(0, 20) + '...' : companyName;
}

/**
 * Get size class for CSS styling
 * @param {string} size - Company size (Large, Mid, Small)
 * @returns {string} CSS class name
 */
function getSizeClass(size) {
    if (!size) return 'size-unknown';
    return 'size-' + size.toLowerCase();
}

/**
 * Get CSS class for price change styling
 * @param {number} changePercent - Price change percentage
 * @returns {string} CSS class name
 */
function getPriceChangeClass(changePercent) {
    if (!changePercent || isNaN(changePercent)) return '';
    return changePercent > 0 ? 'positive-change' : changePercent < 0 ? 'negative-change' : '';
}

/**
 * Get CSS class for surprise styling
 * @param {number} actual - Actual value
 * @param {number} estimate - Estimated value
 * @returns {string} CSS class name
 */
function getSurpriseClass(actual, estimate) {
    const surprise = calculateSurprise(actual, estimate);
    if (isNaN(surprise)) return '';
    return surprise > 0 ? 'positive-surprise' : surprise < 0 ? 'negative-surprise' : '';
}

/**
 * Get CSS class for market cap difference styling
 * @param {number} diff - Market cap difference
 * @returns {string} CSS class name
 */
function getDiffClass(diff) {
    if (!diff || isNaN(diff)) return '';
    return diff > 0 ? 'positive-diff' : diff < 0 ? 'negative-diff' : '';
}

/**
 * Format currency values
 * @param {number} value - Value to format
 * @returns {string} Formatted currency string
 */
function formatCurrency(value) {
    if (!value || isNaN(value)) return '-';
    if (value >= 1e12) return '$' + (value / 1e12).toFixed(1) + 'T';
    if (value >= 1e9) return '$' + (value / 1e9).toFixed(1) + 'B';
    if (value >= 1e6) return '$' + (value / 1e6).toFixed(1) + 'M';
    if (value >= 1e3) return '$' + (value / 1e3).toFixed(1) + 'K';
    return '$' + value.toFixed(0);
}

/**
 * Format price values
 * @param {number} price - Price to format
 * @returns {string} Formatted price string
 */
function formatPrice(price) {
    if (!price || isNaN(price)) return '-';
    return '$' + parseFloat(price).toFixed(2);
}

/**
 * Format EPS values
 * @param {number} eps - EPS value to format
 * @returns {string} Formatted EPS string
 */
function formatEPS(eps) {
    if (!eps || isNaN(eps)) return '-';
    return parseFloat(eps).toFixed(2);
}

/**
 * Format price change percentage
 * @param {number} changePercent - Price change percentage
 * @returns {string} Formatted change string
 */
function formatPriceChange(changePercent) {
    if (!changePercent || isNaN(changePercent)) return '-';
    const sign = changePercent > 0 ? '+' : '';
    return sign + changePercent.toFixed(2) + '%';
}

/**
 * Format market cap difference
 * @param {number} diff - Market cap difference
 * @returns {string} Formatted difference string
 */
function formatMarketCapDiff(diff) {
    if (!diff || isNaN(diff)) return '-';
    const sign = diff > 0 ? '+' : '';
    return sign + formatCurrency(diff);
}

/**
 * Format surprise percentage
 * @param {number} actual - Actual value
 * @param {number} estimate - Estimated value
 * @returns {string} Formatted surprise string
 */
function formatSurprise(actual, estimate) {
    const surprise = calculateSurprise(actual, estimate);
    if (isNaN(surprise)) return '-';
    const sign = surprise > 0 ? '+' : '';
    return sign + surprise.toFixed(1) + '%';
}
