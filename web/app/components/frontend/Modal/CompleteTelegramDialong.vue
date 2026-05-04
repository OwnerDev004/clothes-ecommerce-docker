<template>
    <el-dialog v-model="dialogOpen" width="680px" :close-on-click-modal="false" title="Complete Your Profile"
        class="profile-dialog" @closed="onProfileDialogClosed">
        <div class="space-y-4">

            <el-alert v-if="authCompleteFormMessage" :title="authCompleteFormMessage"
                :type="authCompleteFormMessageType" :closable="false" show-icon />

            <el-form ref="profileFormRef" :model="authCompleteForm" :rules="authCompleteRules" label-position="top"
                class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <el-form-item label="Telegram Username">
                    <el-input v-model="authCompleteForm.telegram_username" />
                </el-form-item>
                <el-form-item label="Enable Telegram Alerts">
                    <el-switch v-model="authCompleteForm.enable_telegram_alerts" />
                </el-form-item>

                <div class="md:col-span-2 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="font-medium text-gray-800">Telegram linking</div>
                            <div class="text-gray-600">
                                {{ telegramLinked ? 'Linked' : 'Not linked yet. Tap Connect and press Start in the bot.'
                                }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <el-button size="small" plain :loading="connectingTelegram" @click="connectTelegram">
                                Connect Telegram
                            </el-button>
                            <el-button size="small" plain @click="refreshProfile">
                                Refresh status
                            </el-button>
                        </div>
                    </div>
                    <p v-if="telegramStatusMessage" class="mt-2 text-xs text-gray-600">
                        {{ telegramStatusMessage }}
                    </p>
                </div>

            </el-form>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <el-button @click="dialogOpen = false">Cancel</el-button>
                <el-button type="primary" :loading="savingAuthComplete" @click="submitAuthComplete">
                    Save changes
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus';
import { ref, reactive, computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '~/stores/authStore'

const savingAuthComplete = ref(false);
const authCompleteFormMessage = ref('');
const authCompleteFormMessageType = ref<'success' | 'warning' | 'error' | 'info'>('info');
const telegramStatusMessage = ref('');
const connectingTelegram = ref(false);
const config = useRuntimeConfig();
const authStore = useAuthStore()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '');
const { accessToken, userProfile } = storeToRefs(authStore)
const profileFormRef = ref<FormInstance>()
const router = useRouter()
type authCompleteForm = {
    telegram_username: string;
    enable_telegram_alerts: boolean;
};

const authCompleteRules: FormRules<authCompleteForm> = {
    telegram_username: [
        { max: 255, message: 'Telegram Username is too long', trigger: 'blur' },
    ]
};

const authCompleteForm = reactive<authCompleteForm>({
    telegram_username: '',
    enable_telegram_alerts: false,
});

const props = defineProps<{ modelValue: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>();
const dialogOpen = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
});

const telegramLinked = computed(() => {
    const profile = (userProfile.value || {}) as Record<string, any>
    return Boolean(profile.telegram_user_id || profile.telegram_chat_id)
})

// getAuthHeaders
const getAuthHeaders = () => {
    return accessToken.value
        ? { Authorization: `Bearer ${accessToken.value}` }
        : undefined
}

const fillFromProfile = () => {
    const profile = (userProfile.value || {}) as Record<string, any>
    authCompleteForm.telegram_username = String(profile.telegram_username || '')
    authCompleteForm.enable_telegram_alerts = Boolean(profile.enable_telegram_alerts)
}

const pollTelegramLink = async () => {
    await $fetch(`${apiBase}/telegram/poll-link`, {
        method: 'POST',
        credentials: 'include',
        headers: getAuthHeaders(),
    })
}

const fetchProfile = async () => {
    const response: any = await $fetch(`${apiBase}/profile`, {
        method: 'GET',
        credentials: 'include',
        headers: getAuthHeaders(),
    })
    authStore.setUserProfile(response?.data || null)
}

const refreshProfile = async () => {
    try {
        await pollTelegramLink()
        await fetchProfile()
    } catch {
        // No-op: handled by parent auth logic
    }
}

const connectTelegram = async () => {
    telegramStatusMessage.value = ''
    connectingTelegram.value = true
    try {
        const response: any = await $fetch(`${apiBase}/telegram/connect-link`, {
            method: 'POST',
            credentials: 'include',
            headers: getAuthHeaders(),
        })
        const deepLink = response?.data?.deep_link
        console.log(deepLink);

        if (!deepLink) {
            telegramStatusMessage.value = 'Unable to generate Telegram link.'
            return
        }
        window.open(deepLink, '_blank', 'noopener,noreferrer')
        telegramStatusMessage.value = 'Telegram opened. Tap Start in the bot, then click Refresh.'
    } catch (err: any) {
        telegramStatusMessage.value = err?.data?.message || 'Failed to connect Telegram.'
    } finally {
        connectingTelegram.value = false
    }
}

watch(dialogOpen, (open) => {
    if (open) {
        fillFromProfile()
        telegramStatusMessage.value = ''
    }
})

const submitAuthComplete = async () => {
    if (!profileFormRef.value) {
        return
    }

    const valid = await profileFormRef.value.validate().catch(() => false)
    if (!valid) {
        return
    }

    savingAuthComplete.value = true;
    try {
        await pollTelegramLink()
        await fetchProfile()
        const response: any = await $fetch(`${apiBase}/profile`, {
            method: 'PUT',
            credentials: 'include',
            headers: getAuthHeaders(),
            body: {
                telegram_username: authCompleteForm.telegram_username,
                enable_telegram_alerts: authCompleteForm.enable_telegram_alerts,

            },
        });
        if (response?.data) {
            authStore.setUserProfile(response.data)
        }
        authCompleteFormMessage.value = 'Profile updated successfully';
        authCompleteFormMessageType.value = 'success';
        emit('update:modelValue', false)
        router.replace('/')
    } catch (error: any) {
        const statusCode = error?.statusCode ?? error?.status
        if (statusCode === 401 || statusCode === 403) {
            authCompleteFormMessage.value = 'Session expired. Please login again.';
            authCompleteFormMessageType.value = 'error';
            authStore.resetAuth()
            return
        }
        const apiMessage = error?.data?.message
        const errors = error?.data?.errors
        if (errors && typeof errors === 'object') {
            const firstKey = Object.keys(errors)[0]
            const firstMessage = firstKey ? errors[firstKey]?.[0] : null
            if (firstMessage) {
                authCompleteFormMessage.value = firstMessage
                authCompleteFormMessageType.value = 'error'
                return
            }
        }
        authCompleteFormMessage.value = apiMessage || 'Failed to update profile';
        authCompleteFormMessageType.value = 'error';
    } finally {
        savingAuthComplete.value = false;
    }
};

const onProfileDialogClosed = () => {
    authCompleteFormMessage.value = '';
    authCompleteFormMessageType.value = 'info';
    telegramStatusMessage.value = '';
};
</script>

<style scoped></style>
