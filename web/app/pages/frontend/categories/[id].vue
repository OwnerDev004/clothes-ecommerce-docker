<template>
  <div>
    <LoadingPage v-if="isFetching" embedded class="px-5 desktop:container py-10" :rows="8">
      <template #template>
        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
          <aside class="hidden lg:block rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="space-y-4">
              <el-skeleton-item variant="h3" class="h-8 w-1/2" />
              <div class="space-y-3">
                <el-skeleton-item v-for="item in 6" :key="item" variant="text" class="h-6 w-full" />
              </div>
              <div class="space-y-4 pt-2">
                <el-skeleton-item variant="h3" class="h-7 w-1/3" />
                <el-skeleton-item variant="rect" class="h-20 w-full rounded-2xl" />
                <el-skeleton-item variant="h3" class="h-7 w-1/3" />
                <div class="grid grid-cols-4 gap-3">
                  <el-skeleton-item v-for="item in 4" :key="`color-${item}`" variant="circle" class="h-8 w-8" />
                </div>
              </div>
            </div>
          </aside>

          <main class="space-y-6">
            <div class="flex items-center justify-between gap-4">
              <div class="space-y-3">
                <el-skeleton-item variant="h3" class="h-9 w-56" />
                <el-skeleton-item variant="text" class="h-5 w-32" />
              </div>
              <el-skeleton-item variant="button" class="h-12 w-12 rounded-full lg:hidden" />
              <el-skeleton-item variant="button" class="hidden h-12 w-60 rounded-full lg:block" />
            </div>

            <div class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4">
              <FrontendCardProduct v-for="item in 8" :key="`product-${item}`" loading />
            </div>
          </main>
        </div>
      </template>
    </LoadingPage>
    <div v-else>
      <div class="px-5 desktop:container relative">
        <BaseBreadcrumb :icon="ArrowRight">
          <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
          <el-breadcrumb-item>Categories</el-breadcrumb-item>
        </BaseBreadcrumb>

        <section class="flex gap-6">
          <div class="border rounded-2xl w-[28%] p-6 hidden lg:block">
            <section class="flex justify-between items-center border-b border-b-gray">
              <h1 class="text-lg font-bold font-Poppins">Filters</h1>
              <button class="bg-gray-800 w-12 h-12 rounded-full text-2xl p-3 flex items-center justify-center">
                <Icon name="lets-icons:filter" />
              </button>
            </section>

            <section class="flex justify-between items-center border-b border-b-gray py-2">
              <ul class="leading-10 w-full">
                <li v-for="category in categories" :key="category.id"
                  class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                  :class="{ 'bg-gray': selectedCategory === (category.slug || String(category.id)) }"
                  @click="changeCategory(category.slug || String(category.id))">
                  <p>{{ category.name }}</p>
                  <Icon name="weui:arrow-filled" />
                </li>
              </ul>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Price</h1>
              <div class="card flex flex-col">
                <el-slider v-model="priceRange" range placement="bottom" style="--el-slider-main-bg-color: black" />
                <p class="pt-4">{{ priceRange[0] }}$ - {{ priceRange[1] }}$</p>
              </div>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Colors</h1>
              <div class="flex flex-wrap gap-3">
                <button v-for="color in colors" :key="color.id"
                  :style="{ backgroundColor: color.hex_code || '#d1d5db' }" @click="selectColor(String(color.id))"
                  :aria-label="`Select color ${color.name}`"
                  class="w-8 h-8 rounded-full border border-gray-300 cursor-pointer hover:opacity-80 relative">
                  <span v-if="colorFilter === String(color.id)"
                    class="text-white text-xs font-bold absolute inset-0 flex items-center justify-center">✓</span>
                </button>
              </div>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Sizes</h1>
              <div class="grid grid-cols-2 gap-3">
                <button v-for="size in sizes" :key="size.id" @click="selectSize(String(size.id))" :class="[
                  'p-3 rounded-3xl cursor-pointer text-sm',
                  sizeFilter === String(size.id) ? 'bg-black text-white' : 'bg-gray',
                  'hover:bg-black hover:text-white',
                ]">
                  {{ size.name }}
                </button>
              </div>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Collection</h1>
              <ul class="leading-10">
                <li v-for="style in dressTypes" :key="style.id"
                  class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                  :class="{ 'bg-gray': dressStyleFilter === (style.slug || String(style.id)) }"
                  @click="selectDressStyle(style.slug || String(style.id))">
                  <p>{{ style.name }}</p>
                  <Icon name="weui:arrow-filled" />
                </li>
              </ul>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Brand</h1>
              <ul class="leading-10">
                <li v-for="brand in brands" :key="brand.id"
                  class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                  :class="{ 'bg-gray': brandFilter === String(brand.id) }" @click="selectBrand(String(brand.id))">
                  <p>{{ brand.name }}</p>
                  <Icon name="weui:arrow-filled" />
                </li>
              </ul>
            </section>

            <section class="border-b border-b-gray py-3">
              <h1 class="text-lg font-bold font-Poppins">Sub Category</h1>
              <ul class="leading-10">
                <li v-for="sub in subCategories" :key="sub.id"
                  class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                  :class="{ 'bg-gray': subCategoryFilter === (sub.slug || String(sub.id)) }"
                  @click="selectSubCategory(sub.slug || String(sub.id))">
                  <p>{{ sub.name }}</p>
                  <Icon name="weui:arrow-filled" />
                </li>
              </ul>
            </section>

            <el-button
              class="border rounded-[64px] p-4 w-full outline-none bg-transparent text-black hover:bg-black hover:text-white mt-4"
              :disabled="isLoadingProducts" onclick="applyFilters">
              Apply Filter
            </el-button>
          </div>

          <div class="w-full rounded-lg">
            <div class="flex justify-between pb-5 items-center">
              <div>
                <h2 class="text-black text-3xl font-semibold">{{ currentCategoryLabel }}</h2>
                <p class="text-sm text-zinc-400">{{ meta.total }} products</p>
              </div>

              <button class="w-12 h-12 rounded-full text-2xl p-3 flex items-center justify-center lg:hidden bg-gray"
                @click="toggleFilter">
                <Icon name="lets-icons:filter" />
              </button>

              <div class="hidden lg:block">
                <span class="font-Poppins text-zinc-400">Sort By:</span>
                <ClientOnly>
                  <el-select v-model="sortBy" placeholder="Select" size="large" style="width: 240px"
                    @change="sortProducts">
                    <el-option label="Newest" value="newest" />
                    <el-option label="Price (Low to High)" value="price_asc" />
                    <el-option label="Price (High to Low)" value="price_desc" />
                    <el-option label="Name (A-Z)" value="name_asc" />
                  </el-select>
                </ClientOnly>
              </div>
            </div>

            <el-alert v-if="errorMessage" :title="errorMessage" type="error" :closable="false" show-icon class="mb-4" />

            <div v-loading="isLoadingProducts" class="min-h-[220px]">

              <div v-if="!isLoadingProducts && displayProducts.length === 0"
                class="rounded-xl border border-dashed border-gray-300 p-8">
                <el-empty description="No products found." />
              </div>
              <div v-else class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4">
                <template v-for="item in displayProducts" :key="item.id">

                  <div class="cursor-pointer" @click="viewProduct(item.id)">
                    <FrontendCardProduct :title="item.title" :price="item.price" :img="item.img"
                      :discount-amount="item.discount_amount" :discount-type="item.discount_type"
                      :rating-amount="item.average_rating" />
                  </div>
                </template>
              </div>
            </div>

            <div class="flex justify-center mt-6">
              <el-pagination v-if="meta.total > meta.per_page" :pager-count="5" layout="prev, pager, next"
                prev-text="⬅ Previous" next-text="Next ➞" :total="meta.total" :current-page="page"
                :page-size="meta.per_page" @current-change="onPageChanged" />
            </div>
          </div>
        </section>
      </div>

      <el-dialog v-model="isToggleFilter" title="Filters" width="auto" class="!rounded-t-3xl !-bottom-[100px] !pt-5">
        <div>
          <section class="border-b border-b-gray py-2">
            <h1 class="text-lg font-bold font-Poppins">Categories</h1>
            <ul class="leading-10 w-full">
              <li v-for="category in categories" :key="category.id"
                class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                :class="{ 'bg-gray': selectedCategory === (category.slug || String(category.id)) }"
                @click="changeCategory(category.slug || String(category.id), true)">
                <p>{{ category.name }}</p>
                <Icon name="weui:arrow-filled" />
              </li>
            </ul>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Price</h1>
            <div class="card flex flex-col">
              <el-slider v-model="priceRange" range placement="bottom" style="--el-slider-main-bg-color: black" />
              <p class="pt-4">{{ priceRange[0] }}$ - {{ priceRange[1] }}$</p>
            </div>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Colors</h1>
            <div class="flex flex-wrap gap-3">
              <button v-for="color in colors" :key="color.id" :style="{ backgroundColor: color.hex_code || '#d1d5db' }"
                @click="selectColor(String(color.id))"
                class="w-8 h-8 rounded-full border border-gray-300 cursor-pointer relative">
                <span v-if="colorFilter === String(color.id)"
                  class="text-white text-xs font-bold absolute inset-0 flex items-center justify-center">✓</span>
              </button>
            </div>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Sizes</h1>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="size in sizes" :key="size.id" @click="selectSize(String(size.id))" :class="[
                'p-3 rounded-3xl cursor-pointer text-sm',
                sizeFilter === String(size.id) ? 'bg-black text-white' : 'bg-gray',
                'hover:bg-black hover:text-white',
              ]">
                {{ size.name }}
              </button>
            </div>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Collection</h1>
            <ul class="leading-10">
              <li v-for="style in dressTypes" :key="style.id"
                class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                :class="{ 'bg-gray': dressStyleFilter === (style.slug || String(style.id)) }"
                @click="selectDressStyle(style.slug || String(style.id))">
                <p>{{ style.name }}</p>
                <Icon name="weui:arrow-filled" />
              </li>
            </ul>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Brand</h1>
            <ul class="leading-10">
              <li v-for="brand in brands" :key="brand.id"
                class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                :class="{ 'bg-gray': brandFilter === String(brand.id) }" @click="selectBrand(String(brand.id))">
                <p>{{ brand.name }}</p>
                <Icon name="weui:arrow-filled" />
              </li>
            </ul>
          </section>

          <section class="border-b border-b-gray py-3">
            <h1 class="text-lg font-bold font-Poppins">Sub Category</h1>
            <ul class="leading-10">
              <li v-for="sub in subCategories" :key="sub.id"
                class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
                :class="{ 'bg-gray': subCategoryFilter === (sub.slug || String(sub.id)) }"
                @click="selectSubCategory(sub.slug || String(sub.id))">
                <p>{{ sub.name }}</p>
                <Icon name="weui:arrow-filled" />
              </li>
            </ul>
          </section>
        </div>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="applyFilters(true)"
              class="border rounded-[64px] p-4 w-full outline-none bg-transparent text-black hover:bg-black hover:text-white">
              Apply Filter
            </el-button>
          </div>
        </template>
      </el-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const backendOrigin = apiBase.replace(/\/api\/v\d+\/?$/, '')

