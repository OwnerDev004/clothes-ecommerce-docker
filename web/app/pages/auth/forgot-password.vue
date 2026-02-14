<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-xl border border-gray-200 shadow-sm p-6">
      <h1 class="text-2xl font-semibold text-gray-900">Forgot password</h1>
      <p class="text-sm text-gray-600 mt-1">
        Enter your account email and we will send a reset link.
      </p>

      <form class="mt-6 space-y-4" @submit.prevent="submitForgotPassword">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            class="w-full border border-gray-300 rounded-md px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500"
            placeholder="you@example.com"
          />
        </div>

        <div v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</div>
        <div v-if="successMessage" class="text-sm text-green-600">{{ successMessage }}</div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60"
        >
          {{ loading ? 'Sending...' : 'Send reset link' }}
        </button>
      </form>

      <p class="text-sm text-gray-600 mt-4">
        Back to
        <NuxtLink to="/auth/login" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: ['guest']
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const email = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const submitForgotPassword = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!email.value) {
    errorMessage.value = 'Email is required.'
    return
  }

  loading.value = true
  try {
    const response: any = await $fetch(`${apiBase}/auth/forgot_password`, {
      method: 'POST',
      body: {
        email: email.value
      }
    })

    successMessage.value = response?.message || 'If this email exists, a reset link has been sent.'
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Cannot send reset link now.'
  } finally {
    loading.value = false
  }
}
</script>
