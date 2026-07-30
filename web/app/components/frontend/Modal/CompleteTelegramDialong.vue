<template>
    <el-dialog v-model="dialogOpen" width="460px" align-center :close-on-click-modal="false" title="Stay Updated"
        class="telegram-dialog" @closed="onDialogClosed">
        <div class="flex flex-col items-center gap-5 py-2 text-center">

            <!-- Connecting / waiting state -->
            <template v-if="connectingTelegram && !telegramLinked">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-100 border-t-blue-500" />
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-800">Waiting for Telegram...</p>
                    <p class="mt-1 text-sm text-gray-500">Tap <strong>Start</strong> in the bot to link your account.</p>
                </div>
                <div class="flex flex-col items-center gap-2 w-full">
                    <a :href="deepLink" target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg border border-[#0088CC] px-4 py-2 text-sm font-medium text-[#0088CC] hover:bg-blue-50 transition-colors"
                        @click="onOpenTelegram">
                        <Icon name="fa-brands:telegram-plane" class="text-base" />
                        Open Telegram
                    </a>
                    <el-button size="small" :loading="refreshingStatus" @click="refreshProfile">
                        Check connection
                    </el-button>
                </div>
            </template>

            <!-- Connected state -->
            <template v-else-if="telegramLinked">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-green-50">
                    <Icon name="mdi:check-circle" class="text-5xl text-green-500" />
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-800">Linked to Telegram</p>
                    <p v-if="telegramUsername" class="text-sm text-gray-500">&#64;{{ telegramUsername }}</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <el-switch v-model="alertsEnabled" @change="saveAlerts" />
                    Enable order notifications
                </label>
                <el-button type="primary" class="mt-1 w-full" @click="done">
                    Done
                </el-button>
            </template>

            <!-- Initial state: not connected -->
            <template v-else>
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-[#E8F4FD]">
                    <Icon name="fa-brands:telegram-plane" class="text-4xl text-[#0088CC]" />
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-800">Get notified on Telegram</p>
                    <p class="mt-1 text-sm leading-relaxed text-gray-500">
                        Receive real-time order updates:<br />
                        order confirmation &bull; payment received &bull; shipping &bull; delivery
                    </p>
                </div>
                <el-button type="primary" size="large" class="mt-2 w-full !text-base" :loading="loading"
                    @click="connectTelegram">
                    <Icon name="fa-brands:telegram-plane" class="mr-2 text-lg" />
                    Connect Telegram
                </el-button>
            </template>

            <p v-if="statusMessage" class="text-xs" :class="statusOk ? 'text-green-600' : 'text-amber-600'">
                {{ statusMessage }}
            </p>
        </div>
    </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '~/stores/authStore'

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const authStore = useAuthStore()
const { accessToken, userProfile } = storeToRefs(authStore)
const router = useRouter()

const telegramBotUsername = config.public.telegramBotUsername || 'DyzakTechStoreBot'

const loading = ref(false)
const connectingTelegram = ref(false)
const refreshingStatus = ref(false)
const statusMessage = ref('')
const statusOk = ref(false)
const deepLink = ref('')
let pollTimer: ReturnType<typeof setInterval> | null = null

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()
const dialogOpen = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
})

const alertsEnabled = ref(false)

const telegramLinked = computed(() => {
    const p = (userProfile.value || {}) as Record<string, any>
    return Boolean(p.telegram_user_id || p.telegram_chat_id)
})

const telegramUsername = computed(() => {
    const p = (userProfile.value || {}) as Record<string, any>
    return String(p.telegram_username || p.telegram_user_id || '')
})

const getHeaders = () => {
    return accessToken.value
        ? { Authorization: `Bearer ${accessToken.value}` }
        : undefined
}

const connectTelegram = async () => {
    statusMessage.value = ''
    loading.value = true
    try {
        const res: any = await $fetch(`${apiBase}/telegram/connect-link`, {
            method: 'POST',
            credentials: 'include',
            headers: getHeaders(),
        })
        const link = res?.data?.deep_link
        if (!link) {
            statusMessage.value = 'Unable to generate Telegram link. Try again.'
            return
        }
        deepLink.value = link
        loading.value = false
        connectingTelegram.value = true
        window.open(link, '_blank', 'noopener,noreferrer')
        startPolling()
    } catch (err: any) {
        statusMessage.value = err?.data?.message || 'Failed to connect. Try again.'
    } finally {
        loading.value = false
    }
}

const onOpenTelegram = () => {
    window.open(deepLink.value, '_blank', 'noopener,noreferrer')
}

const pollLink = async () => {
    return await $fetch(`${apiBase}/telegram/poll-link`, {
        method: 'POST',
        credentials: 'include',
        headers: getHeaders(),
    })
}

const fetchProfile = async () => {
    const res: any = await $fetch(`${apiBase}/profile`, {
        method: 'GET',
        credentials: 'include',
        headers: getHeaders(),
    })
    if (res?.data) {
        authStore.setUserProfile(res.data)
        alertsEnabled.value = Boolean(res.data.enable_telegram_alerts)
    }
}

const startPolling = () => {
    stopPolling()
    const startedAt = Date.now()
    pollTimer = setInterval(async () => {
        if (Date.now() - startedAt > 120_000) {
            stopPolling()
            statusMessage.value = 'Taking longer than expected? Tap "Check connection" below.'
            statusOk.value = false
            return
        }
        try {
            const res: any = await pollLink()
            if (res?.data?.linked || telegramLinked.value) {
                await fetchProfile()
                stopPolling()
                connectingTelegram.value = false
                statusMessage.value = ''
            }
        } catch {
            // keep polling
        }
    }, 3000)
}

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer)
        pollTimer = null
    }
}

const refreshProfile = async () => {
    refreshingStatus.value = true
    try {
        const res: any = await pollLink()
        await fetchProfile()
        if (res?.data?.linked || telegramLinked.value) {
            connectingTelegram.value = false
            statusMessage.value = ''
        } else {
            statusMessage.value = 'Not connected yet. Tap Open Telegram above and press Start.'
            statusOk.value = false
        }
    } catch {
        statusMessage.value = 'Check failed. Try again.'
    } finally {
        refreshingStatus.value = false
    }
}

const saveAlerts = async () => {
    try {
        await $fetch(`${apiBase}/profile`, {
            method: 'PUT',
            credentials: 'include',
            headers: getHeaders(),
            body: { enable_telegram_alerts: alertsEnabled.value },
        })
    } catch {
        alertsEnabled.value = !alertsEnabled.value
    }
}

const done = () => {
    dialogOpen.value = false
    router.replace('/')
}

const onDialogClosed = () => {
    stopPolling()
    connectingTelegram.value = false
    statusMessage.value = ''
    loading.value = false
}

watch(dialogOpen, (open) => {
    if (open) {
        alertsEnabled.value = Boolean((userProfile.value as any)?.enable_telegram_alerts)
        connectingTelegram.value = false
    } else {
        stopPolling()
    }
})
</script>

<style scoped>
.telegram-dialog :deep(.el-dialog__body) {
    padding-top: 8px;
}
.telegram-dialog :deep(.el-dialog__title) {
    font-weight: 600;
}
</style>
