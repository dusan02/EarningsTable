import { exec } from 'child_process';
import { promisify } from 'util';
import { CONFIG } from './config.js';
import { disconnect } from './repository.js';
import { waitForHealth, waitForPort, checkWebHealth, checkStudioHealth } from './utils/health-check.js';

const execAsync = promisify(exec);

interface RestartOptions {
  database?: boolean;
  webApp?: boolean;
  prismaStudio?: boolean;
  fullRestart?: boolean;
  clearDb?: boolean;
  clearFinhub?: boolean;
  clearPolygon?: boolean;
  restartApp?: boolean;
  quick?: boolean;
  soft?: boolean;
  clearOnly?: boolean;
}

class ApplicationRestarter {
  private isWindows = process.platform === 'win32';

  async killAllProcesses(): Promise<void> {
    console.log('🛑 Killing all Node.js processes...');
    
    try {
      if (this.isWindows) {
        await execAsync('taskkill /f /im node.exe');
        console.log('✅ All Node.js processes terminated');
      } else {
        await execAsync('pkill -f node');
        console.log('✅ All Node.js processes terminated');
      }
    } catch (error) {
      console.log('⚠️ No Node.js processes to kill or error occurred');
    }
  }

  async restartDatabase(): Promise<void> {
    console.log('🔄 Restarting database...');
    
    try {
      // Backup current database
      const backupPath = `./backup_${new Date().toISOString().replace(/[:.]/g, '-')}.db`;
      await execAsync(`copy modules\\database\\prisma\\dev.db ${backupPath}`);
      console.log(`✅ Database backed up to ${backupPath}`);

      // Regenerate Prisma Client
      await execAsync('npx prisma generate --schema=modules/database/prisma/schema.prisma');
      console.log('✅ Prisma Client regenerated');

      // Push schema changes
      await execAsync(`$env:DATABASE_URL="file:./modules/database/prisma/dev.db"; npx prisma db push --schema=modules/database/prisma/schema.prisma`);
      console.log('✅ Database schema updated');

    } catch (error) {
      console.error('❌ Database restart failed:', error);
      throw error;
    }
  }

  async restartWebApp(): Promise<void> {
    console.log('🌐 Restarting web application...');
    
    try {
      // Start web app in background
      const webProcess = exec('cd modules/web && $env:PORT=3001; npm start');
      
      // Wait a bit for startup
      await new Promise(resolve => setTimeout(resolve, 3000));
      
      console.log('✅ Web application restarted on http://localhost:3001');
      
    } catch (error) {
      console.error('❌ Web app restart failed:', error);
      throw error;
    }
  }

  async restartPrismaStudio(): Promise<void> {
    console.log('📊 Restarting Prisma Studio...');
    
    try {
      // Copy database to root for Prisma Studio
      await execAsync('copy modules\\database\\prisma\\dev.db .\\temp.db');
      
      // Start Prisma Studio in background
      const studioProcess = exec('$env:DATABASE_URL="file:./temp.db"; npx prisma studio');
      
      // Wait a bit for startup
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      console.log('✅ Prisma Studio restarted on http://localhost:5555');
      
    } catch (error) {
      console.error('❌ Prisma Studio restart failed:', error);
      throw error;
    }
  }

  async clearDatabase(): Promise<void> {
    console.log('🗑️ Clearing all database data...');
    
    try {
      // Create backup before clearing
      const backupPath = `./backup_before_clear_${new Date().toISOString().replace(/[:.]/g, '-')}.db`;
      await execAsync(`copy modules\\database\\prisma\\dev.db ${backupPath}`);
      console.log(`✅ Database backed up to ${backupPath}`);

      // Use the centralized DatabaseManager method
      const { db } = await import('./core/DatabaseManager.js');
      await db.clearAllTables();
      
      console.log('✅ Database cleared successfully');
      
    } catch (error) {
      console.error('❌ Database clear failed:', error);
      throw error;
    }
  }

  // Note: Individual table clearing methods removed - use clearDatabase() instead
  // which uses the centralized DatabaseManager.clearAllTables() method

  async cleanPrismaCache(): Promise<void> {
    console.log('🧹 Cleaning Prisma cache to prevent file locking...');
    
    try {
      // Remove Prisma client and engines cache
      if (this.isWindows) {
        await execAsync('if exist "node_modules\\.prisma\\client" rmdir /s /q "node_modules\\.prisma\\client"');
        await execAsync('if exist "node_modules\\.prisma\\engines" rmdir /s /q "node_modules\\.prisma\\engines"');
      } else {
        await execAsync('rm -rf node_modules/.prisma/client');
        await execAsync('rm -rf node_modules/.prisma/engines');
      }
      
      console.log('✅ Prisma cache cleaned');
      
    } catch (error) {
      console.log('⚠️ Prisma cache clean failed (may not exist):', error);
      // Don't throw error - cache may not exist
    }
  }

