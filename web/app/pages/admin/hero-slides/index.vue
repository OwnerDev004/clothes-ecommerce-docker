<template>
  <div>
    <HeaderBreadCrumb title="Hero Slides">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/hero-slides' }">Hero Slides</el-breadcrumb-item>
      <el-breadcrumb-item>All Slides</el-breadcrumb-item>
    </HeaderBreadCrumb>
    <section class="space-y-6">
      <BaseCard>
        <template #header>
          <div class="space-y-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div class="w-full lg:max-w-[360px]">
                <BaseInput v-model="filters.search_txt" placeholder="Search slides..." clearable />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <BaseButton @click="resetFilters">Reset</BaseButton>
                <BaseButton v-if="can('hero-slides', 'create')" type="primary" @click="addSlide">
                  Add Slide
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
          <BaseTable :table-data="dataTable.data">
            <el-table-column label="Slide" min-width="300">
              <template #default="scope">
                <div class="flex items-center gap-3">
                  <el-image class="h-16 w-24 rounded-xl object-cover" :src="scope.row.image_url || ''" fit="cover" />
                  <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-950">{{ scope.row.title }}</p>
                    <p v-if="scope.row.subtitle" class="text-xs text-slate-500">{{ scope.row.subtitle }}</p>
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Sort" width="80" align="center">
              <template #default="scope">
                <span class="text-sm font-medium">{{ scope.row.sort_order }}</span>
              </template>
            </el-table-column>

            <el-table-column label="Status" width="100">
              <template #default="scope">
                <el-tag :type="scope.row.status === 1 ? 'success' : 'danger'" size="small">
                  {{ slideStatusLabel(scope.row.status) }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Created" width="160">
              <template #default="scope">
                <span class="text-sm text-slate-500">{{ scope.row.created_at }}</span>
              </template>
            </el-table-column>

            <el-table-column fixed="right" label="Action" width="90">
              <template #default="scope">
                <BaseButton v-if="can('hero-slides', 'delete')" link type="danger" size="default"
                  :loading="deletingId === scope.row.id" @click="deleteSlide(scope.row.id)">
                  <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                </BaseButton>
                <BaseButton v-if="can('hero-slides', 'edit')" link type="primary" size="default"
                  @click="editSlide(scope.row)">
                  <Icon name="solar:pen-new-round-broken" class="text-base" />
                </BaseButton>
              </template>
            </el-table-column>
          </BaseTable>

          <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="m-0 text-sm text-slate-500">
              Showing {{ dataTable.data.length }} of {{ pagination.total }} slides
            </p>
            <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
              :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total"
              layout="total, sizes, prev, pager, next" background />
          </section>
        </div>
      </BaseCard>
    </section>
    <HeroSlideModal v-model="isFormModal" :mode="modalMode" :slide="selectedSlide" :loading="saving"
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
import { useAdminHeroSlide } from '~/composables/useAdminHeroSlide';
import { useAdminAuthStore } from '~/stores/adminAuthStore';
import HeroSlideModal from './components/Modal.vue'

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
  selectedSlide,
  slideStatusLabel,
  submitForm,
  resetFilters,
  addSlide,
  deleteSlide,
  editSlide,
} = useAdminHeroSlide()
</script>
