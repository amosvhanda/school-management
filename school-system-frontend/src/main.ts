import { createPinia } from 'pinia'
import { createApp } from 'vue'
import { bootstrapTheme } from './lib/theme'
import { configureRequestProgress } from './lib/request-progress'
import './style.css'
import App from './App.vue'
import router from './app/router'
import { installAutoAnimate } from './plugins/auto-animate'
import { installVueQuery } from './plugins/vue-query'

bootstrapTheme()
configureRequestProgress()

const app = createApp(App)

installAutoAnimate(app)
app.use(createPinia())
installVueQuery(app)
app.use(router)
app.mount('#app')
