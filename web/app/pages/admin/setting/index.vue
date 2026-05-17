<template>
  <div>
    <HeaderBreadCrumb title="Setting">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/setting' }">Setting</el-breadcrumb-item>
      <el-breadcrumb-item>App Setting</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <BaseCard>
      <template #header>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="m-0 text-sm text-slate-500">Manage app-wide values, shipping rules, and store metadata.</p>
            <p v-if="!canEdit" class="m-0 mt-1 text-xs font-medium text-amber-600">
              You have view access only.
            </p>
          </div>

          <div class="flex gap-3">
            <BaseButton @click="loadSetting">Refresh</BaseButton>
            <BaseButton v-if="canEdit" type="primary" :loading="saving" @click="saveSetting">
              Save Setting
            </BaseButton>
          </div>
        </div>
      </template>

      <div v-loading="loading" class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-2">
          <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5">
            <h3 class="m-0 text-lg font-semibold text-slate-950">Store Identity</h3>

            <div class="grid gap-4">
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">App Name</label>
                <el-input v-model="form.app_name" :disabled="!canEdit" placeholder="Clothes Shop" />
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">App Tagline</label>
                <el-input v-model="form.app_tagline" :disabled="!canEdit"
                  placeholder="Manage your store with clarity." />
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Business Address</label>
                <el-input v-model="form.business_address" :disabled="!canEdit" type="textarea" :rows="3"
                  placeholder="Your store address" />
              </div>
            </div>
          </div>

          <div class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5">
            <h3 class="m-0 text-lg font-semibold text-slate-950">Support And Currency</h3>

            <div class="grid gap-4">
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Support Email</label>
                  <el-input v-model="form.support_email" :disabled="!canEdit" placeholder="support@example.com" />
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Support Phone</label>
                  <el-input v-model="form.support_phone" :disabled="!canEdit" placeholder="+855..." />
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Default Currency Code</label>
                  <el-select v-model="form.default_currency_code" :disabled="true" class="w-full" placeholder="USD">
                    <el-option label="USD" value="USD" />
                    <el-option label="KHR" value="KHR" />
                  </el-select>
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Exchange Rate</label>
                  <el-input v-model="form.exchange_rate" placeholder="Please Enter Exchange Rate" />
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Tax Rate (%)</label>
                  <el-input-number v-model="form.tax_rate" :disabled="true" :min="0" :max="100" :step="0.5"
                    class="!w-full" controls-position="right" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
          <div class="rounded-3xl border border-slate-200 bg-white p-5">
            <h3 class="m-0 text-lg font-semibold text-slate-950">Shipping Defaults</h3>
            <div class="mt-4 grid gap-4">
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Default Shipping Fee</label>
                <el-input-number v-model="form.shipping_fee" :disabled="!canEdit" :min="0" :step="0.25" class="!w-full"
                  controls-position="right" />
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Free Shipping Threshold</label>
                <el-input-number v-model="form.free_shipping_threshold" :disabled="!canEdit" :min="0" :step="1"
                  class="!w-full" controls-position="right" />
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Low Stock Threshold</label>
                <el-input-number v-model="form.low_stock_threshold" :disabled="!canEdit" :min="0" :step="1"
                  class="!w-full" controls-position="right" />
              </div>
            </div>
          </div>

          <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <h3 class="m-0 text-lg font-semibold text-slate-950">Province Shipping Rates</h3>
                <p class="m-0 mt-1 text-sm text-slate-500">These override the default shipping fee by province.</p>
              </div>

              <BaseButton v-if="canEdit" @click="addShippingRate">Add Row</BaseButton>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
              <el-table :data="form.shipping_rates" border>
                <el-table-column label="Province" min-width="240">
                  <template #default="{ row }">
                    <el-input v-model="row.province" :disabled="!canEdit" placeholder="Phnom Penh" />
                  </template>
                </el-table-column>
                <el-table-column label="Fee" width="180">
                  <template #default="{ row }">
                    <el-input-number v-model="row.fee" :disabled="!canEdit" :min="0" :step="0.25" class="!w-full"
                      controls-position="right" />
                  </template>
                </el-table-column>
                <el-table-column v-if="canEdit" label="Action" width="120" align="center">
                  <template #default="{ $index }">
                    <BaseButton link type="danger" @click="removeShippingRate($index)">Remove</BaseButton>
                  </template>
                </el-table-column>
              </el-table>
            </div>
          </div>
        </section>
      </div>
    </BaseCard>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { ElMessage } from 'element-plus'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

