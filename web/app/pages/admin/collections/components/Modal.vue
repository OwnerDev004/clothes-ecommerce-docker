<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Check, Delete, ZoomIn } from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseImageUpload from '~/components/ui/BaseImageUpload.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import { useAdminCategory } from '~/composables/useAdminCategory'
import { useAdminProducts } from '~/composables/useAdminProducts'
import type { AdminCollectionsRecord } from '~/composables/useAdminCollections'

type CollectionProductSummary = {
    id: number | string
    name: string
    price?: number | string | null
    image?: string | null
}

type CollectionDetailRecord = AdminCollectionsRecord & {
    products?: CollectionProductSummary[]
    products_count?: number | null
}

type CollectionSubmitPayload = {
    mode: 'create' | 'edit'
    collectionId: string | number | null
    form: {
        name: string
        desc: string
        categoryId: string | number | null
        status: string
        sort_order: string
    }
    image: File | null
    remove_image: boolean
    productIds: Array<string | number>
}

const model = defineModel<boolean>()

const props = withDefaults(
    defineProps<{
        mode?: 'create' | 'edit'
        collection: CollectionDetailRecord | null
        loading?: boolean
    }>(),
    {
        mode: 'create',
        collection: null,
        loading: false,
    },
)




const emit = defineEmits<{
    (e: 'submit', payload: CollectionSubmitPayload): void
}>()

const { tableData } = useAdminProducts()
const { categoriesResponse } = useAdminCategory()

const form = reactive({
    name: '',
    desc: '',
    category_id: null as string | number | null,
    status: 'draft',
    sort_order: '',
})

const statusOptions = [
    { id: 'draft', label: 'Draft' },
    { id: 'published', label: 'Published' },
]

const productSearch = ref('')
const imagePreview = ref('')
const selectedImageFile = ref<File | null>(null)
const initialImagePreview = ref('')
const selectedProductIds = ref<Array<string | number>>([])

const categoriesOptions = computed(() => {
    return categoriesResponse.value?.data?.map((category) => ({
        id: category.id,
        label: category.name,
    })) || []
})

const normalizedProducts = computed(() => {
    return tableData.value || []
})

const collectionProducts = computed(() => {
    return Array.isArray(props.collection?.products) ? props.collection.products : []
})

const productSummaryMap = computed(() => {
    const map = new Map<string, CollectionProductSummary>()

    collectionProducts.value.forEach((product) => {
        map.set(String(product.id), product)
    })

    normalizedProducts.value.forEach((product) => {
        map.set(String(product.id), {
            id: product.id,
            name: product.name,
            price: product.price,
            image: product.image || null,
        })
    })

    return map
})

const selectedProducts = computed(() => {
    return selectedProductIds.value.map((id) => {
        const summary = productSummaryMap.value.get(String(id))
        return summary || {
            id,
            name: `Product #${id}`,
            price: null,
            image: null,
        }
    })
})

const filteredProducts = computed(() => {
    const query = productSearch.value.trim().toLowerCase()
    if (!query) {
        return normalizedProducts.value
    }

    return normalizedProducts.value.filter((product) => {
        return String(product.name || '').toLowerCase().includes(query)
    })
})

const selectedCategoryLabel = computed(() => {
    return categoriesOptions.value.find((category) => category.id === form.category_id)?.label || 'Unassigned'
})

const selectedImageState = computed(() => {
    if (selectedImageFile.value) {
        return 'New upload'
    }

    if (imagePreview.value) {
        return 'Existing image'
    }

    return 'No image'
})

const syncFromCollection = () => {
    form.name = props.collection?.name || ''
    form.desc = props.collection?.desc || ''
    form.category_id = props.collection?.category_id ?? null
    form.status = props.collection?.status || 'draft'
    form.sort_order = props.collection?.sort_order !== undefined && props.collection?.sort_order !== null
        ? String(props.collection.sort_order)
        : ''

    selectedImageFile.value = null
    initialImagePreview.value = props.collection?.image_url || ''
    imagePreview.value = initialImagePreview.value

    selectedProductIds.value = collectionProducts.value.map((product) => product.id)
    productSearch.value = ''
}

