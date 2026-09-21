import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { i18n } from './i18n'
import './style.css'
import './pages/pages.css' 

const app = createApp(App)
app.config.globalProperties.t = i18n.global.t
window.t = i18n.global.t.bind(i18n.global)
app.use(router)
  .use(i18n)
  .mount('#app')
