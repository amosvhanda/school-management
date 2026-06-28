import { useAuthStore } from '../../stores/auth.store'

export function authGuard(to: any, from: any, next: any) {
  const auth = useAuthStore()

  // not logged in → send to login
  if (!auth.isAuthenticated) {
    return next({ name: 'login' })
  }

  // role-based route protection
  const requiredPermission = to.meta?.permission as string | undefined

  if (requiredPermission && !auth.hasPermission(requiredPermission)) {
    return next({ name: 'unauthorized' })
  }

  next()
}
