<template>
    <el-dialog v-model="dialogOpen" width="860px" :close-on-click-modal="false" title="Order History"
        class="order-history-dialog" @closed="onDialogClosed">
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <el-select v-model="statusFilter" class="w-full sm:w-52" placeholder="Lifecycle status" clearable
                    @change="onFilterChanged">
                    <el-option label="All Lifecycle Statuses" value="" />
                    <el-option label="Pending" value="pending" />
                    <el-option label="Paid" value="paid" />
                    <el-option label="Processing" value="processing" />
                    <el-option label="Shipped" value="shipped" />
                    <el-option label="Completed" value="completed" />
                    <el-option label="Cancelled" value="cancelled" />
                    <el-option label="Refunded" value="refunded" />
                </el-select>

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
                    <el-option label="All Shipping/Order Statuses" value="" />
                    <el-option label="Pending" value="pending" />
                    <el-option label="Processing" value="processing" />
                    <el-option label="Shipped" value="shipped" />
                    <el-option label="Delivered" value="delivered" />
                    <el-option label="Cancelled" value="cancelled" />
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
                                <el-tag size="small" :type="paymentTagType(order.payment_state || order.payment_status)">
                                    Payment: {{ order.payment_state || order.payment_status || 'unknown' }}
                                </el-tag>
                                <el-tag size="small" :type="orderTagType(order.order_status)">
                                    Shipping: {{ order.order_status || 'unknown' }}
                                </el-tag>
                                <span class="text-sm font-semibold text-gray-900">
                                    ${{ formatMoney(order.total_price) }}
                                </span>
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
                                    ${{ formatMoney(item.total_price) }}
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
import { useAuthStore } from '~/stores/authStore'

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
    status?: string
    payment_state?: string
    payment_status?: string
    order_status?: string
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
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const loadingOrders = ref(false)
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
                status: statusFilter.value || undefined,
                payment_state: paymentStateFilter.value || undefined,
                order_status: orderStatusFilter.value || undefined,
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

const onFilterChanged = () => {
    page.value = 1
    fetchOrders()
}

const onPageChanged = (nextPage: number) => {
    page.value = nextPage
    fetchOrders()
}

const formatMoney = (value: number | string | null | undefined) => {
    const amount = Number(value || 0)
    if (Number.isNaN(amount)) {
        return '0.00'
    }
    return amount.toFixed(2)
}

const formatDate = (value?: string) => {
    if (!value) {
        return 'N/A'
    }

    const parsed = new Date(value)
    if (Number.isNaN(parsed.getTime())) {
        return value
    }

    return parsed.toLocaleDateString()
}

const itemTitle = (item: OrderItem) => {
    const productName = item?.variant?.product?.name
    const sku = item?.variant?.sku
    if (productName && sku) {
        return `${productName} (${sku})`
    }
    return productName || sku || `Item #${item.id}`
}

const statusTagType = (status?: string) => {
    switch ((status || '').toLowerCase()) {
        case 'completed':
            return 'success'
        case 'cancelled':
        case 'refunded':
            return 'danger'
        case 'processing':
        case 'shipped':
            return 'warning'
        case 'paid':
            return 'primary'
        default:
            return 'info'
    }
}

const paymentTagType = (status?: string) => {
    switch ((status || '').toLowerCase()) {
        case 'paid':
            return 'success'
        case 'pending':
            return 'warning'
        case 'failed':
        case 'expired':
        case 'canceled':
        case 'cancelled':
        case 'refunded':
            return 'danger'
        default:
            return 'info'
    }
}

const orderTagType = (status?: string) => {
    switch ((status || '').toLowerCase()) {
        case 'delivered':
        case 'completed':
            return 'success'
        case 'processing':
        case 'shipped':
            return 'warning'
        case 'cancelled':
            return 'danger'
        default:
            return 'info'
    }
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
</script>

<style scoped></style>
