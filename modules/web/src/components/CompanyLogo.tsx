/**
 * Enhanced Logo Component with Better Error Handling
 * 
 * Features:
 * - Automatic fallback to initials
 * - Better error handling
 * - Loading states
 * - Responsive sizing
 * - Dark mode support
 */

interface LogoProps {
  symbol: string;
  logoUrl?: string | null;
  name?: string;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

interface LogoState {
  imageError: boolean;
  isLoading: boolean;
}

function LogoFallback({ symbol, size = 'md' }: { symbol: string; size?: 'sm' | 'md' | 'lg' }) {
  const sizeClasses = {
    sm: 'w-8 h-8 text-xs',
    md: 'w-12 h-12 sm:w-14 lg:w-16 sm:h-14 lg:h-16 text-xs sm:text-sm',
    lg: 'w-16 h-16 sm:w-20 lg:w-24 sm:h-20 lg:h-24 text-sm sm:text-base'
  };
  
  return (
    <div className={`${sizeClasses[size]} rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-neutral-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold shadow-sm`}>
      {symbol}
    </div>
  );
}

function CompanyLogo({ symbol, logoUrl, name, size = 'md', className = '' }: LogoProps) {
  const [state, setState] = React.useState<LogoState>({
    imageError: false,
    isLoading: true
  });
  
  const sizeClasses = {
    sm: 'w-8 h-8',
    md: 'w-12 h-12 sm:w-14 lg:w-16 sm:h-14 lg:h-16',
    lg: 'w-16 h-16 sm:w-20 lg:w-24 sm:h-20 lg:h-24'
  };
  
  const handleImageError = () => {
    setState(prev => ({ ...prev, imageError: true, isLoading: false }));
  };
  
  const handleImageLoad = () => {
    setState(prev => ({ ...prev, isLoading: false }));
  };
  
  // Show fallback if no logo URL or image failed to load
  if (!logoUrl || state.imageError) {
    return <LogoFallback symbol={symbol} size={size} />;
  }
  
  return (
    <div className={`flex-shrink-0 relative ${className}`}>
      {/* Loading placeholder */}
      {state.isLoading && (
        <div className={`${sizeClasses[size]} rounded-lg sm:rounded-xl bg-gray-200 dark:bg-gray-600 animate-pulse`} />
      )}
      
      {/* Logo image */}
      <img 
        src={logoUrl} 
        alt={`${name || symbol} logo`}
        className={`${sizeClasses[size]} object-contain ${state.isLoading ? 'opacity-0 absolute' : 'opacity-100'} transition-opacity duration-200`}
        onError={handleImageError}
        onLoad={handleImageLoad}
        loading="lazy"
      />
    </div>
  );
}

// Export for use in templates
export { CompanyLogo, LogoFallback };

// For use in HTML templates (vanilla JS)
if (typeof window !== 'undefined') {
  (window as any).CompanyLogo = CompanyLogo;
  (window as any).LogoFallback = LogoFallback;
}
