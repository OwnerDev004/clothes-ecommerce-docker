import { useAuthStore } from '~/stores/authStore'

export default defineNuxtRouteMiddleware(async () => {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated && !authStore.accessToken) {
    return
  }

  if (!authStore.isAuthenticated && authStore.accessToken) {
    authStore.setAuthenticated(true)
  }

  return navigateTo('/')
})
