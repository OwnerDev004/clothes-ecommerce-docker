<template>
    <HeaderBreadCrumb title="Products">
        <el-breadcrumb-item :to="{ path: '/admin' }">Dashboard</el-breadcrumb-item>
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
                            <BaseButton type="primary" @click="addProduct">Add Product</BaseButton>
                        </div>
                    </div>

                    <div class="grid gap-3 xl:grid-cols-4">
                        <BaseSelect v-model="filters.category" :options="categoryOptions" placeholder="All Categories"
                            class="w-full" />
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
                            <el-button link type="success" size="default" @click="openProductDetail(scope.row.id)">
                                <Icon name="ic:twotone-remove-red-eye" class="text-base" />
                            </el-button>
                            <el-button link type="danger" size="default" :loading="deletingId === scope.row.id"
                                @click="deleteProduct(scope.row)">
                                <Icon name="ic:twotone-delete-forever" class="text-base" />
                            </el-button>
                            <el-button link type="primary" size="default" @click="editProduct(scope.row)">
                                <Icon name="ic:outline-edit" class="text-base" />
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

    <ProductModal v-model="isFormModal" :mode="modalMode" :product="selectedProduct" :loading="saving"
        @submit="handleProductSubmit" />

    <ProductDetailModal v-model="detailModalOpen" :product="selectedDetailProduct" :loading="detailLoading" />
</template>

<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { storeToRefs } from 'pinia'
import { watchDebounced } from '@vueuse/core'
import { onMounted, watch } from 'vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseTable from '~/components/ui/BaseTable.vue'
import ProductModal from './components/Modal.vue'
import ProductDetailModal from './components/DetailModal.vue'
import { useAdminAuthStore } from '~/stores/adminAuthStore'
import {
    useAdminProduct,
    type AdminProductRecord,
    type AdminProductVariantRecord,
    type AdminProductTableRow,
} from '~/composables/useAdminProduct'

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

type AdminProductListMeta = {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

type AdminProductListResponse = {
    data: AdminProductRecord[]
    meta?: AdminProductListMeta
}

type ProductFiltersPayload = {
    categories?: Array<{ id: number | string; name?: string | null }>
    colors?: Array<{ id: string; name?: string | null }>
    sizes?: Array<{ id: number | string; name?: string | null }>
    brands?: Array<{ id: number | string; name?: string | null }>
}

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const adminAuthStore = useAdminAuthStore()
const { accessToken } = storeToRefs(adminAuthStore)
const { normalizeAdminProductRow } = useAdminProduct()

const filters = reactive({
    search_txt: '',
    category: null as string | number | null,
    brand: null as string | number | null,
    color: null as string | number | null,
    size: null as string | number | null,
    sort_by: 'latest',
    page: 1,
    per_page: 10,
})

const sortOptions = [
    { id: 'latest', label: 'Latest' },
    { id: 'oldest', label: 'Oldest' },
    { id: 'price_low', label: 'Price low to high' },
    { id: 'price_high', label: 'Price high to low' },
    { id: 'name_asc', label: 'Name A-Z' },
    { id: 'name_desc', label: 'Name Z-A' },
]

const resolveAuthHeaders = () => {
    return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined
}

const buildProductsQuery = () => {
    const query: Record<string, string | number> = {
        page: filters.page,
        per_page: filters.per_page,
        sort_by: filters.sort_by,
    }

    const add = (key: string, value: string | number | null) => {
        if (value !== null && value !== '') {
            query[key] = value
        }
    }

    add('search_txt', filters.search_txt.trim())
    add('category', filters.category)
    add('brand', filters.brand)
    add('color', filters.color)
    add('size', filters.size)

    return query
}

const fetchProducts = async () => {
    const response: any = await $fetch(`${apiBase}/admin/products`, {
        method: 'GET',
        headers: resolveAuthHeaders(),
        query: buildProductsQuery(),
    })

    return {
        data: Array.isArray(response?.data) ? response.data : [],
        meta: response?.meta || {
            current_page: 1,
            last_page: 1,
            per_page: filters.per_page,
            total: 0,
        },
    } as AdminProductListResponse
}

const fetchFilterOptions = async () => {
    const response: any = await $fetch(`${apiBase}/products/filters`, {
        method: 'GET',
        headers: resolveAuthHeaders(),
    })

    return (response?.data || {}) as ProductFiltersPayload
}

const productsResponse = ref<AdminProductListResponse>({
    data: [],
    meta: {
        current_page: 1,
        last_page: 1,
        per_page: filters.per_page,
        total: 0,
    },
})
const pending = ref(false)
const error = ref<Error | null>(null)

const loadProducts = async () => {
    if (!accessToken.value) {
        return
    }

    pending.value = true
    error.value = null

    try {
        productsResponse.value = await fetchProducts()
    } catch (err) {
        error.value = err as Error
        productsResponse.value = {
            data: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: filters.per_page,
                total: 0,
            },
        }
    } finally {
        pending.value = false
    }
}

const { data: filterOptionsResponse } = useAsyncData<ProductFiltersPayload>(
    'admin-product-filters',
    fetchFilterOptions,
    {
        server: false,
        immediate: true,
        getCachedData: () => undefined,
    },
)

