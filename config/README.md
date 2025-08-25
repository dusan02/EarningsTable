# Configuration System Documentation

## Overview

The EarningsTable application now uses a unified configuration system that provides:

- **Environment-based configuration** (development, production, testing)
- **Backward compatibility** with existing constants
- **Centralized configuration management**
- **Validation and error handling**

## File Structure

```
config/
├── AppConfig.php              # Main configuration class (Singleton)
├── config_unified.php         # Unified config with backward compatibility
├── env_loader.php             # Environment variable loader
├── env.example                # Example environment file
├── environments/
│   ├── development.php        # Development environment overrides
│   └── production.php         # Production environment overrides
└── README.md                  # This file
```

## Usage

### Basic Usage

```php
// Include the unified configuration
require_once __DIR__ . '/config/config_unified.php';

// Access configuration via AppConfig
$config = AppConfig::getInstance();

// Get specific values
$dbHost = $config->get('database.host');
$apiKey = $config->get('api.finnhub.key');
$isDebug = $config->isDebug();
```

### Backward Compatibility

All existing constants are still available:

```php
// These still work
echo DB_HOST;
echo FINNHUB_API_KEY;
echo DEBUG_MODE;
```

### Environment-Specific Configuration

The system automatically loads environment-specific settings:

- **Development**: Debug mode, detailed logging, local database
- **Production**: Optimized settings, security headers, production database
- **Testing**: Test database, debug mode enabled

## Configuration Sections

### Database
```php
$config->getDatabase(); // Returns all database settings
$config->get('database.host');
$config->get('database.name');
```

### API
```php
$config->getApi(); // Returns all API settings
$config->get('api.finnhub.key');
$config->get('api.polygon.base_url');
```

### Application
```php
$config->getApp(); // Returns all app settings
$config->get('app.timezone');
$config->get('app.debug');
```

### Rate Limiting
```php
$config->getRateLimiting(); // Returns rate limiting settings
$config->get('rate_limiting.delay');
```

### Logging
```php
$config->getLogging(); // Returns logging settings
$config->get('logging.level');
```

## Environment Setup

### 1. Copy Environment File
```bash
cp config/env.example .env
```

### 2. Configure Environment Variables
Edit `.env` file with your settings:

```env
# Application Environment
APP_ENV=development
APP_DEBUG=true

# Database
DB_HOST=localhost
DB_NAME=earnings_table_dev
DB_USER=root
DB_PASS=

# API Keys
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
```

### 3. Environment-Specific Overrides

For development, you can also include environment-specific files:

```php
// In your entry point
if (file_exists(__DIR__ . '/config/environments/development.php')) {
    require_once __DIR__ . '/config/environments/development.php';
}
```

## Migration from Old System

### Old Way
```php
require_once 'config.php';
// or
require_once 'config/config.php';
// or
require_once 'config/development.php';
```

### New Way
```php
require_once 'config/config_unified.php';
// or simply
require_once 'config.php'; // Now uses unified system
```

## Benefits

1. **Single Source of Truth**: All configuration in one place
2. **Environment Management**: Easy switching between environments
3. **Validation**: Automatic validation of required settings
4. **Type Safety**: Strongly typed configuration access
5. **Backward Compatibility**: Existing code continues to work
6. **Error Handling**: Centralized error handling and logging

## Error Handling

The system provides comprehensive error handling:

- **Missing API keys**: Logged and handled gracefully
- **Database connection failures**: Proper error messages
- **Invalid configuration**: Validation with helpful error messages
- **Environment-specific errors**: Different handling per environment

## Security

- **API keys**: Never hardcoded, always from environment
- **Database credentials**: Secure handling
- **Production settings**: Security headers and error suppression
- **Development settings**: Detailed error reporting for debugging