type CategoryOption = { id: number | string; name: string; slug?: string }
type SubCategoryOption = { id: number | string; name: string; slug?: string; category_id?: number | string }
type ColorOption = { id: number | string; name: string; hex_code?: string }
type SizeOption = { id: number | string; name: string }
type DressTypeOption = { id: number | string; name: string; slug?: string }
type BrandOption = { id: number | string; name: string; slug?: string }
type ProductImage = { image_url?: string }
type ProductApi = { id: number | string; name?: string; price?: number | string; thumbnail?: ProductImage | null; images?: ProductImage[], average_rating: number }
type ProductCard = {
  id: number | string
  title: string
  price: number
  img: string
  discount_amount: number
  discount_type: number | undefined
  average_rating: number
}

const categories = ref<CategoryOption[]>([])
const subCategories = ref<SubCategoryOption[]>([])
const colors = ref<ColorOption[]>([])
const sizes = ref<SizeOption[]>([])
const dressTypes = ref<DressTypeOption[]>([])
const brands = ref<BrandOption[]>([])

const products = ref<ProductCard[]>([])
const displayProducts = ref<ProductCard[]>([])

const selectedCategory = ref('')
const searchText = ref('')
const priceRange = ref<[number, number]>([0, 200])
const colorFilter = ref('')
const sizeFilter = ref('')
const brandFilter = ref('')
const subCategoryFilter = ref('')
const dressStyleFilter = ref('')
const sortBy = ref<'newest' | 'price_asc' | 'price_desc' | 'name_asc'>('newest')

