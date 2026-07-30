<template>
    <div>
        <HeaderBreadCrumb title="Admin Roles">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/roles' }">Admin Roles</el-breadcrumb-item>
            <el-breadcrumb-item>All Roles</el-breadcrumb-item>
        </HeaderBreadCrumb>

    <BaseCard>
      <template #header>
        <div class="space-y-4">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="w-full lg:max-w-[360px]">
              <BaseInput v-model="filters.search_txt" placeholder="Search roles..." clearable />
            </div>

            <div class="flex flex-wrap gap-3">
              <BaseButton @click="resetFilters">Reset</BaseButton>
              <BaseButton v-if="can('roles', 'create')" type="primary" @click="openCreate">Add Role</BaseButton>
            </div>
          </div>
        </div>
      </template>

      <div class="space-y-5">
        <div v-loading="pending">
          <BaseTable :table-data="tableData">
                        <el-table-column label="Role ID" prop="id" width="90" />
                        <el-table-column prop="name" label="Role Name" />
                        <el-table-column prop="slug" label="Slug" />
                        <el-table-column prop="created_at" label="Created At" />
                        <el-table-column prop="updated_at" label="Updated At" />

                        <el-table-column label="Action" fixed="right" width="120">
                            <template #default="scope">
                                <div class="flex items-center gap-1">
                                    <BaseButton v-if="can('roles', 'view')" link type="success" size="default"
                                        @click="goToPermissions(scope.row)">
                                        <Icon name="solar:eye-broken" class="text-base" />
                                    </BaseButton>
                                    <BaseButton v-if="can('roles', 'edit')" link type="primary" size="default"
                                        @click="openEdit(scope.row)">
                                        <Icon name="solar:pen-new-round-broken" class="text-base" />
                                    </BaseButton>
                                    <BaseButton v-if="can('roles', 'delete')" link type="danger" size="default"
                                        :loading="deletingId === scope.row.id" @click="deleteRole(scope.row)">
                                        <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                    </BaseButton>
                                </div>
                            </template>
                        </el-table-column>
                    </BaseTable>
                </div>

                <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="m-0 text-sm text-slate-500">
                        Showing {{ tableData.length }} of {{ pagination.total }} roles
                    </p>

                    <el-pagination v-if="pagination.total > pagination.per_page" background layout="prev, pager, next"
                        :current-page="pagination.current_page" :page-size="pagination.per_page"
                        :total="pagination.total" @current-change="setPage" />
                </section>
            </div>
        </BaseCard>

        <RoleModal v-model="isModalRole" :mode="modalMode" :roles="selectedRole" :loading="loading"
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
import RoleModal from './Modal/RoleModal.vue'
import { useAdminRoles } from '~/composables/useAdminRoles'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

const adminAuthStore = useAdminAuthStore()
const can = adminAuthStore.can
const router = useRouter()

const {
    filters,
    tableData,
    pending,
    loading,
    deletingId,
    isModalRole,
    modalMode,
    selectedRole,
    pagination,
    handleSubmit,
    openCreate,
    openEdit,
    deleteRole,
    resetFilters,
    setPage,
    loadRoles,
} = useAdminRoles()

const goToPermissions = (role: { id: number | string | null }) => {
    if (!role.id) {
        return
    }

    void router.push({
        path: '/admin/roles/permission',
        query: { role_id: String(role.id) },
    })
}

onMounted(() => {
    void loadRoles()
})
</script>
