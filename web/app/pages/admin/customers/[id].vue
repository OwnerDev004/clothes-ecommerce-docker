<template>
  <div class="space-y-6">
    <HeaderBreadCrumb title="Customer Detail">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/customers' }">Customers</el-breadcrumb-item>
      <el-breadcrumb-item>Customer Detail</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <BaseCard>
      <template #header>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="m-0 text-lg font-bold text-slate-950">Customer detail</h2>
            <p class="m-0 text-sm text-slate-500">
              Review profile data, linked accounts, and support-related information.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <el-tag :type="customer?.status === customerStatus.Active ? 'success' : 'danger'" effect="light">
              {{ customer ? getDisplayCustomerStatus(customer.status) : 'Unknown' }}
            </el-tag>
            <BaseButton @click="goBack">Back</BaseButton>
            <BaseButton type="primary" @click="goEdit">Edit</BaseButton>
          </div>
        </div>
      </template>

      <LoadingPage v-if="loading" embedded :rows="10" />

      <div v-else-if="customer" class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
        <section class="rounded-3xl border border-border bg-white p-6 shadow-sm">
          <div class="space-y-4">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
              <img v-if="customer.avatar_url" :src="customer.avatar_url" alt="Customer avatar"
                class="h-72 w-full object-cover" />
              <div v-else class="grid h-72 place-items-center">
                <div class="text-center">
                  <p class="m-0 text-5xl font-black text-slate-300">?</p>
                  <p class="m-0 mt-2 text-sm text-slate-500">No avatar uploaded</p>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <h3 class="m-0 text-xl font-bold text-slate-950">{{ customer.full_name || 'No name' }}</h3>
              <p class="m-0 text-sm text-slate-500">@{{ customer.user_name }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
              <p class="m-0 text-xs uppercase tracking-wide text-slate-500">Quick Summary</p>
              <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p class="m-0">Email: {{ customer.email || 'No email' }}</p>
                <p class="m-0">Phone: {{ customer.phone }}</p>
                <p class="m-0">Telegram: {{ customer.telegram_username || 'Not linked' }}</p>
                <p class="m-0">Alerts: {{ customer.enable_telegram_alerts ? 'Enabled' : 'Disabled' }}</p>
              </div>
            </div>
          </div>
        </section>

        <section class="space-y-6">
          <BaseCard body-class="p-0">
            <div class="grid gap-0 md:grid-cols-2">
              <div class="border-b border-border p-6 md:border-b-0 md:border-r">
                <h3 class="mb-4 text-base font-semibold text-slate-950">Profile Information</h3>
                <div class="grid gap-4 text-sm">
                  <div>
                    <p class="m-0 text-slate-500">Full name</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.full_name || 'None' }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Gender</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.gender || 'None' }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Date of birth</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.dob || 'None' }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Address</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.address || 'None' }}</p>
                  </div>
                </div>
              </div>

              <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-slate-950">Account Information</h3>
                <div class="grid gap-4 text-sm">
                  <div>
                    <p class="m-0 text-slate-500">User name</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.user_name }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Email</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.email || 'None' }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Phone</p>
                    <p class="m-0 font-medium text-slate-950">{{ customer.phone }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Created at</p>
                    <p class="m-0 font-medium text-slate-950">{{ formatDate(customer.created_at) }}</p>
                  </div>
                  <div>
                    <p class="m-0 text-slate-500">Updated at</p>
                    <p class="m-0 font-medium text-slate-950">{{ formatDate(customer.updated_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </BaseCard>

          <BaseCard>
            <template #header>
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h3 class="m-0 text-base font-semibold text-slate-950">Linked OAuth Accounts</h3>
                  <p class="m-0 text-sm text-slate-500">Accounts connected to this customer profile.</p>
                </div>
                <el-tag effect="light">{{ oauthAccounts.length }} account(s)</el-tag>
              </div>
            </template>

            <div v-if="oauthAccounts.length" class="grid gap-3 md:grid-cols-2">
              <div v-for="account in oauthAccounts" :key="account.id"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="m-0 text-base font-semibold text-slate-950">{{ account.provider }}</p>
                <div class="mt-2 space-y-1 text-sm text-slate-600">
                  <p class="m-0">Provider user ID: {{ account.provider_user_id || 'None' }}</p>
                  <p class="m-0">Email: {{ account.email || 'None' }}</p>
                  <p class="m-0">Expires at: {{ formatDate(account.expires_at) }}</p>
                </div>
              </div>
            </div>

            <div v-else
              class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
              No OAuth accounts linked to this customer.
            </div>
          </BaseCard>
        </section>
      </div>
    </BaseCard>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import { useAdminCustomer } from '~/composables/useAdminCustomer'
import { customerStatus, getDisplayCustomerStatus } from '~/enums/customerStatus'
import { formatAnyDate } from '~/utils/date'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

const route = useRoute()
const router = useRouter()

const {
  customerDetail,
  fetchCustomerDetail,
} = useAdminCustomer()

const loading = ref(true)

const customerId = computed(() => String(route.params.id ?? ''))
const customer = computed(() => customerDetail.value)
const oauthAccounts = computed(() => customerDetail.value?.oauth_accounts || [])

const formatDate = (value?: string | null) =>
  formatAnyDate(value, 'MMM D, YYYY h:mm A', 'en-US', 'None')

const goBack = () => {
  router.push('/admin/customers')
}

const goEdit = () => {
  router.push(`/admin/customers/edit/${customerId.value}`)
}

const loadCustomer = async () => {
  loading.value = true

  try {
    await fetchCustomerDetail(customerId.value)
  } catch (error) {
    ElMessage.error('Failed to load customer details.')
    await router.push('/admin/customers')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadCustomer()
})
</script>

<style scoped></style>