const handleAvatarChange = (file: File | null) => {
    selectedImageFile.value = file
}

const toggleProductSelection = (productId: string | number) => {
    const id = Number(productId)
    const index = selectedProductIds.value.findIndex((value) => Number(value) === id)

    if (index === -1) {
        selectedProductIds.value.push(id)
        return
    }

    selectedProductIds.value.splice(index, 1)
}

const removeProduct = (productId: string | number) => {
    const id = Number(productId)
    selectedProductIds.value = selectedProductIds.value.filter((value) => Number(value) !== id)
}

const clearSelection = () => {
    selectedProductIds.value = []
}

const formatPrice = (value: number | string | null | undefined) => {
    const amount = Number(value || 0)
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0)
}

const submitForm = async () => {
    emit('submit', {
        mode: props.mode === 'edit' ? 'edit' : 'create',
        collectionId: props.collection?.id ?? null,
        form: {
            name: form.name.trim(),
            desc: form.desc.trim(),
            categoryId: form.category_id,
            status: form.status,
            sort_order: form.sort_order.trim(),
        },
        image: selectedImageFile.value,
        remove_image: Boolean(initialImagePreview.value && !imagePreview.value && !selectedImageFile.value),
        productIds: selectedProductIds.value.map((id) => Number(id)),
    })
}

const closeModal = () => {
    model.value = false
}

watch(
    () => model.value,
    (open) => {
        if (open) {
            syncFromCollection()
            return
        }

        selectedImageFile.value = null
        productSearch.value = ''
    },
    { immediate: true },
)
</script>

