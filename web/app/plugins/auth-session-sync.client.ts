import { watch } from 'vue'
import { useAuthStore } from '~/stores/authStore'

type SessionAuthState = {
  isAuthenticated: boolean
  accessToken: string | null
  userProfile: Record<string, unknown> | null
}

const SESSION_KEY = 'auth_session_backup'

export default defineNuxtPlugin(() => {
  const authStore = useAuthStore()

  const restoreFromSession = () => {
    try {
      const raw = window.sessionStorage.getItem(SESSION_KEY)
      if (!raw) {
        return
      }

      const parsed = JSON.parse(raw) as SessionAuthState
      if (!parsed?.accessToken && !parsed?.userProfile && !parsed?.isAuthenticated) {
        return
      }

      if (!authStore.accessToken && parsed.accessToken) {
        authStore.setAccessToken(parsed.accessToken)
      }

      if (!authStore.userProfile && parsed.userProfile) {
        authStore.setUserProfile(parsed.userProfile)
      }

      if (!authStore.isAuthenticated && (parsed.isAuthenticated || parsed.accessToken || parsed.userProfile)) {
        authStore.setAuthenticated(true)
      }
    } catch {
      window.sessionStorage.removeItem(SESSION_KEY)
    }
  }

  restoreFromSession()

  watch(
    () => ({
      isAuthenticated: authStore.isAuthenticated,
      accessToken: authStore.accessToken,
      userProfile: authStore.userProfile,
    }),
    (state) => {
      if (!state.isAuthenticated && !state.accessToken && !state.userProfile) {
        window.sessionStorage.removeItem(SESSION_KEY)
        return
      }

      window.sessionStorage.setItem(SESSION_KEY, JSON.stringify(state))
    },
    { deep: true, immediate: true }
  )
})
