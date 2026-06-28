import type { App } from 'vue'
import { vAutoAnimate } from '@formkit/auto-animate/vue'

/** Registers the `v-auto-animate` directive for smooth form/list transitions. */
export function installAutoAnimate(app: App) {
  app.directive('auto-animate', vAutoAnimate)
}
