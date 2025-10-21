# Earnings Table - Modern Financial Data Display

A modern, responsive web application for displaying financial earnings data in a clean, professional table format.

## 🚀 Features

- **Modern Design**: Clean, professional interface with dark/light theme toggle
- **Responsive Layout**: Works perfectly on desktop, tablet, and mobile devices
- **Interactive Table**: Sortable columns with visual indicators
- **Search & Filter**: Real-time search across company names and tickers
- **Color-coded Data**: Visual indicators for positive/negative changes
- **Smooth Animations**: Hover effects and transitions for better UX

## 📊 Table Structure

### Company Column

- **Bold ticker symbol** (e.g., "AAPL")
- **Light gray company name** below (e.g., "Apple Inc.")

### Size Column

- Market cap size categories with color-coded badges:
  - **Mega**: Blue badge
  - **Large**: Green badge
  - **Mid**: Orange badge
  - **Small**: Gray badge

### Market Cap Column

- **Main market cap value** (e.g., "$2.5T")
- **Market cap change** below with color coding

### Price Column

- **Current stock price** (e.g., "$150.25")
- **Percentage change** below with color coding

### EPS Column (3 sub-columns)

- **Actual EPS** value
- **Estimated EPS** value
- **EPS Surprise** percentage (color-coded)

### Revenue Column (3 sub-columns)

- **Actual Revenue** (e.g., "$89.5B")
- **Estimated Revenue**
- **Revenue Surprise** percentage (color-coded)

## 🛠️ Technical Stack

- **React 18** with TypeScript
- **Tailwind CSS** for styling
- **Responsive design** principles
- **Modern web standards**

## 🎨 Design Features

- **Theme Toggle**: Switch between light and dark modes
- **Hover Effects**: Smooth row highlighting on hover
- **Sortable Columns**: Click headers to sort data
- **Search Functionality**: Real-time filtering
- **Loading States**: Smooth transitions and animations
- **Professional Color Palette**: Blues, grays, and whites
- **Excellent Readability**: Proper contrast ratios

## 📱 Responsive Design

- **Desktop**: Full table with all columns visible
- **Tablet**: Horizontal scroll with maintained functionality
- **Mobile**: Optimized layout with touch-friendly interactions

## 🚀 Getting Started

1. **Install dependencies**:

   ```bash
   npm install
   ```

2. **Start development server**:

   ```bash
   npm start
   ```

3. **Build for production**:
   ```bash
   npm run build
   ```

## 📋 Usage

```tsx
import EarningsTable from "./EarningsTable";

const data = [
  {
    symbol: "AAPL",
    name: "Apple Inc.",
    size: "Mega",
    marketCap: 2500000000000,
    marketCapDiff: 50000000000,
    price: 150.25,
    change: 2.15,
    epsActual: 1.52,
    epsEst: 1.43,
    epsSurp: 6.29,
    revActual: 89498000000,
    revEst: 88500000000,
    revSurp: 1.13,
  },
  // ... more data
];

function App() {
  return <EarningsTable data={data} />;
}
```

## 🎯 Key Features

- **Real-time Search**: Filter companies by name or ticker
- **Column Sorting**: Click any column header to sort
- **Theme Switching**: Toggle between light and dark modes
- **Responsive Design**: Works on all screen sizes
- **Professional Styling**: Clean, modern interface
- **Data Formatting**: Proper number formatting and color coding
- **Accessibility**: Keyboard navigation and screen reader support

## 🔧 Customization

The component is fully customizable through props and CSS classes. You can easily modify:

- Color schemes
- Typography
- Spacing and layout
- Animation timing
- Data formatting

## 🚀 Production Deployment

For production deployment, see:

- **[PRODUCTION_MIGRATION_GUIDE.md](PRODUCTION_MIGRATION_GUIDE.md)** - Complete migration guide
- **[PRODUCTION_QUICK_REFERENCE.md](PRODUCTION_QUICK_REFERENCE.md)** - Quick reference
- **Deployment script**: `./deploy-production.sh`
- **PM2 configuration**: `ecosystem.config.js`

## 📄 License

MIT License - feel free to use in your projects!
