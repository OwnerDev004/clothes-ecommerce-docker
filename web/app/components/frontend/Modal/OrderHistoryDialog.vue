<template>
    <el-dialog v-model="dialogOpen" :close-on-click-modal="false" title="Order History"
        class="order-history-dialog !w-full md:!w-[700px]" @closed="onDialogClosed">
        <div class="space-y-4">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <el-select v-model="paymentStateFilter" class="w-full sm:w-52" placeholder="Payment status" clearable
                    @change="onFilterChanged">
                    <el-option label="All Payment Statuses" value="" />
                    <el-option label="Pending" value="pending" />
                    <el-option label="Paid" value="paid" />
                    <el-option label="Failed" value="failed" />
                    <el-option label="Expired" value="expired" />
                    <el-option label="Canceled" value="canceled" />
                    <el-option label="Refunded" value="refunded" />
                </el-select>

                <el-select v-model="orderStatusFilter" class="w-full sm:w-52" placeholder="Shipping/Order status"
                    clearable @change="onFilterChanged">
                    <el-option v-for="item in OrderStatusList" :label="item.label" :value="item.id" />
                </el-select>

                <el-button :loading="loadingOrders" plain @click="fetchOrders">
                    Refresh
                </el-button>
            </div>

            <el-alert v-if="errorMessage" :title="errorMessage" type="error" :closable="false" show-icon />

            <div v-loading="loadingOrders" class="min-h-[240px]">
                <div v-if="orders.length === 0 && !loadingOrders"
                    class="rounded-xl border border-dashed border-gray-300 p-8">
                    <el-empty description="No orders found." />
                </div>

                <div v-else class="space-y-3">
                    <div v-for="order in orders" :key="order.id" class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Order #{{ order.id }}</p>
                                <p class="text-xs text-gray-500">
                                    Date: {{ formatDate(order.order_date || order.created_at) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Payment: {{ order.payment_method || 'N/A' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <el-tag size="small" :type="statusTagType(order.status)">
                                    {{ order.status || 'unknown' }}
                                </el-tag>
                                <el-tag size="small"
                                    :type="paymentTagType(order.payment_status || order.payment_status)">
                                    Payment: {{ order.payment_status || order.payment_status || 'unknown' }}
                                </el-tag>
                                <el-tag size="small" :type="orderTagType(order.status)">
                                    Shipping: {{ order.status || 'unknown' }}
                                </el-tag>
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ formatMoney(order.total_price, defaultCurrencyCode) }}
                                </span>
                                <el-button v-if="canRepay(order)" size="small" type="warning" plain
                                    :loading="repayingOrderId === order.id" @click="repayOrder(order)">
                                    Repay
                                </el-button>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2 border-t border-gray-100 pt-3">
                            <div v-for="item in order.items" :key="item.id"
                                class="flex items-start justify-between gap-2 text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">
                                        {{ itemTitle(item) }}
                                    </p>
                                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                                </div>
                                <p class="font-medium text-gray-700">
                                    {{ formatMoney(item.total_price, defaultCurrencyCode) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <el-pagination v-if="meta.total > meta.per_page" background layout="prev, pager, next"
                    :current-page="page" :page-size="meta.per_page" :total="meta.total"
                    @current-change="onPageChanged" />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <el-button @click="dialogOpen = false">Close</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { computed, ref, watch } from 'vue'
import { orderStatus, OrderStatusList } from '~/enums/orderStatus'
import { useAuthStore } from '~/stores/authStore'
import { useAppSetting } from '~/composables/useAppSetting'
import { formatMoney } from '~/utils/currency'
import { formatAnyDate } from '~/utils/date'
import { getOrderStatusTagType, getPaymentStatusTagType } from '~/utils/orderStatusTheme'

type OrderItem = {
    id: number | string
    quantity: number
    total_price: number | string
    variant?: {
        id?: number | string
        sku?: string
        product?: {
            name?: string
            slug?: string
        }
    }
}

type OrderRecord = {
    id: number | string
    payment_status?: string
    payment_provider?: string
    status?: string
    order_date?: string
    created_at?: string
    payment_method?: string
    total_price: number | string
    items: OrderItem[]
}

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
const { isAuthenticated, accessToken } = storeToRefs(authStore)
const { defaultCurrencyCode, fetchAppSetting } = useAppSetting()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const loadingOrders = ref(false)
const repayingOrderId = ref<number | string | null>(null)
const errorMessage = ref('')
const orders = ref<OrderRecord[]>([])
const page = ref(1)
const statusFilter = ref('')
const paymentStateFilter = ref('')
const orderStatusFilter = ref('')
const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
})

const getAuthHeaders = () => {
    return accessToken.value
        ? { Authorization: `Bearer ${accessToken.value}` }
        : undefined
}

const fetchOrders = async () => {
    if (!isAuthenticated.value && !accessToken.value) {
        orders.value = []
        errorMessage.value = 'Please login to view your order history.'
        return
    }

    loadingOrders.value = true
    errorMessage.value = ''

    try {
        const response: any = await $fetch(`${apiBase}/orders`, {
            method: 'GET',
            credentials: 'include',
            headers: getAuthHeaders(),
            query: {
                page: page.value,
                per_page: meta.value.per_page,
                status: orderStatusFilter.value || undefined,
                payment_status: paymentStateFilter.value || undefined,
            },
        })

        orders.value = Array.isArray(response?.data) ? response.data : []
        meta.value = {
            current_page: Number(response?.meta?.current_page || 1),
            last_page: Number(response?.meta?.last_page || 1),
            per_page: Number(response?.meta?.per_page || 10),
            total: Number(response?.meta?.total || 0),
        }
    } catch (err: any) {
        const statusCode = err?.statusCode ?? err?.status
        if (statusCode === 401 || statusCode === 403) {
            authStore.resetAuth()
            errorMessage.value = 'Session expired. Please login again.'
        } else {
            errorMessage.value = err?.data?.message || 'Failed to load order history.'
        }
        orders.value = []
    } finally {
        loadingOrders.value = false
    }
}

const normalizePaymentState = (order: OrderRecord) => {
    return String(order.payment_status || order.payment_status || '').toLowerCase()
}

const canRepay = (order: OrderRecord) => {
    const paymentState = normalizePaymentState(order)
    const lifecycle = String(order.status || '').toLowerCase()

    if (['paid', 'refunded'].includes(paymentState)) {
        return false
    }
    if (['completed', 'refunded'].includes(lifecycle)) {
        return false
    }
    return ['failed', 'expired', 'canceled', 'cancelled'].includes(paymentState)
}

const repayOrder = async (order: OrderRecord) => {
    if (!canRepay(order)) {
        return
    }

    const provider = String(order.payment_provider || 'khrqr').toLowerCase()
    repayingOrderId.value = order.id
    errorMessage.value = ''

    try {
        const response: any = await $fetch(`${apiBase}/payments/intent`, {
            method: 'POST',
            credentials: 'include',
            headers: getAuthHeaders(),
            body: {
                order_id: Number(order.id),
                provider,
                currency: defaultCurrencyCode.value,
            },
        })

        const checkoutUrl = response?.data?.checkout_url
        if (checkoutUrl) {
            await navigateTo(checkoutUrl, { external: true })
            return
        }

        errorMessage.value = 'Unable to start repayment. Missing checkout URL.'
    } catch (err: any) {
        errorMessage.value = err?.data?.message || 'Unable to start repayment.'
    } finally {
        repayingOrderId.value = null
    }
}

const onFilterChanged = () => {
    page.value = 1
    fetchOrders()
}

const onPageChanged = (nextPage: number) => {
    page.value = nextPage
    fetchOrders()
}

const formatDate = (value?: string) =>
    formatAnyDate(value, 'MMM D, YYYY', 'en-US', 'N/A')

const itemTitle = (item: OrderItem) => {
    const productName = item?.variant?.product?.name
    const sku = item?.variant?.sku
    if (productName && sku) {
        return `${productName} (${sku})`
    }
    return productName || sku || `Item #${item.id}`
}

const statusTagType = (status?: string) => {
    return getOrderStatusTagType(status)
}

const paymentTagType = (status?: string) => {
    return getPaymentStatusTagType(status)
}

const orderTagType = (status?: string) => {
    return getOrderStatusTagType(status)
}

const onDialogClosed = () => {
    errorMessage.value = ''
}

watch(dialogOpen, (isOpen) => {
    if (!isOpen) {
        return
    }
    fetchOrders()
})

onMounted(() => {
    void fetchAppSetting(true)
})
</script>

<style scoped></style>
