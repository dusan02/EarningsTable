# 📊 Market Sentiment Dashboard - Kompletný Plán

## 🎯 Prehľad

Market Sentiment Dashboard bude kombinovať **viacero zdrojov dát** na vytvorenie komplexného sentiment skóre pre jednotlivé akcie aj celý trh. Systém bude automaticky agregovať dáta z rôznych zdrojov a vytvárať jednotné sentiment skóre.

---

## 📡 Zdroje Dát pre Sentiment

### 1. **Finnhub API** (už máte API key)

- ✅ **Company News Sentiment**: `/v2/news-sentiment`
  - Sentiment skóre pre každú spoločnosť
  - Buzz score (aktívnosť v médiách)
  - Bullish/Bearish percentage
- ✅ **Stock News**: `/v2/company-news`
  - Najnovšie správy pre akciu
  - Môžeme analyzovať nadpisy/keywords

### 2. **Polygon API** (už máte API key)

- ✅ **Options Flow**: `/v3/snapshot/options/{underlying}`
  - Put/Call ratio
  - Unusual options activity
  - Open interest changes
- ✅ **Trades**: `/v3/trades/{ticker}`
  - Large block trades
  - Dark pool prints

### 3. **Alpha Vantage API** (FREE tier dostupné)

- ✅ **News & Sentiment**: `/query?function=NEWS_SENTIMENT`
  - Pre každú akciu
  - Sentiment skóre z článkov
  - Relevance score

### 4. **Reddit API** (FREE - public API)

- ✅ **r/wallstreetbets**: Mentions, upvotes, comments
- ✅ **r/stocks**: Dizkusie o akciách
- ✅ **Keyword tracking**: Počet mentionov symbolu

### 5. **Twitter/X API** (možno použiť free tier alebo scraping)

- ✅ **Mentions tracking**: Počet tweetov s $SYMBOL
- ✅ **Hashtag analysis**: Trending hashtags

### 6. **Fear & Greed Index** (CNN)

- ✅ **Market-wide sentiment**: Web scraping alebo API
- ✅ **Historical data**: Graf vývoja

---

## 🏗️ Architektúra Systému

### **Database Schema** (nová tabuľka)

```prisma
model SentimentData {
  id               Int       @id @default(autoincrement())
  symbol           String

  // Agregované sentiment skóre (výsledok kombinácie)
  overallSentiment Float     // -100 až +100 (bearish až bullish)
  sentimentScore   Float     // 0-100 (neutral až extreme)

  // Individuálne zdroje
  finnhubSentiment Float?    // Finnhub sentiment
  finnhubBuzz      Float?    // Buzz score
  finnhubBullish   Float?    // Bullish %
  finnhubBearish   Float?    // Bearish %

  alphaVantageSentiment Float? // Alpha Vantage score
  alphaVantageRelevance Float? // Relevance score

  redditMentions   Int?      // Počet mentionov na Reddit
  redditUpvotes    Int?      // Celkové upvotes
  redditSentiment   Float?    // Vypočítaný sentiment z Reddit

  twitterMentions  Int?      // Počet tweetov
  twitterSentiment Float?    // Vypočítaný sentiment

  optionsFlow      Float?    // Put/Call ratio (normalized)
  unusualActivity  Boolean?  // Unusual options activity

  // Metadata
  lastUpdated      DateTime  @default(now())
  dataSources      Json?     // Array of sources that contributed
  confidence       Float?    // Confidence score (0-100)

  createdAt        DateTime  @default(now())
  updatedAt        DateTime  @updatedAt

  @@unique([symbol])
  @@index([symbol])
  @@index([overallSentiment])
  @@index([lastUpdated])
  @@map("sentiment_data")
}

model SentimentHistory {
  id               Int       @id @default(autoincrement())
  symbol           String
  overallSentiment Float
  timestamp        DateTime  @default(now())

  @@index([symbol, timestamp])
  @@map("sentiment_history")
}

model MarketSentiment {
  id               Int       @id @default(autoincrement())
  fearGreedIndex   Int?      // 0-100 (Fear až Greed)
  marketSentiment  Float?    // Agregovaný sentiment z top akcií

  // Breakdown podľa sektorov
  techSentiment    Float?
  financeSentiment Float?
  healthSentiment  Float?
  // ... ďalšie sektory

  timestamp        DateTime  @default(now())

  @@index([timestamp])
  @@map("market_sentiment")
}
```

---

## 🧮 Algoritmus Kombinovania Sentimentu

### **Vážený Průměr s Confidence Scoring**

