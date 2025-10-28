/**
 * Enhanced Logo Component for HTML Templates
 *
 * Features:
 * - Automatic fallback to initials
 * - Better error handling
 * - Loading states
 * - Responsive sizing
 * - Dark mode support
 */

class LogoComponent {
  constructor(options = {}) {
    this.options = {
      size: "md",
      className: "",
      ...options,
    };
  }

  createFallback(symbol, size = "md") {
    const sizeClasses = {
      sm: "w-8 h-8 text-xs",
      md: "w-12 h-12 sm:w-14 lg:w-16 sm:h-14 lg:h-16 text-xs sm:text-sm",
      lg: "w-16 h-16 sm:w-20 lg:w-24 sm:h-20 lg:h-24 text-sm sm:text-base",
    };

    return `
      <div class="${sizeClasses[size]} rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-neutral-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold shadow-sm">
        ${symbol}
      </div>
    `;
  }

  createLogo(symbol, logoUrl, name = "", size = "md", className = "") {
    const sizeClasses = {
      sm: "w-8 h-8",
      md: "w-12 h-12 sm:w-14 lg:w-16 sm:h-14 lg:h-16",
      lg: "w-16 h-16 sm:w-20 lg:w-24 sm:h-20 lg:h-24",
    };

    if (!logoUrl) {
      return this.createFallback(symbol, size);
    }

    return `
      <div class="flex-shrink-0 relative ${className}">
        <img 
          src="${logoUrl}" 
          alt="${name || symbol} logo"
          class="${
            sizeClasses[size]
          } object-contain transition-opacity duration-200"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
          loading="lazy"
        />
        <div class="${
          sizeClasses[size]
        } rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-neutral-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold text-xs sm:text-sm shadow-sm hidden">
          ${symbol}
        </div>
      </div>
    `;
  }

  // Enhanced version with better error handling
  createEnhancedLogo(symbol, logoUrl, name = "", size = "md", className = "") {
    const sizeClasses = {
      sm: "w-8 h-8",
      md: "w-12 h-12 sm:w-14 lg:w-16 sm:h-14 lg:h-16",
      lg: "w-16 h-16 sm:w-20 lg:w-24 sm:h-20 lg:h-24",
    };

    if (!logoUrl) {
      return this.createFallback(symbol, size);
    }

    // Generate unique ID for this logo instance
    const logoId = `logo-${symbol}-${Date.now()}-${Math.random()
      .toString(36)
      .substr(2, 9)}`;

    return `
      <div class="flex-shrink-0 relative ${className}" id="${logoId}">
        <!-- Loading placeholder -->
        <div class="${
          sizeClasses[size]
        } rounded-lg sm:rounded-xl bg-gray-200 dark:bg-gray-600 animate-pulse logo-loading"></div>
        
        <!-- Logo image -->
        <img 
          src="${logoUrl}" 
          alt="${name || symbol} logo"
          class="${
            sizeClasses[size]
          } object-contain opacity-0 transition-opacity duration-200 logo-image"
          onload="this.style.opacity='1'; this.previousElementSibling.style.display='none';"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
          loading="lazy"
        />
        
        <!-- Fallback -->
        <div class="${
          sizeClasses[size]
        } rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-neutral-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold text-xs sm:text-sm shadow-sm hidden logo-fallback">
          ${symbol}
        </div>
      </div>
    `;
  }
}

// Create global instance
const logoComponent = new LogoComponent();

// Export functions for use in templates
function createCompanyLogo(
  symbol,
  logoUrl,
  name = "",
  size = "md",
  className = ""
) {
  return logoComponent.createLogo(symbol, logoUrl, name, size, className);
}

function createEnhancedCompanyLogo(
  symbol,
  logoUrl,
  name = "",
  size = "md",
  className = ""
) {
  return logoComponent.createEnhancedLogo(
    symbol,
    logoUrl,
    name,
    size,
    className
  );
}

function createLogoFallback(symbol, size = "md") {
  return logoComponent.createFallback(symbol, size);
}

// Make available globally
if (typeof window !== "undefined") {
  window.LogoComponent = LogoComponent;
  window.createCompanyLogo = createCompanyLogo;
  window.createEnhancedCompanyLogo = createEnhancedCompanyLogo;
  window.createLogoFallback = createLogoFallback;
}

// Export for Node.js
if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    LogoComponent,
    createCompanyLogo,
    createEnhancedCompanyLogo,
    createLogoFallback,
  };
}
