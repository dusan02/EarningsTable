# Logo System Documentation

## Overview

The logo system automatically downloads and processes company logos for stock symbols. It integrates with the Finnhub cron job and stores logos in both the database and web server.

## Architecture

### Data Flow

```
Finnhub Cron → Logo Service → Database + File System → Web Server
```

### Components

1. **Logo Service** (`modules/cron/src/core/logoService.ts`)
2. **Database Storage** (FinhubData table)
3. **File Storage** (`modules/web/public/logos/`)
4. **Web Server** (serves logos via HTTP)

## Logo Sources

The system tries multiple sources in order of preference:

1. **Yahoo Finance** (via Clearbit)

   - URL: `https://logo.clearbit.com/finance.yahoo.com/quote/{SYMBOL}`
   - Quality: High, consistent format
   - Success rate: ~30%

2. **Finnhub API**

   - URL: `https://finnhub.io/api/v1/stock/profile2?symbol={SYMBOL}&token={TOKEN}`
   - Quality: High, official company logos
   - Success rate: ~90%

3. **Polygon API**

   - URL: `https://api.polygon.io/v3/reference/tickers/{SYMBOL}?apiKey={KEY}`
   - Quality: Variable, company branding
   - Success rate: ~60%

4. **Clearbit** (fallback)
   - URL: `https://logo.clearbit.com/{DOMAIN}`
   - Quality: Variable, based on company homepage
   - Success rate: ~40%

## Logo Processing

### Image Processing with Sharp

- **Size**: 256x256 pixels
- **Format**: WebP
- **Quality**: 95% (high quality)
- **Effort**: 6 (maximum compression effort)
- **Background**: Fully transparent (no white borders)
- **Fit**: Inside (no padding, clean edges)
- **Enlargement**: Disabled (preserves original quality)

### Why Logos Look Better Now

The improved quality is due to:

- **Higher quality setting**: 95% instead of default 80%
- **Maximum effort**: 6 instead of default 4
- **Fully transparent background**: No white borders or padding
- **Clean fit**: Inside bounds without enlargement
- **Consistent sizing**: 256x256 for all logos
- **Better source selection**: Finnhub provides high-quality official logos

## Database Schema

### FinhubData Table

```sql
logoUrl       String?   -- e.g. "/logos/AAPL.webp"
logoSource    String?   -- "finnhub", "polygon", "yahoo", "clearbit"
logoFetchedAt DateTime? -- When logo was downloaded
```

### FinalReport Table

```sql
logoUrl       String?   -- Copied from FinhubData
logoSource    String?   -- Copied from FinhubData
logoFetchedAt DateTime? -- Copied from FinhubData
```

## File System

### Directory Structure

```
modules/web/public/logos/
├── AAPL.webp
├── MSFT.webp
├── GOOGL.webp
└── ...
```

### File Naming

- Format: `{SYMBOL}.webp`
- Example: `AAPL.webp`, `MSFT.webp`
- Case: Uppercase symbols

## API Endpoints

### Logo Files

- **URL**: `http://localhost:5555/logos/{SYMBOL}.webp`
- **Example**: `http://localhost:5555/logos/AAPL.webp`
- **Response**: WebP image file

### Logo Metadata

- **URL**: `http://localhost:5555/api/final-report`
- **Response**: JSON with logoUrl, logoSource, logoFetchedAt fields

## Usage

### Automatic Processing

Logos are automatically processed during Finnhub cron execution:

```bash
cd modules/cron
npx tsx src/run-once.ts finnhub
```

### Manual Processing

```typescript
import {
  fetchAndStoreLogo,
  processLogosInBatches,
} from "./core/logoService.js";

// Single logo
const result = await fetchAndStoreLogo("AAPL");

// Batch processing
const symbols = ["AAPL", "MSFT", "GOOGL"];
const result = await processLogosInBatches(symbols, 5, 3);
```

## Configuration

### Logo Settings

```typescript
const LOGO_CONFIG = {
  size: 256, // Image size in pixels
  quality: 95, // WebP quality (1-100)
  effort: 6, // Compression effort (1-6)
  background: { r: 0, g: 0, b: 0, alpha: 0 }, // Fully transparent
  fit: 'inside', // No padding, clean edges
  withoutEnlargement: true, // Preserve original quality
  sources: ["yahoo", "finnhub", "polygon", "clearbit"],
};
```

### Batch Processing

```typescript
const BATCH_CONFIG = {
  batchSize: 5, // Symbols per batch
  concurrency: 3, // Concurrent downloads per batch
  timeout: 7000, // Request timeout in ms
};
```

## Error Handling

### Common Issues

1. **404 Errors**: Symbol not found in source
2. **Rate Limiting**: Too many requests to API
3. **Network Timeouts**: Slow or failed connections
4. **Invalid Images**: Corrupted or unsupported formats

### Fallback Strategy

- If one source fails, tries the next
- If all sources fail, logs error and continues
- Database is updated with null values for failed logos

## Performance

### Metrics

- **Success Rate**: ~85% (Finnhub primary source)
- **Processing Time**: ~2-3 seconds per logo
- **File Size**: 1-10KB per logo (WebP compression)
- **Batch Processing**: 5 symbols per batch, 3 concurrent

### Optimization

- **Concurrent Downloads**: Limited to avoid rate limiting
- **Batch Processing**: Reduces memory usage
- **WebP Format**: Better compression than PNG/JPG
- **Caching**: 30-day TTL prevents re-downloading

## Troubleshooting

### Logos Not Appearing

1. Check if logo files exist in `modules/web/public/logos/`
2. Verify database has logoUrl values
3. Check web server is serving static files
4. Verify API response includes logo fields

### Poor Quality Logos

1. Check source quality (Finnhub > Polygon > Yahoo > Clearbit)
2. Verify Sharp processing settings
3. Check original image quality from source

### Missing Logos

1. Check API rate limits
2. Verify network connectivity
3. Check symbol validity
4. Review error logs for specific failures

## Future Improvements

### Potential Enhancements

1. **Logo Caching**: Redis cache for frequently accessed logos
2. **Quality Scoring**: Rate logo quality and prefer better sources
3. **Auto-Refresh**: Periodic logo updates for stale images
4. **Fallback Sources**: Additional logo providers
5. **Logo Validation**: Verify logo quality before saving
6. **CDN Integration**: Serve logos from CDN for better performance

### Monitoring

1. **Success Rates**: Track logo download success by source
2. **Performance Metrics**: Monitor processing times
3. **Quality Metrics**: Track logo quality scores
4. **Error Rates**: Monitor and alert on high failure rates