```typescript
function calculateOverallSentiment(sources: SentimentSource[]): {
  overallSentiment: number; // -100 až +100
  sentimentScore: number; // 0-100
  confidence: number; // 0-100
} {
  // Váhy jednotlivých zdrojov
  const weights = {
    finnhub: 0.3, // Dôveryhodný zdroj
    alphaVantage: 0.25, // Dôveryhodný zdroj
    reddit: 0.2, // Social sentiment
    twitter: 0.15, // Social sentiment
    optionsFlow: 0.1, // Institutional sentiment
  };

  let weightedSum = 0;
  let totalWeight = 0;
  let confidenceSum = 0;

  // Normalizácia jednotlivých skóre na -100 až +100
  sources.forEach((source) => {
    if (source.value !== null && source.confidence > 0) {
      const normalized = normalizeSentiment(source.value, source.type);
      weightedSum += normalized * weights[source.type] * source.confidence;
      totalWeight += weights[source.type] * source.confidence;
      confidenceSum += source.confidence;
    }
  });

  const overallSentiment = totalWeight > 0 ? weightedSum / totalWeight : 0;
  const sentimentScore = Math.abs(overallSentiment); // 0-100
  const confidence = confidenceSum / sources.length;

  return { overallSentiment, sentimentScore, confidence };
}

function normalizeSentiment(value: number, type: string): number {
  switch (type) {
    case "finnhub":
      // Finnhub: -1 až +1 -> -100 až +100
      return value * 100;
    case "alphaVantage":
      // Alpha Vantage: -1 až +1 -> -100 až +100
      return value * 100;
    case "reddit":
      // Reddit: upvotes/downvotes ratio -> -100 až +100
      return normalizeRatio(value);
    case "twitter":
      // Twitter: mentions sentiment -> -100 až +100
      return value;
    case "optionsFlow":
      // Put/Call ratio: >1 = bearish, <1 = bullish
      return (1 - value) * 100; // Invertovaný
    default:
      return value;
  }
}
```

---

## 🔄 Data Fetching Strategy

### **Cron Jobs** (podobne ako existujúce)

```typescript
// modules/cron/src/jobs/sentiment.ts

// 1. Finnhub Sentiment (každú hodinu)
async function fetchFinnhubSentiment(symbols: string[]) {
  for (const symbol of symbols) {
    const data = await axios.get(
      `https://finnhub.io/api/v2/news-sentiment?symbol=${symbol}&token=${FINNHUB_TOKEN}`
    );

    // Uložiť do DB
    await prisma.sentimentData.upsert({
      where: { symbol },
      update: {
        finnhubSentiment: data.redditScore,
        finnhubBuzz: data.buzzScore,
        finnhubBullish: data.bullishPercent,
        finnhubBearish: data.bearishPercent,
      },
      create: { symbol, ... }
    });
  }
}

// 2. Alpha Vantage (každé 2 hodiny - rate limit)
async function fetchAlphaVantageSentiment(symbols: string[]) {
  // Alpha Vantage má free tier: 5 API calls/min
  // Takže batched s delay
}

// 3. Reddit (každé 4 hodiny)
async function fetchRedditSentiment(symbols: string[]) {
  // Reddit API - search mentions
  // Analyzovať sentiment z komentárov
}

// 4. Options Flow (každú hodinu)
async function fetchOptionsFlow(symbols: string[]) {
  // Polygon API - options data
}

// 5. Recalculate Overall Sentiment (po každom update)
async function recalculateSentiment(symbols: string[]) {
  for (const symbol of symbols) {
    const data = await prisma.sentimentData.findUnique({
      where: { symbol }
    });

    const overall = calculateOverallSentiment(data);

    await prisma.sentimentData.update({
      where: { symbol },
      data: {
        overallSentiment: overall.overallSentiment,
        sentimentScore: overall.sentimentScore,
        confidence: overall.confidence
      }
    });

    // Uložiť do histórie
    await prisma.sentimentHistory.create({
      data: {
        symbol,
        overallSentiment: overall.overallSentiment,
        timestamp: new Date()
      }
    });
  }
}
```

---

## 📊 Frontend Dashboard

### **Komponenty:**

1. **Market Overview Card**

   - Fear & Greed Index
   - Market-wide sentiment gauge
   - Sector breakdown

2. **Individual Stock Sentiment**

   - Sentiment score gauge (-100 až +100)
   - Confidence indicator
   - Breakdown podľa zdrojov
   - Historický graf

3. **Sentiment Table** (podobné ako EarningsTable)

   - Symbol, Name
   - Overall Sentiment
   - Finnhub, Reddit, Options indicators
   - Trend (↑↓)
   - Link na detail

4. **Detail Page**
   - Kompletný sentiment breakdown
   - Historický graf
   - Recent news
   - Reddit mentions
   - Options activity

---

## 🔌 API Endpoints

```typescript
// api-routes.ts

