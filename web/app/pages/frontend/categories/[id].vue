<template>
  <div>
    <!-- Loading state -->
    <LoadingPage v-if="isFetching && !products.length" embedded class="px-5 desktop:container py-10" :rows="8">
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
            </div>
            <div class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4">
              <FrontendCardProduct v-for="item in 8" :key="`skeleton-${item}`" loading />
            </div>
          </main>
        </div>
      </template>
    </LoadingPage>

    <!-- Main content -->
    <div v-else>
      <div class="px-5 desktop:container relative">
        <!-- Breadcrumb -->
        <BaseBreadcrumb :icon="ArrowRight">
          <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
          <el-breadcrumb-item v-if="selectedCategoryLabel !== 'All Categories'">
            <a @click="clearFilters">{{ 'Categories' }}</a>
          </el-breadcrumb-item>
          <el-breadcrumb-item v-else>Categories</el-breadcrumb-item>
          <el-breadcrumb-item v-if="selectedCategoryLabel !== 'All Categories'">
            {{ selectedCategoryLabel }}
          </el-breadcrumb-item>
        </BaseBreadcrumb>

        <section class="flex gap-6">
          <!-- Desktop Filter Sidebar -->
          <aside class="hidden lg:block w-[300px] xl:w-[320px] flex-shrink-0">
            <div class="sticky top-24 space-y-1 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm max-h-[calc(100vh-8rem)] overflow-y-auto">
              <!-- Filter Header -->
              <div class="flex items-center justify-between pb-3 mb-2 border-b border-gray-100">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                  <Icon name="lets-icons:filter" class="text-lg" />
                  Filters
                  <span v-if="activeFilterCount" class="ml-1 inline-flex items-center justify-center w-5 h-5 text-[11px] font-bold text-white bg-slate-900 rounded-full">
                    {{ activeFilterCount }}
                  </span>
                </h2>
                <button
                  v-if="activeFilterCount"
                  type="button"
                  class="text-xs text-slate-400 hover:text-slate-900 underline-offset-2 hover:underline transition-colors"
                  @click="clearFilters"
                >
                  Clear all
                </button>
              </div>

              <!-- Price Section -->
              <FilterSection title="Price Range" :collapsible="true" :default-open="true">
                <div class="px-1 pt-2">
                  <el-slider
                    v-model="priceRange"
                    :max="MAX_PRICE"
                    range
                    placement="bottom"
                    style="--el-slider-main-bg-color: #0f172a"
                    @change="onPriceChange"
                  />
                  <div class="flex items-center justify-between mt-3">
                    <span class="text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg px-3 py-1.5">${{ priceRange[0] }}</span>
                    <span class="text-xs text-slate-400">—</span>
                    <span class="text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg px-3 py-1.5">${{ priceRange[1] }}</span>
                  </div>
                </div>
              </FilterSection>

              <!-- Colors Section -->
              <FilterSection title="Colors" :collapsible="true" :default-open="true" :count="colors.length">
                <div class="flex flex-wrap gap-2.5 pt-2">
                  <button
                    v-for="color in colors"
                    :key="color.id"
                    @click="toggleColor(String(color.id))"
                    :aria-label="`Select color ${color.name}`"
                    class="group relative w-9 h-9 rounded-full border-2 transition-all duration-150 hover:scale-110"
                    :class="colorFilter === String(color.id)
                      ? 'border-slate-900 scale-110 shadow-md ring-2 ring-offset-2 ring-slate-900/20'
                      : 'border-gray-300 hover:border-slate-400'"
                    :style="{ backgroundColor: color.hex_code || '#d1d5db' }"
                  >
                    <span
                      v-if="colorFilter === String(color.id)"
                      class="absolute inset-0 flex items-center justify-center text-white text-sm font-bold drop-shadow-sm"
                    >✓</span>
                    <span
                      class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
                    >{{ color.name }}</span>
                  </button>
                </div>
              </FilterSection>

              <!-- Sizes Section -->
              <FilterSection title="Sizes" :collapsible="true" :default-open="true" :count="sizes.length">
                <div class="grid grid-cols-3 gap-2 pt-2">
                  <button
                    v-for="size in sizes"
                    :key="size.id"
                    @click="toggleSize(String(size.id))"
                    class="py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
                    :class="sizeFilter === String(size.id)
                      ? 'bg-slate-900 text-white shadow-md ring-1 ring-slate-900'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                  >
                    {{ size.name }}
                  </button>
                </div>
              </FilterSection>

              <!-- Brands Section -->
              <FilterSection title="Brands" :collapsible="true" :default-open="false" :count="brands.length">
                <div class="space-y-0.5 pt-1">
                  <button
                    v-for="brand in brands"
                    :key="brand.id"
                    @click="toggleBrand(String(brand.id))"
                    class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-sm transition-all duration-150"
                    :class="brandFilter === String(brand.id)
                      ? 'bg-slate-900 text-white font-medium shadow-sm'
                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  >
                    <span class="flex items-center gap-2">
                      <Icon
                        :name="brandFilter === String(brand.id) ? 'mdi:check-circle' : 'mdi:circle-outline'"
                        class="text-base"
                      />
                      {{ brand.name }}
                    </span>
                  </button>
                </div>
              </FilterSection>

            </div>
          </aside>

          <!-- Products Area -->
          <div class="flex-1 min-w-0">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
              <div>
                <h2 class="text-2xl font-bold text-slate-900">
                  {{ selectedCategoryLabel }}
                </h2>
                <p class="text-sm text-slate-400 mt-0.5">
                  <span v-if="isLoadingProducts" class="inline-block w-16 h-4 bg-slate-200 rounded animate-pulse" />
                  <span v-else>{{ meta.total }} product{{ meta.total !== 1 ? 's' : '' }} found</span>
                </p>
              </div>

              <div class="flex items-center gap-3">
                <!-- Mobile filter toggle -->
                <button
                  class="lg:hidden w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 hover:bg-slate-200 transition-colors relative"
                  @click="isToggleFilter = true"
                >
                  <Icon name="lets-icons:filter" class="text-xl" />
                  <span
                    v-if="activeFilterCount"
                    class="absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-slate-900 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                  >
                    {{ activeFilterCount }}
                  </span>
                </button>

                <!-- Sort Dropdown -->
                <div class="hidden lg:flex items-center gap-2">
                  <span class="text-sm text-slate-400">Sort by:</span>
                  <ClientOnly>
                    <el-select
                      v-model="sortBy"
                      placeholder="Select"
                      size="large"
                      style="width: 200px"
                      @change="onSortChange"
                    >
                      <el-option label="Newest" value="latest" />
                      <el-option label="Price (Low to High)" value="price_low" />
                      <el-option label="Price (High to Low)" value="price_high" />
                      <el-option label="Name (A-Z)" value="name_asc" />
                    </el-select>
                  </ClientOnly>
                </div>
              </div>
            </div>

            <!-- Active Filter Chips -->
            <div
              v-if="activeFilterCount && !isLoadingProducts"
              class="flex flex-wrap items-center gap-2 mb-4 animate-fade-in"
            >
              <span class="text-xs font-medium text-slate-400 mr-1">Active:</span>
              <button
                v-if="colorFilter"
                @click="colorFilter = ''; applyFilters()"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-700 hover:bg-slate-200 transition-colors"
              >
                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: activeColorHex }" />
                {{ activeColorName }}
                <Icon name="mdi:close" class="text-sm" />
              </button>
              <button
                v-if="sizeFilter"
                @click="sizeFilter = ''; applyFilters()"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-700 hover:bg-slate-200 transition-colors"
              >
                Size: {{ activeSizeName }}
                <Icon name="mdi:close" class="text-sm" />
              </button>
              <button
                v-if="brandFilter"
                @click="brandFilter = ''; applyFilters()"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-700 hover:bg-slate-200 transition-colors"
              >
                {{ activeBrandName }}
                <Icon name="mdi:close" class="text-sm" />
              </button>
              <button
                v-if="priceRange[0] > 0 || priceRange[1] < MAX_PRICE"
                @click="priceRange = [0, MAX_PRICE]; priceDebounceTimer && clearTimeout(priceDebounceTimer); applyFilters()"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-700 hover:bg-slate-200 transition-colors"
              >
                ${{ priceRange[0] }} – ${{ priceRange[1] }}
                <Icon name="mdi:close" class="text-sm" />
              </button>
              <button
                @click="clearFilters"
                class="text-xs text-slate-400 hover:text-slate-700 underline-offset-2 hover:underline transition-colors ml-1"
              >
                Clear all
              </button>
            </div>

            <!-- Error Alert -->
            <el-alert
              v-if="errorMessage"
              :title="errorMessage"
              type="error"
              :closable="false"
              show-icon
              class="mb-4 rounded-xl"
            />

            <!-- Product Grid -->
            <div class="min-h-[300px]">
              <!-- Loading Skeleton -->
              <div
                v-if="isLoadingProducts && !products.length"
                class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6"
              >
                <FrontendCardProduct v-for="item in 8" :key="`load-${item}`" loading />
              </div>

              <!-- Empty State -->
              <div
                v-else-if="displayProducts.length === 0 && !isLoadingProducts"
                class="rounded-2xl border border-dashed border-gray-200 bg-slate-50/50 p-10 text-center"
              >
                <div class="max-w-sm mx-auto">
                  <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                    <Icon name="mdi:package-variant-closed" class="text-3xl text-slate-400" />
                  </div>
                  <h3 class="text-lg font-semibold text-slate-900 mb-1">No products found</h3>
                  <p class="text-sm text-slate-500 mb-6">
                    Try adjusting your filters or browse a different category.
                  </p>
                  <div class="flex flex-wrap justify-center gap-3">
                    <button
                      @click="clearFilters"
                      class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors"
                    >
                      <Icon name="mdi:close-circle-outline" class="text-base" />
                      Clear all filters
                    </button>
                  </div>
                </div>
              </div>

              <!-- Products Grid -->
              <TransitionGroup
                v-else
                name="product-grid"
                tag="div"
                class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6"
              >
                <div
                  v-for="item in displayProducts"
                  :key="item.id"
                  class="cursor-pointer transition-all duration-300 hover:-translate-y-1"
                  @click="viewProduct(item.id)"
                >
                  <FrontendCardProduct
                    :title="item.title"
                    :price="item.price"
                    :img="item.img"
                    :discount-amount="item.discount_amount"
                    :discount-type="item.discount_type"
                    :rating-amount="item.average_rating"
                  />
                </div>
              </TransitionGroup>

              <!-- Loading Overlay for filter changes -->
              <div
                v-if="isLoadingProducts && products.length"
                class="relative"
              >
                <div class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-2xl">
                  <div class="flex items-center gap-2.5 px-4 py-2 bg-white rounded-full shadow-md border border-slate-100">
                    <div class="w-4 h-4 border-2 border-slate-900 border-t-transparent rounded-full animate-spin" />
                    <span class="text-sm font-medium text-slate-600">Updating&hellip;</span>
                  </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6 opacity-30 pointer-events-none">
                  <div v-for="item in 4" :key="`overlay-${item}`">
                    <FrontendCardProduct loading />
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="meta.total > meta.per_page" class="flex justify-center mt-8 mb-6">
              <el-pagination
                :pager-count="5"
                layout="prev, pager, next"
                :total="meta.total"
                :current-page="page"
                :page-size="meta.per_page"
                @current-change="onPageChanged"
              />
            </div>

            <div v-else-if="!isLoadingProducts && displayProducts.length" class="text-center py-6">
              <p class="text-xs text-slate-400">All {{ meta.total }} product{{ meta.total !== 1 ? 's' : '' }} loaded</p>
            </div>
          </div>
        </section>
      </div>

      <!-- Mobile Filter Dialog -->
      <el-dialog
        v-model="isToggleFilter"
        title="Filters"
        width="96%"
        align-center
        class="!rounded-t-3xl !rounded-b-none !pt-5 mobile-filter-dialog"
        top="8vh"
      >
        <div class="space-y-1 pb-4 max-h-[65vh] overflow-y-auto">
          <FilterSection title="Price Range" :collapsible="true" :default-open="true" mobile>
            <div class="px-1 pt-2">
              <el-slider v-model="priceRange" :max="MAX_PRICE" range placement="bottom" style="--el-slider-main-bg-color: #0f172a" @change="onPriceChangeMobile" />
              <div class="flex items-center justify-between mt-3">
                <span class="text-sm font-semibold bg-slate-100 rounded-lg px-3 py-1.5">${{ priceRange[0] }}</span>
                <span class="text-xs text-slate-400">—</span>
                <span class="text-sm font-semibold bg-slate-100 rounded-lg px-3 py-1.5">${{ priceRange[1] }}</span>
              </div>
            </div>
          </FilterSection>

          <FilterSection title="Colors" :collapsible="true" :default-open="true" mobile>
            <div class="flex flex-wrap gap-2.5 pt-2">
              <button v-for="color in colors" :key="color.id"
                @click="toggleColor(String(color.id))"
                class="w-9 h-9 rounded-full border-2 transition-all duration-150"
                :class="colorFilter === String(color.id) ? 'border-slate-900 ring-2 ring-offset-2 ring-slate-900/20' : 'border-gray-300'"
                :style="{ backgroundColor: color.hex_code || '#d1d5db' }"
              >
                <span v-if="colorFilter === String(color.id)" class="flex items-center justify-center text-white text-sm font-bold">✓</span>
              </button>
            </div>
          </FilterSection>

          <FilterSection title="Sizes" :collapsible="true" :default-open="true" mobile>
            <div class="grid grid-cols-4 gap-2 pt-2">
              <button v-for="size in sizes" :key="size.id" @click="toggleSize(String(size.id))"
                class="py-2.5 rounded-xl text-sm font-medium transition-all"
                :class="sizeFilter === String(size.id) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
              >{{ size.name }}</button>
            </div>
          </FilterSection>

          <FilterSection title="Brands" :collapsible="true" :default-open="false" mobile>
            <div class="space-y-0.5 pt-1">
              <button v-for="brand in brands" :key="brand.id" @click="toggleBrand(String(brand.id))"
                class="flex items-center justify-between w-full px-3 py-2.5 rounded-xl text-sm"
                :class="brandFilter === String(brand.id) ? 'bg-slate-900 text-white font-medium' : 'text-slate-600 hover:bg-slate-100'"
              >
                <span>{{ brand.name }}</span>
                <Icon name="mdi:check-bold" v-if="brandFilter === String(brand.id)" class="text-sm" />
              </button>
            </div>
          </FilterSection>

        </div>

        <template #footer>
          <div class="flex items-center gap-3">
            <button
              @click="clearFilters"
              class="flex-1 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
            >
              Clear all {{ activeFilterCount ? `(${activeFilterCount})` : '' }}
            </button>
            <button
              @click="closeMobileFilter"
              class="flex-1 py-3 rounded-2xl bg-slate-900 text-sm font-medium text-white hover:bg-slate-800 transition-colors"
            >
              Done
            </button>
          </div>
        </template>
      </el-dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'
