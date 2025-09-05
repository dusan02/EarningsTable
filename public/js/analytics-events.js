/**
 * Google Analytics Custom Events Tracking
 *
 * Tento súbor obsahuje custom eventy pre EarningsTable
 */

// Custom events pre EarningsTable
const AnalyticsEvents = {
  /**
   * Track page load
   */
  trackPageLoad: function () {
    if (typeof trackEvent !== "undefined") {
      trackEvent("page_load", {
        page_title: document.title,
        page_location: window.location.href,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track earnings data refresh
   */
  trackDataRefresh: function () {
    if (typeof trackEvent !== "undefined") {
      trackEvent("data_refresh", {
        event_category: "earnings_data",
        event_label: "manual_refresh",
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track ticker click
   */
  trackTickerClick: function (ticker) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("ticker_click", {
        event_category: "user_interaction",
        event_label: ticker,
        ticker: ticker,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track filter usage
   */
  trackFilterUsage: function (filterType, filterValue) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("filter_used", {
        event_category: "user_interaction",
        event_label: filterType,
        filter_type: filterType,
        filter_value: filterValue,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track search usage
   */
  trackSearch: function (searchTerm) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("search_performed", {
        event_category: "user_interaction",
        event_label: "search",
        search_term: searchTerm,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track view toggle (EPS & Revenue vs Guidance)
   */
  trackViewToggle: function (viewType) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("view_toggle", {
        event_category: "user_interaction",
        event_label: viewType,
        view_type: viewType,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track API error
   */
  trackApiError: function (apiName, errorType) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("api_error", {
        event_category: "error",
        event_label: apiName,
        api_name: apiName,
        error_type: errorType,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track earnings beat/miss
   */
  trackEarningsBeat: function (ticker, beatType, percentage) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("earnings_beat", {
        event_category: "earnings_data",
        event_label: beatType,
        ticker: ticker,
        beat_type: beatType,
        percentage: percentage,
        timestamp: new Date().toISOString(),
      });
    }
  },

  /**
   * Track market cap change
   */
  trackMarketCapChange: function (ticker, changeType, changeValue) {
    if (typeof trackEvent !== "undefined") {
      trackEvent("market_cap_change", {
        event_category: "earnings_data",
        event_label: changeType,
        ticker: ticker,
        change_type: changeType,
        change_value: changeValue,
        timestamp: new Date().toISOString(),
      });
    }
  },
};

// Automatické tracking pri načítaní stránky
document.addEventListener("DOMContentLoaded", function () {
  AnalyticsEvents.trackPageLoad();
});

// Export pre použitie v iných súboroch
if (typeof module !== "undefined" && module.exports) {
  module.exports = AnalyticsEvents;
}
