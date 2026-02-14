<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Set new password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Please enter your new password below.
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="submitForm">
        <input type="hidden" v-model="form.token" />
        <input type="hidden" v-model="form.email" />
        
        <div class="space-y-4">
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              New Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Enter new password"
            />
            <div v-if="errors.password" class="text-red-500 text-sm mt-1">
              {{ errors.password[0] }}
            </div>
          </div>
          
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirm Password
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              required
              class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Confirm new password"
            />
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="loading">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span v-else>
              Reset password
            </span>
          </button>
        </div>
      </form>
      
      <!-- Success/Error Messages -->
      <div v-if="message.type" :class="messageClass">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg v-if="message.type === 'success'" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p :class="`text-sm font-medium ${message.type === 'success' ? 'text-green-800' : 'text-red-800'}`">
              {{ message.text }}
            </p>
          </div>
        </div>
      </div>
      
      <div class="text-center">
        <NuxtLink to="/auth/login" class="font-medium text-indigo-600 hover:text-indigo-500">
          Back to login
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter, useRuntimeConfig } from '#app'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const errors = ref<any>({})
const loading = ref(false)
const message = reactive({
  type: '' as 'success' | 'error',
  text: ''
})

const canSubmit = computed(() => !!form.token && !!form.email)

const messageClass = computed(() => {
  return message.type === 'success' 
    ? 'rounded-md bg-green-50 p-4'
    : 'rounded-md bg-red-50 p-4'
})

const parseQueryValue = (value: unknown): string => {
  if (Array.isArray(value)) {
    return String(value[0] ?? '')
  }
  return value ? String(value) : ''
}

// Extract token and email from URL
onMounted(async () => {
  const token = parseQueryValue(route.query.token)
  const email = parseQueryValue(route.query.email)
  
  if (!token || !email) {
    message.type = 'error'
    message.text = 'Invalid reset link'
    return
  }
  
  form.token = token
  form.email = email
})

const submitForm = async () => {
  if (!canSubmit.value) {
    message.type = 'error'
    message.text = 'Please use a valid reset link'
    return
  }
  
  loading.value = true
  errors.value = {}
  message.type = ''
  
  try {
    const response: any = await $fetch(`${apiBase}/auth/reset_password`, {
      method: 'POST',
      body: {
        email: form.email,
        token: form.token,
        password: form.password,
        password_confirmation: form.password_confirmation
      }
    })
    message.type = 'success'
    message.text = response?.message || 'Password reset successfully!'
    
    // Redirect to login after 3 seconds
    setTimeout(() => {
      router.push('/auth/login')
    }, 3000)
  } catch (error: any) {
    const data = error?.data
    if (data?.errors) {
      errors.value = data.errors
    } else {
      message.type = 'error'
      message.text = data?.message || error?.message || 'Failed to reset password'
    }
  } finally {
    loading.value = false
  }
}
</script>
