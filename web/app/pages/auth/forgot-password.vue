<template>
  <div class="bg-surface-2 box-border flex items-center justify-center h-screen">
    <div class="grid grid-cols-1 lg:grid-cols-3  w-[100vw] h-[100vh]">
      <section class="bg-primary hidden lg:flex justify-center items-center">
        <NuxtImg src="/img/auth/graphic1.svg" alt="Login graphic" format="webp" loading="lazy" />
      </section>
      <section class=" flex justify-center items-center col-span-2 ">
        <div class="w-[80vw] lg:w-[30vw] bg-surface p-6 rounded-element  space-y-6">
          <header>
            <p class="text-xl font-semibold">Forgot password</p>
          </header>
          <el-form label-position="top" class="w-full grid grid-cols-1 items-center" autocomplete="off">

            <el-form-item label="New Password" prop="new_password">
              <BaseInput placeholder="you@example.com" type="email" :prefix-icon="MessageBox" v-model="email" />
            </el-form-item>
          </el-form>


          <div class="flex justify-center gap-8 items-center">
            <BaseButton class="w-[300px]">
              <NuxtLink to="/auth/login" class="text-xs ">
                Back to Sign in
              </NuxtLink>
            </BaseButton>
            <BaseButton type="primary" class="w-[300px]" @click="submitForgotPassword">
              {{ loading ? 'Sending...' : 'Send reset link' }} Reset Paswword
            </BaseButton>

          </div>


        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { MessageBox } from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'

definePageMeta({
  layout: 'guest',
  middleware: ['guest']
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const email = ref('')
const loading = ref(false)


const submitForgotPassword = async () => {

  if (!email.value) {
    ElMessage({ message: 'Email is required', type: 'error' })
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

    ElMessage({ message: response?.message || 'If this email exists, a reset link has been sent.', type: 'success' })
  } catch (error: any) {
    ElMessage({ message: error?.data?.message || 'Cannot send reset link now.', type: 'error' })
  } finally {
    loading.value = false
  }
}
</script>
