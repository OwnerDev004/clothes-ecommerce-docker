import { useAdminAuthStore } from '~/stores/adminAuthStore'

const resolveModuleKey = (path: string) => {
  const parts = path.split('/').filter(Boolean)
  if (parts[0] !== 'admin') {
    return null
  }

  return parts[1] ? parts[1].replace(/-/g, '_') : null
}

export default defineNuxtRouteMiddleware((to) => {
  const adminAuthStore = useAdminAuthStore()

  if (!adminAuthStore.isAuthenticated) {
    return navigateTo('/admin/login')
  }

  if (adminAuthStore.isSuperAdmin) {
    return
  }

  const moduleKey = resolveModuleKey(to.path)
  if (!moduleKey) {
    return
  }

  if (!adminAuthStore.can(moduleKey, 'view')) {
    return abortNavigation(createError({ statusCode: 403, statusMessage: 'Forbidden' }))
  }
})
