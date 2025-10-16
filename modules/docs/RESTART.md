# 🔄 Application Restart Guide

## Overview

This guide covers all available restart options for the EarningsTable application.

## 🚀 Quick Restart Options

### 1. **PowerShell Script (Recommended)**

```bash
npm run restart
```

- ✅ Cross-platform compatible
- ✅ Colored output
- ✅ Error handling
- ✅ Automatic service startup

### 2. **Batch Script (Windows)**

```bash
npm run restart:bat
```

- ✅ Windows native
- ✅ Simple execution
- ✅ No PowerShell required

### 3. **Quick Restart (Node.js)**

```bash
npm run restart:quick
```

- ✅ Fast execution
- ✅ Programmatic control
- ✅ Background processes

## 🛠️ Advanced Restart Options

### Cron Module Restart Scripts

```bash
cd modules/cron

# Full restart with options
npm run restart -- --full

# Restart specific services
npm run restart -- --database
npm run restart -- --web
npm run restart -- --studio

# Combined options
npm run restart -- --web --studio
```

### Available Options:

- `--database, -d` - Restart database only
- `--web, -w` - Restart web application only
- `--studio, -s` - Restart Prisma Studio only
- `--full, -f` - Full restart (all services)
- `--help, -h` - Show help

## 📊 What Gets Restarted

### Full Restart Includes:

1. **🛑 Process Termination**

   - Kills all Node.js processes
   - Clears process locks
   - Waits for clean shutdown

2. **🔄 Database Operations**

   - Creates backup of current database
   - Regenerates Prisma Client
   - Updates database schema
   - Copies database to root for Prisma Studio

3. **🌐 Web Application**

   - Starts on port 3001
   - Loads environment variables
   - Connects to database

4. **📊 Prisma Studio**
   - Starts on port 5555
   - Uses copied database file
   - Provides database interface

## 🎯 Service URLs After Restart

| Service       | URL                                | Description                |
| ------------- | ---------------------------------- | -------------------------- |
| Web App       | http://localhost:3001              | Main application interface |
| Prisma Studio | http://localhost:5555              | Database management        |
| API           | http://localhost:3001/api/earnings | REST API endpoint          |

## 🔧 Troubleshooting

### Common Issues:

#### 1. **Port Already in Use**

```bash
# Kill processes manually
taskkill /f /im node.exe

# Or use restart script
npm run restart
```

#### 2. **Database Lock Error**

```bash
# Full restart clears all locks
npm run restart -- --full
```

#### 3. **Prisma Studio Not Loading**

```bash
# Restart with database copy
npm run restart -- --studio
```

#### 4. **Web App Not Starting**

```bash
# Check port availability
netstat -an | findstr :3001

# Restart web service
npm run restart -- --web
```

## 📝 Manual Restart Steps

If automated scripts fail:

1. **Stop all processes:**

   ```bash
   taskkill /f /im node.exe
   ```

2. **Copy database:**

   ```bash
   copy modules\database\prisma\dev.db temp.db
   ```

3. **Start Prisma Studio:**

   ```bash
   $env:DATABASE_URL="file:./temp.db"; npx prisma studio
   ```

4. **Start Web App:**
   ```bash
   cd modules\web
   $env:PORT=3001; npm start
   ```

## 🚀 Production Considerations

### For Production Deployment:

- Use process managers (PM2, systemd)
- Implement health checks
- Add monitoring and logging
- Use environment-specific configurations

### Docker Restart:

```bash
docker-compose restart
# or
docker-compose down && docker-compose up -d
```

## 📋 Restart Checklist

Before restarting:

- [ ] Save any unsaved work
- [ ] Check for running processes
- [ ] Verify database file exists
- [ ] Confirm environment variables
- [ ] Test service URLs after restart

After restart:

- [ ] Verify Web App loads (http://localhost:3001)
- [ ] Check Prisma Studio (http://localhost:5555)
- [ ] Test API endpoint
- [ ] Confirm database connectivity
- [ ] Check logs for errors
