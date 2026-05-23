<template>
    <div class="bg-surface-2 box-border flex items-center justify-center h-screen">
        <div class="grid grid-cols-1 lg:grid-cols-3  w-[100vw] h-[100vh]">
            <section class="bg-primary hidden lg:flex justify-center items-center">
                <NuxtImg src="/img/auth/graphic1.svg" alt="Login graphic" format="webp" loading="lazy" />
            </section>
            <section class=" flex justify-center items-center col-span-2 ">
                <div class="w-[80vw] lg:w-[30vw] bg-surface p-6 rounded-element  space-y-6">
                    <header>
                        <p class="text-xl font-semibold">Sign In to Your Account</p>
                    </header>
                    <el-form label-position="top" class="w-full grid grid-cols-1 items-center" autocomplete="off">
                        <el-form-item label="Username" prop="userName">
                            <BaseInput placeholder="Your username" type="text" :prefix-icon="User" v-model="userName" />
                        </el-form-item>
                        <el-form-item label="Password" prop="password">
                            <BaseInput placeholder="Your Password" type="password" :prefix-icon="Key"
                                v-model="password" />
                        </el-form-item>
                    </el-form>
                    <div v-if="errorMessage" class="text-danger text-xs grid place-items-center">{{ errorMessage }}
                    </div>

                    <div class="flex justify-center gap-8 items-center">
                        <BaseButton class="w-[300px]">
                            <NuxtLink to="/auth/forgot-password" class="text-xs ">
                                Forgot password?
                            </NuxtLink>
                        </BaseButton>
                        <BaseButton type="primary" class="w-[300px]" @click="submitLogin">
                            Sign In
                        </BaseButton>

                    </div>

                    <div class="flex justify-center gap-3 flex-col items-center">
                        <p class="text-xs">--or continue with--</p>
                        <div class="flex gap-3">
                            <BaseButton class="!text-xl !text-[#1877F2]" @click="onFacebookLogin">
                                <Icon name="flowbite:facebook-solid" />
                            </BaseButton>
                            <BaseButton class="!text-xl" @click="onGoogleLogin">
                                <Icon name="material-icon-theme:google" />
                            </BaseButton>
                            <BaseButton class="!text-xl" @click="onGithubLogin">
                                <Icon name="flowbite:github-solid" />
                            </BaseButton>
                            <BaseButton class="!text-xl !text-[#0088CC]" @click="onTelegramLogin">
                                <Icon name="fa-brands:telegram-plane" />
                            </BaseButton>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <p class="text-xs">Don't have an account?
                            </p>
                            <NuxtLink to="/auth/signup" class="text-xs hover:text-primary/70 underline">Create
                                account
                            </Nuxtlink>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <CompleteOAuthDialog v-model="oAuthCompleteDialogOpen" />
        <CompleteTelegramDialong v-model="telegramCompleteDialogOpen" />
    </div>
</template>

<script setup lang="ts">
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import { User, Key } from '@element-plus/icons-vue'
import CompleteOAuthDialog from '~/components/frontend/Modal/CompleteOAuthDialog.vue'
import CompleteTelegramDialong from '~/components/frontend/Modal/CompleteTelegramDialong.vue';
import { useAuthStore } from '~/stores/authStore'

declare global {
    interface Window {
        google?: any
        FB?: any
        fbAsyncInit?: () => void
    }
}

