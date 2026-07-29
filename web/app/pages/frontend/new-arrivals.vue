<template>
  <div>
    <LoadingPage v-if="isFetching" embedded class="px-5 desktop:container py-10" :rows="8">
      <template #template>
        <div class="grid gap-6 lg:grid-cols-1">
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

    <div v-else>
      <div class="px-5 desktop:container relative">
        <BaseBreadcrumb :icon="ArrowRight">
          <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
          <el-breadcrumb-item>New Arrivals</el-breadcrumb-item>
        </BaseBreadcrumb>

        <section class="flex gap-6">
          <main class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">New Arrivals</h1>
                <p v-if="meta.total" class="text-sm text-slate-500 mt-1">
                  {{ meta.total }} product{{ meta.total !== 1 ? 's' : '' }} found
                </p>
              </div>
              <div class="flex items-center gap-2">
                <Icon name="mdi:sort" class="text-lg text-slate-400 hidden sm:block" />
                <el-select v-model="sortBy" class="sort-select" @change="onSortChange">
                  <el-option value="latest" label="Latest" />
                  <el-option value="price_low" label="Price: Low to High" />
                  <el-option value="price_high" label="Price: High to Low" />
                  <el-option value="name_asc" label="Name: A to Z" />
                </el-select>
              </div>
            </div>

            <div
              v-if="isLoadingProducts && !products.length"
              class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6"
            >
              <FrontendCardProduct v-for="item in 8" :key="`load-${item}`" loading />
            </div>

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
                  New arrivals will appear here as products are added.
                </p>
              </div>
            </div>

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
          </main>
        </section>
      </div>
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

const products = ref<ProductCard[]>([])
const displayProducts = ref<ProductCard[]>([])
const sortBy = ref<'latest' | 'price_low' | 'price_high' | 'name_asc'>('latest')
const page = ref(1)
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 })
const isLoadingProducts = ref(false)

const isFetching = computed(() => isLoadingProducts.value && !products.value.length)

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

const fetchProducts = async () => {
  if (isLoadingProducts.value) return
  isLoadingProducts.value = true

  try {
    const response: any = await $fetch(`${apiBase}/products`, {
      method: 'GET',
      query: {
        page: page.value,
        per_page: meta.value.per_page,
        new_arrivals: 1,
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
  } catch {
    products.value = []
    displayProducts.value = []
  } finally {
    isLoadingProducts.value = false
  }
}

const sortDisplayProducts = () => {
  const rows = [...products.value]
  if (sortBy.value === 'price_low') rows.sort((a, b) => a.price - b.price)
  else if (sortBy.value === 'price_high') rows.sort((a, b) => b.price - a.price)
  else if (sortBy.value === 'name_asc') rows.sort((a, b) => a.title.localeCompare(b.title))
  displayProducts.value = rows
}

const onSortChange = async () => {
  page.value = 1
  await fetchProducts()
}

const onPageChanged = async (nextPage: number) => {
  page.value = nextPage
  await fetchProducts()
  if (import.meta.client) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const viewProduct = (id: number | string) => {
  router.push(`/frontend/product_detail/${id}`)
}

await useAsyncData('new-arrivals', fetchProducts)
</script>

<style scoped>
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

:deep(.el-pagination) {
  width: 100% !important;
  display: flex !important;
  justify-content: center;
  gap: 0.25rem;
}
:deep(.el-pagination button),
:deep(.el-pagination .el-pager li) {
  border-radius: 9999px !important;
  min-width: 2.25rem;
  height: 2.25rem;
}
:deep(.el-pagination .el-pager li.is-active) {
  background-color: #0f172a !important;
  color: white !important;
}

:deep(.sort-select) {
  width: 180px;
}
:deep(.sort-select .el-select__wrapper) {
  border-radius: 9999px;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  box-shadow: none;
  border: 1px solid #e2e8f0;
  min-height: 2.5rem;
}
:deep(.sort-select .el-select__wrapper:hover) {
  border-color: #94a3b8;
}
</style>