import FilterSection from './components/FilterSection.vue'
import { onBeforeUnmount, onMounted } from 'vue'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const backendOrigin = apiBase.replace(/\/api\/v\d+\/?$/, '')

// --- Types ---
type CategoryOption = { id: number | string; name: string; slug?: string }
type ColorOption = { id: number | string; name: string; hex_code?: string }
type SizeOption = { id: number | string; name: string }
type BrandOption = { id: number | string; name: string; slug?: string }
type ProductImage = { image_url?: string }
type ProductApi = { id: number | string; name?: string; price?: number | string; thumbnail?: ProductImage | null; images?: ProductImage[]; average_rating: number }
type ProductCard = {
  id: number | string
  title: string
  price: number
  img: string
  discount_amount: number
  discount_type: number | undefined
  average_rating: number
}

// --- State ---
const categories = ref<CategoryOption[]>([])
const colors = ref<ColorOption[]>([])
const sizes = ref<SizeOption[]>([])
const brands = ref<BrandOption[]>([])

const products = ref<ProductCard[]>([])
const displayProducts = ref<ProductCard[]>([])

const selectedCategory = ref('')
const MAX_PRICE = 1000
const priceRange = ref<[number, number]>([0, MAX_PRICE])
const colorFilter = ref('')
const sizeFilter = ref('')
const brandFilter = ref('')
const subCategoryFilter = ref('')
const subCategorySlugMap = ref<Record<string, string>>({})
const collectionFilter = ref('')
const sortBy = ref<'latest' | 'price_low' | 'price_high' | 'name_asc'>('latest')

