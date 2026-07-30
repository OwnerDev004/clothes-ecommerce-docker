<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormItemRule, FormRules } from 'element-plus'
import BaseModal from '~/components/ui/BaseModal.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import type { AdminProductRecord, AdminProductImageRecord, AdminProductVariantRecord } from '~/composables/useAdminProduct'

type SelectOption = {
    id: string | number
    label: string
}

type ProductForm = {
    mode: 'edit' | 'create'
    name: string
    category_id: string | number | null
    sub_category_id: string | number | null
    status: 'draft' | 'active' | 'archived'
    unit_price: string
    description: string
}

type ProductVariantForm = {
    id?: number | string | null
    sku: string
    color: string,
    color_label: string,
    color_name: string,
    size: string | number | null
    stock_quantity: string
    sale_price: string
    cost_price: string
}

type ProductImageForm = {
    id?: number | string | null
    file?: File | null
    preview: string
    image_type: 'thumbnail' | 'gallery'
    sort_order: number
    existing: boolean
    cloudinary_public_id?: string | null
}

type ProductVariantRuleKeys = keyof Pick<ProductVariantForm, 'sku' | 'color' | 'color_label' | 'color_name' | 'stock_quantity' | 'sale_price' | 'cost_price'>

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        mode?: 'create' | 'edit'
        product?: AdminProductRecord | null
        categoryOptions?: SelectOption[]
        sizeOptions?: Array<{ id: number | string; label: string }>
        loading?: boolean
    }>(),
    {
        mode: 'create',
        product: null,
        categoryOptions: () => [],
        sizeOptions: () => [{ id: "", label: 'Select size' }],
        loading: false,
    },
)
const defaultPrice = ref<any>('0')
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', payload: {
        mode: 'create' | 'edit'
        productId: number | string | null
        form: {
            product: ProductForm
            product_variants: ProductVariantForm[]
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
    }): void
}>()

const dialogOpen = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
})

const formRef = ref<FormInstance>()
const imageInputRef = ref<HTMLInputElement | null>(null)
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const subCategoryOptions = ref<SelectOption[]>([])
const subCategoryLoading = ref(false)
const selectedCategoryLabel = computed(() => {
    return props.categoryOptions?.find((item) => String(item.id) === String(form.product.category_id))?.label || ''
})
const subCategoryPlaceholder = computed(() => {
    if (!form.product.category_id) {
        return 'Choose a category first'
    }

    return subCategoryLoading.value ? 'Loading sub categories...' : 'Select sub category'
})

const statusOptions = [
    { id: 'draft', label: 'Draft' },
    { id: 'active', label: 'Active' },
    { id: 'archived', label: 'Archived' },
]