  async regeneratePrismaClient(): Promise<void> {
    console.log('🔄 Regenerating Prisma Client...');
    
    try {
      // Change to database directory and generate
      await execAsync('cd modules/database && npx prisma generate');
      console.log('✅ Prisma Client regenerated successfully');
      
    } catch (error) {
      console.error('❌ Prisma Client regeneration failed:', error);
      throw error;
    }
  }


  async quickRestart(): Promise<void> {
    console.log('⚡ Quick Restart - Proper order: stop → kill → clean → generate → start...');
    
    try {
      // Step 1: Stop all Node processes
      console.log('🛑 Step 1: Killing all Node.js processes...');
      await this.killAllProcesses();
      console.log('✅ All processes terminated');
      
      // Step 2: Wait for processes to fully terminate
      console.log('⏳ Step 2: Waiting for processes to fully terminate...');
      await new Promise(resolve => setTimeout(resolve, 3000));
      
      // Step 3: Clean Prisma cache to prevent file locking
      console.log('🧹 Step 3: Cleaning Prisma cache...');
      await this.cleanPrismaCache();
      console.log('✅ Prisma cache cleaned');
      
      // Step 4: Regenerate Prisma Client
      console.log('🔄 Step 4: Regenerating Prisma Client...');
      await this.regeneratePrismaClient();
      console.log('✅ Prisma Client regenerated');
      
      // Step 5: Clear all database data
      console.log('🗑️ Step 5: Clearing all database data...');
      await this.clearDatabase();
      console.log('✅ All data cleared');
      
      // Step 6: Start services in background
      console.log('🚀 Step 6: Starting services...');
      
      // Start Prisma Studio with direct path to main database
      exec('$env:DATABASE_URL="file:./modules/database/prisma/dev.db"; npx prisma studio --schema=modules/database/prisma/schema.prisma', (error, stdout, stderr) => {
        if (error) console.error('Prisma Studio error:', error);
      });
      
      // Start Web App
      exec('cd modules/web && $env:PORT=3001; npm start', (error, stdout, stderr) => {
        if (error) console.error('Web app error:', error);
      });
      
      // Wait for services to start with health checks
      console.log('🔍 Waiting for services to start...');
      
      try {
        await waitForHealth('http://localhost:3001/health', 25000);
        console.log('✅ Web application is healthy');
      } catch (error) {
        console.log('⚠️ Web application health check failed, but continuing...');
      }
      
      try {
        await waitForPort(5555, 15000);
        console.log('✅ Prisma Studio is available');
      } catch (error) {
        console.log('⚠️ Prisma Studio not available, but continuing...');
      }
      
      console.log('🎉 Quick restart completed!');
      console.log('📊 Services available:');
      console.log('   - Web App: http://localhost:3001');
      console.log('   - Prisma Studio: http://localhost:5555');
      console.log('   - API: http://localhost:3001/api/earnings');
      console.log('   - Health: http://localhost:3001/health');
      
    } catch (error) {
      console.error('❌ Quick restart failed:', error);
      throw error;
    }
  }

  async softRestart(): Promise<void> {
    console.log('🔄 Soft Restart - Restarting services without clearing data...');
    
    try {
      // Kill all Node processes
      console.log('🛑 Killing all Node.js processes...');
      await this.killAllProcesses();
      console.log('✅ All processes terminated');
      
      // Wait 2 seconds for processes to fully terminate
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      // Start services in background
      console.log('🚀 Starting services...');
      
      // Start Prisma Studio with direct path to main database
      exec('$env:DATABASE_URL="file:./modules/database/prisma/dev.db"; npx prisma studio --schema=modules/database/prisma/schema.prisma', (error, stdout, stderr) => {
        if (error) console.error('Prisma Studio error:', error);
      });
      
      // Start Web App
      exec('cd modules/web && $env:PORT=3001; npm start', (error, stdout, stderr) => {
        if (error) console.error('Web app error:', error);
      });
      
      // Wait for services to start with health checks
      console.log('🔍 Waiting for services to start...');
      
      try {
        await waitForHealth('http://localhost:3001/health', 25000);
        console.log('✅ Web application is healthy');
      } catch (error) {
        console.log('⚠️ Web application health check failed, but continuing...');
      }
      
      try {
        await waitForPort(5555, 15000);
        console.log('✅ Prisma Studio is available');
      } catch (error) {
        console.log('⚠️ Prisma Studio not available, but continuing...');
      }
      
      console.log('🎉 Soft restart completed!');
      console.log('📊 Services available:');
      console.log('   - Web App: http://localhost:3001');
      console.log('   - Prisma Studio: http://localhost:5555');
      console.log('   - API: http://localhost:3001/api/earnings');
      console.log('   - Health: http://localhost:3001/health');
      
    } catch (error) {
      console.error('❌ Soft restart failed:', error);
      throw error;
    }
  }

  async clearOnly(): Promise<void> {
    console.log('🗑️ Clear Only - Clearing database data without restarting services...');
    
    try {
      // Clear all database data
      await this.clearDatabase();
      console.log('✅ Database cleared successfully');
      
    } catch (error) {
      console.error('❌ Clear only failed:', error);
      throw error;
    }
  }

