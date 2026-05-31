<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAppSetting } from '~/composables/useAppSetting'
import { formatMoney, normalizeCurrencyCode } from '~/utils/currency'

type ProductImage = {
  image_url?: string | null
  image_type?: 'thumbnail' | 'gallery' | null
}

type ProductSize = {
  id?: number | string
  name?: string | null
}

type ProductVariant = {
  id?: number | string
  sku?: string | null
  color?: string | null
  size?: ProductSize | null
  stock_quantity?: number | string | null
  sell_price?: number | string | null
  cost_price?: number | string | null
}

type ProductDetail = {
  id?: number | string
  name?: string | null
  sku?: string | null
  desc?: string | null
  price?: number | string | null
  thumbnail?: ProductImage | null
  images?: ProductImage[]
  category?: { name?: string | null } | null
  subCategory?: { name?: string | null } | null
  brand?: { name?: string | null } | null
  collections?: Array<{ name?: string | null }> | null
  variants?: ProductVariant[]
}

const props = withDefaults(
  defineProps<{
    product: ProductDetail | { data?: ProductDetail } | null
  }>(),
  {
    product: null,
  },
)

const { appSetting, defaultCurrencyCode, convertAmount, fetchAppSetting } = useAppSetting()

const baseCurrencyCode = computed(() =>
  normalizeCurrencyCode(
    appSetting.value.base_currency_code || defaultCurrencyCode.value,
  ),
)

const resolvedProduct = computed<ProductDetail | null>(() => {
  const value = props.product
  if (!value) {
    return null
  }

  if ('data' in value && value.data) {
    return value.data
  }

  return value as ProductDetail
})

const imageList = computed(() => {
  const rows = [
    resolvedProduct.value?.thumbnail?.image_url || '',
    ...((resolvedProduct.value?.images || []).map((image) => image?.image_url || '')),
  ].filter(Boolean)

  return Array.from(new Set(rows))
})

const selectedImage = ref('')

const variants = computed(() => resolvedProduct.value?.variants || [])

const displayPrice = computed(() => {
  const firstVariant = variants.value[0]
  const variantPrice = Number(firstVariant?.sell_price || 0)
  const rawPrice =
    variantPrice > 0 ? variantPrice : Number(resolvedProduct.value?.price || 0)

  return convertAmount(
    rawPrice,
    baseCurrencyCode.value,
    defaultCurrencyCode.value,
  )
})

const formatBaseMoney = (value: unknown) =>
  formatMoney(
    convertAmount(value, baseCurrencyCode.value, defaultCurrencyCode.value),
    defaultCurrencyCode.value,
  )

const stockQuantity = computed(() => {
  return variants.value.reduce((sum, variant) => sum + Number(variant.stock_quantity || 0), 0)
})

const colorOptions = computed(() => {
  return variants.value
    .map((variant) => String(variant.color || '').trim())
    .filter(Boolean)
    .filter((value, index, array) => array.indexOf(value) === index)
})

const selectedVariant = computed(() => variants.value[0] || null)

const detailRows = computed(() => [
  { label: 'Category', value: resolvedProduct.value?.category?.name || '-' },
  { label: 'Sub category', value: resolvedProduct.value?.subCategory?.name || '-' },
  { label: 'Brand', value: resolvedProduct.value?.brand?.name || '-' },
  { label: 'Collections', value: resolvedProduct.value?.collections?.length ? resolvedProduct.value.collections.map((item) => item.name).filter(Boolean).join(', ') : '-' },
  { label: 'Variants', value: variants.value.length },
  { label: 'Stock', value: stockQuantity.value },
])

const ensureSelectedImage = () => {
  selectedImage.value = imageList.value[0] || '/img/products/default_image.webp'
}

watch(
  () => props.product,
  () => {
    ensureSelectedImage()
  },
  { immediate: true, deep: true },
)

onMounted(() => {
  void fetchAppSetting(true)
})
</script>

