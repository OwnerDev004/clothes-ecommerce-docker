<template>
  <div>
    <HeaderBreadCrumb title="Sub Categories">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/sub_categories' }">Sub Categories</el-breadcrumb-item>
      <el-breadcrumb-item>All Sub Categories</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <section class="space-y-6">
      <BaseCard>
        <template #header>
          <div class="space-y-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div class="w-full lg:max-w-[360px]">
                <BaseInput v-model="filters.search_txt" placeholder="Search sub categories..." clearable />
              </div>

              <div class="flex flex-wrap gap-3">
                <BaseButton @click="resetFilters">Reset</BaseButton>
                <BaseButton v-if="can('categories', 'create')" type="primary" @click="addSubCategory">
                  Add Sub Category
                </BaseButton>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <BaseSelect v-model="filters.status" :options="statusOption" placeholder="All Status" class="w-full" />
              <BaseSelect v-model="filters.sort_by" :options="sortOptions" placeholder="Sort by" class="w-full" />
            </div>
          </div>
        </template>

        <div class="space-y-5">
          <BaseTable :table-data="dataTable">
            <el-table-column fixed="left" label="Sub Category" min-width="220">
              <template #default="scope">
                <div class="flex items-center gap-3">
                  <el-avatar :src="scope.row.image_url || ''" :size="40" shape="square">
                    {{ scope.row.name?.charAt(0) || 'S' }}
                  </el-avatar>
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-950">{{ scope.row.name }}</p>
                    <p class="truncate text-xs text-slate-500">Slug: {{ scope.row.slug || '-' }}</p>
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Category" min-width="180">
              <template #default="scope">
                <el-tag effect="light" type="info">{{ scope.row.category_label }}</el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Tags" min-width="200">
              <template #default="scope">
                <span class="text-sm text-slate-600">{{ scope.row.parent_label }}</span>
              </template>
            </el-table-column>

            <el-table-column label="Level" width="120">
              <template #default="scope">
                <el-tag effect="plain" type="info">{{ scope.row.level_label }}</el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Image" width="120">
              <template #default="scope">
                <el-image class="h-16 w-16 rounded-xl object-cover" :src="scope.row.image_url || ''"
                  :preview-src-list="scope.row.preview_image" preview-teleported fit="cover" />
              </template>
            </el-table-column>

            <el-table-column prop="des" label="Description" min-width="240" />

            <el-table-column label="Status" width="120">
              <template #default="scope">
                <el-tag :type="scope.row.status_value === 1 ? 'success' : 'danger'">
                  {{ categoryStatus(scope.row.status_value) }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column fixed="right" label="Action" width="150">
              <template #default="scope">
                <BaseButton v-if="can('categories', 'delete')" link type="danger" size="default"
                  :loading="deletingId === scope.row.id" @click="deleteSubCategory(scope.row.id)">
                  <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                </BaseButton>
                <BaseButton v-if="can('categories', 'edit')" link type="primary" size="default"
                  @click="editSubCategory(scope.row)">
                  <Icon name="solar:pen-new-round-broken" class="text-base" />
                </BaseButton>
              </template>
            </el-table-column>
          </BaseTable>

          <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="m-0 text-sm text-slate-500">
              Showing {{ dataTable.length }} of {{ pagination.total }} sub categories
            </p>

            <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
              :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total" layout="total, sizes, prev, pager, next"
              background />
          </section>
        </div>
      </BaseCard>
    </section>

    <SubCategoryModal v-model="isFormModal" :mode="modalMode" :sub-category="selectedSubCategory"
      :categories="categoryOptions" :parent-sub-categories="parentSubCategories" :loading="saving"
      @submit="submitSubCategory" />
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted } from 'vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import SubCategoryModal from './components/Modal.vue'
import { useAdminSubCategory } from '~/composables/useAdminSubCategory'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
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
  categoryOptions,
  parentSubCategories,
  isFormModal,
  modalMode,
  selectedSubCategory,
  saving,
  categoryStatus,
  resetFilters,
  addSubCategory,
  deleteSubCategory,
  editSubCategory,
  submitSubCategory,
} = useAdminSubCategory()

const route = useRoute()
const router = useRouter()

const clearActionQuery = async () => {
  const { action, ...query } = route.query
  await router.replace({ path: route.path, query })
}

onMounted(async () => {
  if (String(route.query.action || '') !== 'create') {
    return
  }

  addSubCategory()
  await nextTick()
  await clearActionQuery()
})
</script>