const categoryOptions = computed(() =>
    [
        { id: null, label: 'All Categories' },
        ...(filterOptionsResponse.value?.categories || []).map((item) => ({
            id: item.id,
            label: item.name || 'Uncategorized',
        })),
    ],
)

const brandOptions = computed(() =>
    [
        { id: null, label: 'All Brands' },
        ...(filterOptionsResponse.value?.brands || []).map((item) => ({
            id: item.id,
            label: item.name || 'Brand',
        })),
    ],
)

const colorOptions = computed(() =>
    [
        { id: null, label: 'All Colors' },
        ...(filterOptionsResponse.value?.colors || []).map((item) => ({
            id: item.id,
            label: item.name || item.id,
        })),
    ],
)

const sizeOptions = computed(() =>
    [
        { id: null, label: 'All Sizes' },
        ...(filterOptionsResponse.value?.sizes || []).map((item) => ({
            id: item.id,
            label: item.name || 'Size',
        })),
    ],
)

const tableData = computed<AdminProductTableRow[]>(() => {
    return (productsResponse.value?.data || []).map(normalizeAdminProductRow)
})

const pagination = computed(() => ({
    current_page: productsResponse.value?.meta?.current_page || 1,
    last_page: productsResponse.value?.meta?.last_page || 1,
    per_page: productsResponse.value?.meta?.per_page || filters.per_page,
    total: productsResponse.value?.meta?.total || 0,
}))

const isFormModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const selectedProduct = ref<AdminProductRecord | null>(null)
const saving = ref(false)
const deletingId = ref<number | string | null>(null)
const detailModalOpen = ref(false)
const detailLoading = ref(false)
const selectedDetailProduct = ref<AdminProductRecord | null>(null)

const resetFilters = () => {
    filters.search_txt = ''
    filters.category = null
    filters.brand = null
    filters.color = null
    filters.size = null
    filters.sort_by = 'latest'
    filters.page = 1
    void loadProducts()
}

const addProduct = () => {
    selectedProduct.value = null
    modalMode.value = 'create'
    isFormModal.value = true
}

const editProduct = (product: AdminProductRecord) => {
    selectedProduct.value = product
    modalMode.value = 'edit'
    isFormModal.value = true
}

const openProductDetail = async (productId: number | string) => {
    detailModalOpen.value = true
    detailLoading.value = true
    selectedDetailProduct.value = null

    try {
        const response: any = await $fetch(`${apiBase}/admin/products/${productId}`, {
            method: 'GET',
            headers: resolveAuthHeaders(),
        })

        selectedDetailProduct.value = response?.data?.data || response?.data || null
    } catch (error) {
        console.error('Failed to load product detail', error)
        ElMessage.error('Failed to load product detail.')
    } finally {
        detailLoading.value = false
    }
}

const appendFormValue = (formData: FormData, key: string, value: unknown): void => {
    if (value === null || value === undefined || value === '') {
        return
    }

    if (value instanceof File) {
        formData.append(key, value)
        return
    }

    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            appendFormValue(formData, `${key}[${index}]`, item)
        })
        return
    }

    if (typeof value === 'object') {
        Object.entries(value as Record<string, unknown>).forEach(([childKey, childValue]) => {
            appendFormValue(formData, `${key}[${childKey}]`, childValue)
        })
        return
    }

    formData.append(key, String(value))
}

