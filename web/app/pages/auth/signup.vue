<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-10 px-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h1 class="text-2xl font-semibold text-gray-900">Create account</h1>
      <p class="text-sm text-gray-600 mt-1">Sign up with email and username.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submitSignup">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
          <input v-model="form.full_name" type="text" class="input" placeholder="Your full name (optional)" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input v-model="form.user_name" type="text" class="input" placeholder="your_username" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="you@example.com" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
          <input v-model="form.phone" type="text" class="input" placeholder="+1..." />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
          <select v-model="form.gender" class="input">
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input v-model="form.password" type="password" class="input" placeholder="At least 6 characters" />
        </div>

        <div v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</div>

        <button type="submit" :disabled="loading" class="w-full py-2.5 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60">
          {{ loading ? 'Creating account...' : 'Create account' }}
        </button>
      </form>

      <p class="text-sm text-gray-600 mt-4">
        Already have an account?
        <NuxtLink to="/auth/login" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/authStore'

definePageMeta({
  middleware: ['guest']
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  full_name: '',
  user_name: '',
  email: '',
  phone: '',
  gender: 'male',
  password: ''
})

const loading = ref(false)
const errorMessage = ref('')

const submitSignup = async () => {
  errorMessage.value = ''
  if (!form.user_name || !form.email || !form.phone || !form.gender || !form.password) {
    errorMessage.value = 'Please fill all required fields.'
    return
  }

  loading.value = true
  try {
    await $fetch(`${apiBase}/auth/register`, {
      method: 'POST',
      credentials: 'include',
      body: form
    })
    authStore.setAuthenticated(true)
    await router.replace('/')
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Signup failed. Please try again.'
    authStore.resetAuth()
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.input {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.625rem 0.75rem;
  outline: none;
}

.input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}
</style>