  async restartApplication(): Promise<void> {
    console.log('🔄 Restarting application (kill + start services)...');
    
    try {
      // Kill all processes
      await this.killAllProcesses();
      
      // Wait for processes to terminate
      await new Promise(resolve => setTimeout(resolve, 2000));
      
      // Start services
      await this.restartWebApp();
      await this.restartPrismaStudio();
      
      console.log('✅ Application restarted successfully');
      
    } catch (error) {
      console.error('❌ Application restart failed:', error);
      throw error;
    }
  }

  async performRestart(options: RestartOptions): Promise<void> {
    console.log('🚀 Starting application restart...');
    console.log(`📋 Options: ${JSON.stringify(options, null, 2)}`);
    
    try {
      // Clear only
      if (options.clearOnly) {
        await this.clearOnly();
        return;
      }

      // Soft restart (no data clearing)
      if (options.soft) {
        await this.softRestart();
        return;
      }

      // Quick restart (simplified version)
      if (options.quick) {
        await this.quickRestart();
        return;
      }

      // Clear data operations (do first, before killing processes)
      if (options.clearDb) {
        await this.clearDatabase();
      } else if (options.clearFinhub) {
        await this.clearFinhubData();
      } else if (options.clearPolygon) {
        await this.clearPolygonData();
      }

      // Application restart (kill + start services)
      if (options.restartApp) {
        await this.restartApplication();
      } else {
        // Individual service restarts
        if (options.database || options.fullRestart) {
          await this.restartDatabase();
        }

        if (options.webApp || options.fullRestart) {
          await this.restartWebApp();
        }

        if (options.prismaStudio || options.fullRestart) {
          await this.restartPrismaStudio();
        }
      }

      console.log('🎉 Application restart completed successfully!');
      console.log('📊 Services available:');
      console.log('   - Web App: http://localhost:3001');
      console.log('   - Prisma Studio: http://localhost:5555');
      console.log('   - API: http://localhost:3001/api/earnings');

    } catch (error) {
      console.error('💥 Restart failed:', error);
      throw error;
    } finally {
      await disconnect();
    }
  }
}

// CLI interface
async function main() {
  const args = process.argv.slice(2);
  const options: RestartOptions = {};

  // Parse command line arguments
  for (const arg of args) {
    switch (arg) {
      case '--database':
      case '-d':
        options.database = true;
        break;
      case '--web':
      case '-w':
        options.webApp = true;
        break;
      case '--studio':
      case '-s':
        options.prismaStudio = true;
        break;
      case '--full':
      case '-f':
        options.fullRestart = true;
        break;
      case '--clear-db':
      case '--clear':
        options.clearDb = true;
        break;
      case '--clear-finnhub':
        options.clearFinhub = true;
        break;
      case '--restart-app':
      case '--app':
        options.restartApp = true;
        break;
      case '--quick':
      case '-q':
        options.quick = true;
        break;
      case '--soft':
        options.soft = true;
        break;
      case '--clear-only':
        options.clearOnly = true;
        break;
      case '--help':
      case '-h':
        console.log(`
🔄 Application Restart Tool

Usage: npm run restart [options]

Options:
  -d, --database        Restart database only
  -w, --web             Restart web application only
  -s, --studio          Restart Prisma Studio only
  -f, --full            Full restart (all services)
  --clear-db, --clear   Clear all database data
  --clear-finnhub       Clear FinhubData only
  --restart-app, --app  Restart application (kill + start)
  -q, --quick           Quick restart (kill + clear + start)
  --soft                Soft restart (kill + start, no data clearing)
  --clear-only          Clear database data only (no restart)
  -h, --help            Show this help

Examples:
  npm run restart --full                    # Restart everything
  npm run restart --database                # Restart database only
  npm run restart --web --studio            # Restart web app and Prisma Studio
  npm run restart --clear-db                # Clear all data
  npm run restart --clear-finnhub           # Clear FinhubData only
  npm run restart --restart-app             # Restart application
  npm run restart --quick                   # Quick restart (kill + clear + start)
  npm run restart --soft                    # Soft restart (kill + start, no clear)
  npm run restart --clear-only              # Clear data only
  npm run restart --clear-db --restart-app  # Clear data + restart app
        `);
        process.exit(0);
        break;
    }
  }

  // Default to full restart if no options specified
  if (!options.database && !options.webApp && !options.prismaStudio && 
      !options.clearDb && !options.clearFinhub && !options.clearPolygon && 
      !options.restartApp && !options.quick && !options.soft && !options.clearOnly) {
    options.fullRestart = true;
  }

  const restarter = new ApplicationRestarter();
  await restarter.performRestart(options);
}

if (import.meta.url === `file://${process.argv[1]}`) {
  main().catch(async (e) => {
    console.error('✗ Restart script failed:', e);
    await disconnect();
    process.exit(1);
  });
}

export { ApplicationRestarter };