const generateSkuFallback = (name: string, index: number) => {
    const prefix = (name || 'VAR')
        .replace(/[^a-zA-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 8)
        .toUpperCase() || 'VAR'

    return `${prefix}-${Date.now().toString(36).toUpperCase()}-${index + 1}`
}

const syncProductVariants = async (
    productId: number | string,
    productName: string,
    submittedVariants: Array<{
        id?: number | string | null
        sku: string
        color: string
        stock_quantity: string
        sale_price: string
        cost_price: string
    }>,
    originalVariants: AdminProductVariantRecord[] = [],
) => {
    const originalMap = new Map<string, AdminProductVariantRecord>()
    originalVariants.forEach((variant) => {
        if (variant.id === undefined || variant.id === null) {
            return
        }
        originalMap.set(String(variant.id), variant)
    })

    const keepIds = new Set(
        submittedVariants
            .map((variant) => (variant.id === undefined || variant.id === null ? null : String(variant.id)))
            .filter((value): value is string => Boolean(value)),
    )

    const upsertTasks = submittedVariants.map(async (variant, index) => {
        const normalizedSku = variant.sku?.trim() || generateSkuFallback(productName, index)
        const payload = {
            product_id: productId,
            sku: normalizedSku,
            color: variant.color,
            stock_quantity: Number(variant.stock_quantity || 0),
            sell_price: Number(variant.sale_price || 0),
            cost_price: Number(variant.cost_price || 0),
        }

        if (variant.id !== undefined && variant.id !== null && originalMap.has(String(variant.id))) {
            await $fetch(`${apiBase}/admin/product_variants/${variant.id}`, {
                method: 'POST',
                credentials: 'include',
                headers: resolveAuthHeaders(),
                body: {
                    _method: 'PUT',
                    ...payload,
                },
            })
            return
        }

        await $fetch(`${apiBase}/admin/product_variants`, {
            method: 'POST',
            credentials: 'include',
            headers: resolveAuthHeaders(),
            body: payload,
        })
    })

    const deleteTasks = originalVariants
        .filter((variant) => variant.id !== undefined && variant.id !== null)
        .filter((variant) => !keepIds.has(String(variant.id)))
        .map((variant) =>
            $fetch(`${apiBase}/admin/product_variants/${variant.id}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: resolveAuthHeaders(),
            }),
        )

    await Promise.all([...upsertTasks, ...deleteTasks])
}

const createProductRequest = async (payload: any) => {
    const formData = new FormData()
    appendFormValue(formData, 'name', payload.form.product.name)
    appendFormValue(formData, 'desc', payload.form.product.description || '')
    appendFormValue(formData, 'price', payload.form.product.unit_price)
    appendFormValue(formData, 'category_id', payload.form.product.category_id)
    appendFormValue(formData, 'images', payload.images.new_images)

    const response: any = await $fetch(`${apiBase}/admin/products`, {
        method: 'POST',
        credentials: 'include',
        headers: resolveAuthHeaders(),
        body: formData,
    })

    const productId = response?.data?.id
    if (!productId) {
        throw new Error('Product was created but no product id was returned.')
    }

    await syncProductVariants(productId, payload.form.product.name, payload.form.product_variants, [])
}

const updateProductRequest = async (payload: any) => {
    const formData = new FormData()
    appendFormValue(formData, '_method', 'PUT')
    appendFormValue(formData, 'name', payload.form.product.name)
    appendFormValue(formData, 'desc', payload.form.product.description || '')
    appendFormValue(formData, 'price', payload.form.product.unit_price)
    appendFormValue(formData, 'category_id', payload.form.product.category_id)

    if (!payload.images.existing_images.length && !payload.images.new_images.length) {
        formData.append('clear_images', '1')
    } else {
        appendFormValue(formData, 'existing_images', payload.images.existing_images)
        appendFormValue(formData, 'new_images', payload.images.new_images)
    }

    await $fetch(`${apiBase}/admin/products/${payload.productId}`, {
        method: 'POST',
        credentials: 'include',
        headers: resolveAuthHeaders(),
        body: formData,
    })

    await syncProductVariants(
        payload.productId,
        payload.form.product.name,
        payload.form.product_variants,
        selectedProduct.value?.variants || [],
    )
}

const handleProductSubmit = async (payload: {
    mode: 'create' | 'edit'
    productId: number | string | null
    form: {
        product: {
            name: string
            category_id: string | number | null
            status: 'draft' | 'active' | 'archived'
            unit_price: string
            description: string
        }
        product_variants: Array<{
            id?: number | string | null
            sku: string
            color: string
            stock_quantity: string
            sale_price: string
            cost_price: string
        }>
    }
    images: {
        existing_images: Array<{
            id: number | string
            image_type: 'thumbnail' | 'gallery'
            sort_order: number
        }>
        new_images: Array<{
            file: File
            image_type: 'thumbnail' | 'gallery'
            sort_order: number
        }>
    }
}) => {
    try {
        saving.value = true

        if (payload.mode === 'edit' && payload.productId) {
            await updateProductRequest(payload)
            ElMessage.success('Product updated.')
        } else {
            await createProductRequest(payload)
            ElMessage.success('Product created.')
        }

        await loadProducts()
        isFormModal.value = false
        selectedProduct.value = null
        modalMode.value = 'create'
    } catch (error) {
        console.error('Failed to save product', error)
        ElMessage.error('Failed to save product.')
    } finally {
        saving.value = false
    }
}

const deleteProduct = async (product: AdminProductRecord) => {
    if (!product?.id) {
        return
    }

    try {
        await ElMessageBox.confirm(
            `Delete "${product.name}"? This also removes its images from Cloudinary.`,
            'Confirm delete',
            {
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                type: 'warning',
            },
        )

        deletingId.value = product.id
        await $fetch(`${apiBase}/admin/products/${product.id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: resolveAuthHeaders(),
        })

        await loadProducts()
        ElMessage.success('Product deleted.')
    } catch (error: any) {
        if (error !== 'cancel' && error !== 'close') {
            ElMessage.error(error?.data?.message || 'Failed to delete product.')
        }
    } finally {
        deletingId.value = null
    }
}

watchDebounced(
    [
        () => filters.search_txt,
        () => filters.category,
        () => filters.brand,
        () => filters.color,
        () => filters.size,
        () => filters.sort_by,
    ],
    () => {
        filters.page = 1
        void loadProducts()
    },
    { debounce: 300, maxWait: 600 },
)

watch(
    () => [filters.page, filters.per_page],
    () => {
        void loadProducts()
    },
)

watch(
    () => accessToken.value,
    () => {
        void loadProducts()
    },
    { immediate: true },
)

onMounted(() => {
    void loadProducts()
})
</script>
