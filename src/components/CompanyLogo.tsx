import React, { useEffect, useState } from 'react';

interface CompanyLogoProps {
  symbol: string;
  logoUrl: string | null;
  name: string | null;
}

/**
 * Company logo with a gradient initials fallback. Resets the error state when
 * the URL changes so a previously-failed image doesn't stay in fallback after
 * a valid URL arrives (H7).
 */
const CompanyLogo: React.FC<CompanyLogoProps> = ({ symbol, logoUrl, name }) => {
  const [imgError, setImgError] = useState(false);
  useEffect(() => { setImgError(false); }, [logoUrl]);

  if (logoUrl && !imgError) {
    return (
      <img
        src={logoUrl}
        alt={`${symbol} logo`}
        className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg object-contain bg-white dark:bg-slate-100 border border-neutral-200 dark:border-slate-700"
        onError={() => setImgError(true)}
        loading="lazy"
      />
    );
  }
  return (
    <div
      className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs sm:text-sm shadow-sm"
      aria-label={name ? `${symbol} — ${name}` : symbol}
      role="img"
    >
      {symbol?.slice(0, 3)}
    </div>
  );
};

export default CompanyLogo;
