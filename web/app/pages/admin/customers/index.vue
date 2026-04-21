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
                        </div>
                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.sort_by" :options="sortOptions" placeholder="All Categories"
                                class="w-full" />
                            <BaseSelect v-model="filters.status" :options="statusOptions" placeholder="All Status"
                                class="w-full" />

                        </div>

                    </div>
                </template>

                <div class="space-y-5">
                    <BaseTable :table-data="dataTable?.data">
                        <el-table-column label="Profile" fixed width="120">
                            <template #default="scope">
                                <el-image class="h-16 w-16 rounded-xl object-cover" :src="scope.row.avatar_url || ''"
                                    :preview-src-list="[scope.row.avatar_url]" preview-teleported fit="cover" />
                            </template>
                        </el-table-column>
                        <el-table-column label="Full Name">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.full_name }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="User Name">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.user_name }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Gender">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.gender || 'None'
                                        }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>

                        <el-table-column label="Email">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.email || 'None'
                                        }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Phone">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.phone || 'None'
                                        }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Address">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.address || 'None'
                                        }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Telegram UserName">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.telegram_username
                                            || 'None'
                                        }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Status">
                            <template #default="scope">
                                <el-tag :type="scope.row.status == customerStatus.Active ? 'success' : 'danger'">
                                    {{ getDisplayCustomerStatus(scope.row.status) }}
                                </el-tag>
                            </template>
                        </el-table-column>

                        <el-table-column fixed="right" label="Action" min-width="120">
                            <template #default="scope">
                                <BaseButton link type="danger" size="default" :loading="deletingId === scope.row.id"
                                    @click="deleteCustomer(scope.row)">
                                    <Icon name="ic:twotone-delete-forever" class="text-base" />
                                </BaseButton>
                                <BaseButton link type="primary" size="default" @click="editCustomer(scope.row)">
                                    <Icon name="ic:outline-edit" class="text-base" />
                                </BaseButton>
                                <BaseButton link type="warning" size="default"
                                    @click="resetCustomerPassword(scope.row)">
                                    <Icon name="material-symbols:mail-shield-outline" class="text-base" />
                                </BaseButton>
                            </template>
                        </el-table-column>
                    </BaseTable>

                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ dataTable?.data.length }} of {{ pagination.per_page }} products
                        </p>

                        <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
                            :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total"
                            layout="total, sizes, prev, pager, next" background />
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
import BaseSelect from '~/components/ui/BaseSelect.vue';
import BaseTable from '~/components/ui/BaseTable.vue';
import { useAdminCustomer } from '~/composables/useAdminCustomer';
import { customerStatus, getDisplayCustomerStatus } from '~/enums/customerStatus';
definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})
const {
    filters,
    deletingId,
    dataTable,
    pagination,
    sortOptions,
    statusOptions,
    editCustomer,
    resetCustomerPassword,
    deleteCustomer,
} = useAdminCustomer()


</script>

<style scoped></style>