const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 })

const isLoadingCategories = ref(false)
const isLoadingFilters = ref(false)
const isLoadingProducts = ref(false)
const errorMessage = ref('')
const isToggleFilter = ref(false)

const isFetching = computed(() => isLoadingCategories.value || isLoadingFilters.value || isLoadingProducts.value)

const categoryParam = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const collectionParam = computed(() => {
  const raw = route.query.collection
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const subCategoryParam = computed(() => {
  const raw = route.query.sub_category
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const brandParam = computed(() => {
  const raw = route.query.brand
  const value = Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
  return /^\d+$/.test(value) ? value : ''
})
const brandOnlyParam = computed(() => {
  const raw = route.query.brand_only
  const value = Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
  return ['1', 'true', 'yes'].includes(value.toLowerCase())
})
const collectionOnlyParam = computed(() => {
  const raw = route.query.collection_only
  const value = Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
  return ['1', 'true', 'yes'].includes(value.toLowerCase()) && !!collectionParam.value
})
const priceMinParam = computed(() => {
  const raw = route.query.price_min
  const value = Array.isArray(raw) ? Number(raw[0]) : Number(raw)
  return Number.isFinite(value) ? value : 0
})
const priceMaxParam = computed(() => {
  const raw = route.query.price_max
  const value = Array.isArray(raw) ? Number(raw[0]) : Number(raw)
  return Number.isFinite(value) ? value : 200
})
// searchTxtParam
const searchTxtParam = computed(() => {
  const raw = route.query.search_txt ?? route.query.searchTxt
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})

const currentCategoryLabel = computed(() => {
  if (!selectedCategory.value) return 'All Categories'
  const matched = categories.value.find((row) => (row.slug || String(row.id)) === selectedCategory.value)
  return matched?.name || selectedCategory.value
})

const resolveImageUrl = (input?: string) => {
  if (!input) return '/img/products/default_image.webp'
  if (/^https?:\/\//i.test(input)) return input
  if (input.startsWith('/')) return `${backendOrigin}${input}`
  return `${backendOrigin}/${input}`
}

const mapProductToCard = (item: ProductApi): ProductCard => {
  const thumbnail = item.thumbnail?.image_url || item.images?.[0]?.image_url || ''
  const parsedPrice = Number(item.price || 0)
  return {
    id: item.id,
    title: String(item.name || 'Untitled product'),
    price: Number.isFinite(parsedPrice) ? parsedPrice : 0,
    img: resolveImageUrl(thumbnail),
    discount_amount: 0,
    discount_type: undefined,
    average_rating: item?.average_rating
  }
}

const sortProducts = () => {
  const rows = [...products.value]
  if (sortBy.value === 'price_asc') rows.sort((a, b) => a.price - b.price)
  else if (sortBy.value === 'price_desc') rows.sort((a, b) => b.price - a.price)
  else if (sortBy.value === 'name_asc') rows.sort((a, b) => a.title.localeCompare(b.title))
  displayProducts.value = rows
}

const fetchCategories = async () => {
  if (isLoadingCategories.value) return
  isLoadingCategories.value = true
  try {
    const response: any = await $fetch(`${apiBase}/categories`, {
      method: 'GET',
    })
    categories.value = Array.isArray(response?.data) ? response.data : []
  } finally {
    isLoadingCategories.value = false
  }
}

const fetchFilterOptions = async () => {
  if (isLoadingFilters.value) return
  isLoadingFilters.value = true
  try {
    const isCategoryLocked = brandOnlyParam.value || collectionOnlyParam.value
    const response: any = await $fetch(`${apiBase}/products/filters`, {
      method: 'GET',
      query: {
        category: isCategoryLocked ? undefined : (selectedCategory.value || undefined),
        sub_category: isCategoryLocked ? undefined : (subCategoryFilter.value || undefined),
        collection: brandOnlyParam.value ? undefined : (dressStyleFilter.value || undefined),
        brand: collectionOnlyParam.value ? undefined : (brandFilter.value || undefined),
        search_txt: isCategoryLocked ? undefined : (searchText.value || undefined),
        price_min: isCategoryLocked ? undefined : (priceRange.value[0] > 0 ? priceRange.value[0] : undefined),
        price_max: isCategoryLocked ? undefined : (priceRange.value[1] < 200 ? priceRange.value[1] : undefined),
      },
    })
    subCategories.value = Array.isArray(response?.data?.sub_categories) ? response.data.sub_categories : []
    colors.value = Array.isArray(response?.data?.colors) ? response.data.colors : []
    sizes.value = Array.isArray(response?.data?.sizes) ? response.data.sizes : []
    dressTypes.value = Array.isArray(response?.data?.collections) ? response.data.collections : []
    brands.value = Array.isArray(response?.data?.brands) ? response.data.brands : []
    selectedCategory.value = categoryParam.value || ''

    if (colorFilter.value && !colors.value.some((row) => String(row.id) === colorFilter.value)) {
      colorFilter.value = ''
    }
    if (sizeFilter.value && !sizes.value.some((row) => String(row.id) === sizeFilter.value)) {
      sizeFilter.value = ''
    }
    // Keep explicit brand query even when API returns no matching products/brands.
    // Otherwise brand filter gets cleared and products fallback to unfiltered results.
    if (subCategoryFilter.value && !subCategories.value.some((row) => (row.slug || String(row.id)) === subCategoryFilter.value)) {
      subCategoryFilter.value = ''
    }
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Unable to load category filters.'
  } finally {
    isLoadingFilters.value = false
  }
}

const fetchProducts = async () => {
  if (isLoadingProducts.value) return
  isLoadingProducts.value = true
  errorMessage.value = ''

  try {
    const isCategoryLocked = brandOnlyParam.value || collectionOnlyParam.value
    const response: any = await $fetch(`${apiBase}/products`, {
      method: 'GET',
      query: {
        page: page.value,
        per_page: meta.value.per_page,
        category: isCategoryLocked ? undefined : (selectedCategory.value || undefined),
        sub_category: isCategoryLocked ? undefined : (subCategoryFilter.value || undefined),
        search_txt: isCategoryLocked ? undefined : (searchText.value || undefined),
        price_min: isCategoryLocked ? undefined : (priceRange.value[0] > 0 ? priceRange.value[0] : undefined),
        price_max: isCategoryLocked ? undefined : (priceRange.value[1] < 200 ? priceRange.value[1] : undefined),
        color: colorFilter.value || undefined,
        size: sizeFilter.value || undefined,
        collection: brandOnlyParam.value ? undefined : (dressStyleFilter.value || undefined),
        brand: collectionOnlyParam.value ? undefined : (brandFilter.value || undefined),
      },
    })

    products.value = (Array.isArray(response?.data) ? response.data : []).map((row: ProductApi) => mapProductToCard(row))
    meta.value = {
      current_page: Number(response?.meta?.current_page || 1),
      last_page: Number(response?.meta?.last_page || 1),
      per_page: Number(response?.meta?.per_page || 12),
      total: Number(response?.meta?.total || 0),
    }
    sortProducts()
  } catch (error: any) {
    products.value = []
    displayProducts.value = []
    errorMessage.value = error?.data?.message || 'Failed to load products.'
  } finally {
    isLoadingProducts.value = false
  }
}

const applyFilters = async (closeDialog = false) => {
  page.value = 1
  const isCategoryLocked = brandOnlyParam.value || collectionOnlyParam.value
  const targetPath = isCategoryLocked
    ? '/frontend/categories'
    : selectedCategory.value
      ? `/frontend/categories/${selectedCategory.value}`
      : '/frontend/categories'
  const targetQuery: Record<string, string> = {}
  if (!brandOnlyParam.value && dressStyleFilter.value) targetQuery.collection = dressStyleFilter.value
  if (!collectionOnlyParam.value && brandFilter.value) targetQuery.brand = brandFilter.value
  if (!isCategoryLocked && subCategoryFilter.value) targetQuery.sub_category = subCategoryFilter.value
  if (!isCategoryLocked && priceRange.value[0] > 0) targetQuery.price_min = String(priceRange.value[0])
  if (!isCategoryLocked && priceRange.value[1] < 200) targetQuery.price_max = String(priceRange.value[1])
  if (!isCategoryLocked && searchText.value) targetQuery.search_txt = searchText.value
  if (brandOnlyParam.value && brandFilter.value) targetQuery.brand_only = '1'
  if (collectionOnlyParam.value && dressStyleFilter.value) targetQuery.collection_only = '1'

  if (route.path !== targetPath || JSON.stringify(route.query) !== JSON.stringify(targetQuery)) {
    await router.push({ path: targetPath, query: targetQuery })
  } else {
    await fetchFilterOptions()
    await fetchProducts()
  }

  if (closeDialog) isToggleFilter.value = false
}

const changeCategory = async (value: string, inDialog = false) => {
  selectedCategory.value = value
  if (inDialog) isToggleFilter.value = false
}

const selectColor = (value: string) => {
  colorFilter.value = colorFilter.value === value ? '' : value
}

const selectSize = (value: string) => {
  sizeFilter.value = sizeFilter.value === value ? '' : value
}

const selectDressStyle = (value: string) => {
  dressStyleFilter.value = dressStyleFilter.value === value ? '' : value
}

const selectBrand = (value: string) => {
  brandFilter.value = brandFilter.value === value ? '' : value
}

const selectSubCategory = (value: string) => {
  subCategoryFilter.value = subCategoryFilter.value === value ? '' : value
}

const toggleFilter = () => {
  isToggleFilter.value = true
}

const onPageChanged = async (nextPage: number) => {
  page.value = nextPage
  await fetchProducts()
}

const viewProduct = (id: number | string) => {
  router.push(`/frontend/product_detail/${id}`)
}

let resizeListenerBound = false

const checkScreenSize = () => {
  if (!import.meta.client) return
  if (window.innerWidth >= 720) isToggleFilter.value = false
}

watch(() => route.fullPath, async () => {
  const isCategoryLocked = brandOnlyParam.value || collectionOnlyParam.value
  selectedCategory.value = isCategoryLocked ? '' : (categoryParam.value || '')
  dressStyleFilter.value = brandOnlyParam.value ? '' : (collectionParam.value || '')
  brandFilter.value = collectionOnlyParam.value ? '' : (brandParam.value || '')
  subCategoryFilter.value = isCategoryLocked ? '' : (subCategoryParam.value || '')
  priceRange.value = isCategoryLocked ? [0, 200] : [priceMinParam.value, priceMaxParam.value]
  searchText.value = isCategoryLocked ? '' : (searchTxtParam.value || '')
  page.value = 1
  await fetchCategories()
  await fetchFilterOptions()
  await fetchProducts()
})

onMounted(async () => {
  const isCategoryLocked = brandOnlyParam.value || collectionOnlyParam.value
  selectedCategory.value = isCategoryLocked ? '' : (categoryParam.value || '')
  dressStyleFilter.value = brandOnlyParam.value ? '' : (collectionParam.value || '')
  brandFilter.value = collectionOnlyParam.value ? '' : (brandParam.value || '')
  subCategoryFilter.value = isCategoryLocked ? '' : (subCategoryParam.value || '')
  priceRange.value = isCategoryLocked ? [0, 200] : [priceMinParam.value, priceMaxParam.value]
  searchText.value = isCategoryLocked ? '' : (searchTxtParam.value || '')
  await fetchCategories()
  await fetchFilterOptions()
  await fetchProducts()
  if (import.meta.client) {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize)
    resizeListenerBound = true
  }
})

onBeforeUnmount(() => {
  if (!resizeListenerBound || !import.meta.client) return
  window.removeEventListener('resize', checkScreenSize)
  resizeListenerBound = false
})
</script>

<style>
.el-pagination {
  width: 100% !important;
  display: flex !important;
  justify-content: space-between;
}

.el-pagination.is-background .btn-next {
  align-self: flex-start !important;
}

.el-pagination.is-background .btn-prev {
  align-self: flex-end !important;
}
</style>
