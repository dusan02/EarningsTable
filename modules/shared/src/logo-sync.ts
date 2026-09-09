// modules/shared/src/logo-sync.ts
import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import { prisma } from './prismaClient.js';

// Resolve the logo directory relative to this source file so it does not
// depend on process.cwd() (which varies by launch directory).
// This file lives at modules/shared/src/logo-sync.ts; logos live at
// modules/web/public/logosos.
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const DEFAULT_LOGO_DIR = path.resolve(__dirname, '..', '..', 'web', 'public', 'logos');

export class LogoSyncManager {
  private logoDir: string;

  constructor(logoDir?: string) {
    this.logoDir = logoDir || process.env.LOGO_DIR || DEFAULT_LOGO_DIR;
  }

  /**
   * Sync logos from filesystem to database
   * Finds logos that exist on FS but are missing in DB
   */
  async syncLogosFromFS(): Promise<{
    synced: number;
    skipped: number;
    errors: number;
    details: Array<{ symbol: string; action: string; error?: string }>;
  }> {
    console.log('🔄 Starting logo sync from filesystem to database...');
    
    const result = {
      synced: 0,
      skipped: 0,
      errors: 0,
      details: [] as Array<{ symbol: string; action: string; error?: string }>
    };

    try {
      // Get all logo files from filesystem
      const logoFiles = await this.getLogoFiles();
      console.log(`📁 Found ${logoFiles.length} logo files in filesystem`);

      // Get all symbols that have logos in database
      const dbLogos = await prisma.finalReport.findMany({
        where: { logoUrl: { not: null } },
        select: { symbol: true, logoUrl: true }
      });
      
      const dbLogoMap = new Map(dbLogos.map(logo => [logo.symbol, logo.logoUrl]));

      // Process each logo file
      for (const logoFile of logoFiles) {
        const symbol = logoFile.symbol;
        const logoUrl = `/logos/${logoFile.filename}`;

        try {
          // Skip only if the DB already has the *same* logo URL for this symbol.
          // (Previously skipped whenever any logoUrl existed, even if it pointed
          //  to a different/missing file.)
          if (dbLogoMap.get(symbol) === logoUrl) {
            result.skipped++;
            result.details.push({ symbol, action: 'skipped (already in sync)' });
            continue;
          }

          // Update database with logo info
          await this.updateLogoInDatabase(symbol, logoUrl, 'filesystem-sync');
          
          result.synced++;
          result.details.push({ symbol, action: 'synced from filesystem' });
          
        } catch (error: any) {
          result.errors++;
          result.details.push({ 
            symbol, 
            action: 'error', 
            error: error.message 
          });
          console.error(`❌ Error syncing logo for ${symbol}:`, error.message);
        }
      }

      console.log(`✅ Logo sync completed: ${result.synced} synced, ${result.skipped} skipped, ${result.errors} errors`);
      return result;

    } catch (error: any) {
      console.error('❌ Logo sync failed:', error);
      throw error;
    }
  }

  /**
   * Clean up orphaned logos (exist in DB but not on FS)
   */
  async cleanupOrphanedLogos(): Promise<{
    cleaned: number;
    details: Array<{ symbol: string; action: string }>;
  }> {
    console.log('🧹 Starting cleanup of orphaned logos...');
    
    const result = {
      cleaned: 0,
      details: [] as Array<{ symbol: string; action: string }>
    };

    try {
      // Get all logos from database
      const dbLogos = await prisma.finalReport.findMany({
        where: { logoUrl: { not: null } },
        select: { symbol: true, logoUrl: true }
      });

      // Get all logo files from filesystem
      const logoFiles = await this.getLogoFiles();
      const fsLogoMap = new Set(logoFiles.map(file => file.symbol));

      // Check each database logo
      for (const dbLogo of dbLogos) {
        const symbol = dbLogo.symbol;
        
        if (!fsLogoMap.has(symbol)) {
          // Logo exists in DB but not on FS - remove from DB
          await prisma.finalReport.updateMany({
            where: { symbol },
            data: { 
              logoUrl: null, 
              logoSource: null, 
              logoFetchedAt: null 
            }
          });
          
          result.cleaned++;
          result.details.push({ symbol, action: 'removed orphaned logo from DB' });
        }
      }

      console.log(`✅ Orphaned logo cleanup completed: ${result.cleaned} cleaned`);
      return result;

    } catch (error: any) {
      console.error('❌ Orphaned logo cleanup failed:', error);
      throw error;
    }
  }

  /**
   * Full logo sync (both directions)
   */
  async fullSync(): Promise<{
    fsToDb: { synced: number; skipped: number; errors: number; details: Array<{ symbol: string; action: string; error?: string }> };
    orphanedCleanup: { cleaned: number; details: Array<{ symbol: string; action: string }> };
  }> {
    console.log('🔄 Starting full logo synchronization...');
    
    const fsToDb = await this.syncLogosFromFS();
    const orphanedCleanup = await this.cleanupOrphanedLogos();
    
    console.log('✅ Full logo synchronization completed');
    return { fsToDb, orphanedCleanup };
  }

  /**
   * Get all logo files from filesystem
   */
  private async getLogoFiles(): Promise<Array<{ symbol: string; filename: string; path: string }>> {
    try {
      const files = await fs.readdir(this.logoDir);
      const logoFiles = files
        .filter(file => file.match(/\.(webp|svg|png|jpg|jpeg)$/i))
        .map(file => {
          const symbol = path.basename(file, path.extname(file));
          return {
            symbol,
            filename: file,
            path: path.join(this.logoDir, file)
          };
        });
      
      return logoFiles;
    } catch (error: any) {
      console.error(`❌ Error reading logo directory ${this.logoDir}:`, error.message);
      return [];
    }
  }

  /**
   * Update logo info in database
   */
  private async updateLogoInDatabase(symbol: string, logoUrl: string, source: string): Promise<void> {
    await prisma.finalReport.updateMany({
      where: { symbol },
      data: {
        logoUrl,
        logoSource: source,
        logoFetchedAt: new Date()
      }
    });
  }

  /**
   * Get logo sync statistics
   */
  async getSyncStats(): Promise<{
    totalSymbols: number;
    symbolsWithLogos: number;
    logoPercentage: number;
    fsLogos: number;
    dbLogos: number;
    orphanedLogos: number;
  }> {
    const totalSymbols = await prisma.finalReport.count();
    const symbolsWithLogos = await prisma.finalReport.count({
      where: { logoUrl: { not: null } }
    });
    
    const logoFiles = await this.getLogoFiles();
    const fsLogos = logoFiles.length;
    const dbLogos = symbolsWithLogos;
    
    // Calculate orphaned logos (exist in DB but not on FS)
    const dbLogoMap = new Set(
      (await prisma.finalReport.findMany({
        where: { logoUrl: { not: null } },
        select: { symbol: true }
      })).map(logo => logo.symbol)
    );
    const fsLogoMap = new Set(logoFiles.map(file => file.symbol));
    const orphanedLogos = Array.from(dbLogoMap).filter(symbol => !fsLogoMap.has(symbol)).length;

    return {
      totalSymbols,
      symbolsWithLogos,
      logoPercentage: totalSymbols > 0 ? (symbolsWithLogos / totalSymbols) * 100 : 0,
      fsLogos,
      dbLogos,
      orphanedLogos
    };
  }
}

// Export singleton instance
export const logoSyncManager = new LogoSyncManager();
