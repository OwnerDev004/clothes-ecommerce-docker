<template>
    <div>
        <HeaderBreadCrumb title="Orders">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item>Orders</el-breadcrumb-item>
        </HeaderBreadCrumb>
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-6">
            <div v-for="card in orders_states" :key="card.id">
                <article
                    class="flex items-center justify-between rounded-card border border-border bg-surface p-6 shadow-sm">
                    <div class="space-y-6">
                        <h3 class="text-base font-semibold font-sans text-slate-900">{{ card.title }}</h3>
                        <p class="text-[2rem] font-bold leading-none text-slate-500">{{ card.amount }}</p>
                    </div>
                    <div class="rounded-card bg-orange-400/10 p-3">
                        <Icon :name="card.icon" class="text-3xl text-orange-500" />
                    </div>
                </article>
            </div>
        </section>

        <section class="space-y-6">
            <BaseCard>
                <template #header>
                    <h2 class="text-base font-semibold font-sans text-slate-800 mb-3">All Order List</h2>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="w-full lg:w-[360px]">
                                <BaseInput v-model="filters.search_txt" placeholder="Search Customer, Order ID..."
                                    clearable />

                            </div>

                            <div class="flex flex-wrap gap-3">
                                <BaseButton @click="resetFilters">Reset Filters</BaseButton>
                            </div>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.is_active" :options="orderStatus" placeholder="All Status"
                                class="w-full" />
                        </div>
                    </div>
                </template>

                <div class="space-y-5">
                    <BaseTable :table-data="tableData">
                        <el-table-column prop="order_id" label="Order ID" />
                        <el-table-column prop="created_at" label="Created At" />
                        <el-table-column prop="customer" label="Customer" />
                        <el-table-column prop="payment_status" label="Payment Status" />
                        <el-table-column prop="total" label="Total" />
                        <el-table-column prop="items" label="Items" />
                        <el-table-column prop="delivery_number" label="Delivery Number" />
                        <el-table-column prop="order_status" label="Order Status" />
                        <el-table-column label="Action" fixed="right">
                            <template #default="scope">
                                <el-button link type="success" size="default" @click="viewOrderDetail(scope.row.id)">
                                    <Icon name="solar:eye-broken" class="text-base" />
                                </el-button>
                                <el-button link type="danger" size="default" :loading="deletingId === scope.row.id"
                                    @click="(scope.row)">
                                    <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                </el-button>
                                <el-button link type="primary" size="default" @click="editProduct(scope.row)">
                                    <Icon name="solar:pen-new-round-broken" class="text-base" />
                                </el-button>
                            </template>
                        </el-table-column>

                    </BaseTable>



                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ tableData.length }} promotions
                        </p>
                    </section>
                </div>
            </BaseCard>
        </section>

    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseCard from '~/components/ui/BaseCard.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import BaseTable from '~/components/ui/BaseTable.vue';

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth']
})
// types
interface TableData {
    id: number | string,
    order_id: string,
    customer: string,
    total: number | string,
    payment_status: 'paid' | 'unpaid' | 'refund',
    order_status: 'draft' | 'packaging' | 'completed' | 'cancel',
    items: number,
    delivery_number: string,
    created_at: string
}
const filters = reactive({
    search_txt: "",
    is_active: ''
})
// defualt filter
const default_filters = reactive({
    search_txt: "",
    is_active: ''
})

const tableData = ref<TableData[]>([
    { id: 1, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 2, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 3, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 4, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 5, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 6, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 7, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 8, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 9, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 10, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' },
    { id: 11, order_id: '#583488/80', customer: "dyzak", total: 1002, payment_status: 'unpaid', order_status: 'draft', items: 4, delivery_number: '#50308391', created_at: '07-20-2026' }
])
const orders_states = ref([
    { id: 1, title: 'Payment Refund', amount: 430, icon: 'solar:chat-round-money-broken' },
    { id: 2, title: 'Order Cancel', amount: 430, icon: 'solar:cart-cross-broken' },
    { id: 3, title: 'Order Shipped', amount: 20, icon: 'solar:box-outline' },
    { id: 4, title: 'Order Delivering', amount: 30, icon: 'solar:bus-outline' },

    { id: 5, title: 'Pending Review', amount: 430, icon: 'solar:clipboard-remove-broken' },
    { id: 6, title: 'Pending Payment', amount: 430, icon: 'solar:clock-circle-broken' },
    { id: 7, title: 'Order Shipped', amount: 20, icon: 'solar:clipboard-check-broken' },
    { id: 8, title: 'Delivered', amount: 30, icon: 'solar:inbox-archive-broken' }

])

//functions
const resetFilters = () => {
    Object.assign(filters, default_filters);
}
</script>

<style scoped></style>
