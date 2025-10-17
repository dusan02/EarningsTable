#!/bin/bash

# 🚀 Complete SSH Deployment - One Command
# Downloads and runs all deployment scripts

set -e

echo "🚀 Starting complete deployment..."

# Download and run deployment script
echo "📥 Downloading deployment script..."
wget -q https://raw.githubusercontent.com/dusan02/EarningsTable/main/deploy-production.sh
chmod +x deploy-production.sh
./deploy-production.sh

# Download and run Nginx setup
echo "🌐 Setting up Nginx..."
wget -q https://raw.githubusercontent.com/dusan02/EarningsTable/main/setup-nginx.sh
chmod +x setup-nginx.sh
./setup-nginx.sh

echo "🎉 Complete deployment finished!"
echo "🌐 Your site is now live at: http://www.earningstable.com"