const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 })

const isLoadingCategories = ref(false)
const isLoadingFilters = ref(false)
const isLoadingProducts = ref(false)
const errorMessage = ref('')
const isToggleFilter = ref(false)
const isPriceDirty = ref(false)

// Debounce timer for price slider
let priceDebounceTimer: ReturnType<typeof setTimeout> | null = null
let resizeListenerBound = false

// --- Computed ---
const isFetching = computed(() =>
  (isLoadingCategories.value || isLoadingFilters.value) &&
  !products.value.length
)

const activeFilterCount = computed(() => {
  let count = 0
  if (colorFilter.value) count++
  if (sizeFilter.value) count++
  if (brandFilter.value) count++
  if (subCategoryFilter.value) count++
  if (collectionFilter.value) count++
  if (priceRange.value[0] > 0 || priceRange.value[1] < MAX_PRICE) count++
  return count
})

const selectedCategoryLabel = computed(() => {
  if (!selectedCategory.value) return 'All Categories'
  const matched = categories.value.find((row) => (row.slug || String(row.id)) === selectedCategory.value)
  return matched?.name || selectedCategory.value
})

const activeColorName = computed(() => {
  const matched = colors.value.find((c) => String(c.id) === colorFilter.value)
  return matched?.name || colorFilter.value
})

