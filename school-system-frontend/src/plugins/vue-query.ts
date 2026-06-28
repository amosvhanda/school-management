import { VueQueryPlugin } from '@tanstack/vue-query'
import type { App } from 'vue'
import { queryClient } from '@/lib/query-client'

export function installVueQuery(app: App) {
  app.use(VueQueryPlugin, { queryClient })
}
