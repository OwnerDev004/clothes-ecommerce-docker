import { useAuthStore } from '~/stores/authStore'

export default defineNuxtRouteMiddleware(async () => {
  const config = useRuntimeConfig()
  const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
  const authStore = useAuthStore()


  if (import.meta.server) {
    const cookieHeader = useRequestHeaders(['cookie']).cookie || ''
    if (!cookieHeader.includes('access_token=')) {
      authStore.resetAuth()
      return 
    }
  }

  if (import.meta.client && !authStore.isAuthenticated) {
    return
  }

   authStore.setAuthenticated(true)
   
    return navigateTo('/')
})
