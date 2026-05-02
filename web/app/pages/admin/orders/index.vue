<template>
    <div>
        <HeaderBreadCrumb title="Orders">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item>Orders</el-breadcrumb-item>
        </HeaderBreadCrumb>
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-6">
            <div v-for="card in ordersStates" :key="card.id">
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

                        <div class="grid gap-3 xl:grid-cols-3">
                            <BaseSelect v-model="filters.status" :options="orderStatus" placeholder="All Status"
                                class="w-full" />
                            <BaseSelect v-model="filters.payment_status" :options="paymentStatusOptions"
                                placeholder="Payment Status" class="w-full" />
                            <BaseSelect v-model="filters.customer_id" :options="customerOptions" placeholder="Customer"
                                class="w-full" />
                        </div>
                    </div>
                </template>

                <div class="space-y-5">
                    <div :v-loading="pending">
                        <BaseTable :table-data="tableData">
                            <el-table-column prop="order_id" label="Order ID" />
                            <el-table-column prop="created_at" label="Created At" />
                            <el-table-column prop="customer" label="Customer" />
                            <el-table-column prop="payment_status" label="Payment Status">
                                <template #default="scope">
                                    <el-tag :type="getPaymentStatusTagType(scope.row.payment_status)">{{
                                        scope.row.payment_status }}</el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column prop="total" label="Total" />
                            <el-table-column prop="items" label="Items" />
                            <el-table-column prop="delivery_number" label="Delivery Number" />
                            <el-table-column prop="order_note" label="Order Note" />

                            <el-table-column prop="status" label="Order Status">
                                <template #default="scope">
                                    <el-tag effect="plain" :type="getOrderStatusTagType(scope.row.status)">{{
                                        scope.row.status }}</el-tag>
                                </template>
                            </el-table-column>
                            <el-table-column label="Action" fixed="right">
                                <template #default="scope">
                                    <el-button link type="success" size="default"
                                        @click="viewOrderDetail(scope.row.id)">
                                        <Icon name="solar:eye-broken" class="text-base" />
                                    </el-button>
                                </template>
                            </el-table-column>

                        </BaseTable>
                    </div>

                    <el-alert v-if="error" :title="error.message" type="error" :closable="false" show-icon />

                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ tableData.length }} orders
                        </p>
                        <el-pagination v-if="pagination.total > pagination.per_page" background
                            layout="prev, pager, next" :current-page="pagination.current_page"
                            :page-size="pagination.per_page" :total="pagination.total" @current-change="setPage" />
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
import { useAdminOrders } from '~/composables/useAdminOrders'
import { getOrderStatusTagType, getPaymentStatusTagType } from '~/utils/orderStatusTheme'

const {
    filters,
    pagination,
    pending,
    error,
    tableData,
    orderStatus,
    ordersStates,
    viewOrderDetail,
    resetFilters,
    setPage,
} = useAdminOrders()

const paymentStatusOptions = [
    { id: '', label: 'All Payment Status' },
    { id: 'pending', label: 'Pending' },
    { id: 'paid', label: 'Paid' },
    { id: 'failed', label: 'Failed' },
    { id: 'refunded', label: 'Refunded' },
]

const customerOptions = [{ id: null, label: 'All Customers' }]

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth']
})

</script>

<style scoped></style>
