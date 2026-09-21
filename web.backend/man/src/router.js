import { createRouter, createWebHistory } from 'vue-router'
import login from './views/Login.vue'
import dashboard from './views/Dashboard.vue'
import confirmSMS from './views/SmsConfirmation.vue'
import Reports from './views/Reports.vue'
import GoalProduct from './pages/GoalProduct.vue'
import MfActionsTable from './pages/MfActions.vue'
import MfMaterials from './pages/MfMaterials.vue'

import axios from './api' 
import { compile } from 'vue'

const routes = [
  {path:'/',
  redirect: (to) => {
      const isLoggedIn = !!localStorage.getItem('token') 
      return isLoggedIn ? '/dashboard' : '/login'
    }},
  { path: '/login', component: login },
  { path: '/dashboard', component: dashboard },
  {path: '/confirmSMS', component: confirmSMS},
  {path:'/reports', component: Reports},
  {path: '/goal/new', component: GoalProduct},
  {path: '/goal/edit', component: GoalProduct},
  { path: '/mfactions', component: MfActionsTable },
  { path: '/mfmaterials', component: MfMaterials },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to, from, next) => {
  console.log('ROUTING:', to.path)
  const token = localStorage.getItem('token')

  if (to.path === '/login') return next()
  if (to.path === '/confirmSMS') return next()
  if (!token) return next('/login')

  try {
    await axios.post('worker/user-login/auth', { token })
    console.log('Auth OK')
    return next()
  } catch (err) {
    console.error('Auth error', err)
    localStorage.removeItem('token')
    return next('/login')
  }
})


export default router