GET /api/sentiment/:symbol
GET /api/sentiment/:symbol/history
GET /api/sentiment/market-overview
GET /api/sentiment/top-bullish
GET /api/sentiment/top-bearish
GET /api/sentiment/search?q=AAPL
```

---

## 🚀 Implementačný Plán

### **Fáza 1: Základná infraštruktúra**

1. ✅ Database schema (Prisma migration)
2. ✅ Finnhub sentiment fetching
3. ✅ Základný API endpoint
4. ✅ Frontend card pre sentiment

### **Fáza 2: Viacero zdrojov**

5. ✅ Alpha Vantage sentiment
6. ✅ Reddit mentions tracking
7. ✅ Options flow analysis
8. ✅ Kombinovaný algoritmus

### **Fáza 3: Dashboard**

9. ✅ Sentiment table
10. ✅ Detail page
11. ✅ Historické grafy
12. ✅ Market overview

### **Fáza 4: Advanced features**

13. ✅ Real-time updates (WebSocket)
14. ✅ Alerts (email/push)
15. ✅ Sentiment predictions
16. ✅ Backtesting

---

## 💰 Cost Estimation

### **Free Tier APIs:**

- ✅ Finnhub: Už máte
- ✅ Polygon: Už máte
- ✅ Alpha Vantage: 5 calls/min (free)
- ✅ Reddit API: Unlimited (public)
- ✅ Twitter: Free tier alebo scraping

### **Možné rozšírenia:**

- NewsAPI.org: News sentiment ($449/mo)
- Google News API: News scraping
- Sentiment AI: Pokročilá analýza ($99/mo)

---

## 🎯 Odpovede na Vaše Otázky

### **1. Ako by mohla fungovať?**

- Automatické fetchovanie dát z viacerých zdrojov každú hodinu
- Kombinovanie pomocou váženého algoritmu
- Ukladanie histórie pre tracking
- Dashboard pre vizualizáciu

### **2. Odkiaľ dáta?**

- **Finnhub** (už máte) - news sentiment
- **Alpha Vantage** (free) - news sentiment
- **Reddit API** (free) - social sentiment
- **Twitter** (free tier) - social mentions
- **Polygon** (už máte) - options flow

### **3. Dá sa urobiť pre jednotlivé akcie?**

**ÁNO!** To je hlavná funkcia:

- Každá akcia má vlastné sentiment skóre
- Kombinácia viacerých zdrojov pre každú akciu
- Historický tracking
- Porovnanie medzi akciami

### **4. Kombinovaním rôznych zdrojov?**

**ÁNO!** Kombinujeme:

- **Finnhub** (30% váha) - profesionálny zdroj
- **Alpha Vantage** (25% váha) - profesionálny zdroj
- **Reddit** (20% váha) - retail sentiment
- **Twitter** (15% váha) - social sentiment
- **Options Flow** (10% váha) - institutional sentiment

**Výsledok:** Jednotné skóre -100 až +100 s confidence score

---

## 📝 Príklad Výstupu

```json
{
  "symbol": "AAPL",
  "overallSentiment": 65.5, // Bullish
  "sentimentScore": 65.5, // 0-100
  "confidence": 82.3, // Vysoká dôvera
  "sources": {
    "finnhub": { "sentiment": 0.7, "buzz": 0.85 },
    "alphaVantage": { "sentiment": 0.65, "relevance": 0.9 },
    "reddit": { "mentions": 1523, "sentiment": 0.6 },
    "twitter": { "mentions": 5821, "sentiment": 0.68 },
    "optionsFlow": { "putCallRatio": 0.7, "unusual": false }
  },
  "trend": "↑", // Zlepšuje sa
  "lastUpdated": "2025-01-20T10:30:00Z"
}
```

---

## ✅ Ďalšie Kroky

Chcete začať s implementáciou? Môžem:

1. Vytvoriť database schema
2. Implementovať Finnhub sentiment fetching
3. Vytvoriť základný API endpoint
4. Vytvoriť frontend komponentu

Ktoré API chcete použiť ako prvé? Odporúčam začať s **Finnhub** (už máte) a **Alpha Vantage** (free).

