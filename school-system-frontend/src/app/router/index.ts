import { createRouter, createWebHistory } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import { createRouteGuards } from './guards'
import { publicRoutes, staffRoutes, parentRoutes, studentRoutes, platformRoutes } from './routes'
import { setupApiInterceptors } from '@/lib/api'
import { useAuthStore } from '@/stores/auth.store'
import { useNotificationStore } from '@/stores/notification.store'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: AuthenticatedLayout,
      meta: { requiresAuth: true },
      children: [...staffRoutes, ...parentRoutes, ...studentRoutes, ...platformRoutes],
    },
    {
      path: '/',
      component: AuthLayout,
      children: publicRoutes,
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/modules/shared/NotFoundView.vue'),
    },
  ],
})

createRouteGuards(router)

setupApiInterceptors({
  router,
  onUnauthorized: () => {
    useAuthStore().clearLocalSession()
  },
  onForbidden: (message) => {
    useNotificationStore().notify({ title: 'Forbidden', description: message, variant: 'destructive' })
  },
  onLicenseRequired: () => {
    useNotificationStore().notify({
      title: 'License required',
      description: 'Please activate your school license to continue.',
      variant: 'destructive',
    })
  },
})

export default router
