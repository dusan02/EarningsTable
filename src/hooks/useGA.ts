import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

/**
 * Track page views in Google Analytics on route changes.
 * The gtag snippet in index.html only fires on initial load; this hook
 * sends a page_view event whenever the URL changes (SPA navigation).
 *
 * Requires the gtag() global and GA measurement ID to be set up in index.html.
 */
const GA_MEASUREMENT_ID = 'G-E6DJ7N6W1L';

export function useGA(): void {
  const location = useLocation();

  useEffect(() => {
    // Guard: gtag may not be available if GA failed to load (ad blockers, etc.)
    if (typeof window.gtag !== 'function') return;

    window.gtag('event', 'page_view', {
      page_path: location.pathname + location.search,
      page_location: window.location.origin + location.pathname + location.search,
      page_title: document.title,
    });
  }, [location.pathname, location.search]);
}

// Augment the Window interface so TypeScript knows about gtag.
declare global {
  interface Window {
    gtag: (...args: any[]) => void;
  }
}
