<script setup lang="ts">
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
  layout: false,
  middleware: ['admin-guest'],
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const router = useRouter()
const adminAuthStore = useAdminAuthStore()

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const errorMessage = ref('')

const submitLogin = async () => {
  errorMessage.value = ''

  if (!form.email || !form.password) {
    errorMessage.value = 'Email and password are required.'
    return
  }

  loading.value = true
  try {
    const response: any = await $fetch(`${apiBase}/admin/login`, {
      method: 'POST',
      body: form,
    })

    const token = response?.data?.admin_access_token
    const admin_data = response?.data?.admin_data
    if (!token) {
      throw new Error('Login token was not returned.')
    }

    adminAuthStore.setAccessToken(token)
    adminAuthStore.setAuthenticated(true)
    adminAuthStore.setAdminProfile(admin_data)
    await router.replace('/admin/dashboard')
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Admin login failed.'
    adminAuthStore.resetAuth()
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-[100dvh] bg-[radial-gradient(circle_at_top_left,rgba(79,70,229,0.22),transparent_35%),linear-gradient(180deg,#08111f_0%,#0f172a_45%,#f4f6fb_100%)] px-4 py-8">
    <div
      class="mx-auto grid min-h-[calc(100dvh-4rem)] max-w-6xl overflow-hidden rounded-[32px] border border-white/10 bg-white shadow-[0_30px_80px_rgba(2,6,23,0.24)] lg:grid-cols-2">
      <section
        class="flex flex-col justify-between bg-[linear-gradient(180deg,#0f172a,#111827)] px-8 py-10 text-white sm:px-10">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-300">Admin access</p>
          <h1 class="mt-4 text-4xl font-semibold leading-tight">Clothes Shop dashboard</h1>
          <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300">
            Sign in to manage products, orders, categories, promotions, and daily operations from one clean workspace.
          </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
          <div class="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.14em] text-white/45">Secure</p>
            <strong class="mt-2 block text-lg">JWT access</strong>
          </div>
          <div class="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.14em] text-white/45">Fast</p>
            <strong class="mt-2 block text-lg">Dashboard load</strong>
          </div>
          <div class="rounded-[20px] border border-white/10 bg-white/5 p-4">
            <p class="text-xs uppercase tracking-[0.14em] text-white/45">Clear</p>
            <strong class="mt-2 block text-lg">Single flow</strong>
          </div>
        </div>
      </section>

      <section class="flex items-center justify-center bg-slate-50 px-6 py-10 sm:px-10">
        <div class="w-full max-w-md">
          <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Welcome back</p>
            <h2 class="mt-2 text-3xl font-semibold text-slate-950">Admin sign in</h2>
            <p class="mt-3 text-sm leading-6 text-slate-500">
              Use your admin credentials to open the dashboard.
            </p>
          </div>

          <form class="space-y-4" @submit.prevent="submitLogin">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
              <input v-model="form.email" type="email"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                placeholder="admin@example.com" />
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>
              <input v-model="form.password" type="password"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                placeholder="••••••••" />
            </div>

            <p v-if="errorMessage" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-600">
              {{ errorMessage }}
            </p>

            <button type="submit" :disabled="loading"
              class="flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
              <span>{{ loading ? 'Signing in...' : 'Sign in' }}</span>
            </button>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>
