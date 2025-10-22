import { prisma } from '../../../shared/src/prismaClient.js';
import { CreateFinhubData, CreatePolygonData, CreateFinalReport } from '../../../shared/src/types.js';
import { Prisma } from '@prisma/client';
import Decimal from 'decimal.js';

export class DatabaseManager {
  async upsertFinalReport(incoming: any): Promise<void> {
    console.log('upsertFinalReport called');
  }

  async upsertFinhubData(incoming: any): Promise<void> {
    console.log('upsertFinhubData called');
  }

  async upsertPolygonData(incoming: any): Promise<void> {
    console.log('upsertPolygonData called');
  }

  async copySymbolsToPolygonData(): Promise<void> {
    console.log('copySymbolsToPolygonData called');
  }

  async generateFinalReport(): Promise<void> {
    console.log('generateFinalReport called');
  }

  async clearAllData(): Promise<void> {
    console.log('clearAllData called');
  }

  async updateCronStatus(status: string): Promise<void> {
    console.log('updateCronStatus called:', status);
  }

  async getSymbolsNeedingLogoRefresh(): Promise<string[]> {
    console.log('getSymbolsNeedingLogoRefresh called');
    return [];
  }

  async disconnect(): Promise<void> {
    console.log('disconnect called');
  }
}

export const db = new DatabaseManager();