<template>
    <BaseModal v-model="model" :title="mode === 'edit' ? 'Edit Collection' : 'Add Collection'" width="min(1100px, 96vw)"
        body-class="p-0" footer-class="px-4 pb-4 sm:px-6 sm:pb-4pt-0">
        <el-form label-position="top">
            <div class="grid gap-4 px-4 pb-4 sm:px-6 sm:pb-4 md:grid-cols-2">
                <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-6">
                    <h3 class="mb-4 text-lg font-bold text-slate-950">Collection Image</h3>

                    <BaseImageUpload v-model="imagePreview" class-name="mx-auto" width="260px" height="260px"
                        @change="handleAvatarChange">
                        <template #file="{ file, handlePictureCardPreview, handleRemove, disabled }">
                            <div class="relative h-[260px] w-[260px] overflow-hidden rounded-2xl">
                                <img class="h-full w-full object-cover" :src="file.url" alt="" />
                                <span
                                    class="absolute inset-0 flex items-center justify-center gap-3 bg-slate-950/35 backdrop-blur-[2px]">
                                    <span
                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30"
                                        @click="handlePictureCardPreview(file)">
                                        <el-icon size="20px">
                                            <ZoomIn />
                                        </el-icon>
                                    </span>
                                    <span v-if="!disabled"
                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/20 text-white transition hover:bg-white/30"
                                        @click="handleRemove(file)">
                                        <el-icon>
                                            <Delete />
                                        </el-icon>
                                    </span>
                                </span>
                            </div>
                        </template>
                    </BaseImageUpload>

                    <div class="mt-4 rounded-2xl bg-surface px-4 py-4">
                        <p class="m-0 font-semibold text-slate-950">Tip</p>
                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Recommended size: 1:1 ratio, JPG or PNG, under 2MB.
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-surface px-4 py-4">
                            <span class="block text-xs uppercase tracking-[0.12em] text-slate-500">Products</span>
                            <strong class="mt-2 block text-xl text-slate-950">{{ selectedProducts.length }}</strong>
                        </div>
                        <div class="rounded-2xl bg-surface px-4 py-4">
                            <span class="block text-xs uppercase tracking-[0.12em] text-slate-500">Preview</span>
                            <strong class="mt-2 block text-sm text-slate-950">{{ selectedImageState }}</strong>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-6">
                    <div class="grid gap-5">
                        <div>
                            <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Collection details
                            </p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950">Create a curated collection</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Give the collection a name, choose its category, and keep the order tidy.
                            </p>
                        </div>

                        <el-form-item label="Collection Name" prop="name">
                            <BaseInput v-model="form.name" placeholder="Enter collection name" />
                        </el-form-item>

                        <el-form-item label="Category" prop="category_id">
                            <BaseSelect v-model="form.category_id" :options="categoriesOptions"
                                placeholder="Select category" class="w-full" />
                        </el-form-item>

                        <el-form-item label="Status" prop="status">
                            <BaseSelect v-model="form.status" :options="statusOptions" placeholder="Select status"
                                class="w-full" />
                        </el-form-item>

                        <el-form-item label="Sort Order" prop="sort_order">
                            <BaseInput v-model="form.sort_order" placeholder="e.g. 10" />
                        </el-form-item>

                        <el-form-item label="Description" prop="desc">
                            <BaseInput v-model="form.desc" type="textarea" :rows="8"
                                placeholder="Write a short description for the collection" />
                        </el-form-item>
                    </div>
                </section>
            </div>

            <div class="px-6 pb-6">
                <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="m-0 text-lg font-bold text-slate-950">Featured products</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                Pick the products that should appear inside this collection.
                            </p>
                        </div>

                        <span
                            class="inline-flex w-fit items-center rounded-full bg-surface px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">
                            {{ filteredProducts.length }} visible
                        </span>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                        <BaseInput v-model="productSearch" placeholder="Search products by name..." clearable
                            class="w-full" />
                        <BaseButton type="primary" plain :disabled="!selectedProductIds.length" @click="clearSelection">
                            Clear selection
                        </BaseButton>
                    </div>

                    <div v-if="selectedProducts.length" class="mt-4 flex flex-wrap gap-2">
                        <el-tag v-for="product in selectedProducts.slice(0, 8)" :key="product.id" closable
                            @close="removeProduct(product.id)">
                            {{ product.name }}
                        </el-tag>
                        <span v-if="selectedProducts.length > 8" class="text-sm text-slate-500">
                            +{{ selectedProducts.length - 8 }} more
                        </span>
                    </div>

                    <div v-if="filteredProducts.length"
                        class="mt-4 grid gap-2 grid-cols-2 sm:grid-cols-3 md:grid-cols-4">
                        <button v-for="product in filteredProducts" :key="product.id" type="button"
                            class="group overflow-hidden rounded-3xl border bg-white p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                            :class="selectedProductIds.some((id) => Number(id) === Number(product.id))
                                ? 'border-primary ring-2 ring-primary/15'
                                : 'border-border'" @click="toggleProductSelection(product.id)">
                            <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-surface-2">
                                <img :src="product.image || '/img/products/default_image.webp'" :alt="product.name"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" />
                                <span
                                    class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-primary shadow-sm transition"
                                    :class="selectedProductIds.some((id) => Number(id) === Number(product.id))
                                        ? 'scale-100 opacity-100'
                                        : 'scale-90 opacity-0'">
                                    <el-icon>
                                        <Check />
                                    </el-icon>
                                </span>
                            </div>
                            <div class="mt-3">
                                <p class="line-clamp-1 font-semibold text-slate-950">{{ product.name }}</p>
                                <p class="mt-1 text-sm font-medium text-primary">{{ formatPrice(product.price) }}</p>
                            </div>
                        </button>
                    </div>

                    <el-empty v-else description="No products match your search." />
                </section>
            </div>
        </el-form>

        <template #footer>
            <div class="flex justify-end gap-3 px-6 pb-6 pt-0">
                <BaseButton @click="closeModal">Cancel</BaseButton>
                <BaseButton type="primary" :loading="loading" @click="submitForm">
                    {{ mode === 'edit' ? 'Update collection' : 'Save collection' }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>
