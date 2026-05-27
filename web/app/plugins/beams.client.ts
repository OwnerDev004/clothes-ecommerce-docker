import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '~/stores/authStore'

type BeamsSdk = typeof import('@pusher/push-notifications-web')

export default defineNuxtPlugin(() => {
  if (!import.meta.client) {
    return
  }

  const config = useRuntimeConfig()
  const route = useRoute()
  const authStore = useAuthStore()
  const apiBase = String(config.public.apiBase || '').replace(/\/$/, '')
  const instanceId = String(config.public.beamsInstanceId || '').trim()

  let beamsClient: import('@pusher/push-notifications-web').Client | null = null
  let tokenProvider: import('@pusher/push-notifications-web').TokenProvider | null = null
  let sdkPromise: Promise<BeamsSdk> | null = null
  let registeredUserId: string | null = null

  const loadSdk = () => {
    if (!sdkPromise) {
      sdkPromise = import('@pusher/push-notifications-web')
    }

    return sdkPromise
  }

  const ensureClient = async () => {
    if (!instanceId) {
      return null
    }

    if (!beamsClient || !tokenProvider) {
      const { Client, TokenProvider } = await loadSdk()
      beamsClient = new Client({ instanceId })
      tokenProvider = new TokenProvider({
        url: `${apiBase}/beams/auth`,
        credentials: 'include',
      })
    }

    return beamsClient
  }

  const stopBeams = async () => {
    if (!beamsClient) {
      registeredUserId = null
      return
    }

    try {
      await beamsClient.stop()
    } catch (error) {
      if (import.meta.dev) {
        console.warn('Failed to stop Beams client', error)
      }
    } finally {
      registeredUserId = null
    }
  }

  const syncBeams = async () => {
    if (!instanceId || route.path.startsWith('/admin')) {
      await stopBeams()
      return
    }

    const userId = String(authStore.userProfile?.id || '').trim()
    if (!authStore.isAuthenticated || !authStore.accessToken || !userId) {
      await stopBeams()
      return
    }

    if (registeredUserId === userId) {
      return
    }

    const client = await ensureClient()
    if (!client || !tokenProvider) {
      return
    }

    try {
      await client.start()

      const currentUserId = await client.getUserId().catch(() => '')
      if (currentUserId && currentUserId !== userId) {
        await client.stop()
        await client.start()
      }

      await client.setUserId(userId, tokenProvider)
      registeredUserId = userId
    } catch (error) {
      if (import.meta.dev) {
        console.warn('Failed to initialize Pusher Beams', error)
      }
    }
  }

  watch(
    [
      () => route.path,
      () => authStore.isAuthenticated,
      () => authStore.accessToken,
      () => authStore.userProfile?.id,
      () => instanceId,
    ],
    () => {
      void syncBeams()
    },
    { immediate: true },
  )
})
