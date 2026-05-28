<template>
  <div class="invoice-page">
    <div class="invoice-card">
      <div class="header">
        <div>
          <h1>Invoice</h1>
          <p v-if="orderNumber">Order #{{ orderNumber }}</p>
        </div>
        <div class="status" :data-status="order?.payment_status || 'unknown'">
          {{ (order?.payment_status || 'pending').toUpperCase() }}
        </div>
      </div>

      <LoadingPage v-if="loading" embedded :rows="4" />
      <div v-else-if="errorMessage" class="error">{{ errorMessage }}</div>
      <div v-else-if="order" class="body">
        <div class="section">
          <h2>Customer</h2>
          <p>{{ order.customer?.full_name || order.customer?.user_name || 'Customer' }}</p>
          <p v-if="order.customer?.email">{{ order.customer?.email }}</p>
          <p v-if="order.customer?.phone">{{ order.customer?.phone }}</p>
        </div>

        <div class="section">
          <h2>Items</h2>
          <div class="items">
            <div v-for="item in order.items" :key="item.id" class="item">
              <div>
                <div class="item-name">{{ item.variant?.product?.name || 'Product' }}</div>
                <div class="item-meta">Qty {{ item.quantity }}</div>
              </div>
              <div class="item-price">{{ formatMoney(item.price ?? item.unit_price ?? item.sell_price ?? 0,
                defaultCurrencyCode) }}</div>
            </div>
          </div>
        </div>

        <div class="section totals">
          <div class="row">
            <span>Subtotal</span>
            <span>{{ formatMoney(order.summary?.subtotal ?? order.total_price ?? 0, defaultCurrencyCode) }}</span>
          </div>
          <div class="row" v-if="order.summary?.discount">
            <span>Discount</span>
            <span>- {{ formatMoney(order.summary?.discount, defaultCurrencyCode) }}</span>
          </div>
          <div class="row total">
            <span>Total</span>
            <span>{{ formatMoney(order.total_price ?? 0, defaultCurrencyCode) }}</span>
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn" @click="printInvoice">Print</button>
        <NuxtLink class="btn ghost" to="/"><span>Back to home</span></NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useAuthStore } from '~/stores/authStore'
import { useAppSetting } from '~/composables/useAppSetting'
import { formatMoney } from '~/utils/currency'
import LoadingPage from '~/components/shares/LoadingPage.vue'

definePageMeta({
  middleware: []
})

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const authStore = useAuthStore()
const { accessToken } = storeToRefs(authStore)
const { defaultCurrencyCode, fetchAppSetting } = useAppSetting()

const loading = ref(true)
const errorMessage = ref('')
const order = ref<any>(null)

const orderNumber = computed(() => order.value?.id ?? route.params.id)

const getAuthHeaders = () => {
  return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined
}

const fetchOrder = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const response: any = await $fetch(`${apiBase}/orders/${route.params.id}`, {
      method: 'GET',
      credentials: 'include',
      headers: getAuthHeaders()
    })
    order.value = response?.data || null
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Unable to load invoice.'
  } finally {
    loading.value = false
  }
}

const printInvoice = () => {
  if (import.meta.client) {
    window.print()
  }
}

onMounted(fetchOrder)
onMounted(() => {
  void fetchAppSetting(true)
})
</script>

<style scoped>
.invoice-page {
  min-height: 100vh;
  background: linear-gradient(120deg, #f7f3ea, #f1f6f9);
  display: flex;
  justify-content: center;
  padding: 48px 16px;
  font-family: "Space Grotesk", "DM Sans", system-ui, sans-serif;
}

.invoice-card {
  width: min(860px, 100%);
  background: #fffdf7;
  border: 1px solid #efe6d4;
  border-radius: 24px;
  box-shadow: 0 20px 60px rgba(44, 62, 80, 0.08);
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  border-bottom: 1px solid #efe6d4;
  padding-bottom: 16px;
}

.header h1 {
  margin: 0 0 6px;
  font-size: 28px;
  color: #1f2937;
}

.status {
  align-self: flex-start;
  padding: 6px 14px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 12px;
  letter-spacing: 0.06em;
  background: #e5f4ff;
  color: #0f4c81;
}

.section h2 {
  margin: 0 0 8px;
  font-size: 16px;
  color: #6b4f2a;
}

.items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.item {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #fdf7ee;
}

.item-name {
  font-weight: 600;
  color: #2c2c2c;
}

.item-meta {
  font-size: 12px;
  color: #6b7280;
}

.item-price {
  font-weight: 600;
  color: #111827;
}

.totals {
  border-top: 1px solid #efe6d4;
  padding-top: 16px;
  display: grid;
  gap: 8px;
}

.row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
}

.row.total {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn {
  border: none;
  background: #f3c874;
  color: #1f2937;
  font-weight: 600;
  padding: 10px 16px;
  border-radius: 999px;
  cursor: pointer;
}

.btn.ghost {
  background: transparent;
  border: 1px solid #d2b277;
  color: #7a5b2c;
}

.error {
  color: #b91c1c;
  background: #fee2e2;
  padding: 12px;
  border-radius: 12px;
}

.skeleton {
  color: #6b7280;
  padding: 12px;
}

@media (max-width: 640px) {
  .invoice-card {
    padding: 24px;
  }

  .header {
    flex-direction: column;
    align-items: flex-start;
  }

  .actions {
    flex-direction: column;
  }
}
</style>
