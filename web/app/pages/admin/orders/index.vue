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
                            <BaseSelect v-model="filters.is_active" :options="statusOptions" placeholder="All Status"
                                class="w-full" />
                        </div>
                    </div>
                </template>

                <div class="space-y-5">



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

const filters = reactive({
    search_txt: "",
    is_active: ''
})
// defualt filter
const default_filters = reactive({
    search_txt: "",
    is_active: ''
})

const tableData = ref([
    { id: 1, user_name: "dyzak" }
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
