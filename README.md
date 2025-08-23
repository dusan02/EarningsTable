# Earnings Table - Real-Time Stock Earnings Dashboard

A comprehensive real-time dashboard for tracking stock earnings, EPS beats/misses, revenue surprises, and market cap changes.

## 🚀 Features

- **Real-time Earnings Data**: Live tracking of today's earnings reports
- **Market Cap Analysis**: Categorization by Large, Mid, and Small cap companies
- **EPS & Revenue Tracking**: Beat/miss analysis with surprise percentages
- **Price Movement Monitoring**: Real-time stock price changes
- **Interactive Dashboard**: Sortable tables and search functionality
- **Responsive Design**: Works on desktop and mobile devices

## 📊 Dashboard Components

### KPI Cards
- **Size Distribution**: Large, Mid, Small cap counts and total market cap
- **Winners**: Best performing stocks by price change, market cap diff, EPS beat, revenue beat
- **Losers**: Worst performing stocks by price change, market cap diff, EPS miss, revenue miss

### Data Table
- Company information and ticker symbols
- Earnings report times (BMO/AMC/TNS)
- Market cap and market cap changes
- Current prices and daily price changes
- EPS estimates vs actual results
- Revenue estimates vs actual results
- Surprise percentages for both EPS and revenue

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **APIs**: Finnhub, Yahoo Finance, Polygon.io
- **Web Server**: Apache/Nginx
- **Scheduling**: Windows Task Scheduler

## 📁 Project Structure

```
EarningsTable/
├── cron/                    # Scheduled tasks
│   ├── clear_old_data.php
│   ├── fetch_finnhub_earnings_today_tickers.php
│   ├── fetch_missing_tickers_yahoo.php
│   ├── fetch_market_data_complete.php
│   ├── run_5min_updates.php
│   └── ...
├── public/                  # Web-accessible files
│   ├── dashboard-fixed.html # Main dashboard
│   ├── api/                 # API endpoints
│   └── adminlte/           # AdminLTE framework
├── common/                  # Shared components
│   ├── Finnhub.php
│   └── Lock.php
├── utils/                   # Utility functions
│   ├── database.php
│   └── polygon_api_optimized.php
├── scripts/                 # Setup and maintenance scripts
└── sql/                     # Database schema
```

## 🔧 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/dusan02/EarningsTable.git
   cd EarningsTable
   ```

2. **Configure database**
   - Create a MySQL database
   - Import the schema from `sql/setup_all_tables.sql`
   - Copy `config.example.php` to `config.php` and update database credentials

3. **Set up API keys**
   - Get API keys from [Finnhub](https://finnhub.io/)
   - Get API keys from [Polygon.io](https://polygon.io/)
   - Update the API keys in your configuration

4. **Configure web server**
   - Point your web server to the `public/` directory
   - Ensure PHP has write permissions to `logs/` and `storage/` directories

5. **Set up scheduled tasks**
   - Run the setup scripts in the `scripts/` directory
   - Configure Windows Task Scheduler for automated data fetching

## 📅 Cron Jobs Schedule

### Daily Tasks (NY Time)
- **02:00**: Clear old data (`clear_old_data.php`)
- **02:30**: Fetch Finnhub earnings tickers (`fetch_finnhub_earnings_today_tickers.php`)
- **02:40**: Fetch missing Yahoo Finance tickers (`fetch_missing_tickers_yahoo.php`)
- **03:00**: Fetch complete market data (`fetch_market_data_complete.php`)

### Continuous Updates
- **Every 5 minutes**: Update prices and earnings data (`run_5min_updates.php`)

## 🎯 Usage

1. **Access the dashboard**: Navigate to `http://your-domain/dashboard-fixed.html`
2. **View earnings data**: The dashboard automatically loads today's earnings data
3. **Sort and filter**: Click column headers to sort, use the search box to filter companies
4. **Monitor updates**: Data refreshes automatically every 5 minutes

## 🔒 Security

- API keys are stored in configuration files (not in version control)
- Database credentials are protected
- Input validation and sanitization implemented
- CORS headers configured for API endpoints

## 📈 Data Sources

- **Earnings Calendar**: Finnhub API
- **Stock Prices**: Polygon.io API
- **Market Cap Data**: Polygon.io Ticker Details API
- **Additional Tickers**: Yahoo Finance scraping

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation in the `docs/` directory
- Review the troubleshooting guides

## 🔄 Changelog

### Version 1.0.0 (2025-08-23)
- Initial release
- Real-time earnings dashboard
- Automated data fetching
- Responsive design
- Interactive sorting and filtering

---

**Note**: This project requires active API subscriptions to Finnhub and Polygon.io for full functionality.
