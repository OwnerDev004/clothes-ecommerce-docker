<template>
  <div class="bg-surface-2 box-border flex items-center justify-center h-screen">
    <div class="grid grid-cols-1 lg:grid-cols-3  w-[100vw] h-[100vh]">
      <section class="bg-primary hidden lg:flex justify-center items-center">
        <NuxtImg src="/img/auth/graphic1.svg" alt="Login graphic" format="webp" loading="lazy" />
      </section>
      <section class=" flex justify-center items-center col-span-2 ">
        <div class="w-[80vw] lg:w-[25vw] bg-surface drop-shadow-xl p-6 rounded-element  space-y-6">
          <header>
            <p class="text-xl font-semibold">Create account</p>
            <p class="text-xs">Sign up with email and username.</p>
          </header>
          <el-form ref="signupFormRef" :model="form" :rules="signupRules" label-position="top"
            class="w-full grid grid-cols-1 items-center" autocomplete="off" @submit.prevent="submitSignup">
            <el-form-item label="Full Name" prop="full_name">
              <BaseInput placeholder="Your Full Name" type="text" v-model="form.full_name" />
            </el-form-item>
            <el-form-item label="Username" prop="user_name">
              <BaseInput placeholder="Your Username" type="text" v-model="form.user_name" />
            </el-form-item>
            <el-form-item label="Email" prop="email">
              <BaseInput placeholder="Your Email" type="email" v-model="form.email" />
            </el-form-item>
            <el-form-item label="Phone" prop="phone">
              <BaseInput placeholder="Your Phone Number" type="tel" v-model="form.phone" />
            </el-form-item>
            <el-form-item label="Password" prop="password">
              <BaseInput placeholder="Your Password" type="password" v-model="form.password" show-password />
            </el-form-item>
            <el-form-item label="Gender" prop="gender">
              <BaseSelect v-model="form.gender" :options="genderOptions" placeholder="Choose your gender"
                class="w-full" />
            </el-form-item>
          </el-form>
          <div v-if="errorMessage" class="text-danger text-xs grid place-items-center">{{ errorMessage }}
          </div>

          <div class="flex flex-col justify-center gap-4 items-center">
            <BaseButton type="primary" class="w-[300px]" :loading="loading" @click="submitSignup">
              Sign Up
            </BaseButton>
            <div class="flex gap-1">
              <span class="text-xs"> Already have an account?</span>
              <NuxtLink to="/auth/login" class="text-xs underline">
                Sign in
              </NuxtLink>
            </div>
          </div>


        </div>
      </section>
    </div>
    <CompleteOAuthDialog v-model="authCompleteDialogOpen" />
  </div>
</template>

<script setup lang="ts">
import CompleteOAuthDialog from '~/components/frontend/Modal/CompleteOAuthDialog.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import { useAuthStore } from '~/stores/authStore'
import type { FormInstance, FormRules } from 'element-plus'

definePageMeta({
  layout: 'guest',
  middleware: ['guest']
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const router = useRouter()
const authStore = useAuthStore()
const authCompleteDialogOpen = ref<boolean>(false)
const genderOptions = ref<any[]>([
  {
    id: 'male',
    label: 'Male'
  },
  {
    id: 'female',
    label: 'Female'
  }
])

const form = reactive({
  full_name: '',
  user_name: '',
  email: '',
  phone: '',
  gender: '',
  password: ''
})

const signupFormRef = ref<FormInstance>()
const signupRules: FormRules<typeof form> = {
  full_name: [
    { required: true, message: 'Full name is required', trigger: 'blur' },
    { min: 2, max: 255, message: 'Full name must be between 2 and 255 characters', trigger: 'blur' },
  ],
  user_name: [
    { required: true, message: 'Username is required', trigger: 'blur' },
    { min: 3, message: 'Username must be at least 3 characters', trigger: 'blur' },
    { max: 255, message: 'Username cannot exceed 255 characters', trigger: 'blur' },
    { pattern: /^[A-Za-z0-9_.-]+$/, message: 'Username may only contain letters, numbers, dots, dashes, and underscores', trigger: 'blur' },
  ],
  email: [
    { required: true, message: 'Email is required', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email address', trigger: ['blur', 'change'] },
    { max: 255, message: 'Email cannot exceed 255 characters', trigger: 'blur' },
  ],
  phone: [
    { required: true, message: 'Phone number is required', trigger: 'blur' },
    { max: 20, message: 'Phone number cannot exceed 20 characters', trigger: 'blur' },
    { pattern: /^\+?[0-9\s().-]{7,20}$/, message: 'Please enter a valid phone number', trigger: 'blur' },
  ],
  gender: [{ required: true, message: 'Gender is required', trigger: 'change' }],
  password: [
    { required: true, message: 'Password is required', trigger: 'blur' },
    { min: 6, message: 'Password must be at least 6 characters', trigger: 'blur' },
  ],
}

const loading = ref(false)
const errorMessage = ref('')


const shouldCompleteProfile = (profile: any) => {
  return Boolean(profile?.requires_profile_completion)
}


const submitSignup = async () => {
  errorMessage.value = ''
  const valid = await signupFormRef.value?.validate().catch(() => false)
  if (!valid) {
    return
  }

  loading.value = true
  try {
    const response: any = await $fetch(`${apiBase}/auth/register`, {
      method: 'POST',
      credentials: 'include',
      body: form
    })
    // applyAuthFromResponse(response)
    const profile = response?.data?.user ?? response?.data?.customer ?? null

    if (shouldCompleteProfile(profile) || response?.data?.requires_profile_completion) {
      authCompleteDialogOpen.value = true
      return
    }
    await router.replace('/auth/login')
  } catch (err: any) {
    const errors = err?.data?.errors
    const firstError = errors && typeof errors === 'object'
      ? Object.values(errors).flat()?.[0]
      : null
    errorMessage.value = String(firstError || err?.data?.message || 'Signup failed. Please try again.')
    authStore.resetAuth()
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
