import { useInfiniteScroll } from '@vueuse/core'
import { computed, ref } from 'vue'
import {
  normalizeProductCard,
  resolveApiImageUrl,
  type ProductApiRecord,
  type ProductCardItem,
} from '~/utils/product'

type BrandRecord = {
  id: number | string
  name?: string
  image_url?: string
}

type CategoryRecord = {
  id: number | string
  name?: string
  slug?: string
  image_url?: string
}

type CollectionRecord = {
  id: number | string
  name?: string
  slug?: string
  image_url?: string
}

type HomeCatalogPayload = {
  brands: BrandRecord[]
  categories: CategoryRecord[]
  collections: CollectionRecord[]
}

export const useHomeProducts = () => {
  const config = useRuntimeConfig()
  const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

  const products = ref<ProductCardItem[]>([])
  const brands = ref<BrandRecord[]>([])
  const categories = ref<CategoryRecord[]>([])
  const collections = ref<CollectionRecord[]>([])
  const isLoadingProducts = ref(false)
  const productError = ref('')
  const currentPage = ref(1)
  const hasMoreProducts = ref(true)
  const perPage = 8
  const loadMoreTrigger = ref<HTMLElement | null>(null)

  const topSellingProducts = computed(() => products.value.slice(0, 4))
  const collectionItems = computed(() => collections.value.slice(0, 4))

  const resolveVisualImage = (input?: string, fallback = '/img/products/default_image.webp') => {
    return resolveApiImageUrl(apiBase, input, fallback)
  }

  const getCollectionSpanClass = (index: number) => {
    const position = index % 4
    return position === 1 || position === 2 ? 'md:col-span-2' : ''
  }

  const fetchProducts = async (reset = false) => {
    if (isLoadingProducts.value) {
      return
    }

    if (!hasMoreProducts.value && !reset) {
      return
    }

    if (reset) {
      currentPage.value = 1
      hasMoreProducts.value = true
      products.value = []
    }

    isLoadingProducts.value = true
    productError.value = ''

    try {
      const response: any = await $fetch(`${apiBase}/products`, {
        method: 'GET',
        query: {
          page: currentPage.value,
          per_page: perPage,
        },
      })

      const rows = Array.isArray(response?.data) ? response.data : []
      products.value.push(...rows.map((row: ProductApiRecord) => normalizeProductCard(row, apiBase)))

      const current = Number(response?.meta?.current_page || currentPage.value)
      const last = Number(response?.meta?.last_page || current)
      hasMoreProducts.value = current < last
      currentPage.value = current + 1
    } catch (error: any) {
      productError.value = error?.data?.message || 'Failed to load products.'
    } finally {
      isLoadingProducts.value = false
    }
  }

  const fetchCatalogMeta = async () => {
    const [brandResponse, categoryResponse, collectionResponse] = await Promise.allSettled([
      $fetch(`${apiBase}/brands`, { method: 'GET' }),
      $fetch(`${apiBase}/categories`, { method: 'GET' }),
      $fetch(`${apiBase}/collections`, { method: 'GET' }),
    ])

    const parseRows = <T>(result: PromiseSettledResult<any>): T[] => {
      return result.status === 'fulfilled' && Array.isArray(result.value?.data) ? result.value.data : []
    }

    const payload: HomeCatalogPayload = {
      brands: parseRows<BrandRecord>(brandResponse),
      categories: parseRows<CategoryRecord>(categoryResponse),
      collections: parseRows<CollectionRecord>(collectionResponse),
    }

    brands.value = payload.brands
    categories.value = payload.categories
    collections.value = payload.collections
  }

  const loadInitialHomeData = async () => {
    await Promise.all([fetchCatalogMeta(), fetchProducts(true)])
  }

  useInfiniteScroll(
    loadMoreTrigger,
    () => {
      void fetchProducts()
    },
    {
      distance: 200,
      canLoadMore: () => hasMoreProducts.value && !isLoadingProducts.value,
    },
  )

  return {
    products,
    brands,
    categories,
    collections,
    isLoadingProducts,
    productError,
    currentPage,
    hasMoreProducts,
    loadMoreTrigger,
    topSellingProducts,
    collectionItems,
    fetchProducts,
    loadInitialHomeData,
    getCollectionSpanClass,
    resolveVisualImage,
  }
}
