<template>
    <div class="space-y-6">
        <HeaderBreadCrumb title="Edit Customer">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/customers' }">Customers</el-breadcrumb-item>
            <el-breadcrumb-item>Edit Customer</el-breadcrumb-item>
        </HeaderBreadCrumb>

        <BaseCard>
            <template #header>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="m-0 text-lg font-bold text-slate-950">Customer profile</h2>
                        <p class="m-0 text-sm text-slate-500">
                            Update personal details, contact information, and account status.
                        </p>
                    </div>

                    <el-tag :type="form.status === customerStatus.Active ? 'success' : 'danger'" effect="light">
                        {{ getDisplayCustomerStatus(form.status) }}
                    </el-tag>
                </div>
            </template>

            <LoadingPage v-if="loading" embedded :rows="8" />

            <div v-else class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                <section class="rounded-3xl border border-border bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-slate-950">Avatar & account</h3>

                    <BaseImageUpload v-model="imagePreview" width="100%" height="280px" :max-size-m-b="5"
                        @change="handleImageChange">
                        <template #file="{ file, handlePictureCardPreview, handleRemove, disabled }">
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-3">
                                <img :src="file.url" alt="Customer avatar"
                                    class="h-64 w-full rounded-xl object-cover" />

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <button type="button"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:border-slate-400"
                                        @click="handlePictureCardPreview(file)">
                                        Preview
                                    </button>

                                    <button v-if="!disabled" type="button"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:border-slate-400"
                                        @click="handleRemove(file)">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </BaseImageUpload>

                    <div class="mt-4 space-y-4 rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="m-0 text-sm font-medium text-slate-700">Account status</p>
                                <p class="m-0 text-xs text-slate-500">Toggle active or disable access.</p>
                            </div>
                            <el-switch v-model="statusEnabled" active-text="Active" inactive-text="Disabled" />
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="m-0 text-sm font-medium text-slate-700">Telegram alerts</p>
                                <p class="m-0 text-xs text-slate-500">Allow order notifications on Telegram.</p>
                            </div>
                            <el-switch v-model="form.enable_telegram_alerts" active-text="On" inactive-text="Off" />
                        </div>
                    </div>

                    <div v-if="oauthAccounts.length" class="mt-4 space-y-3">
                        <p class="m-0 text-sm font-semibold text-slate-950">OAuth accounts</p>
                        <div class="space-y-2">
                            <div v-for="account in oauthAccounts" :key="account.id"
                                class="rounded-2xl border border-slate-200 bg-white p-3 text-sm">
                                <p class="m-0 font-semibold text-slate-900">{{ account.provider }}</p>
                                <p class="m-0 text-xs text-slate-500">{{ account.email || account.provider_user_id ||
                                    'Linked account' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-border bg-white p-6 shadow-sm">
                    <el-form label-position="top">
                        <div class="grid gap-5 md:grid-cols-2">
                            <el-form-item label="Full Name">
                                <BaseInput v-model="form.full_name" placeholder="Enter full name" />
                            </el-form-item>

                            <el-form-item label="Gender">
                                <BaseSelect v-model="form.gender" :options="genderOptions"
                                    placeholder="Select gender" />
                            </el-form-item>

                            <el-form-item label="Date of Birth">
                                <BaseInput v-model="form.dob" placeholder="YYYY-MM-DD" />
                            </el-form-item>

                            <el-form-item label="User Name">
                                <BaseInput v-model="form.user_name" placeholder="Enter username" />
                            </el-form-item>

                            <el-form-item label="Email">
                                <BaseInput v-model="form.email" placeholder="Enter email address" />
                            </el-form-item>

                            <el-form-item label="Phone">
                                <BaseInput v-model="form.phone" placeholder="Enter phone number" />
                            </el-form-item>

                            <el-form-item label="Telegram Username">
                                <BaseInput v-model="form.telegram_username" placeholder="@username" />
                            </el-form-item>

                            <el-form-item label="Address" class="md:col-span-2">
                                <BaseInput v-model="form.address" type="textarea" :rows="4"
                                    placeholder="Enter address" />
                            </el-form-item>
                        </div>
                    </el-form>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <BaseButton @click="goBack">Cancel</BaseButton>
                        <BaseButton type="primary" :loading="saving" @click="submitForm">
                            Save Changes
                        </BaseButton>
                    </div>
                </section>
            </div>
        </BaseCard>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseImageUpload from '~/components/ui/BaseImageUpload.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import { useAdminCustomer } from '~/composables/useAdminCustomer'
import { customerStatus, getDisplayCustomerStatus } from '~/enums/customerStatus'
import LoadingPage from '~/components/shares/LoadingPage.vue'

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

const route = useRoute()
const router = useRouter()

const {
    saving,
    customerDetail,
    fetchCustomerDetail,
    submitCustomer,
} = useAdminCustomer()

const loading = ref(true)
const imagePreview = ref('')
const selectedImageFile = ref<File | null>(null)
const initialImagePreview = ref('')

const form = reactive({
    full_name: '',
    gender: '' as 'male' | 'female' | '',
    dob: '',
    user_name: '',
    email: '',
    phone: '',
    address: '',
    telegram_username: '',
    enable_telegram_alerts: false,
    status: 'active' as 'active' | 'inactive' | 'active',
})

const genderOptions = [
    { id: 'male', label: 'Male' },
    { id: 'female', label: 'Female' },
]

const customerId = computed(() => String(route.params.id ?? ''))

const oauthAccounts = computed(() => customerDetail.value?.oauth_accounts || [])
const statusEnabled = computed({
    get: () => form.status === customerStatus.Active,
    set: (value: boolean) => {
        form.status = value ? customerStatus.Active : customerStatus.Inactive
    },
})

const syncFromCustomer = () => {
    const customer = customerDetail.value

    form.full_name = customer?.full_name || ''
    form.gender = customer?.gender || ''
    form.dob = customer?.dob || ''
    form.user_name = customer?.user_name || ''
    form.email = customer?.email || ''
    form.phone = customer?.phone || ''
    form.address = customer?.address || ''
    form.telegram_username = customer?.telegram_username || ''
    form.enable_telegram_alerts = Boolean(customer?.enable_telegram_alerts)
    form.status = customer?.status || customerStatus.Active

    selectedImageFile.value = null
    initialImagePreview.value = customer?.avatar_url || ''
    imagePreview.value = initialImagePreview.value
}

const handleImageChange = (file: File | null) => {
    selectedImageFile.value = file
}

const submitForm = async () => {
    await submitCustomer({
        customerId: customerId.value,
        form: {
            full_name: form.full_name.trim(),
            gender: form.gender,
            dob: form.dob.trim(),
            user_name: form.user_name.trim(),
            email: form.email.trim(),
            phone: form.phone.trim(),
            address: form.address.trim(),
            telegram_username: form.telegram_username.trim(),
            enable_telegram_alerts: form.enable_telegram_alerts,
            status: form.status,
        },
        image: selectedImageFile.value,
        remove_image: Boolean(initialImagePreview.value && !imagePreview.value && !selectedImageFile.value),
    })

    await router.push('/admin/customers')
}

const goBack = () => {
    router.push('/admin/customers')
}

const loadCustomer = async () => {
    loading.value = true

    try {
        await fetchCustomerDetail(customerId.value)
        syncFromCustomer()
    } catch (error) {
        ElMessage.error('Failed to load customer details.')
        await router.push('/admin/customers')
    } finally {
        loading.value = false
    }
}

watch(
    () => customerDetail.value,
    () => {
        if (customerDetail.value) {
            syncFromCustomer()
        }
    },
)

onMounted(() => {
    void loadCustomer()
})
</script>

<style scoped></style>