definePageMeta({
    layout: 'guest',
    middleware: 'guest'
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const frontendBase = (config.public.frontendUrl || 'http://localhost:3000').replace(/\/$/, '')
const backendOrigin = (() => {
    try {
        const url = new URL(apiBase)
        return `${url.protocol}//${url.host}`
    } catch {
        return 'http://localhost:8000'
    }
})()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const userName = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')
const oAuthCompleteDialogOpen = ref(false)
const telegramCompleteDialogOpen = ref(false)

const applyAuthFromResponse = (response: any) => {
    const token = response?.data?.access_token ?? null
    const profile = response?.data?.user ?? response?.data?.customer ?? null

    authStore.setAccessToken(token)
    authStore.setAuthenticated(Boolean(token) || Boolean(profile))
    authStore.setUserProfile(profile)
}

const shouldOauthCompleteProfile = (profile: any) => {
    return Boolean(profile?.requires_profile_completion)
}
const shouldTelegramAuthCompleteProfile = (profile: any) => {
    return Boolean(profile?.requires_telegram_completion)
}

const submitLogin = async () => {
    errorMessage.value = ''
    if (!userName.value || !password.value) {
        errorMessage.value = 'Username and password are required'
        return
    }
    loading.value = true
    try {

        const response: any = await $fetch(`${apiBase}/auth/login`, {
            method: 'POST',
            credentials: 'include',
            body: {
                user_name: userName.value,
                password: password.value
            }
        })
        applyAuthFromResponse(response)
        const profile = response?.data?.customer ?? response?.data?.user ?? null

        if (shouldOauthCompleteProfile(profile) || response?.data?.requires_profile_completion) {
            oAuthCompleteDialogOpen.value = true
            return
        }

        if (shouldTelegramAuthCompleteProfile(profile) || response?.data?.requires_telegram_completion) {
            telegramCompleteDialogOpen.value = true
            return
        }
        ElMessage({ message: 'Login successfully', type: 'success' })
        await router.replace('/')
    } catch (err: any) {
        errorMessage.value = err?.data?.message || 'Login failed. Please try again.'
        authStore.resetAuth()
    } finally {
        loading.value = false
    }
}

const decodeCustomerPayload = (raw: string | null | undefined) => {
    if (!raw) {
        return null
    }
    try {
        const decoded = atob(raw)
        const parsed = JSON.parse(decoded)
        return typeof parsed === 'object' && parsed ? parsed : null
    } catch {
        return null
    }
}

const handleOAuthToken = async (token: string, encodedCustomer?: string | null) => {
    errorMessage.value = ''
    try {
        await $fetch(`${apiBase}/auth/oauth/cookie`, {
            method: 'POST',
            credentials: 'include',
            body: { token },
        })

        const customer = decodeCustomerPayload(encodedCustomer)
        authStore.setAccessToken(token)
        authStore.setAuthenticated(Boolean(token) || Boolean(customer))
        if (customer) {
            authStore.setUserProfile(customer)
        }

        const profileResponse: any = await $fetch(`${apiBase}/profile`, {
            method: 'GET',
            credentials: 'include',
            headers: token ? { Authorization: `Bearer ${token}` } : undefined,
        })
        const profile = profileResponse?.data || customer
        if (profile) {
            authStore.setUserProfile(profile)
        }

        if (shouldOauthCompleteProfile(profile) || profileResponse?.data?.requires_profile_completion) {
            oAuthCompleteDialogOpen.value = true
            return
        }
        await router.replace('/')
    } catch (err: any) {
        errorMessage.value = err?.data?.message || 'OAuth login failed'
        authStore.resetAuth()
    }
}

onMounted(async () => {
    if (import.meta.client) {
        const tokenParam = route.query.token
        const customerParam = route.query.customer
        const oauthToken = Array.isArray(tokenParam) ? tokenParam[0] : tokenParam
        const oauthCustomer = Array.isArray(customerParam) ? customerParam[0] : customerParam
        if (oauthToken) {
            await handleOAuthToken(oauthToken, oauthCustomer)
            return
        }

        const errorParam = route.query.error
        const error = Array.isArray(errorParam) ? errorParam[0] : errorParam
        if (error) {
            errorMessage.value = 'OAuth login failed. Please try again.'
        }

        const successParam = route.query.success
        const success = Array.isArray(successParam) ? successParam[0] : successParam
        if (success) {
            authStore.setAuthenticated(true)
            router.replace('/')
            return
        }
    }
})

const onGoogleLogin = async () => {
    errorMessage.value = ''
    try {
        await startOAuthLogin('google')
    } catch (e: any) {
        errorMessage.value = e?.message || 'Failed to start Google login'
    }
}

const onFacebookLogin = async () => {
    errorMessage.value = ''
    try {
        await startOAuthLogin('facebook')
    } catch (e: any) {
        errorMessage.value = e?.message || 'Failed to start Facebook login'
    }
}

const onGithubLogin = async () => {
    errorMessage.value = ''
    try {
        await startOAuthLogin('github')
    } catch (e: any) {
        errorMessage.value = e?.message || 'Failed to start GitHub login'
    }
}

const onTelegramLogin = async () => {
    errorMessage.value = ''
    try {
        await startOAuthLogin('telegram')
    } catch (e: any) {
        errorMessage.value = e?.message || 'Failed to start Telegram login'
    }
}

const startOAuthLogin = async (provider: 'google' | 'facebook' | 'github' | 'telegram') => {
    const redirectUrl = new URL(`${backendOrigin}/auth/${provider}/redirect`)
    redirectUrl.searchParams.set('redirect', `${frontendBase}/auth/login`)
    window.location.href = redirectUrl.toString()
}
</script>

<style scoped></style>
