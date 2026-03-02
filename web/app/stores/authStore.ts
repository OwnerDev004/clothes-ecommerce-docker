import { defineStore } from 'pinia'
import { ref } from 'vue'

type UserProfile = {
  id?: number | string
  email?: string
  name?: string
  [key: string]: unknown
}

export const useAuthStore = defineStore(
  'auth',
  () => {
    const isAuthenticated = ref(false)
    const accessToken = ref<string | null>(null)
    const userProfile = ref<UserProfile | null>(null)

    const syncAuthenticated = () => {
      isAuthenticated.value = Boolean(accessToken.value || userProfile.value)
    }

    const setAuthenticated = (value: boolean) => {
      if (value) {
        isAuthenticated.value = true
        return
      }

      syncAuthenticated()
      if (!isAuthenticated.value) {
        userProfile.value = null
      }
    }

    const setAccessToken = (token: string | null) => {
      accessToken.value = token
      syncAuthenticated()
    }

    const setUserProfile = (profile: UserProfile | null) => {
      userProfile.value = profile
      syncAuthenticated()
    }

    const resetAuth = () => {
      isAuthenticated.value = false
      accessToken.value = null
      userProfile.value = null
    }

    return {
      isAuthenticated,
      accessToken,
      userProfile,
      setAuthenticated,
      setAccessToken,
      setUserProfile,
      resetAuth,
    }
  },
  {
    persist: {
      key: 'auth',
      pick: ['isAuthenticated', 'accessToken', 'userProfile'],
      storage: 'cookies',
    },
  }
)
