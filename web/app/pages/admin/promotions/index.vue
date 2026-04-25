<template>
  <div>
    <HeaderBreadCrumb title="Promotions">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/promotions' }">Promotions</el-breadcrumb-item>
      <el-breadcrumb-item>Voucher Campaigns</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <section class="space-y-6">
      <BaseCard>
        <template #header>
          <div class="space-y-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div class="w-full lg:w-[360px]">
                <BaseInput v-model="filters.search_txt" placeholder="Search voucher code or name..." clearable />
              </div>

              <div class="flex flex-wrap gap-3">
                <BaseButton @click="resetFilters">Reset Filters</BaseButton>
                <BaseButton type="primary" @click="addVoucher">Add Promotion</BaseButton>
              </div>
            </div>

            <div class="grid gap-3 xl:grid-cols-4">
              <BaseSelect v-model="filters.is_active" :options="statusOptions" placeholder="All Status"
                class="w-full" />
            </div>
          </div>
        </template>

        <div class="space-y-5">
          <el-alert v-if="error" :title="error.message || 'Failed to load promotions.'" type="error" show-icon
            :closable="false" />
          <BaseTable :table-data="tableData" :v-loading="pending">
            <el-table-column fixed="left" label="Code" min-width="150">
              <template #default="scope">
                <div class="space-y-1">
                  <p class="m-0 font-semibold text-slate-950">{{ scope.row.code }}</p>
                  <p class="m-0 text-xs text-slate-500">{{ scope.row.name }}</p>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Discount" min-width="160">
              <template #default="scope">
                <div class="space-y-1">
                  <el-tag :type="scope.row.discount_type === 'percentage' ? 'success' : 'warning'">
                    {{ formatDiscount(scope.row) }}
                  </el-tag>
                  <p class="m-0 text-xs text-slate-500 capitalize">
                    {{ scope.row.discount_type?.replace('_', ' ') }}
                  </p>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Rules" min-width="220">
              <template #default="scope">
                <div class="flex flex-wrap gap-2">
                  <el-tag v-if="scope.row.is_signup_coupon" type="info">Signup coupon</el-tag>
                  <el-tag v-if="scope.row.first_order_only" type="warning">First order only</el-tag>
                  <el-tag v-if="scope.row.minimum_order_amount" type="success">
                    Min {{ scope.row.minimum_order_amount }}
                  </el-tag>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Status" width="120">
              <template #default="scope">
                <el-tag :type="scope.row.is_active ? 'success' : 'danger'">
                  {{ scope.row.is_active ? 'Active' : 'Inactive' }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Usage" width="120">
              <template #default="scope">
                <span class="font-medium text-slate-700">{{ scope.row.uses_count || 0 }}</span>
              </template>
            </el-table-column>

            <el-table-column label="Expires At" width="150">
              <template #default="scope">
                <span class="text-slate-600">
                  {{ scope.row.expires_at || '-' }}
                </span>
              </template>
            </el-table-column>

            <el-table-column fixed="right" label="Action" min-width="160">
              <template #default="scope">
                <BaseButton link type="danger" size="default" :loading="deletingId === scope.row.id"
                  @click="deleteVoucher(scope.row)">
                  <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                </BaseButton>
                <BaseButton link type="primary" size="default" @click="editVoucher(scope.row)">
                  <Icon name="solar:pen-new-round-broken" class="text-base" />
                </BaseButton>
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

    <PromotionModal v-model="isFormModal" :mode="modalMode" :voucher="selectedVoucher" :loading="saving"
      @submit="submitForm" />
  </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import PromotionModal from './components/Modal.vue'
import { useAdminVoucher, type AdminVoucherRecord } from '~/composables/useAdminVoucher'

definePageMeta({
  layout: 'admin',
  middleware: 'admin-auth',
})

const {
  filters,
  statusOptions,
  tableData,
  pending,
  error,
  isFormModal,
  modalMode,
  selectedVoucher,
  saving,
  deletingId,
  resetFilters,
  addVoucher,
  editVoucher,
  deleteVoucher,
  submitForm,
} = useAdminVoucher()

const formatDiscount = (voucher: AdminVoucherRecord) => {
  const value = voucher?.discount_value ?? 0

  if (voucher?.discount_type === 'percentage') {
    return `${value}%`
  }

  return `${value}`
}
</script>