const activeColorHex = computed(() => {
  const matched = colors.value.find((c) => String(c.id) === colorFilter.value)
  return matched?.hex_code || '#000'
})

const activeSizeName = computed(() => {
  const matched = sizes.value.find((s) => String(s.id) === sizeFilter.value)
  return matched?.name || sizeFilter.value
})

const activeBrandName = computed(() => {
  const matched = brands.value.find((b) => String(b.id) === brandFilter.value)
  return matched?.name || brandFilter.value
})


// --- URL Param Helpers ---
const categoryParam = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const subCategoryParam = computed(() => {
  const raw = route.query.sub_category
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const collectionParam = computed(() => {
  const raw = route.query.collection
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const brandParam = computed(() => {
  const raw = route.query.brand
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})
const priceMinParam = computed(() => {
  const raw = route.query.price_min
  const value = Array.isArray(raw) ? Number(raw[0]) : Number(raw)
  return Number.isFinite(value) ? value : 0
})
const priceMaxParam = computed(() => {
  const raw = route.query.price_max
  const value = Array.isArray(raw) ? Number(raw[0]) : Number(raw)
  return Number.isFinite(value) ? value : MAX_PRICE
})

const queryValue = (key: string) => {
  const raw = route.query[key]
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
}

// Fetch all sub-categories and build a map: sub_category_slug -> parent_category_slug
const fetchSubCategorySlugMap = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/sub-categories`, { method: 'GET' })
    const items = Array.isArray(response?.data) ? response.data : []
    const map: Record<string, string> = {}
    items.forEach((item: any) => {
      if (item.slug && item.category?.slug) {
        map[item.slug] = item.category.slug
      }
    })
    subCategorySlugMap.value = map
  } catch {
    subCategorySlugMap.value = {}
  }
}

// --- Initialize from URL ---
const initializeCategoryState = () => {
  const rawSlug = categoryParam.value || ''
  // Check if the URL param is actually a sub_category slug
  if (rawSlug && subCategorySlugMap.value[rawSlug]) {
    selectedCategory.value = subCategorySlugMap.value[rawSlug]
    subCategoryFilter.value = rawSlug
  } else {
    selectedCategory.value = rawSlug
    subCategoryFilter.value = subCategoryParam.value || ''
  }
  collectionFilter.value = collectionParam.value || ''
  brandFilter.value = brandParam.value || ''
  colorFilter.value = queryValue('color') || ''
  sizeFilter.value = queryValue('size') || ''
  const requestedSort = queryValue('sort_by')
  sortBy.value = (['latest', 'price_low', 'price_high', 'name_asc'] as string[]).includes(requestedSort)
    ? requestedSort as typeof sortBy.value
    : 'latest'
  priceRange.value = [priceMinParam.value, Math.max(priceMinParam.value, priceMaxParam.value)]
}

// --- Image Helpers ---
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
    average_rating: item?.average_rating || 0,
  }
}

// --- API Calls ---
const fetchCategories = async () => {
  if (isLoadingCategories.value) return
  isLoadingCategories.value = true
  try {
    const response: any = await $fetch(`${apiBase}/categories`, { method: 'GET' })
    categories.value = Array.isArray(response?.data) ? response.data : []
  } finally {
    isLoadingCategories.value = false
  }
}

const fetchFilterOptions = async () => {
  if (isLoadingFilters.value) return
  isLoadingFilters.value = true
  try {
    const response: any = await $fetch(`${apiBase}/products/filters`, {
      method: 'GET',
      query: {
        category: selectedCategory.value || undefined,
        sub_category: subCategoryFilter.value || undefined,
        collection: collectionFilter.value || undefined,
        brand: brandFilter.value || undefined,
        price_min: priceRange.value[0] > 0 ? priceRange.value[0] : undefined,
        price_max: priceRange.value[1] < MAX_PRICE ? priceRange.value[1] : undefined,
        color: colorFilter.value || undefined,
        size: sizeFilter.value || undefined,
      },
    })
    colors.value = Array.isArray(response?.data?.colors) ? response.data.colors : []
    sizes.value = Array.isArray(response?.data?.sizes) ? response.data.sizes : []
    brands.value = Array.isArray(response?.data?.brands) ? response.data.brands : []

    // Clean up stale filter values
    if (colorFilter.value && !colors.value.some((row) => String(row.id) === colorFilter.value)) {
      colorFilter.value = ''
    }
    if (sizeFilter.value && !sizes.value.some((row) => String(row.id) === sizeFilter.value)) {
      sizeFilter.value = ''
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
    const response: any = await $fetch(`${apiBase}/products`, {
      method: 'GET',
      query: {
        page: page.value,
        per_page: meta.value.per_page,
        category: selectedCategory.value || undefined,
        sub_category: subCategoryFilter.value || undefined,
        collection: collectionFilter.value || undefined,
        price_min: priceRange.value[0] > 0 ? priceRange.value[0] : undefined,
        price_max: priceRange.value[1] < MAX_PRICE ? priceRange.value[1] : undefined,
        color: colorFilter.value || undefined,
        size: sizeFilter.value || undefined,
        brand: brandFilter.value || undefined,
        sort_by: sortBy.value,
      },
    })

    products.value = (Array.isArray(response?.data) ? response.data : []).map((row: ProductApi) => mapProductToCard(row))
    meta.value = {
      current_page: Number(response?.meta?.current_page || 1),
      last_page: Number(response?.meta?.last_page || 1),
      per_page: Number(response?.meta?.per_page || 12),
      total: Number(response?.meta?.total || 0),
    }
    sortDisplayProducts()
  } catch (error: any) {
    products.value = []
    displayProducts.value = []
    errorMessage.value = error?.data?.message || 'Failed to load products.'
  } finally {
    isLoadingProducts.value = false
  }
}

// --- Core Actions ---
const sortDisplayProducts = () => {
  const rows = [...products.value]
  if (sortBy.value === 'price_low') rows.sort((a, b) => a.price - b.price)
  else if (sortBy.value === 'price_high') rows.sort((a, b) => b.price - a.price)
  else if (sortBy.value === 'name_asc') rows.sort((a, b) => a.title.localeCompare(b.title))
  displayProducts.value = rows
}

const applyFilters = async (closeMobile = false) => {
  page.value = 1
  const targetPath = selectedCategory.value
    ? `/frontend/categories/${selectedCategory.value}`
    : '/frontend/categories'

  const targetQuery: Record<string, string> = {}
  if (subCategoryFilter.value) targetQuery.sub_category = subCategoryFilter.value
  if (collectionFilter.value) targetQuery.collection = collectionFilter.value
  if (brandFilter.value) targetQuery.brand = brandFilter.value
  if (priceRange.value[0] > 0) targetQuery.price_min = String(priceRange.value[0])
  if (priceRange.value[1] < MAX_PRICE) targetQuery.price_max = String(priceRange.value[1])
  if (colorFilter.value) targetQuery.color = colorFilter.value
  if (sizeFilter.value) targetQuery.size = sizeFilter.value
  if (sortBy.value !== 'latest') targetQuery.sort_by = sortBy.value

  const currentQueryStr = JSON.stringify(route.query)
  const newQueryStr = JSON.stringify(targetQuery)

  if (route.path !== targetPath || currentQueryStr !== newQueryStr) {
    await router.push({ path: targetPath, query: targetQuery })
  } else {
    await Promise.all([
      fetchFilterOptions(),
      fetchProducts(),
    ])
  }

  if (closeMobile) isToggleFilter.value = false
  isPriceDirty.value = false
}

// --- Filter Toggle Actions (instant apply) ---
const toggleColor = async (value: string) => {
  colorFilter.value = colorFilter.value === value ? '' : value
  await applyFilters()
}

const toggleSize = async (value: string) => {
  sizeFilter.value = sizeFilter.value === value ? '' : value
  await applyFilters()
}

const toggleBrand = async (value: string) => {
  brandFilter.value = brandFilter.value === value ? '' : value
  await applyFilters()
}

const onSortChange = async () => {
  await applyFilters()
}

// --- Debounced Price ---
const onPriceChange = async (_value: [number, number]) => {
  if (priceDebounceTimer) clearTimeout(priceDebounceTimer)
  isPriceDirty.value = true
  priceDebounceTimer = setTimeout(async () => {
    await applyFilters()
  }, 500)
}

const onPriceChangeMobile = (_value: [number, number]) => {
  isPriceDirty.value = true
}

// --- Clear Filters ---
const clearFilters = async () => {
  colorFilter.value = ''
  sizeFilter.value = ''
  brandFilter.value = ''
  subCategoryFilter.value = ''
  collectionFilter.value = ''
  priceRange.value = [0, MAX_PRICE]
  sortBy.value = 'latest'
  selectedCategory.value = ''
  page.value = 1
  await router.push({ path: '/frontend/categories', query: {} })
  isToggleFilter.value = false
}

const closeMobileFilter = () => {
  if (isPriceDirty.value) {
    applyFilters().then(() => {
      isToggleFilter.value = false
    })
    return
  }
  isToggleFilter.value = false
}

// --- Pagination ---
const onPageChanged = async (nextPage: number) => {
  page.value = nextPage
  await fetchProducts()
  // Smooth scroll to top of product grid
  if (import.meta.client) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const viewProduct = (id: number | string) => {
  router.push(`/frontend/product_detail/${id}`)
}

// --- Lifecycle ---
const loadInitialCategoryData = async () => {
  await fetchSubCategorySlugMap()
  initializeCategoryState()
  await Promise.all([
    fetchCategories(),
    fetchFilterOptions(),
    fetchProducts(),
  ])
}

await useAsyncData(`frontend-category-${route.fullPath}`, loadInitialCategoryData)

watch(() => route.fullPath, async () => {
  initializeCategoryState()
  page.value = 1
  await Promise.all([
    fetchCategories(),
    fetchFilterOptions(),
    fetchProducts(),
  ])
})

// Auto-close mobile dialog on resize to desktop
const checkScreenSize = () => {
  if (!import.meta.client) return
  if (window.innerWidth >= 1024 && isToggleFilter.value) {
    isToggleFilter.value = false
  }
}

onMounted(() => {
  if (import.meta.client) {
    window.addEventListener('resize', checkScreenSize)
    resizeListenerBound = true
  }
})

onBeforeUnmount(() => {
  // Clean up price debounce timer
  if (priceDebounceTimer) {
    clearTimeout(priceDebounceTimer)
    priceDebounceTimer = null
  }
  // Clean up resize listener
  if (resizeListenerBound && import.meta.client) {
    window.removeEventListener('resize', checkScreenSize)
    resizeListenerBound = false
  }
})
</script>

<style>
/* --- Transition Groups --- */
.product-grid-enter-active {
  transition: all 0.3s ease-out;
}
.product-grid-leave-active {
  transition: all 0.2s ease-in;
}
.product-grid-enter-from {
  opacity: 0;
  transform: translateY(16px) scale(0.98);
}
.product-grid-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
.product-grid-move {
  transition: transform 0.3s ease;
}

/* --- Fade animation --- */
.animate-fade-in {
  animation: fadeIn 0.25s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

/* --- Pagination --- */
.el-pagination {
  width: 100% !important;
  display: flex !important;
  justify-content: center;
  gap: 0.25rem;
}
.el-pagination button,
.el-pagination .el-pager li {
  border-radius: 9999px !important;
  min-width: 2.25rem;
  height: 2.25rem;
}
.el-pagination .el-pager li.is-active {
  background-color: #0f172a !important;
  color: white !important;
}

/* --- Mobile Filter Dialog --- */
.mobile-filter-dialog .el-dialog__body {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}
.mobile-filter-dialog .el-dialog__footer {
  padding-top: 0.75rem;
  border-top: 1px solid #f1f5f9;
}

/* --- Price Slider Tooltip --- */
.el-slider__runway .el-slider__bar {
  height: 6px;
}
.el-slider__runway {
  height: 6px;
}
.el-slider__button {
  width: 18px;
  height: 18px;
  border: 3px solid #0f172a;
}
</style>