<template>
  <div class="grid gap-6 bg-slate-50 p-4 md:p-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
    <section class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm md:p-5">
      <div class="grid gap-4 lg:grid-cols-[92px_minmax(0,1fr)]">
        <div class="order-2 grid grid-cols-4 gap-2 lg:order-1 lg:grid-cols-1">
          <button
            v-for="(image, index) in imageList"
            :key="`${image}-${index}`"
            type="button"
            class="overflow-hidden rounded-2xl border transition"
            :class="selectedImage === image ? 'border-indigo-500 ring-2 ring-indigo-100' : 'border-slate-200 hover:border-slate-300'"
            @click="selectedImage = image"
          >
            <img :src="image" alt="Product thumbnail" class="h-16 w-full object-cover lg:h-20" />
          </button>
        </div>

        <div class="order-1 overflow-hidden rounded-[28px] bg-slate-100 lg:order-2">
          <img
            :src="selectedImage || imageList[0] || '/img/products/default_image.webp'"
            alt="Selected product image"
            class="aspect-[4/5] w-full object-cover"
          />
        </div>
      </div>
    </section>

    <aside class="space-y-4 rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
      <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Product Detail</p>
        <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-950">
          {{ resolvedProduct?.name || 'Untitled product' }}
        </h2>
        <p class="mt-2 text-sm text-slate-500">
          SKU: {{ resolvedProduct?.sku || selectedVariant?.sku || '-' }}
        </p>
      </div>

      <div class="flex items-end justify-between rounded-3xl bg-slate-50 p-4">
        <div>
          <p class="text-sm text-slate-500">Price</p>
          <strong class="block text-3xl text-slate-950">{{ formatMoney(displayPrice, defaultCurrencyCode) }}</strong>
        </div>
        <div class="text-right">
          <p class="text-sm text-slate-500">Stock</p>
          <strong class="block text-lg text-slate-950">{{ stockQuantity }}</strong>
        </div>
      </div>

      <p class="text-sm leading-7 text-slate-600">
        {{ resolvedProduct?.desc || 'No description provided.' }}
      </p>

      <div class="grid gap-3 sm:grid-cols-2">
        <div
          v-for="row in detailRows"
          :key="row.label"
          class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
        >
          <p class="text-xs uppercase tracking-[0.14em] text-slate-500">{{ row.label }}</p>
          <strong class="mt-1 block text-sm text-slate-950">{{ row.value }}</strong>
        </div>
      </div>

      <div v-if="colorOptions.length" class="space-y-2">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Colors</p>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="color in colorOptions"
            :key="color"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700"
          >
            <span class="h-4 w-4 rounded-full border border-slate-200" :style="{ backgroundColor: color }"></span>
            {{ color }}
          </span>
        </div>
      </div>

      <div v-if="variants.length" class="space-y-2">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Variants</p>
        <div class="space-y-2">
          <article
            v-for="variant in variants"
            :key="variant.id || `${variant.sku}-${variant.color}`"
            class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <strong class="block text-sm text-slate-950">{{ variant.sku || 'Variant' }}</strong>
                <p class="mt-1 text-xs text-slate-500">
                  {{ variant.size?.name || 'No size' }}
                </p>
              </div>
              <span class="h-5 w-5 rounded-full border border-slate-300" :style="{ backgroundColor: variant.color || '#e2e8f0' }"></span>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs text-slate-500">
              <div>
                <span class="block">Stock</span>
                <strong class="text-slate-900">{{ variant.stock_quantity ?? 0 }}</strong>
              </div>
              <div>
                <span class="block">Sale</span>
                <strong class="text-slate-900">
                  {{ variant.sell_price != null ? formatBaseMoney(variant.sell_price) : '-' }}
                </strong>
              </div>
              <div>
                <span class="block">Cost</span>
                <strong class="text-slate-900">
                  {{ variant.cost_price != null ? formatBaseMoney(variant.cost_price) : '-' }}
                </strong>
              </div>
            </div>
          </article>
        </div>
      </div>
    </aside>
  </div>
</template>
