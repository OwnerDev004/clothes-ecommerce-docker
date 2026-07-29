<template>
    <el-dialog v-model="dialogOpen" :close-on-click-modal="false" title="Edit Profile" class="profile-dialog  !w-fit"
        align-center @closed="onProfileDialogClosed">
        <div class="space-y-4">
            <el-alert title="Username and password updates are not available in the current API yet." type="info"
                :closable="false" show-icon />

            <el-alert v-if="profileFormMessage" :title="profileFormMessage" :type="profileFormMessageType"
                :closable="false" show-icon />

            <div class="flex items-center gap-4 rounded-xl bg-gray-50 p-4">
                <div class="h-16 w-16 overflow-hidden rounded-full border border-gray-200 bg-white">
                    <img v-if="avatarPreview" :src="avatarPreview" alt="Profile avatar"
                        class="h-full w-full object-cover">
                    <div v-else
                        class="flex h-full w-full items-center justify-center bg-gray-100 text-lg font-semibold text-gray-500">
                        {{ userInitials }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <el-upload :auto-upload="false" :show-file-list="false" :on-change="onAvatarPicked"
                        accept="image/jpeg,image/png,image/webp,image/gif">
                        <el-button type="primary" plain>Select image</el-button>
                    </el-upload>
                    <el-button type="danger" plain :disabled="!avatarPreview && !selectedAvatarFile"
                        @click="removeAvatar">
                        Remove
                    </el-button>
                </div>
            </div>

            <el-form ref="profileFormRef" :model="profileForm" :rules="profileRules" label-position="top"
                class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <el-form-item label="Username">
                    <el-input v-model="profileForm.user_name" disabled />
                </el-form-item>

                <el-form-item label="Full Name" prop="full_name">
                    <el-input v-model="profileForm.full_name" placeholder="Enter full name" />
                </el-form-item>

                <el-form-item label="Email" prop="email">
                    <el-input v-model="profileForm.email" placeholder="Enter email" />
                </el-form-item>

                <el-form-item label="Phone" prop="phone">
                    <el-input v-model="profileForm.phone" placeholder="Enter phone number" />
                </el-form-item>

                <el-form-item label="Gender" prop="gender">
                    <el-select v-model="profileForm.gender" placeholder="Select gender" clearable class="w-full">
                        <el-option label="Male" value="male" />
                        <el-option label="Female" value="female" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Date of Birth" prop="dob">
                    <el-date-picker v-model="profileForm.dob" type="date" value-format="YYYY-MM-DD" format="YYYY-MM-DD"
                        placeholder="Select date" class="w-full" />
                </el-form-item>

                <el-form-item label="Address" prop="address" class="md:col-span-2">
                    <el-input v-model="profileForm.address" type="textarea" :rows="3"
                        placeholder="Enter your address" />
                </el-form-item>

                <el-form-item label="Password" class="md:col-span-2">
                    <el-input disabled placeholder="Password change API is not available yet" />
                </el-form-item>
            </el-form>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <el-button @click="dialogOpen = false">Cancel</el-button>
                <el-button type="primary" :loading="savingProfile" @click="submitProfileUpdate">
                    Save changes
                </el-button>
            </div>
        </template>
    </el-dialog>

</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { computed, reactive, ref, watch } from 'vue'
import type { FormInstance, FormRules, UploadProps } from 'element-plus'
import { useAuthStore } from '~/stores/authStore'

const props = defineProps<{
    modelValue: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()

const dialogOpen = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const authStore = useAuthStore()
const { isAuthenticated, accessToken, userProfile } = storeToRefs(authStore)
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const savingProfile = ref(false)
const profileFormRef = ref<FormInstance>()
const profileFormMessage = ref('')
const profileFormMessageType = ref<'success' | 'warning' | 'error' | 'info'>('info')
const selectedAvatarFile = ref<File | null>(null)
const avatarPreview = ref('')
const shouldDeleteAvatar = ref(false)

type ProfileForm = {
    user_name: string
    full_name: string
    email: string
    phone: string
    gender: '' | 'male' | 'female'
    dob: string
    address: string
}

const profileForm = reactive<ProfileForm>({
    user_name: '',
    full_name: '',
    email: '',
    phone: '',
    gender: '',
    dob: '',
    address: '',
})

const profileRules: FormRules<ProfileForm> = {
    full_name: [
        { max: 255, message: 'Full name is too long', trigger: 'blur' }
    ],
    email: [
        { type: 'email', message: 'Email is invalid', trigger: 'blur' }
    ],
    phone: [
        { max: 20, message: 'Phone number is too long', trigger: 'blur' }
    ],
    address: [
        { max: 500, message: 'Address is too long', trigger: 'blur' }
    ],
    gender: [
        {
            validator: (_rule, value, callback) => {
                if (!value || value === 'male' || value === 'female') {
                    callback()
                    return
                }
                callback(new Error('Gender must be male or female'))
            },
            trigger: 'change'
        }
    ]
}

const userDisplayName = computed(() => {
    const profile = userProfile.value || {}
    return (
        (profile.full_name as string | undefined) ||
        (profile.name as string | undefined) ||
        (profile.email as string | undefined) ||
        'Account'
    )
})

const userInitials = computed(() => {
    const normalized = userDisplayName.value.trim()
    if (!normalized) {
        return 'AC'
    }

    const parts = normalized.split(/\s+/).filter(Boolean)
    if (parts.length === 1) {
        return parts[0]?.slice(0, 2).toUpperCase()
    }

    return `${parts?.[0]?.[0]}${parts?.[1]?.[0]}`.toUpperCase()
})

const getAuthHeaders = () => {
    return accessToken.value
        ? { Authorization: `Bearer ${accessToken.value}` }
        : undefined
}

const fillProfileFormFromStore = () => {
    const profile = (userProfile.value || {}) as Record<string, any>
    profileForm.user_name = String(profile.user_name || '')
    profileForm.full_name = String(profile.full_name || profile.name || '')
    profileForm.email = String(profile.email || '')
    profileForm.phone = String(profile.phone || '')
    profileForm.gender = (profile.gender === 'male' || profile.gender === 'female') ? profile.gender : ''
    profileForm.dob = String(profile.dob || '')
    profileForm.address = String(profile.address || '')
    avatarPreview.value = String(profile.avatar_url || '')
}

const hydrateProfile = async () => {
    if (!accessToken.value && !isAuthenticated.value) {
        return
    }

    try {
        const response: any = await $fetch(`${apiBase}/profile`, {
            method: 'GET',
            credentials: 'include',
            headers: getAuthHeaders()
        })
        authStore.setAuthenticated(true)
        authStore.setUserProfile(response?.data || null)
    } catch (err: any) {
        const statusCode = err?.statusCode ?? err?.status
        if (statusCode === 401 || statusCode === 403) {
            authStore.resetAuth()
        }
    }
}

const onAvatarPicked: UploadProps['onChange'] = (uploadFile) => {
    const raw = uploadFile.raw
    if (!raw) {
        return
    }

    selectedAvatarFile.value = raw
    shouldDeleteAvatar.value = false

    const reader = new FileReader()
    reader.onload = () => {
        avatarPreview.value = String(reader.result || '')
    }
    reader.readAsDataURL(raw)
}

const removeAvatar = () => {
    selectedAvatarFile.value = null
    avatarPreview.value = ''
    shouldDeleteAvatar.value = true
}

const onProfileDialogClosed = () => {
    selectedAvatarFile.value = null
    shouldDeleteAvatar.value = false
    savingProfile.value = false
    profileFormMessage.value = ''
}

const submitProfileUpdate = async () => {
    if (!profileFormRef.value) {
        return
    }

    const valid = await profileFormRef.value.validate().catch(() => false)
    if (!valid) {
        return
    }

    savingProfile.value = true
    profileFormMessage.value = ''

    try {
        const headers = getAuthHeaders()
        const profilePayload = {
            full_name: profileForm.full_name || null,
            email: profileForm.email || null,
            phone: profileForm.phone || null,
            gender: profileForm.gender || null,
            dob: profileForm.dob || null,
            address: profileForm.address || null,
        }

        let latestProfile: Record<string, any> | null = null

        const profileResponse: any = await $fetch(`${apiBase}/profile`, {
            method: 'PUT',
            credentials: 'include',
            headers,
            body: profilePayload
        })
        latestProfile = profileResponse?.data || latestProfile

        if (shouldDeleteAvatar.value) {
            const deleteAvatarResponse: any = await $fetch(`${apiBase}/delete_avatar`, {
                method: 'POST',
                credentials: 'include',
                headers
            })
            latestProfile = deleteAvatarResponse?.data || latestProfile
        }

        if (selectedAvatarFile.value) {
            const avatarFormData = new FormData()
            avatarFormData.append('avatar', selectedAvatarFile.value)
            const avatarResponse: any = await $fetch(`${apiBase}/change_avatar`, {
                method: 'POST',
                credentials: 'include',
                headers,
                body: avatarFormData
            })
            latestProfile = avatarResponse?.data || latestProfile
        }

        if (latestProfile) {
            authStore.setUserProfile(latestProfile)
            avatarPreview.value = String(latestProfile.avatar_url || '')
        }

        await hydrateProfile()
        fillProfileFormFromStore()
        profileFormMessageType.value = 'success'
        profileFormMessage.value = 'Profile updated successfully.'
        shouldDeleteAvatar.value = false
        selectedAvatarFile.value = null
    } catch (err: any) {
        profileFormMessageType.value = 'error'
        profileFormMessage.value = err?.data?.message || 'Failed to update profile.'
    } finally {
        savingProfile.value = false
    }
}

watch(dialogOpen, async (isOpen) => {
    if (!isOpen) {
        return
    }

    profileFormMessage.value = ''
    shouldDeleteAvatar.value = false
    selectedAvatarFile.value = null
    await hydrateProfile()
    fillProfileFormFromStore()
})
</script>

<style scoped></style>
