import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { bootstrapTheme } from './lib/theme.ts'
import { configureRequestProgress } from './lib/request-progress.ts'
import { useNotificationStore } from './stores/notification.store.ts'
import './style.css'
import App from './App.vue'
import router from './app/router/index.ts'
import { installAutoAnimate } from './plugins/auto-animate.ts'
import { installVueQuery } from './plugins/vue-query.ts'

bootstrapTheme()
configureRequestProgress()

const app = createApp(App)

installAutoAnimate(app)
app.use(createPinia())
installVueQuery(app)
app.use(router)

app.config.errorHandler = (err, _instance, info) => {
  console.error('[vue]', err, info)
  try {
    useNotificationStore().notify({
      title: 'Something went wrong',
      description: 'Please refresh the page and try again.',
      variant: 'destructive',
    })
  } catch {
    // Pinia may be unavailable during early boot.
  }
}

app.mount('#app')
