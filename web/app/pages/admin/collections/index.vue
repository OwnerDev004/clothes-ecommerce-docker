<template>
    <div>
        <HeaderBreadCrumb title="Collections">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/collections' }">Collections</el-breadcrumb-item>
            <el-breadcrumb-item>All Collections</el-breadcrumb-item>
        </HeaderBreadCrumb>

        <section class="space-y-6">
            <BaseCard>
                <template #header>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="w-full lg:max-w-[360px]">
                                <BaseInput v-model="filters.search_txt" placeholder="Search collections..." clearable />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <BaseButton @click="resetFilters">Reset Filters</BaseButton>
                                <BaseButton type="primary" @click="addCollection">Add Collection</BaseButton>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <BaseSelect v-model="filters.category" :options="categoriesOptions"
                                placeholder="All Categories" class="w-full" />
                            <BaseSelect v-model="filters.status" :options="statusOptions" placeholder="All Status"
                                class="w-full" />

                        </div>

                    </div>
                </template>
                <div class="space-y-5">
                    <BaseTable :table-data="collectionsData.data">
                        <el-table-column fixed="left" label="Name">
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

                        <el-table-column prop="desc" label="Description" />
                        <el-table-column fixed="left" label="Category">
                            <template #default="scope">
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-950">{{ scope.row.category?.name }}
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </el-table-column>


                        <el-table-column label="Status">
                            <template #default="scope">
                                <el-tag :type="scope.row.status === 'published' ? 'success' : 'danger'">
                                    {{ scope.row.status }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column fixed="right" label="Action">
                            <template #default="scope">
                                <BaseButton link type="danger" size="default" :loading="deletingId === scope.row.id"
                                    @click="deleteCollection(scope.row.id)">
                                    <Icon name="solar:trash-bin-minimalistic-2-broken" class="text-base" />
                                </BaseButton>
                                <BaseButton link type="primary" size="default" @click="editCollection(scope.row)">
                                    <Icon name="solar:pen-new-round-broken" class="text-base" />
                                </BaseButton>
                            </template>
                        </el-table-column>
                    </BaseTable>

                    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="m-0 text-sm text-slate-500">
                            Showing {{ collectionsData.data.length }} of {{ pagination.total }} products
                        </p>

                        <el-pagination v-model:current-page="filters.page" v-model:page-size="filters.per_page"
                            :page-sizes="[10, 20, 30, 40, 50]" :total="pagination.total"
                            layout="total, sizes, prev, pager, next" background />
                    </section>
                </div>
            </BaseCard>
        </section>
        <CollectionModal v-model="isCollectionModal" :mode="modalMode" :collection="selectedData" :loading="saving"
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
import { useAdminCollections } from '~/composables/useAdminCollections'
import CollectionModal from './components/Modal.vue'
definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

const {
    filters,
    collectionsData,
    selectedData,
    pagination,
    resetFilters,
    statusOptions,
    deletingId,
    saving,
    isCollectionModal,
    modalMode,
    addCollection,
    deleteCollection,
    editCollection,
    fetchCollections,
    submitForm,
} = useAdminCollections()

const { categoriesResponse } = useAdminCategory()
const categoriesOptions = computed(() => {
    return categoriesResponse.value.data.map((e) => ({ id: e.id, label: e.name }))
})

// functions

// const resetFilters = () => {
//     Object.assign(filters, ...[defaultFilter]);
// }


</script>

<style scoped></style>
