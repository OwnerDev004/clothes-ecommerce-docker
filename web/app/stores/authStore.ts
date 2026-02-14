import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const isAuthenticated = ref(false)

  const setAuthenticated = (value: boolean) => {
    isAuthenticated.value = value
  }

  const resetAuth = () => {
    isAuthenticated.value = false
  }

  return { isAuthenticated, setAuthenticated, resetAuth }
})
