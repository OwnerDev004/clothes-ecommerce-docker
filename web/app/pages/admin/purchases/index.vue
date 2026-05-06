<template>
    <div>
        <HeaderBreadCrumb title="Purchases">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item>Purchases</el-breadcrumb-item>
        </HeaderBreadCrumb>

        <section class="space-y-6">
            <BaseCard>
                <template #header>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="grid w-full gap-3 lg:grid-cols-[minmax(0,1fr)_280px]">
                                <BaseInput v-model="filters.search_txt"
                                    placeholder="Search product, size, color, note..." clearable />
                                <BaseSelect v-model="filters.product_variant_id" :options="variantFilterOptions"
                                    placeholder="All Variants" class="w-full" />
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <BaseButton @click="resetFilters">Reset Filters</BaseButton>
                                <BaseButton type="primary" @click="openPurchaseModal">Add Purchase</BaseButton>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="space-y-5">
                    <div v-loading="pending">
                        <BaseTable :table-data="tableData">
                            <el-table-column label="Variant" min-width="240">
                                <template #default="scope">
                                    <div class="min-w-0">
                                        <p class="m-0 truncate font-semibold text-slate-950">
                                            {{ scope.row.variant?.product?.name || 'Product Variant' }}
                                        </p>
                                        <p class="m-0 mt-1 truncate text-xs text-slate-500">
                                            {{ [scope.row.variant?.size?.name,
                                            scope.row.variant?.color].filter(Boolean).join(' • ') || '-' }}
                                        </p>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="quantity" label="Qty" width="90" />
                            <el-table-column label="Cost Price" width="130">
                                <template #default="scope">
                                    {{ formatMoney(scope.row.cost_price) }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Total Cost" width="130">
                                <template #default="scope">
                                    {{ formatMoney(scope.row.total_cost) }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Stock After" width="120">
                                <template #default="scope">
                                    {{ scope.row.variant?.stock_quantity ?? '-' }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Note" min-width="220">
                                <template #default="scope">
                                    {{ scope.row.note || '-' }}
                                </template>
                            </el-table-column>
                            <el-table-column label="Created By" min-width="180">
                                <template #default="scope">
                                    {{ creatorName(scope.row) }}
                                </template>
                            </el-table-column>
                            <el-table-column prop="created_at" label="Purchased At" min-width="180" />
                            <el-table-column label="Action" fixed="right" width="120">
                                <template #default="scope">
                                    <div class="flex items-center gap-1">
                                        <BaseButton link type="primary" size="default" @click="editPurchase(scope.row)">
                                            <Icon name="solar:pen-new-round-broken" class="text-base" />
                                        </BaseButton>
                                        <BaseButton link type="danger" size="default"
                                            :loading="deletingId === scope.row.id" @click="deletePurchase(scope.row)">
                                            <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                        </BaseButton>
                                    </div>
                                </template>
                            </el-table-column>
                        </BaseTable>
                    </div>

                    <el-alert v-if="error" :title="error.message" type="error" :closable="false" show-icon />

                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ tableData.length }} of {{ pagination.total }} purchases
                        </p>

                        <el-pagination v-if="pagination.total > pagination.per_page" background
                            layout="prev, pager, next" :current-page="pagination.current_page"
                            :page-size="pagination.per_page" :total="pagination.total" @current-change="setPage" />
                    </section>
                </div>
            </BaseCard>
        </section>

        <PurchaseModal v-model="modalOpen" :mode="modalMode" :purchase="selectedPurchase" :loading="saving"
            :variant-options="variantOptions" @submit="handleSubmit" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import PurchaseModal from './components/Modal.vue'
import { useAdminPurchases } from '~/composables/useAdminPurchases'

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

const {
    filters,
    pending,
    error,
    saving,
    deletingId,
    purchases,
    pagination,
    variantOptions,
    variantFilterOptions,
    resetFilters,
    modalOpen,
    modalMode,
    selectedPurchase,
    openPurchaseModal,
    editPurchase,
    closePurchaseModal,
    refreshAll,
    savePurchase,
    deletePurchase,
    setPage,
} = useAdminPurchases()

const tableData = computed(() => purchases.value || [])

const creatorName = (purchase: any) => {
    const creator = purchase?.creator
    if (!creator) return '-'
    return creator.first_name || creator.last_name
        ? `${creator.first_name || ''} ${creator.last_name || ''}`.trim()
        : creator.user_name || creator.email || '-'
}

const formatMoney = (value: unknown) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value || 0))
}

const handleSubmit = async (payload: {
    mode: 'create' | 'edit'
    purchaseId: number | string | null
    form: {
        product_variant_id: number | string | null
        quantity: number
        cost_price: number
        note: string
    }
}) => {
    try {
        await savePurchase(payload)
        closePurchaseModal()
    } catch {
        // savePurchase already shows the error message
    }
}

onMounted(() => {
    void refreshAll().catch(() => undefined)
})
</script>
