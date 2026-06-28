import { z } from 'zod'

const envSchema = z.object({
  VITE_API_URL: z.string().optional(),
  VITE_APP_NAME: z.string().default('School Management'),
  MODE: z.enum(['development', 'production', 'test']),
  DEV: z.boolean(),
  PROD: z.boolean(),
})

/** Validated Vite environment variables (build-time). */
export const env = envSchema.parse(import.meta.env)

export const appName = env.VITE_APP_NAME
