<template>
  <div>
    <HeaderBreadCrumb title="Admins">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/admins' }">Admins</el-breadcrumb-item>
      <el-breadcrumb-item>All Admins</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <BaseCard>
      <template #header>
        <div class="space-y-4">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="w-full lg:max-w-[360px]">
              <BaseInput v-model="filters.search_txt" placeholder="Search admins..." clearable />
            </div>

            <div class="flex flex-wrap gap-3">
              <BaseButton @click="resetFilters">Reset</BaseButton>
              <BaseButton v-if="can('admins', 'create')" type="primary" @click="openCreate">Add Admin</BaseButton>
            </div>
          </div>

        </div>
      </template>

      <div class="space-y-5">
        <div v-loading="pending">
          <BaseTable :table-data="tableData">
            <el-table-column label="Name" min-width="200">
              <template #default="scope">
                <div>
                  <p class="m-0 font-semibold text-slate-950">{{ scope.row.full_name }}</p>
                  <p class="m-0 text-xs text-slate-500">{{ scope.row.user_name }}</p>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="Role" prop="role" width="140">
              <template #default="scope">
                <el-tag :type="scope.row.role === 'super_admin' ? 'primary' : 'success'">
                  {{ scope.row.role }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="gender" label="Gender" width="110" />
            <el-table-column prop="email" label="Email" min-width="220" />
            <el-table-column prop="phone" label="Phone" min-width="140" />
            <el-table-column prop="created_at" label="Created At" min-width="180" />

            <el-table-column label="Action" fixed="right" width="150">
              <template #default="scope">
                <div class="flex items-center gap-1">
                  <BaseButton v-if="can('admins', 'edit')" link type="primary" size="default"
                    @click="openEdit(scope.row)">
                    <Icon name="solar:pen-new-round-broken" class="text-base" />
                  </BaseButton>
                  <BaseButton v-if="can('admins', 'delete')" link type="danger" size="default"
                    :loading="deletingId === scope.row.id" @click="deleteAdmin(scope.row)">
                    <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                  </BaseButton>
                </div>
              </template>
            </el-table-column>
          </BaseTable>
        </div>

        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="m-0 text-sm text-slate-500">
            Showing {{ tableData.length }} of {{ pagination.total }} admins
          </p>

          <el-pagination v-if="pagination.total > pagination.per_page" background layout="prev, pager, next"
            :current-page="pagination.current_page" :page-size="pagination.per_page" :total="pagination.total"
            @current-change="setPage" />
        </section>
      </div>
    </BaseCard>

    <AdminModal v-model="isModalAdmin" :mode="modalMode" :admin="selectedAdmin" :loading="loading"
      @submit="handleSubmit" />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import AdminModal from './Modal/AdminModal.vue'
import { useAdminAdmins } from '~/composables/useAdminAdmins'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

const adminAuthStore = useAdminAuthStore()
const can = adminAuthStore.can

const {
  filters,
  tableData,
  pending,
  loading,
  deletingId,
  isModalAdmin,
  modalMode,
  selectedAdmin,
  pagination,
  handleSubmit,
  openCreate,
  openEdit,
  deleteAdmin,
  resetFilters,
  setPage,
  loadAdmins,
} = useAdminAdmins()

onMounted(() => {
  void loadAdmins()
})
</script>