type ShippingRate = {
  province: string
  fee: number
}

type SettingPayload = {
  app_name: string
  app_tagline: string
  support_email: string
  support_phone: string
  business_address: string
  default_currency_code: string
  exchange_rate: number
  shipping_fee: number
  free_shipping_threshold: number
  low_stock_threshold: number
  tax_rate: number
  shipping_rates: ShippingRate[]
}

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const adminAuthStore = useAdminAuthStore()
const { accessToken } = storeToRefs(adminAuthStore)
const can = adminAuthStore.can
const canEdit = computed(() => can('setting', 'edit'))

const loading = ref(false)
const saving = ref(false)

const form = reactive<SettingPayload>({
  app_name: '',
  app_tagline: '',
  support_email: '',
  support_phone: '',
  business_address: '',
  default_currency_code: 'USD',
  exchange_rate: 4000,
  shipping_fee: 0,
  free_shipping_threshold: 0,
  low_stock_threshold: 20,
  tax_rate: 0,
  shipping_rates: [],
})

const defaultShippingRates = (): ShippingRate[] => ([
  { province: 'Phnom Penh', fee: 1.5 },
  { province: 'Kandal', fee: 2 },
  { province: 'Siem Reap', fee: 2.5 },
  { province: 'Battambang', fee: 2.5 },
  { province: 'Preah Sihanouk', fee: 3 },
])

const resolveAuthHeaders = () => (accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined)

const hydrateForm = (payload: Partial<SettingPayload> | null) => {
  form.app_name = String(payload?.app_name || '')
  form.app_tagline = String(payload?.app_tagline || '')
  form.support_email = String(payload?.support_email || '')
  form.support_phone = String(payload?.support_phone || '')
  form.business_address = String(payload?.business_address || '')
  form.default_currency_code = String(payload?.default_currency_code || 'USD')
  form.exchange_rate = Number(payload?.exchange_rate || 4000)
  form.shipping_fee = Number(payload?.shipping_fee || 0)
  form.free_shipping_threshold = Number(payload?.free_shipping_threshold || 0)
  form.low_stock_threshold = Number(payload?.low_stock_threshold || 20)
  form.tax_rate = Number(payload?.tax_rate || 0)
  form.shipping_rates = Array.isArray(payload?.shipping_rates) && payload?.shipping_rates.length
    ? payload.shipping_rates.map((item: any) => ({
      province: String(item?.province || ''),
      fee: Number(item?.fee || 0),
    }))
    : defaultShippingRates()
}

const loadSetting = async () => {
  loading.value = true
  try {
    const response: any = await $fetch(`${apiBase}/admin/setting`, {
      method: 'GET',
      headers: resolveAuthHeaders(),
    })

    hydrateForm(response?.data || null)
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to load settings.')
  } finally {
    loading.value = false
  }
}

const addShippingRate = () => {
  form.shipping_rates.push({ province: '', fee: 0 })
}

const removeShippingRate = (index: number) => {
  form.shipping_rates.splice(index, 1)
}

const saveSetting = async () => {
  if (!canEdit.value) {
    return
  }

  saving.value = true
  try {
    const body = {
      app_name: form.app_name,
      app_tagline: form.app_tagline || null,
      support_email: form.support_email || null,
      support_phone: form.support_phone || null,
      business_address: form.business_address || null,
      default_currency_code: form.default_currency_code,
      exchange_rate: Number(form.exchange_rate || 4000),
      shipping_fee: Number(form.shipping_fee || 0),
      free_shipping_threshold: Number(form.free_shipping_threshold || 0),
      low_stock_threshold: Number(form.low_stock_threshold || 0),
      tax_rate: Number(form.tax_rate || 0),
      shipping_rates: form.shipping_rates
        .filter((item) => item.province.trim() !== '')
        .map((item) => ({
          province: item.province.trim(),
          fee: Number(item.fee || 0),
        })),
    }

    const response: any = await $fetch(`${apiBase}/admin/setting`, {
      method: 'PUT',
      headers: resolveAuthHeaders(),
      body,
    })

    hydrateForm(response?.data || body)
    ElMessage.success('Settings updated successfully.')
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to save settings.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  void loadSetting()
})
</script>
