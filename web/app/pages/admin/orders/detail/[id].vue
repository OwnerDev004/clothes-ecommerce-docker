<template>
    <div>
        <HeaderBreadCrumb title="Order Details">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item>Order Details</el-breadcrumb-item>
        </HeaderBreadCrumb>

        <el-alert v-if="error" :title="error.message" type="error" :closable="false" show-icon class="mb-6" />

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_25%] w-full">
            <!-- Left Side -->
            <div class="min-w-0 space-y-6">

                <!-- Progressing Card -->
                <div class="shadow-sm bg-surface rounded-card p-6">
                    <section class="flex justify-between gap-6">
                        <div class="space-y-5">
                            <div class="flex flex-wrap gap-3 items-center">
                                <h2 class="font-sans font-semibold">
                                    {{ order_summary.order_id || '#--' }}
                                </h2>
                                <el-tag effect="light" :type="paymentTagType">
                                    {{ order_summary.payment_status || 'Pending' }}
                                </el-tag>

                                <el-tag effect="plain" :type="progressTagType">
                                    {{ progressOrderStore.statusText }}
                                </el-tag>
                            </div>

                            <p class="font-Lato text-slate-800 text-sm">
                                Order / Order Details / {{ order_summary.order_id || '#--' }} -
                                {{ order_summary.created_at || 'No date available' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-end">
                            <BaseButton plain :disabled="!activeRefundButton" @click="refundOrderModal">
                                Refund
                            </BaseButton>
                            <BaseButton type="primary" :disabled="!activeEditProgressButton" @click="editOrder">
                                Edit Order</BaseButton>
                        </div>

                    </section>

                    <section class="mt-8">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="my-8">Progress</h2>
                            <el-tag effect="light" :type="progressTagType" class="!rounded-full !px-3 !py-2">
                                {{ progressOrderStore.subStatusText }}
                            </el-tag>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                            <div v-for="step in order_timeline" :key="step.key"
                                class="flex flex-col items-center gap-2">
                                <el-progress class="w-full"
                                    :percentage="step.state === 'done' ? 100 : step.state === 'current' ? 50 : 0"
                                    :stroke-width="12" :status="step.state === 'current' ? 'warning' : 'success'"
                                    striped striped-flow :duration="10" :show-text="false" />
                                <div class="text-center text-xs font-medium text-slate-600 flex items-center gap-1">

                                    {{ step.title }}
                                    <el-icon v-if="step.state === 'current'" class="is-loading !text-warning"
                                        :size="12">
                                        <Loading />
                                    </el-icon>
                                    <el-icon v-else-if="step.state === 'done'" class="!text-emerald-500" :size="12">
                                        <CircleCheckFilled />
                                    </el-icon>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="place-self-end mt-8 flex flex-wrap gap-3">
                        <BaseButton plain :disabled="!activeCancelButton" @click="cancelOrderModal">Cancel Order
                        </BaseButton>
                        <BaseButton type="primary" :loading="confirmingOrder"
                            :disabled="!canAdvanceOrder || confirmingOrder" @click="handleAdvanceOrder">
                            {{ progressOrderStore.currentButton.text }}
                        </BaseButton>
                    </section>
                </div>

                <!-- Product List Card -->
                <div class="shadow-sm bg-surface rounded-card p-6 space-y-3">
                    <h2>Product</h2>
                    <hr>

                    <BaseTable :table-data="products_order">
                        <el-table-column prop="order_id" label="Product Name & Size">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <el-image class="h-16 w-16 rounded-xl object-cover" :src="scope.row.thumbnail || ''"
                                        :preview-src-list="scope.row.preview_images" preview-teleported fit="cover" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.name }}</p>
                                        <p class="truncate text-xs text-slate-500">Size : {{ scope.row.size || '-' }}
                                        </p>
                                        <div class="truncate text-xs text-slate-500 flex gap-2">
                                            <p> Color :</p>
                                            <div class="w-4 h-4 border border-black"
                                                :style="{ backgroundColor: scope.row.color }">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column prop="qty" label="Quantity" />
                        <el-table-column prop="price" label="Price" />
                        <el-table-column prop="amount" label="Amount" />
                    </BaseTable>
                </div>

                <!-- Order Timeline -->
                <div class="shadow-sm bg-surface rounded-card p-6 space-y-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <h2 class="text-lg font-semibold text-slate-950">Order Timeline</h2>
                            <p class="text-sm text-slate-500">
                                A clean view of the order journey from payment to delivery.
                            </p>
                        </div>

                        <el-tag effect="light" :type="progressTagType" class="!rounded-full !px-3 !py-2">

                            {{ progressOrderStore.currentStepDisplayName }}
                        </el-tag>
                    </div>

                    <el-timeline>
                        <el-timeline-item v-for="step in order_timeline" :key="step.title" placement="top"
                            :type="step.state === 'done' ? 'success' : step.state === 'current' ? 'warning' : 'info'"
                            :color="step.state === 'done' ? '#16a34a' : step.state === 'current' ? '#f59e0b' : '#cbd5e1'">
                            <template #dot>
                                <span
                                    class="el-timeline-item__node el-timeline-item__node--large inline-flex items-center justify-center rounded-full"
                                    :class="step.state === 'done'
                                        ? '!bg-emerald-500 text-white'
                                        : step.state === 'current'
                                            ? '!bg-amber-500 text-white'
                                            : '!bg-slate-300 text-white'">
                                    <el-icon :size="12">
                                        <Loading v-if="step.state === 'current'" class="animate-spin" />
                                        <CircleCheckFilled v-else-if="step.state === 'done'" />
                                        <Clock v-else />
                                    </el-icon>
                                </span>
                            </template>

                            <div class="rounded-2xl border p-4 transition" :class="step.state === 'done'
                                ? 'border-emerald-200 bg-emerald-50/70'
                                : step.state === 'current'
                                    ? 'border-amber-200 bg-amber-50/80 shadow-sm'
                                    : 'border-slate-200 bg-white'">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="m-0 text-sm font-semibold text-slate-950">{{ step.title }}</h3>
                                        <p class="m-0 mt-1 text-sm leading-6 text-slate-600">
                                            {{ step.description }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex shrink-0 rounded-full py-1 text-[11px] font-semibold uppercase tracking-wide"
                                        :class="step.state === 'done'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : step.state === 'current'
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-slate-100 text-slate-500'">
                                        {{ step.label }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span>{{ step.time }}</span>
                                    <span v-if="step.actor">- {{ step.actor }}</span>
                                </div>
                            </div>
                        </el-timeline-item>
                    </el-timeline>
                </div>
            </div>

            <!-- Right Side -->
            <div class="min-w-0 space-y-6">
                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Order Summary
                            </p>
                            <h2 class="mt-2 text-lg font-semibold text-slate-950">
                                {{ order_summary.order_id || '#--' }}
                            </h2>
                        </div>
                        <el-tag effect="light" :type="paymentTagType">
                            {{ order_summary.payment_status || 'Pending' }}
                        </el-tag>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div v-for="value in order_summaries" :key="value.title"
                            class="flex items-center justify-between gap-4 border-b border-dashed border-slate-200 pb-3 last:border-b-0 last:pb-0">
                            <p class="m-0 text-sm text-slate-500">{{ value.title }}</p>
                            <p class="m-0 text-sm font-semibold text-slate-950">{{ value.amount }}</p>
                        </div>

                        <div class="flex items-center justify-between border-t border-dashed border-slate-200 pt-4">
                            <p class="m-0 text-sm font-semibold text-slate-950">Total Amount</p>
                            <p class="m-0 text-lg font-bold text-slate-950">
                                {{ formatMoney(order_summary.total_price) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Payment Information</p>

                    <div class="mt-5 flex items-center gap-4 rounded-2xl bg-slate-50 p-4">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-white shadow-sm">
                            <span class="text-sm font-bold text-slate-800">PM</span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="m-0 text-sm font-semibold text-slate-950">
                                {{ order_summary.payment_method || 'Payment Method' }}
                            </h3>
                            <p class="m-0 mt-1 text-sm text-slate-500">
                                {{ order_summary.payment_provider || 'No provider selected' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Transaction ID</span>
                            <span class="font-medium text-slate-950">{{ order_summary.order_id || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Card Holder</span>
                            <span class="font-medium text-slate-950">{{ order_summary.customer_name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-card border border-border bg-surface p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                            <span class="text-lg font-bold">CU</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                Customer Details
                            </p>
                            <h3 class="m-0 mt-2 text-lg font-semibold text-slate-950">
                                {{ order_summary.customer_name || 'Customer' }}
                            </h3>
                            <p class="m-0 mt-1 text-sm text-slate-500">
                                {{ order_summary.customer_email || 'No email available' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4 text-sm">
                        <div>
                            <p class="m-0 text-sm font-semibold uppercase tracking-wide">Contact Number</p>
                            <p class="m-0 mt-1 font-medium text-slate-800 text-xs">
                                {{ order_summary.customer_phone || '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="m-0 text-sm font-semibold uppercase tracking-wide">Shipping Address</p>
                            <p class="m-0 mt-1 leading-6 text-slate-800 text-xs">
                                {{ order_summary.shipping_address || 'No shipping address available' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4">
                            <p class="m-0 text-sm font-semibold uppercase tracking-wide text-amber-800">
                                Order Note
                            </p>
                            <p class="m-0 mt-2 text-xs leading-6 text-amber-900">
                                {{ selectedOrder?.order_note || 'No order note available.' }}
                            </p>
                        </div>
                        <div>
                            <p class="m-0 text-sm font-semibold uppercase tracking-wide">Billing Address</p>
                            <p class="m-0 mt-1 text-xs font-medium text-slate-800">
                                Same as shipping address
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <EditOrderDetailModal v-model="isEditOrderModal" :order-id="orderId" :form="formEditOrder" />
        <CancelNoteModal v-model="isCancelOrderModal" :order-id="orderId" :form="formEditOrder"
            @submit="handleCancelOrder" :loading="pending" />
        <RefundNoteModal v-model="isRefundOrderModal" :order-id="orderId" :form="formEditOrder"
            @submit="handleRefundOrder" />
    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseTable from '~/components/ui/BaseTable.vue';
import { CircleCheckFilled, Clock, Loading } from '@element-plus/icons-vue';
import { useRoute } from 'vue-router';
import { useAdminOrders } from '~/composables/useAdminOrders';
import { useAdminOrderDetail } from '~/composables/useAdminOrderDetail';
import { useProgressOrderStore } from '~/stores/progressOrderStore';
import { getOrderStatusTagType, getPaymentStatusTagType } from '~/utils/orderStatusTheme';
import EditOrderDetailModal from './components/EditOrderDetailModal.vue'
import CancelNoteModal from './components/CancelNoteModal.vue';
import RefundNoteModal from './components/RefundNoteModal.vue';

const {
    error,
    selectedOrder,
    order_summary,
    products_order,
    order_timeline,
    refundOrder,
    cancelOrder,
    refreshOrderDetail,
    pending,
} = useAdminOrderDetail();

const {
    updateOrderStatus,
    savingStatus,
    updatingOrderId,
    fetchOrders,
} = useAdminOrders();

const progressOrderStore = useProgressOrderStore();
const route = useRoute();

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
});
const isEditOrderModal = ref<boolean>(false)
const isCancelOrderModal = ref<boolean>(false)
const isRefundOrderModal = ref<boolean>(false)
type EditOrderForm = {
    customer?: {
        shipping_phone: string
        shipping_address: string
        shipping_province: string
        shipping_fee: number
    }
    order_note: string
}
// handleOrderType
type handleUpdateOrder = {
    id: string | number | undefined
    order_note: string
    status: string
}

const formEditOrder = ref<EditOrderForm>({
    customer: {
        shipping_phone: '',
        shipping_address: '',
        shipping_province: '',
        shipping_fee: 0,
    },
    order_note: '',
})
const orderId = computed(() => {
    const value = route.params.id;
    return Array.isArray(value) ? value[0] : value;
});

const normalizeStatus = (value?: string | null) => {
    return String(value || 'pending').toLowerCase();
};

const nextStatusMap: Record<string, string> = {
    order_confirming: 'payment_confirmed',
    payment_confirmed: 'processing',
    processing: 'shipped',
    shipped: 'delivered',
};

watch(
    selectedOrder,
    (order) => {
        if (order) {
            progressOrderStore.initFromOrder(order as any);
            return;
        }

        progressOrderStore.resetStore();
    },
    { immediate: true },
);

const currentOrderStatus = computed(() => {
    return normalizeStatus(selectedOrder.value?.status);
});

const nextOrderStatus = computed(() => {
    return nextStatusMap[currentOrderStatus.value] || '';
});

const confirmingOrder = computed(() => {
    return savingStatus.value && updatingOrderId.value === orderId.value;
});

const canAdvanceOrder = computed(() => {
    return Boolean(orderId.value && nextOrderStatus.value);
});

// Cancel Button Active
const activeCancelButton = computed(() => {
    if (currentOrderStatus.value == 'order_confirming' ||
        currentOrderStatus.value == 'payment_confirmed' ||
        currentOrderStatus.value == 'processing') {
        return true;
    }
    return false
})

// Refund Button Active
const activeRefundButton = computed(() => {
    if (
        currentOrderStatus.value == 'payment_confirmed' ||
        currentOrderStatus.value == 'processing' ||
        currentOrderStatus.value == 'shipped' ||
        currentOrderStatus.value == 'delivered'
    ) {
        return true;
    }
    return false
})

// Edit Button Active
const activeEditProgressButton = computed(() => {
    if (
        currentOrderStatus.value == 'order_confirming' ||
        currentOrderStatus.value == 'payment_confirmed' ||
        currentOrderStatus.value == 'processing'
    ) {
        return true;
    }
    return false
})

// edit order function 

const editOrder = () => {
    formEditOrder.value = {
        customer: {
            shipping_phone: selectedOrder.value?.shipping_phone ?? '',
            shipping_address: selectedOrder.value?.shipping_address ?? '',
            shipping_province: selectedOrder.value?.shipping_province ?? '',
            shipping_fee: Number(selectedOrder.value?.shipping_fee),
        },
        order_note: selectedOrder.value?.order_note ?? '',
    }
    isEditOrderModal.value = true
}
const refundOrderModal = () => {
    formEditOrder.value = {
        order_note: selectedOrder.value?.order_note ?? '',
    }
    isRefundOrderModal.value = true

}
const cancelOrderModal = () => {
    formEditOrder.value = {
        order_note: selectedOrder.value?.order_note ?? '',
    }
    isCancelOrderModal.value = true

}


const handleRefundOrder = async (payload: handleUpdateOrder) => {
    if (!payload.id) {
        return;
    }

    await refundOrder(payload);
    await Promise.all([
        refreshOrderDetail(payload.id),
        fetchOrders(),
    ]);
    isRefundOrderModal.value = false;
}

const handleCancelOrder = async (payload: handleUpdateOrder) => {
    if (!payload.id) {
        return;
    }

    await cancelOrder(payload);
    await Promise.all([
        refreshOrderDetail(payload.id),
        fetchOrders(),
    ]);
    isCancelOrderModal.value = false;
}


const handleAdvanceOrder = async () => {
    if (!orderId.value || !nextOrderStatus.value) {
        return;
    }

    await updateOrderStatus(orderId.value, nextOrderStatus.value);
    await refreshOrderDetail(orderId.value);

    if (selectedOrder.value) {
        progressOrderStore.initFromOrder(selectedOrder.value as any);
    }
};

const progressTagType = computed(() => {
    return getOrderStatusTagType(order_summary.value.status || selectedOrder.value?.status);
});

const paymentTagType = computed(() => {
    return getPaymentStatusTagType(order_summary.value.payment_status);
});

const formatMoney = (value: unknown) => {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const order_summaries = computed(() => {
    const subtotal = Number(order_summary.value.subtotal_price || 0);
    const discount = Number(order_summary.value.discount_amount || 0);
    const shipping = Number(order_summary.value.shipping_fee || 0);

    return [
        {
            title: 'Sub Total',
            amount: formatMoney(subtotal),
        },
        {
            title: 'Discount',
            amount: `-${formatMoney(discount)}`,
        },
        {
            title: 'Delivery Charge',
            amount: formatMoney(shipping),
        },
    ];
});
</script>

<style scoped></style>