const createVariant = (index = 0, productName = ''): ProductVariantForm => {
    const prefix = (productName || 'VAR')
        .replace(/[^a-zA-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 8)
        .toUpperCase() || 'VAR'

    return {
        sku: `${prefix}-${Date.now().toString(36).toUpperCase()}-${index + 1}`,
        color: '#000000',
        color_label: '',
        color_name: '',
        size: null,
        stock_quantity: '',
        sale_price: '',
        cost_price: defaultPrice.value,
    }
}

const form = reactive<{
    product: ProductForm
    product_variants: ProductVariantForm[]
}>({
    product: {
        mode: 'create',
        name: '',
        category_id: null,
        sub_category_id: null,
        status: 'active',
        unit_price: '',
        description: '',
    },
    product_variants: [createVariant(0)],
})

const imageItems = ref<ProductImageForm[]>([])

const rules: FormRules<any> = {
    'product.name': [{ required: true, message: 'Product name is required', trigger: 'blur' }],
    'product.category_id': [{ required: true, message: 'Category is required', trigger: 'change' }],
    'product.unit_price': [{ required: true, message: 'Unit price is required', trigger: 'blur' }],
}

const variantFieldRules: Record<ProductVariantRuleKeys, FormItemRule[]> = {
    sku: [],
    color: [{ required: true, message: 'Color is required', trigger: 'change' }],
    color_label: [{ required: true, message: 'Color Label is required', trigger: 'blur' }],
    color_name: [{ required: true, message: 'Color name is required', trigger: 'blur' }],
    stock_quantity: [{ required: true, message: 'Quantity is required', trigger: 'blur' }],
    sale_price: [{ required: true, message: 'Sale price is required', trigger: 'blur' }],
    cost_price: [{ required: true, message: 'Cost price is required', trigger: 'blur' }],
}

const revokePreview = (item: ProductImageForm) => {
    if (!item.existing && item.preview.startsWith('blob:')) {
        URL.revokeObjectURL(item.preview)
    }
}

const resetForm = () => {
    imageItems.value.forEach(revokePreview)
    subCategoryOptions.value = []

    form.product.name = ''
    form.product.category_id = null
    form.product.sub_category_id = null
    form.product.status = 'active'
    form.product.unit_price = ''
    form.product.description = ''
    form.product_variants.splice(0, form.product_variants.length, createVariant(0))
    imageItems.value = []
    formRef.value?.clearValidate()
    if (imageInputRef.value) {
        imageInputRef.value.value = ''
    }
}

const hydrateFromProduct = (product: AdminProductRecord | null) => {
    resetForm()

    if (!product) {
        return
    }

    form.product.name = product.name || ''
    form.product.category_id = product.category_id ?? null
    form.product.sub_category_id = product.subCategory?.id ?? null
    form.product.unit_price = String(product.price ?? '')
    form.product.description = product.desc || ''

    const variants = Array.isArray(product.variants) && product.variants.length
        ? product.variants
        : [null]

    form.product_variants.splice(
        0,
        form.product_variants.length,
        ...variants.map((variant, index) => {
            const item = variant || {}
            return {
                id: item?.id ?? null,
                sku: String(item?.sku || createVariant(index, product.name).sku),
                color: String(item?.color || '#000000'),
                color_label: String(item?.color_label || ''),
                color_name: String(item?.color_name || item?.color_label || ''),
                size: item?.size_id ?? item?.size?.id ?? null,
                stock_quantity: String(item?.stock_quantity ?? ''),
                sale_price: String(item?.sell_price ?? product.price ?? ''),
                cost_price: String(item?.cost_price ?? product.price ?? ''),
            }
        }),
    )

    const orderedImages = (product.images || [])
        .slice()
        .sort((left, right) => Number(left.sort_order ?? 0) - Number(right.sort_order ?? 0))

    imageItems.value = orderedImages.map((image: AdminProductImageRecord, index) => ({
        id: image.id ?? null,
        file: null,
        preview: image.image_url || '',
        image_type: image.image_type === 'thumbnail' ? 'thumbnail' : 'gallery',
        sort_order: Number(image.sort_order ?? index),
        existing: true,
        cloudinary_public_id: image.cloudinary_public_id ?? null,
    }))

    ensureThumbnail()
}

const ensureThumbnail = () => {
    if (!imageItems.value.length) {
        return
    }

    const hasThumbnail = imageItems.value.some((item) => item.image_type === 'thumbnail')
    if (hasThumbnail) {
        const thumbnailIndex = imageItems.value.findIndex((item) => item.image_type === 'thumbnail')
        imageItems.value.forEach((item, index) => {
            if (index !== thumbnailIndex) {
                item.image_type = 'gallery'
            }
        })
        return
    }

    imageItems.value[0]!.image_type = 'thumbnail'
}

const loadSubCategories = async (categoryId: string | number | null) => {
    if (!categoryId) {
        subCategoryOptions.value = []
        form.product.sub_category_id = null
        return
    }

    subCategoryLoading.value = true
    try {
        const response: any = await $fetch(`${apiBase}/sub-categories`, {
            method: 'GET',
            query: {
                category: categoryId,
            },
        })

        const rows = Array.isArray(response?.data) ? response.data : []
        subCategoryOptions.value = rows.map((item: any) => ({
            id: item.id,
            label: item.name || 'Sub Category',
        }))

        if (
            form.product.sub_category_id &&
            !subCategoryOptions.value.some((item) => String(item.id) === String(form.product.sub_category_id))
        ) {
            form.product.sub_category_id = null
        }
    } catch {
        subCategoryOptions.value = []
    } finally {
        subCategoryLoading.value = false
    }
}

const openImagePicker = () => {
    imageInputRef.value?.click()
}

const readFiles = async (files: FileList | File[]) => {
    const baseOrder = imageItems.value.length
    const nextImages = await Promise.all(
        Array.from(files).map(async (file, index) => ({
            id: null,
            file,
            preview: URL.createObjectURL(file),
            image_type: 'gallery' as const,
            sort_order: baseOrder + index,
            existing: false,
            cloudinary_public_id: null,
        })),
    )

    imageItems.value.push(...nextImages)
    ensureThumbnail()
}

const onPickImages = async (event: Event) => {
    const target = event.target as HTMLInputElement | null
    const files = target?.files
    if (!files?.length) {
        return
    }

    await readFiles(files)
    if (target) {
        target.value = ''
    }
}

const setThumbnail = (index: number) => {
    imageItems.value.forEach((item, itemIndex) => {
        item.image_type = itemIndex === index ? 'thumbnail' : 'gallery'
    })
}

const removeImage = (index: number) => {
    const [removed] = imageItems.value.splice(index, 1)
    if (removed) {
        revokePreview(removed)
    }
    ensureThumbnail()
}

const addProductVariant = () => {
    form.product_variants.push(createVariant(form.product_variants.length, form.product.name))
}

const removeProductVariant = (index: number) => {
    form.product_variants.splice(index, 1)
    if (!form.product_variants.length) {
        form.product_variants.push(createVariant(0, form.product.name))
    }
}

const closeModal = () => {
    dialogOpen.value = false
}

const submitForm = async () => {
    if (!formRef.value) {
        return
    }

    const valid = await formRef.value.validate().catch(() => false)
    if (!valid) {
        return
    }

    if (!imageItems.value.length) {
        ElMessage.warning('Please add at least one product image.')
        return
    }

    ensureThumbnail()

    const existing_images = imageItems.value
        .map((item, index) => ({
            item,
            index,
        }))
        .filter(({ item }) => item.existing && item.id !== null && item.id !== undefined)
        .map(({ item, index }) => ({
            id: item.id as number | string,
            image_type: item.image_type,
            sort_order: index,
        }))

    const new_images = imageItems.value
        .map((item, index) => ({
            item,
            index,
        }))
        .filter(({ item }) => !item.existing && item.file)
        .map(({ item, index }) => ({
            file: item.file as File,
            image_type: item.image_type,
            sort_order: index,
        }))

    emit('submit', {
        mode: props.mode,
        productId: props.product?.id ?? null,
        form: {
            product: { ...form.product },
            product_variants: form.product_variants.map((variant) => ({ ...variant })),
        },
        images: {
            existing_images,
            new_images,
        },
    })
}

// computed


watch(
    () => dialogOpen.value,
    (open) => {
        if (!open) {
            resetForm()
            return
        }

        hydrateFromProduct(props.product)
    },
)

watch(
    () => props.product,
    (product) => {
        if (dialogOpen.value) {
            hydrateFromProduct(product)
        }
    },
    { deep: true },
)

watch(
    () => form.product.category_id,
    (categoryId) => {
        void loadSubCategories(categoryId)
    },
)

watch(
    () => form.product.unit_price,
    (price: string, oldPrice: string | undefined) => {
        const prev = defaultPrice.value
        defaultPrice.value = price

        // Update variant cost_price when it's empty or still equals previous default
        form.product_variants.forEach((v) => {
            if (!v.cost_price || v.cost_price === prev) {
                v.cost_price = price
            }
        })
    }
)

onBeforeUnmount(() => {
    imageItems.value.forEach(revokePreview)
})
</script>

<template>
    <BaseModal v-model="dialogOpen" :title="mode === 'edit' ? 'Edit Product' : 'Add Product'" width="1200px"
        :show-footer="false" body-class="p-0">
        <el-form ref="formRef" :model="form" :rules="rules" label-position="top">
            <section class="border-b p-6 mb-3  rounded-3xl border border-dashed border-muted bg-surface-2/10">
                <section class="pb-2 mb-2 border-b">
                    <h2 class="text-lg font-extrabold text-slate-950 ">
                        Product Info
                    </h2>
                </section>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-4">
                        <el-form-item label="Product Images">
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-4">
                                <div v-if="imageItems.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                    <article v-for="(image, index) in imageItems"
                                        :key="`${image.existing ? 'existing' : 'new'}-${image.id ?? index}-${index}`"
                                        class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                        <div class="relative aspect-[4/5] bg-slate-100">
                                            <img :src="image.preview" alt="Product image"
                                                class="h-full w-full object-cover" />
                                            <span
                                                class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-[0.72rem] font-semibold"
                                                :class="image.image_type === 'thumbnail'
                                                    ? 'bg-slate-800 text-white'
                                                    : 'bg-white/90 text-slate-700'">
                                                {{ image.image_type === 'thumbnail' ? 'Thumbnail' : 'Gallery' }}
                                            </span>
                                        </div>

                                        <div class="space-y-3 p-3">
                                            <div class="grid grid-cols-2 gap-2">
                                                <BaseButton type="primary" native-type="button" size="small"
                                                    @click="setThumbnail(index)">
                                                    Set thumbnail
                                                </BaseButton>
                                                <BaseButton type="danger" native-type="button" plain size="small"
                                                    @click="removeImage(index)">
                                                    Remove
                                                </BaseButton>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                <div v-else
                                    class="grid place-items-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-10 text-center text-sm text-slate-500">
                                    <strong class="block text-slate-900">No images yet</strong>
                                    <span>Add one thumbnail and any number of gallery images.</span>
                                </div>

                                <input ref="imageInputRef" type="file"
                                    accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden"
                                    @change="onPickImages" />

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <BaseButton type="primary" native-type="button" @click="openImagePicker">
                                        Add images
                                    </BaseButton>
                                    <p class="m-0 text-sm text-slate-500">
                                        The server uploads to Cloudinary. First image becomes the thumbnail if you do
                                        not choose one.
                                    </p>
                                </div>
                            </div>
                        </el-form-item>

                        <el-form-item label="Product Name" prop="product.name">
                            <BaseInput v-model="form.product.name" placeholder="Enter product name" />
                        </el-form-item>

                        <el-form-item label="Category" prop="product.category_id">
                            <BaseSelect v-model="form.product.category_id" :options="props.categoryOptions || []"
                                placeholder="Select category" class="w-full" clearable filterable
                                no-match-text="No matching category" />
                            <p v-if="selectedCategoryLabel" class="mt-2 text-xs text-slate-500">
                                Sub categories will update for <strong class="font-semibold text-slate-700">{{
                                    selectedCategoryLabel }}</strong>.
                            </p>
                        </el-form-item>

                        <el-form-item label="Sub Category" prop="product.sub_category_id">
                            <BaseSelect v-model="form.product.sub_category_id" :options="subCategoryOptions"
                                :disabled="!form.product.category_id || subCategoryLoading"
                                :loading="subCategoryLoading" :placeholder="subCategoryPlaceholder" class="w-full"
                                clearable filterable no-match-text="No matching sub category"
                                no-data-text="Pick a category to load sub categories" />
                            <p class="mt-2 text-xs text-slate-500">
                                Keep the parent category first, then narrow down to a sub category.
                            </p>
                        </el-form-item>
                    </div>

                    <div class="space-y-4">
                        <el-form-item label="Unit Price" prop="product.unit_price">
                            <BaseInput v-model="form.product.unit_price" type="number" min="0" placeholder="0.00" />
                        </el-form-item>

                        <el-form-item label="Status" prop="product.status">
                            <BaseSelect v-model="form.product.status" :options="statusOptions"
                                placeholder="Select status" class="w-full" />
                        </el-form-item>

                        <el-form-item label="Description" prop="product.description" class="min-h-[240px]">
                            <BaseInput v-model="form.product.description" type="textarea" :rows="10"
                                placeholder="Write a product description" />
                        </el-form-item>
                    </div>
                </div>
            </section>

            <section class="p-6 rounded-3xl border border-dashed border-muted bg-surface-2/10">
                <div class="pb-2 mb-6 flex items-center justify-between border-b border-slate-200">
                    <h2 class="text-lg font-extrabold text-slate-950">Product Variants</h2>
                    <BaseButton type="primary" native-type="button" @click="addProductVariant">
                        Add variant
                    </BaseButton>
                </div>

                <div v-for="(variant, index) in form.product_variants" :key="variant.id ?? index"
                    class="relative my-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-4 lg:grid-cols-7">
                        <el-form-item :label="`SKU ${index + 1}`" :prop="`product_variants.${index}.sku`"
                            :rules="variantFieldRules.sku">
                            <div class="space-y-1">
                                <BaseInput v-model="variant.sku" placeholder="Auto generated SKU" />
                                <p class="text-xs text-slate-500">Auto generated by default, still editable.</p>
                            </div>
                        </el-form-item>

                        <el-form-item :label="`Color ${index + 1}`" :prop="`product_variants.${index}.color`"
                            :rules="variantFieldRules.color">
                            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-2">
                                <el-color-picker v-model="variant.color" />
                                <BaseInput v-model="variant.color" class="flex-1" placeholder="#000000" />
                            </div>
                        </el-form-item>
                        <el-form-item :label="`Color Label ${index + 1}`"
                            :prop="`product_variants.${index}.color_label`" :rules="variantFieldRules.color_label">
                            <BaseInput v-model="variant.color_label" />
                        </el-form-item>
                        <el-form-item :label="`Color Name ${index + 1}`"
                            :prop="`product_variants.${index}.color_name`" :rules="variantFieldRules.color_name">
                            <BaseInput v-model="variant.color_name" placeholder="e.g. Navy Blue" />
                        </el-form-item>

                        <el-form-item :label="`Size ${index + 1}`" :prop="`product_variants.${index}.size_id`">
                            <BaseSelect v-model="variant.size" :options="props.sizeOptions || []"
                                placeholder="Select size" class="w-full" />
                        </el-form-item>

                        <el-form-item :label="`Quantity ${index + 1}`"
                            :prop="`product_variants.${index}.stock_quantity`"
                            :rules="variantFieldRules.stock_quantity">
                            <BaseInput v-model="variant.stock_quantity" type="number" min="0" placeholder="0" />
                        </el-form-item>

                        <el-form-item :label="`Sale Price ${index + 1}`" :prop="`product_variants.${index}.sale_price`"
                            :rules="variantFieldRules.sale_price">
                            <BaseInput v-model="variant.sale_price" type="number" min="0" placeholder="0" />
                        </el-form-item>

                        <el-form-item :label="`Cost Price ${index + 1}`" :prop="`product_variants.${index}.cost_price`"
                            :rules="variantFieldRules.cost_price">
                            <BaseInput v-model="variant.cost_price" type="number" />
                        </el-form-item>
                    </div>

                    <BaseButton type="danger" plain circle native-type="button" class="absolute -top-3 right-3"
                        @click="removeProductVariant(index)">
                        -
                    </BaseButton>
                </div>
            </section>
        </el-form>

        <template #footer>
            <BaseButton @click="closeModal">Cancel</BaseButton>
            <BaseButton type="primary" :loading="loading" @click="submitForm">
                {{ mode === 'edit' ? 'Update Product' : 'Save Product' }}
            </BaseButton>
        </template>
    </BaseModal>
</template>
