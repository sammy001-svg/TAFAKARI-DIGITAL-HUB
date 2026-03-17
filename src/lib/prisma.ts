import { Pool } from 'pg'
import { PrismaPg } from '@prisma/adapter-pg'
// Use a more robust import that works across different bundler configurations
import { PrismaClient } from '@prisma/client'

const prismaClientSingleton = () => {
  const connectionString = process.env.DATABASE_URL
  const pool = new Pool({ connectionString })
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const adapter = new PrismaPg(pool as any)
  try {
    return new PrismaClient({ adapter })
  } catch (error) {
    console.error('Failed to initialize PrismaClient:', error)
    throw error
  }
}

declare global {
  var prisma: undefined | ReturnType<typeof prismaClientSingleton>
}

const prisma = globalThis.prisma ?? prismaClientSingleton()

export default prisma

if (process.env.NODE_ENV !== 'production') globalThis.prisma = prisma
