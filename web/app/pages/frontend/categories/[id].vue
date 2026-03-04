<template>
  <div v-loading.fullscreen.lock="isFetching" element-loading-text="Loading data..."
    element-loading-background="rgba(255, 255, 255, 0.75)">
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
              <button v-for="color in colors" :key="color.id" :style="{ backgroundColor: color.hex_code || '#d1d5db' }"
                @click="selectColor(String(color.id))" :aria-label="`Select color ${color.name}`"
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
            <h1 class="text-lg font-bold font-Poppins">Dress Style</h1>
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

          <el-button
            class="border rounded-[64px] p-4 w-full outline-none bg-transparent text-black hover:bg-black hover:text-white mt-4"
            :disabled="isLoadingProducts" @click="applyFilters">
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
                    :stars-num="item.stars_num" :rating-amount="item.rating_amount" />
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
          <h1 class="text-lg font-bold font-Poppins">Dress Style</h1>
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
type ColorOption = { id: number | string; name: string; hex_code?: string }
type SizeOption = { id: number | string; name: string }
type DressTypeOption = { id: number | string; name: string; slug?: string }
type ProductImage = { image_url?: string }
type ProductApi = { id: number | string; name?: string; price?: number | string; thumbnail?: ProductImage | null; images?: ProductImage[] }
type ProductCard = {
  id: number | string
  title: string
  price: number
  img: string
  discount_amount: number
  discount_type: number | undefined
  stars_num: number
  rating_amount: number
}

const categories = ref<CategoryOption[]>([])
const colors = ref<ColorOption[]>([])
const sizes = ref<SizeOption[]>([])
const dressTypes = ref<DressTypeOption[]>([])

const products = ref<ProductCard[]>([])
const displayProducts = ref<ProductCard[]>([])

const selectedCategory = ref('')
const searchText = ref('')
const priceRange = ref<[number, number]>([0, 200])
const colorFilter = ref('')
const sizeFilter = ref('')
const dressStyleFilter = ref('')
const sortBy = ref<'newest' | 'price_asc' | 'price_desc' | 'name_asc'>('newest')

const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 })

const isLoadingFilters = ref(false)
const isLoadingProducts = ref(false)
const errorMessage = ref('')
const isToggleFilter = ref(false)

const maxPrice = computed(() => priceRange.value[1] || undefined)
const isFetching = computed(() => isLoadingFilters.value || isLoadingProducts.value)

const categoryParam = computed(() => {
  const raw = route.params.id
  return Array.isArray(raw) ? String(raw[0] || '') : String(raw || '')
})

const currentCategoryLabel = computed(() => {
  if (!selectedCategory.value) return 'All Categories'
  const matched = categories.value.find((row) => (row.slug || String(row.id)) === selectedCategory.value)
  return matched?.name || selectedCategory.value
})

const resolveImageUrl = (input?: string) => {
  if (!input) return '/img/products/product1.png'
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
    stars_num: 5,
    rating_amount: 0,
  }
}

const sortProducts = () => {
  const rows = [...products.value]
  if (sortBy.value === 'price_asc') rows.sort((a, b) => a.price - b.price)
  else if (sortBy.value === 'price_desc') rows.sort((a, b) => b.price - a.price)
  else if (sortBy.value === 'name_asc') rows.sort((a, b) => a.title.localeCompare(b.title))
  displayProducts.value = rows
}

const fetchFilterOptions = async () => {
  if (isLoadingFilters.value) return
  isLoadingFilters.value = true
  try {
    const response: any = await $fetch(`${apiBase}/products/filters`, {
      method: 'GET',
      query: {
        category: selectedCategory.value || undefined,
        dress_style: dressStyleFilter.value || undefined,
        search_txt: searchText.value || undefined,
        price: maxPrice.value,
      },
    })
    categories.value = Array.isArray(response?.data?.categories) ? response.data.categories : []
    colors.value = Array.isArray(response?.data?.colors) ? response.data.colors : []
    sizes.value = Array.isArray(response?.data?.sizes) ? response.data.sizes : []
    dressTypes.value = Array.isArray(response?.data?.dress_types) ? response.data.dress_types : []
    selectedCategory.value = categoryParam.value || ''

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
        search_txt: searchText.value || undefined,
        price: maxPrice.value,
        color: colorFilter.value || undefined,
        size: sizeFilter.value || undefined,
        dress_style: dressStyleFilter.value || undefined,
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
  const targetPath = selectedCategory.value
    ? `/frontend/categories/${selectedCategory.value}`
    : '/frontend/categories'

  if (route.path !== targetPath) {
    await router.push(targetPath)
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

const checkScreenSize = () => {
  if (!import.meta.client) return
  if (window.innerWidth >= 720) isToggleFilter.value = false
}

watch(() => route.params.id, async () => {
  selectedCategory.value = categoryParam.value || ''
  page.value = 1
  await fetchFilterOptions()
  await fetchProducts()
})

onMounted(async () => {
  selectedCategory.value = categoryParam.value || ''
  await fetchFilterOptions()
  await fetchProducts()
  if (import.meta.client) {
    checkScreenSize()
    window.addEventListener('resize', checkScreenSize)
  }
})

onBeforeUnmount(() => {
  if (import.meta.client) window.removeEventListener('resize', checkScreenSize)
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
