import { useAdminAuthStore } from '~/stores/adminAuthStore'

export default defineNuxtRouteMiddleware(() => {
  const adminAuthStore = useAdminAuthStore()

  if (adminAuthStore.isAuthenticated || adminAuthStore.accessToken) {
    return navigateTo('/admin')
  }
})
