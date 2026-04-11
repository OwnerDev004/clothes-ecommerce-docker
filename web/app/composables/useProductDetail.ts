import { storeToRefs } from 'pinia'
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { useAuthStore } from '~/stores/authStore'
import { useCartStore } from '~/stores/cartStore'
import {
  extractResponseData,
  normalizeImageList,
  normalizeProductCard,
  type ProductApiRecord,
  type ProductCardItem,
} from '~/utils/product'

type SizeOption = {
  id: number
  name: string
}

type ProductVariant = {
  id: number
  sell_price?: number | string
  stock_quantity?: number
  color?: string | null
  size?: SizeOption
}

type ProductDetail = {
  id: number
  name?: string
  desc?: string
  price?: number | string
  thumbnail?: { image_url?: string | null } | null
  images?: Array<{ image_url?: string | null }>
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

type ReviewFilters = {
  sort_by: string
  rating: number | null
  mine_only: boolean
}

type ReviewForm = {
  rating: number
  comment: string
}

const normalizeRouteId = (input: unknown) => {
  if (Array.isArray(input)) {
    return input[0] ? String(input[0]) : ''
  }

  return input ? String(input) : ''
}

export const useProductDetail = () => {
  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const { accessToken, isAuthenticated } = storeToRefs(authStore)
  const cartStore = useCartStore()
  const config = useRuntimeConfig()
  const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

  const productId = computed(() => normalizeRouteId(route.params.id))
  const loadRequestId = ref(0)
  const sectionLoading = ref(false)
  const selectedImage = ref('')
  const selectedColor = ref<string | null>(null)
  const selectedSizeId = ref<number | null>(null)
  const qtyAmount = ref(1)
  const activeIndex = ref('pro_detail')
  const relatedProducts = ref<ProductCardItem[]>([])
  const productDeatil = ref<ProductDetailSection[]>([])
  const ratingAndReviews = ref<ProductReview[]>([])
  const faqsDetail = ref<ProductFaq[]>([])
  const reviewStats = ref({
    total_reviews: 0,
    average_rating: 0,
  })
  const reviewFilterDialogOpen = ref(false)
  const writeReviewDialogOpen = ref(false)
  const submittingReview = ref(false)
  const reviewFilters = reactive<ReviewFilters>({
    sort_by: 'latest',
    rating: null,
    mine_only: false,
  })
  const reviewForm = reactive<ReviewForm>({
    rating: 5,
    comment: '',
  })

  const sortBy = computed({
    get: () => reviewFilters.sort_by,
    set: (value: string | number | null) => {
      reviewFilters.sort_by = String(value || 'latest')
    },
  })

  const fetchWithTimeout = async <T>(
    url: string,
    options: Record<string, any> = {},
    timeoutMs = 15000,
  ): Promise<T> => {
    let timeoutHandle: ReturnType<typeof setTimeout> | null = null

    try {
      return await Promise.race([
        $fetch<T>(url, options),
        new Promise<T>((_, reject) => {
          timeoutHandle = setTimeout(() => {
            reject(new Error(`Request timed out after ${timeoutMs}ms`))
          }, timeoutMs)
        }),
      ])
    } finally {
      if (timeoutHandle) {
        clearTimeout(timeoutHandle)
      }
    }
  }

  const resolveAuthHeaders = () => {
    return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined
  }

  const imageList = computed(() => {
    return normalizeImageList(apiBase, [
      product.value?.thumbnail,
      ...(product.value?.images || []),
    ])
  })

  const colorOptions = computed(() => {
    const set = new Set<string>()

    for (const variant of product.value?.variants || []) {
      if (variant.color) {
        set.add(String(variant.color))
      }
    }

    return Array.from(set.values())
  })

  const sizeOptions = computed(() => {
    const map = new Map<number, SizeOption>()

    for (const variant of product.value?.variants || []) {
      if (selectedColor.value && variant.color !== selectedColor.value) {
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
      const colorOk = selectedColor.value ? variant.color === selectedColor.value : true
      const sizeOk = selectedSizeId.value ? variant.size?.id === selectedSizeId.value : true
      return colorOk && sizeOk
    }) || null
  })

  const displayPrice = computed(() => {
    const variantPrice = Number(selectedVariant.value?.sell_price || 0)
    return variantPrice > 0 ? variantPrice : Number(product.value?.price || 0)
  })

  const stockLabel = computed(() => {
    const stock = selectedVariant.value?.stock_quantity ?? null
    return stock === null || stock === undefined ? 'Stock: N/A' : `Stock: ${stock}`
  })

  const canAddToCart = computed(() => {
    const stock = Number(selectedVariant.value?.stock_quantity ?? 0)
    return Boolean(selectedVariant.value?.id) && qtyAmount.value > 0 && qtyAmount.value <= stock
  })

  const selectedVariantPreviewImage = computed(() => {
    return selectedImage.value || imageList.value[0] || '/img/products/default_image.webp'
  })

  const productKey = computed(() => `product-detail:${productId.value || 'missing'}`)

  const fetchProductById = async (id: string | number) => {
    const response: any = await fetchWithTimeout(`${apiBase}/products/${id}`, { method: 'GET' })
    return extractResponseData<ProductDetail>(response)
  }

  const fetchRelatedProducts = async (currentProduct: ProductDetail | null) => {
    try {
      const response: any = await fetchWithTimeout(`${apiBase}/products`, {
        method: 'GET',
        query: {
          category: currentProduct?.category_id,
          page: 1,
          per_page: 8,
        },
      })

      const rows = Array.isArray(response?.data) ? response.data : []
      return rows
        .filter((row: ProductApiRecord) => Number(row?.id) !== Number(currentProduct?.id))
        .slice(0, 4)
        .map((row: ProductApiRecord) => normalizeProductCard(row, apiBase))
    } catch {
      return []
    }
  }

  const fetchDetailSections = async (id: string | number) => {
    try {
      const response: any = await fetchWithTimeout(`${apiBase}/products/${id}/detail-sections`, {
        method: 'GET',
      })
      const payload = response?.data?.data || response?.data || response || {}

      return {
        product_detail: Array.isArray(payload?.product_detail) ? payload.product_detail : [],
        faqs_detail: Array.isArray(payload?.faqs_detail) ? payload.faqs_detail : [],
      }
    } catch {
      return {
        product_detail: [] as ProductDetailSection[],
        faqs_detail: [] as ProductFaq[],
      }
    }
  }

  const fetchRatingAndReviews = async (id: string | number) => {
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

      const response: any = await fetchWithTimeout(`${apiBase}/products/${id}/reviews`, {
        method: 'GET',
        credentials: 'include',
        headers: resolveAuthHeaders(),
        query,
      })

      const payload = response?.data || {}
      return {
        reviews: Array.isArray(payload?.reviews) ? payload.reviews : [],
        total_reviews: Number(payload?.total_reviews || 0),
        average_rating: Number(payload?.average_rating || 0),
      }
    } catch {
      return {
        reviews: [] as ProductReview[],
        total_reviews: 0,
        average_rating: 0,
      }
    }
  }


  // Main function fetching
  const productFetch = useAsyncData<ProductDetail | null>(
    productKey,
    async () => {
      if (!productId.value) {
        throw new Error('Missing product id.')
      }

      const payload = await fetchProductById(productId.value)

      if (!payload) {
        throw new Error('Failed to load product detail.')
      }

      return payload
    },
    {
      server: false,
      immediate: true,
      watch: [productId],
      getCachedData: () => undefined,
    },
  )

  const product = computed(() => productFetch.data.value)
  const pageLoading = computed(() => productFetch.pending.value)
  const pageErrorState = computed(() => productFetch.error.value?.message || '')

  const resetDetailState = () => {
    selectedImage.value = ''
    selectedColor.value = null
    selectedSizeId.value = null
    qtyAmount.value = 1
    relatedProducts.value = []
    productDeatil.value = []
    faqsDetail.value = []
    ratingAndReviews.value = []
    reviewStats.value = {
      total_reviews: 0,
      average_rating: 0,
    }
    sectionLoading.value = false
  }

  const chooseColor = (color: string) => {
    selectedColor.value = color
    const availableSizes = sizeOptions.value
    if (!availableSizes.some((size) => size.id === selectedSizeId.value)) {
      selectedSizeId.value = availableSizes[0]?.id || null
    }
  }

  const chooseSize = (sizeId: number) => {
    selectedSizeId.value = sizeId
  }

  const increment = () => {
    qtyAmount.value += 1
  }

  const decrement = () => {
    qtyAmount.value = qtyAmount.value > 1 ? qtyAmount.value - 1 : 1
  }

  const getDefaultVariantSelection = (currentProduct: ProductDetail | null) => {
    const firstVariant = currentProduct?.variants?.[0] || null
    selectedColor.value = firstVariant?.color || null
    selectedSizeId.value = firstVariant?.size?.id || null
  }

  const loadProductExtras = async (id: string | number, currentProduct: ProductDetail, requestId: number) => {
    sectionLoading.value = true

    try {
      const [relatedRowsResult, detailRowsResult, reviewRowsResult] = await Promise.allSettled([
        fetchRelatedProducts(currentProduct),
        fetchDetailSections(id),
        fetchRatingAndReviews(id),
      ])

      if (requestId !== loadRequestId.value) {
        return
      }

      if (relatedRowsResult.status === 'fulfilled') {
        relatedProducts.value = relatedRowsResult.value
      }

      if (detailRowsResult.status === 'fulfilled') {
        productDeatil.value = detailRowsResult.value.product_detail
        faqsDetail.value = detailRowsResult.value.faqs_detail
      }

      if (reviewRowsResult.status === 'fulfilled') {
        ratingAndReviews.value = reviewRowsResult.value.reviews
        reviewStats.value = {
          total_reviews: reviewRowsResult.value.total_reviews,
          average_rating: reviewRowsResult.value.average_rating,
        }
      }
    } finally {
      if (requestId === loadRequestId.value) {
        sectionLoading.value = false
      }
    }
  }

  const refreshReviews = async () => {
    if (!productId.value) {
      return
    }

    const reviewRows = await fetchRatingAndReviews(productId.value)
    ratingAndReviews.value = reviewRows.reviews
    reviewStats.value = {
      total_reviews: reviewRows.total_reviews,
      average_rating: reviewRows.average_rating,
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
    await refreshReviews()
  }

  const openWriteReviewDialog = async () => {
    if (!isAuthenticated.value && !accessToken.value) {
      ElMessage.warning('Please login first to write a review.')
      await router.push('/auth/login')
      return
    }

    writeReviewDialogOpen.value = true
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
      await fetchWithTimeout(`${apiBase}/cart/items`, {
        method: 'POST',
        credentials: 'include',
        headers: resolveAuthHeaders(),
        body: {
          variant_id: selectedVariant.value.id,
          quantity: qtyAmount.value,
        },
      })

      cartStore.addItem({
        id: selectedVariant.value.id,
        name: String(product.value?.name || 'Product'),
        price: Number(displayPrice.value || 0),
        image: selectedVariantPreviewImage.value,
        size: selectedVariant.value?.size?.name,
        color: selectedVariant.value?.color || undefined,
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

  const submitReview = async () => {
    if (!productId.value) {
      return
    }

    submittingReview.value = true

    try {
      await fetchWithTimeout(`${apiBase}/products/${productId.value}/reviews`, {
        method: 'POST',
        credentials: 'include',
        headers: resolveAuthHeaders(),
        body: {
          rating: reviewForm.rating,
          comment: reviewForm.comment.trim(),
        },
      })

      ElMessage.success('Review submitted successfully.')
      writeReviewDialogOpen.value = false
      reviewForm.rating = 5
      reviewForm.comment = ''
      await refreshReviews()
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

  watch(
    productId,
    () => {
      resetDetailState()
    },
    { immediate: true },
  )

  watch(
    product,
    (currentProduct) => {
      if (!currentProduct) {
        return
      }

      const previewImages = normalizeImageList(apiBase, [
        currentProduct.thumbnail,
        ...(currentProduct.images || []),
      ])

      selectedImage.value = previewImages[0] || '/img/products/default_image.webp'
      getDefaultVariantSelection(currentProduct)

      const requestId = ++loadRequestId.value
      void loadProductExtras(productId.value, currentProduct, requestId)
    },
    { immediate: true },
  )

  return {
    product,
    relatedProducts,
    qtyAmount,
    selectedImage,
    selectedColor,
    selectedSizeId,
    activeIndex,
    productDeatil,
    ratingAndReviews,
    faqsDetail,
    pageLoading,
    pageErrorState,
    sectionLoading,
    reviewStats,
    reviewFilterDialogOpen,
    writeReviewDialogOpen,
    submittingReview,
    reviewFilters,
    reviewForm,
    sortBy,
    imageList,
    colorOptions,
    sizeOptions,
    selectedVariant,
    displayPrice,
    stockLabel,
    canAddToCart,
    increment,
    decrement,
    chooseColor,
    chooseSize,
    addToCart,
    openReviewFilterDialog,
    applyReviewFilter,
    openWriteReviewDialog,
    submitReview,
    refreshReviews,
    refreshProductDetail: productFetch.refresh,
    viewProduct,
  }
}
