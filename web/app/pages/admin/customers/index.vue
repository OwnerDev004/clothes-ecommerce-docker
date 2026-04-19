<template>
    <div>
        <HeaderBreadCrumb title="Customers">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/customers' }">Customers</el-breadcrumb-item>
            <el-breadcrumb-item>All Customers</el-breadcrumb-item>
        </HeaderBreadCrumb>
        <section class="space-y-6">
            <BaseCard>
                <template #header>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="w-full lg:w-[360px]">
                                <BaseInput v-model="filters.search_txt" placeholder="Search Customers..." clearable />
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <!-- <BaseButton @click="s">Reset Filters</BaseButton> -->
                                <BaseButton type="primary" @click="addCustomer">Add Customer</BaseButton>
                            </div>
                        </div>
                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.category" :options="categoriesOptions"
                                placeholder="All Categories" class="w-full" />
                            <BaseSelect v-model="filters.status" :options="statusOptions" placeholder="All Status"
                                class="w-full" />

                        </div>

                    </div>
                </template>
            </BaseCard>
        </section>

    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})
const filters = reactive({
    search_txt: '',
    category: '',
    status: ''
})

</script>

<style scoped></style>