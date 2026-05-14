<template>
    <div>
        <HeaderBreadCrumb title="Categories">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/categories' }">Categories</el-breadcrumb-item>
            <el-breadcrumb-item>All Categories</el-breadcrumb-item>
        </HeaderBreadCrumb>
        <section class="space-y-6">
            <BaseCard>
                <template #header>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="w-full lg:w-[360px]">
                                <BaseInput v-model="filters.search_txt" placeholder="Search products..." clearable />
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <BaseButton @click="resetFilters">Reset Filters</BaseButton>
                                <BaseButton v-if="can('categories', 'create')" type="primary" @click="addCategory">Add Category</BaseButton>
                            </div>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.status" :options="statusOption" placeholder="All Status"
                                class="w-full" />
                            <BaseSelect v-model="filters.sort_by" :options="sortOptions" placeholder="Sort by"
                                class="w-full" />
                        </div>
                    </div>
                </template>

                <div class="space-y-5">
                    <BaseTable :table-data="dataTable.data">
                        <el-table-column fixed="left" label="Brand">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.name }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>
                        <el-table-column label="Image">
                            <template #default="scope">
                                <el-image class="h-16 w-16 rounded-xl object-cover" :src="scope.row.image_url || ''"
                                    :preview-src-list="scope.row.preview_image" preview-teleported fit="cover" />
                            </template>
                        </el-table-column>

                        <el-table-column prop="des" label="Description" />

                        <el-table-column label="Status" width="120">
                            <template #default="scope">
                                <el-tag :type="scope.row.status === 1 ? 'success' : 'danger'">
                                    {{ categoryStatus(scope.row.status) }}
                                </el-tag>
                            </template>
                        </el-table-column>

                        <el-table-column fixed="right" label="Action">
                            <template #default="scope">
                                <BaseButton v-if="can('categories', 'delete')" link type="danger" size="default" :loading="deletingId === scope.row.id"
                                    @click="deleteCategory(scope.row.id)">
                                    <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                </BaseButton>
                                <BaseButton v-if="can('categories', 'edit')" link type="primary" size="default" @click="editCategory(scope.row)">
                                    <Icon name="solar:pen-new-round-broken" class="text-base" />
                                </BaseButton>
                            </template>
                        </el-table-column>
                    </BaseTable>
x
                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ dataTable.data.length }} of {{ pagination.total }} products
                        </p>

                        <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
                            :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total"
                            layout="total, sizes, prev, pager, next" background />
                    </section>
                </div>
            </BaseCard>
        </section>
        <CategoryModal v-model="isFormModal" :mode="modalMode" :category="selectedCategory" :loading="saving"
            @submit="submitForm" />
    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseCard from '~/components/ui/BaseCard.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import BaseSelect from '~/components/ui/BaseSelect.vue';
import BaseTable from '~/components/ui/BaseTable.vue';
import { useAdminCategory } from '~/composables/useAdminCategory';
import { useAdminAuthStore } from '~/stores/adminAuthStore';
import CategoryModal from './components/Modal.vue'
// pageMeta
definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth']
})
const adminAuthStore = useAdminAuthStore()
const can = adminAuthStore.can
const {
    filters,
    pagination,
    deletingId,
    statusOption,
    sortOptions,
    dataTable,
    isFormModal,
    modalMode,
    saving,
    selectedCategory,
    categoryStatus,
    pending,
    error,
    submitForm,
    resetFilters,
    addCategory,
    deleteCategory,
    editCategory
} = useAdminCategory()

</script>

<style scoped></style>
