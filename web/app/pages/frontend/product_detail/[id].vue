<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { ElMessage } from 'element-plus'
import type { TabsPaneContext } from 'element-plus'
import { useAuthStore } from '~/stores/authStore'
import { useCartStore } from '~/stores/cartStore'
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'

type ProductImage = {
  image_url?: string
}

type ColorOption = {
  id: number
  name: string
  hex_code?: string
}

type SizeOption = {
  id: number
  name: string
}

type ProductVariant = {
  id: number
  sell_price?: number | string
  stock_quantity?: number
  color?: ColorOption
  size?: SizeOption
}

type ProductDetail = {
  id: number
  name?: string
  desc?: string
  price?: number | string
  thumbnail?: ProductImage | null
  images?: ProductImage[]
  category_id?: number
  variants?: ProductVariant[]
}

type ProductDetailSection = {
  key: string
  label: string
  value: string | number
}

type ProductReview = {
  id: number
  customer_name: string
  rating: number
  comment: string
  created_at: string | null
}

type ProductFaq = {
  id: number
  question: string
  answer: string
}

type ProductCard = {
  id: number | string
  title: string
  price: number
  img: string
  discount_amount: number
  discount_type?: number
  stars_num: number
  rating_amount: number
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { accessToken, isAuthenticated } = storeToRefs(authStore)
const cartStore = useCartStore()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const backendOrigin = apiBase.replace(/\/api\/v\d+\/?$/, '')

const product = ref<ProductDetail | null>(null)
const loading = ref(false)
const errorMessage = ref('')
const relatedProducts = ref<ProductCard[]>([])
const qtyAmount = ref(1)
const selectedImage = ref('')
const selectedColorId = ref<number | null>(null)
const selectedSizeId = ref<number | null>(null)
const sortBy = ref('')
const activeIndex = ref(1)
const productDeatil = ref<ProductDetailSection[]>([])
const ratingAndReviews = ref<ProductReview[]>([])
const faqsDetail = ref<ProductFaq[]>([])
const detailsLoading = ref(false)
const reviewStats = ref({
  total_reviews: 0,
  average_rating: 0,
})
const reviewFilterDialogOpen = ref(false)
const writeReviewDialogOpen = ref(false)
const submittingReview = ref(false)
const reviewFilters = reactive({
  sort_by: 'latest',
  rating: null as number | null,
  mine_only: false,
})
const reviewForm = reactive({
  rating: 5,
  comment: '',
})


const tablists = ref([
  { id: 1, name: 'Product Details' },
  { id: 2, name: 'Rating & Reviews' },
  { id: 3, name: 'FAQs' },
])

const dropdownOptions = ref([
  { id: 1, label: 'Latest' },
  { id: 2, label: 'Oldest' },
])

const tabClick = (tab: TabsPaneContext) => {
  activeIndex.value = Number(tab.paneName)
}

const resolveImageUrl = (input?: string) => {
  if (!input) {
    return '/img/products/default_image.webp'
  }
  if (/^https?:\/\//i.test(input)) {
    return input
  }
  if (input.startsWith('/')) {
    return `${backendOrigin}${input}`
  }
  return `${backendOrigin}/${input}`
}

const imageList = computed(() => {
  const rows = [
    product.value?.thumbnail?.image_url || '',
    ...((product.value?.images || []).map((img) => img.image_url || '')),
  ].filter(Boolean)

  const uniqueRows = Array.from(new Set(rows))
  return uniqueRows.map((path) => resolveImageUrl(path))
})

const colorOptions = computed(() => {
  const map = new Map<number, ColorOption>()
  for (const variant of product.value?.variants || []) {
    if (variant.color?.id) {
      map.set(variant.color.id, variant.color)
    }
  }
  return Array.from(map.values())
})

const sizeOptions = computed(() => {
  const map = new Map<number, SizeOption>()
  for (const variant of product.value?.variants || []) {
    if (selectedColorId.value && variant.color?.id !== selectedColorId.value) {
      continue
    }
    if (variant.size?.id) {
      map.set(variant.size.id, variant.size)
    }
  }
  return Array.from(map.values())
})

const selectedVariant = computed(() => {
  return (product.value?.variants || []).find((variant) => {
    const colorOk = selectedColorId.value ? variant.color?.id === selectedColorId.value : true
    const sizeOk = selectedSizeId.value ? variant.size?.id === selectedSizeId.value : true
    return colorOk && sizeOk
  }) || null
})

const displayPrice = computed(() => {
  const variantPrice = Number(selectedVariant.value?.sell_price || 0)
  if (variantPrice > 0) {
    return variantPrice
  }
  return Number(product.value?.price || 0)
})

const stockLabel = computed(() => {
  const stock = selectedVariant.value?.stock_quantity ?? null
  if (stock === null || stock === undefined) {
    return 'Stock: N/A'
  }
  return `Stock: ${stock}`
})

const canAddToCart = computed(() => {
  const stock = Number(selectedVariant.value?.stock_quantity ?? 0)
  return Boolean(selectedVariant.value?.id) && qtyAmount.value > 0 && qtyAmount.value <= stock
})

const increment = () => {
  qtyAmount.value += 1
}

const decrement = () => {
  qtyAmount.value = qtyAmount.value > 1 ? qtyAmount.value - 1 : 1
}

const chooseColor = (colorId: number) => {
  selectedColorId.value = colorId
  const availableSizes = sizeOptions.value
  if (!availableSizes.some((size) => size.id === selectedSizeId.value)) {
    selectedSizeId.value = availableSizes[0]?.id || null
  }
}

const chooseSize = (sizeId: number) => {
  selectedSizeId.value = sizeId
}

const getAuthHeaders = () => {
  return accessToken.value
    ? { Authorization: `Bearer ${accessToken.value}` }
    : undefined
}

const addToCart = async () => {
  if (!selectedVariant.value?.id) {
    ElMessage.error('Please select color and size first.')
    return
  }

  if (!isAuthenticated.value && !accessToken.value) {
    ElMessage.warning('Please login first.')
    await router.push('/auth/login')
    return
  }

  if (!canAddToCart.value) {
    ElMessage.error('Quantity exceeds stock.')
    return
  }

  try {
    await $fetch(`${apiBase}/cart/items`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: {
        variant_id: selectedVariant.value.id,
        quantity: qtyAmount.value,
      }
    })
    cartStore.addItem({
      id: selectedVariant.value.id,
      name: String(product.value?.name || 'Product'),
      price: Number(displayPrice.value || 0),
      image: selectedImage.value || '',
      size: selectedVariant.value?.size?.name,
      color: selectedVariant.value?.color?.name,
    }, qtyAmount.value)
    ElMessage.success('Added to cart.')
    await router.push('/frontend/cart')
  } catch (error: any) {
    const statusCode = error?.statusCode ?? error?.status
    if (statusCode === 401 || statusCode === 403) {
      authStore.resetAuth()
      ElMessage.error('Session expired. Please login again.')
      await router.push('/auth/login')
      return
    }
    ElMessage.error(error?.data?.message || 'Failed to add to cart.')
  }
}

