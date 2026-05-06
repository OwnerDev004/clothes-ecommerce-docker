<template>
    <div>
        <HeaderBreadCrumb title="Products">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/products' }">Products</el-breadcrumb-item>
            <el-breadcrumb-item>All Products</el-breadcrumb-item>
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
                                <BaseButton v-if="can('products', 'create')" type="primary" @click="addProduct">Add
                                    Product</BaseButton>
                            </div>
                        </div>

                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.category" :options="categoryOptions"
                                placeholder="All Categories" class="w-full" />
                            <BaseSelect v-model="filters.brand" :options="brandOptions" placeholder="All Brands"
                                class="w-full" />
                            <BaseSelect v-model="filters.color" :options="colorOptions" placeholder="All Colors"
                                class="w-full" />
                            <BaseSelect v-model="filters.size" :options="sizeOptions" placeholder="All Sizes"
                                class="w-full" />
                        </div>

                        <div class="grid gap-3 xl:grid-cols-4">
                            <BaseSelect v-model="filters.sort_by" :options="sortOptions" placeholder="Sort by"
                                class="w-full" />
                        </div>
                    </div>
                </template>

                <div class="space-y-5">
                    <BaseTable :table-data="tableData">
                        <el-table-column fixed="left" label="Product" width="320">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <el-image class="h-16 w-16 rounded-xl object-cover" :src="scope.row.image || ''"
                                        :preview-src-list="scope.row.previewImages" preview-teleported fit="cover" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ scope.row.sku || '-' }}</p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>

                        <el-table-column prop="price" label="Unit Price" width="140" />
                        <el-table-column prop="sale_price" label="Sale Price" width="140" />
                        <el-table-column prop="cost_price" label="Cost Price" width="140" />
                        <el-table-column prop="qty" label="Quantity" width="110" />
                        <el-table-column label="Stock" width="120">
                            <template #default="scope">
                                <el-tag :type="scope.row.stock === 'In Stock' ? 'success' : 'danger'">
                                    {{ scope.row.stock }}
                                </el-tag>
                            </template>
                        </el-table-column>

                        <el-table-column fixed="right" label="Action" min-width="160">
                            <template #default="scope">
                                <el-button v-if="can('products', 'view')" link type="success" size="default"
                                    @click="openProductDetail(scope.row.id)">
                                    <Icon name="ic:twotone-remove-red-eye" class="text-base" />
                                </el-button>
                                <el-button v-if="can('products', 'delete')" link type="danger" size="default"
                                    :loading="deletingId === scope.row.id" @click="deleteProduct(scope.row)">
                                    <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                </el-button>
                                <el-button v-if="can('products', 'edit')" link type="primary" size="default"
                                    @click="editProduct(scope.row)">
                                    <Icon name="solar:pen-new-round-broken" class="text-base" />
                                </el-button>
                            </template>
                        </el-table-column>
                    </BaseTable>

                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ tableData.length }} of {{ pagination.total }} products
                        </p>

                        <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
                            :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total"
                            layout="total, sizes, prev, pager, next" background />
                    </section>
                </div>
            </BaseCard>
        </section>

        <ProductModal v-model="isFormModal" :mode="modalMode" :product="selectedProduct" :size-options="sizeOptions"
            :loading="saving" @submit="handleProductSubmit" />

        <ProductDetailModal v-model="detailModalOpen" :product="selectedDetailProduct" :loading="detailLoading" />
    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import ProductModal from './components/Modal.vue'
import ProductDetailModal from './components/DetailModal.vue'
import { useAdminProducts } from '~/composables/useAdminProducts'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})
const adminAuthStore = useAdminAuthStore()
const can = adminAuthStore.can

const {
    filters,
    sortOptions,
    categoryOptions,
    brandOptions,
    colorOptions,
    sizeOptions,
    tableData,
    pagination,
    pending,
    error,
    isFormModal,
    modalMode,
    selectedProduct,
    saving,
    deletingId,
    detailModalOpen,
    detailLoading,
    selectedDetailProduct,
    resetFilters,
    addProduct,
    editProduct,
    openProductDetail,
    handleProductSubmit,
    deleteProduct,
} = useAdminProducts()
</script>
