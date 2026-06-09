<template>
    <div class="bg-surface-2 box-border flex items-center justify-center h-screen">
        <div class="grid grid-cols-1 lg:grid-cols-3  w-[100vw] h-[100vh]">
            <section class="bg-primary hidden lg:flex justify-center items-center">
                <img src="/img/auth/graphic1.svg" alt="Login graphic" format="webp" densities="x1" loading="lazy" />
            </section>
            <section class=" flex justify-center items-center col-span-2 ">
                <div class="w-[80vw] lg:w-[25vw] bg-surface p-6 rounded-element  space-y-6">
                    <header>
                        <p class="text-xl font-semibold">Set new password</p>
                    </header>
                    <el-form label-position="top" class="w-full grid grid-cols-1 items-center" autocomplete="off">
                        <input type="hidden" v-model="form.token" />
                        <input type="hidden" v-model="form.email" />
                        <el-form-item label="New Password" prop="new_password">
                            <BaseInput placeholder="Enter New Password" type="password" :prefix-icon="Key"
                                v-model="form.password" />
                        </el-form-item>
                        <el-form-item label="Password" prop="password">
                            <BaseInput placeholder="Confirm Password" type="password" :prefix-icon="Key"
                                v-model="form.password_confirmation" />
                        </el-form-item>
                    </el-form>
                    <div v-if="errors.password" class="text-red-500 text-xs mt-1">
                        {{ errors.password[0] }}
                    </div>

                    <div class="flex justify-center gap-8 items-center">
                        <BaseButton class="w-[300px]">
                            <NuxtLink to="/auth/login" class="text-xs ">
                                Back to login
                            </NuxtLink>
                        </BaseButton>
                        <BaseButton type="primary" class="w-[300px]" @click="submitForm">
                            Reset Paswword
                        </BaseButton>

                    </div>


                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">

import BaseButton from '~/components/ui/BaseButton.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter, useRuntimeConfig } from '#app'
import { Key } from '@element-plus/icons-vue'

definePageMeta({
    layout: 'guest',
    middleware: 'guest'
})

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
    type: '' as 'success' | 'error' | '',
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

<style scoped></style>