const mapCardProduct = (item: any): ProductCard => {
  const thumbnail = item?.thumbnail?.image_url || item?.images?.[0]?.image_url || ''
  return {
    id: item?.id,
    title: String(item?.name || 'Untitled product'),
    price: Number(item?.price || 0),
    img: resolveImageUrl(thumbnail),
    discount_amount: 0,
    discount_type: undefined,
    stars_num: 5,
    rating_amount: 0,
  }
}

const fetchRelatedProducts = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/products`, {
      method: 'GET',
      query: {
        category: product.value?.category_id,
        page: 1,
        per_page: 8,
      },
    })

    const rows = Array.isArray(response?.data) ? response.data : []
    relatedProducts.value = rows
      .filter((row: any) => Number(row?.id) !== Number(product.value?.id))
      .slice(0, 4)
      .map((row: any) => mapCardProduct(row))
  } catch {
    relatedProducts.value = []
  }
}

// fetch Products
const fetchProduct = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const id = route.params.id
    const response: any = await $fetch(`${apiBase}/products/${id}`, { method: 'GET' })
    product.value = (response?.data || null) as ProductDetail | null

    const images = imageList.value
    selectedImage.value = images[0] || '/img/products/default_image.webp'

    selectedColorId.value = colorOptions.value[0]?.id || null
    selectedSizeId.value = sizeOptions.value[0]?.id || null

    await fetchRelatedProducts()
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Failed to load product detail.'
    product.value = null
    relatedProducts.value = []
  } finally {
    loading.value = false
  }
}

const fetDetailProduct = async () => {
  detailsLoading.value = true
  try {
    const response: any = await $fetch(`${apiBase}/products/${route.params.id}/detail-sections`, {
      method: 'GET',
    })
    const payload = response?.data || {}
    productDeatil.value = Array.isArray(payload?.product_detail) ? payload.product_detail : []
    faqsDetail.value = Array.isArray(payload?.faqs_detail) ? payload.faqs_detail : []
    await fetchRatingAndReviews()
  } catch {
    productDeatil.value = []
    ratingAndReviews.value = []
    faqsDetail.value = []
    reviewStats.value = {
      total_reviews: 0,
      average_rating: 0,
    }
  } finally {
    detailsLoading.value = false
  }
}

const fetchRatingAndReviews = async () => {
  try {
    const query: Record<string, string | number> = {
      sort_by: reviewFilters.sort_by,
    }
    if (reviewFilters.rating !== null) {
      query.rating = reviewFilters.rating
    }
    if (reviewFilters.mine_only) {
      query.mine_only = 1
    }

    const response: any = await $fetch(`${apiBase}/products/${route.params.id}/reviews`, {
      method: 'GET',
      credentials: 'include',
      headers: getAuthHeaders(),
      query,
    })

    const payload = response?.data || {}
    ratingAndReviews.value = Array.isArray(payload?.reviews) ? payload.reviews : []
    reviewStats.value = {
      total_reviews: Number(payload?.total_reviews || 0),
      average_rating: Number(payload?.average_rating || 0),
    }
  } catch (error: any) {
    ratingAndReviews.value = []
    reviewStats.value = {
      total_reviews: 0,
      average_rating: 0,
    }
    if (reviewFilters.mine_only) {
      ElMessage.error(error?.data?.message || 'Failed to filter reviews.')
    }
  }
}

const openReviewFilterDialog = () => {
  reviewFilterDialogOpen.value = true
}

const applyReviewFilter = async () => {
  if (reviewFilters.mine_only && !isAuthenticated.value && !accessToken.value) {
    ElMessage.warning('Please login first to filter your own reviews.')
    await router.push('/auth/login')
    return
  }
  reviewFilterDialogOpen.value = false
  await fetchRatingAndReviews()
}

const openWriteReviewDialog = async () => {
  if (!isAuthenticated.value && !accessToken.value) {
    ElMessage.warning('Please login first to write a review.')
    await router.push('/auth/login')
    return
  }
  writeReviewDialogOpen.value = true
}

const submitReview = async () => {
  submittingReview.value = true
  try {
    await $fetch(`${apiBase}/products/${route.params.id}/reviews`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: {
        rating: reviewForm.rating,
        comment: reviewForm.comment.trim(),
      },
    })
    ElMessage.success('Review submitted successfully.')
    writeReviewDialogOpen.value = false
    reviewForm.rating = 5
    reviewForm.comment = ''
    await fetchRatingAndReviews()
  } catch (error: any) {
    const statusCode = error?.statusCode ?? error?.status
    if (statusCode === 401 || statusCode === 403) {
      authStore.resetAuth()
      ElMessage.error('Session expired. Please login again.')
      await router.push('/auth/login')
      return
    }
    ElMessage.error(error?.data?.message || 'Failed to submit review.')
  } finally {
    submittingReview.value = false
  }
}

const viewProduct = (id: number | string) => {
  router.push(`/frontend/product_detail/${id}`)
}

watch(() => route.params.id, () => {
  fetchProduct()
  fetDetailProduct()
}, { immediate: true })
</script>

<template>
  <div class="px-5 desktop:container">
    <BaseBreadcrumb :icon="ArrowRight">
      <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
      <el-breadcrumb-item>Product Detail</el-breadcrumb-item>
    </BaseBreadcrumb>

    <div v-if="loading" class="py-16 text-center text-gray-500">Loading product...</div>

    <div v-else-if="errorMessage" class="py-16 text-center">
      <p class="text-red-600">{{ errorMessage }}</p>
      <button class="mt-4 rounded-full border px-5 py-2 hover:bg-black hover:text-white" @click="fetchProduct">
        Retry
      </button>
    </div>

    <div v-else-if="product" class="flex items-center gap-16 flex-col desktop:flex-row mb-10">
      <div class="flex gap-14 flex-col-reverse desktop:flex-row items-center">
        <div class="grid grid-cols-3 desktop:grid-cols-1 gap-3">
          <button v-for="(image, imageIndex) in imageList" :key="imageIndex"
            class="focus:opacity-60 w-full desktop:w-[152px] h-full desktop:h-[167px] rounded-2lg"
            @click="selectedImage = image">
            <NuxtImg class="rounded-md hover:opacity-70 w-full h-full object-cover" :src="image" />
          </button>
        </div>

        <div class="w-full desktop:w-[440px] h-full desktop:h-[530px] rounded-3xl">
          <NuxtImg :src="selectedImage" class="rounded-3xl w-full h-full object-cover" />
        </div>
      </div>
      <div class="w-full desktop:w-1/2">
        <section class="border-b py-4 border-b-black">
          <h1 class="text-4xl font-extrabold">{{ product.name }}</h1>
          <SharesRating :rating-amount="0" :stars-num="5" />
          <SharesDiscount :discount-amount="0" :price="displayPrice" class="!text-3xl font-extrabold"
            :discountPercentage="'text-xl'" />
          <p>{{ product.desc || 'No description.' }}</p>
          <p class="mt-2 text-sm text-gray-500">{{ stockLabel }}</p>
        </section>

        <section class="border-b py-4 border-b-black">
          <h2 class="pb-2">Select Colors</h2>
          <div class="flex gap-4">
            <button v-for="color in colorOptions" :key="color.id"
              :style="{ backgroundColor: color.hex_code || '#9ca3af' }"
              class="w-10 h-10 rounded-full border border-gray-300 cursor-pointer hover:opacity-80 relative"
              @click="chooseColor(color.id)">
              <span v-if="selectedColorId === color.id"
                class="text-white font-bold absolute inset-0 flex items-center justify-center">
                ✓
              </span>
            </button>
          </div>
        </section>

        <section class="border-b py-4 border-b-black">
          <h2 class="pb-2">Choose Size</h2>
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="grid grid-flow-col gap-4 overflow-x-visible whitespace-nowrap">
              <button v-for="size in sizeOptions" :key="size.id" class="w-[100px] p-3 rounded-3xl cursor-pointer"
                :class="selectedSizeId === size.id ? 'bg-black text-white' : 'bg-gray hover:bg-black hover:text-white'"
                @click="chooseSize(size.id)">
                {{ size.name }}
              </button>
            </div>
          </div>
        </section>

        <section class="border-b py-4 border-black">
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="flex gap-3 items-center bg-gray rounded-2xl">
              <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-l-2xl"
                @click="decrement">
                <Icon name="ic:baseline-minus" class="text-base" />
              </button>
              <p class="mx-2">{{ qtyAmount }}</p>
              <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-r-2xl"
                @click="increment">
                <Icon name="ic:round-plus" class="text-base" />
              </button>
            </div>
            <button
              class="p-3 border bg-black hover:bg-white text-white hover:text-black rounded-2xl w-full disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!canAddToCart" @click="addToCart">
              Add to Cart
            </button>
          </div>
        </section>
      </div>
    </div>

    <div role="tablist" class="tabs tabs-bordered mt-6">
      <el-tabs v-model="activeIndex" class="demo-tabs" @tab-click="tabClick">
        <el-tab-pane v-for="tab in tablists" :key="tab.id" :label="tab.name" :name="tab.id">
          <div class="w-full">
            <div class="p-3" v-if="tab.id === 1">
              <h1>Product details</h1>
              <ul v-if="productDeatil.length" class="space-y-2">
                <li v-for="item in productDeatil" :key="item.key" class="text-sm leading-6">
                  <span class="font-semibold">{{ item.label }}:</span>
                  {{ item.value }}
                </li>
              </ul>
              <p v-else class="text-sm text-gray-500">No product details available.</p>
            </div>
            <div class="p-3" v-if="tab.id === 2">
              <div class="flex justify-between">
                <h1 class="text-lg sm:text-2xl">
                  All Reviews ({{ reviewStats.total_reviews }}) - Avg {{ reviewStats.average_rating }}/5
                </h1>
                <div class="flex gap-2">
                  <button class="bg-gray w-12 h-12 rounded-full text-2xl p-3" @click="openReviewFilterDialog">
                    <Icon name="lets-icons:filter" class="text-black" />
                  </button>
                  <SharesDropdown v-model="sortBy" :options="dropdownOptions" class="hidden desktop:block" />
                  <button class="bg-black text-white text-xs lg:text-md w-auto px-1 desktop:w-[300px] rounded-3xl"
                    @click="openWriteReviewDialog">
                    Write a Review
                  </button>
                </div>
              </div>

              <ul v-if="ratingAndReviews.length" class="mt-4 space-y-3">
                <li v-for="review in ratingAndReviews" :key="review.id" class="rounded-lg border p-3">
                  <p class="font-semibold">{{ review.customer_name }}
                    <el-rate v-model="review.rating" :max="5" disabled />

                  </p>
                  <p class="text-sm text-gray-700">{{ review.comment }}</p>
                </li>
              </ul>
              <p v-else class="mt-4 text-sm text-gray-500">No reviews yet.</p>
            </div>
            <div class="bg-gray p-3" v-if="tab.id === 3">
              <h1>FAQs</h1>
              <ul v-if="faqsDetail.length" class="space-y-3">
                <li v-for="faq in faqsDetail" :key="faq.id" class="rounded-lg bg-white p-3">
                  <p class="font-semibold">{{ faq.question }}</p>
                  <p class="text-sm text-gray-700">{{ faq.answer }}</p>
                </li>
              </ul>
              <p v-else class="text-sm text-gray-500">No FAQs available.</p>
            </div>
            <p v-if="detailsLoading" class="text-sm text-gray-500">Loading section content...</p>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <section class="py-10">
      <h1 class="font-Poppins text-2xl md:text-4xl leading-tight text-center py-4 font-extrabold">
        You might also like
      </h1>
      <div class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4">
        <template v-for="item in relatedProducts" :key="item.id">
          <FrontendCardProduct :title="item.title" :price="item.price" :img="item.img"
            :discount-amount="item.discount_amount" :discount-type="item.discount_type" :stars-num="item.stars_num"
            :rating-amount="item.rating_amount" @click="viewProduct(item.id)" />
        </template>
      </div>
      <div class="border-b border-zinc-300 mt-10"></div>
    </section>

    <el-dialog v-model="reviewFilterDialogOpen" title="Filter Reviews" width="420px">
      <div class="space-y-4">
        <div>
          <p class="mb-2 text-sm font-semibold">Sort by</p>
          <el-select v-model="reviewFilters.sort_by" class="w-full">
            <el-option label="Latest" value="latest" />
            <el-option label="Oldest" value="oldest" />
            <el-option label="Highest Rating" value="rating_high" />
            <el-option label="Lowest Rating" value="rating_low" />
          </el-select>
        </div>
        <div>
          <p class="mb-2 text-sm font-semibold">Rating</p>
          <el-select v-model="reviewFilters.rating" class="w-full" clearable placeholder="All ratings">
            <el-option :value="5" label="5 stars" />
            <el-option :value="4" label="4 stars" />
            <el-option :value="3" label="3 stars" />
            <el-option :value="2" label="2 stars" />
            <el-option :value="1" label="1 star" />
          </el-select>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-sm font-semibold">Only my reviews</p>
          <el-switch v-model="reviewFilters.mine_only" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="reviewFilterDialogOpen = false">Cancel</el-button>
          <el-button type="primary" @click="applyReviewFilter">Apply</el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog v-model="writeReviewDialogOpen" title="Write a Review" width="520px">
      <div class="space-y-4">
        <div>
          <p class="mb-2 text-sm font-semibold">Rating</p>
          <el-rate v-model="reviewForm.rating" :max="5" />
        </div>
        <div>
          <p class="mb-2 text-sm font-semibold">Comment</p>
          <el-input v-model="reviewForm.comment" type="textarea" :rows="5" maxlength="1000" show-word-limit />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="writeReviewDialogOpen = false">Cancel</el-button>
          <el-button type="primary" :loading="submittingReview" @click="submitReview">Submit Review</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.hide-scrollbar {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
