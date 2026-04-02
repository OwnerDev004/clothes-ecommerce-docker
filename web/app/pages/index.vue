<script setup lang="ts">
import { useRouter } from 'nuxt/app'
import { useInfiniteScroll } from '@vueuse/core'
import { Loading } from '@element-plus/icons-vue'
import { computed, onMounted, ref } from 'vue'
import { useCartStore } from '~/stores/cartStore'
const router = useRouter()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const backendOrigin = apiBase.replace(/\/api\/v\d+\/?$/, '')

type ProductImage = {
  image_url?: string
}

type ProductApi = {
  id: number | string
  name?: string
  price?: number | string
  thumbnail?: ProductImage | null
  images?: ProductImage[]
}

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

type BrandApi = {
  id: number | string
  name?: string
  image_url?: string
}

type CategoryApi = {
  id: number | string
  name?: string
  slug?: string
  image_url?: string
}

type DressTypeApi = {
  id: number | string
  name?: string
  slug?: string
  image_url?: string
}
const cartStore = useCartStore()
const products = ref<ProductCard[]>([])
const isLoadingProducts = ref(false)
const productError = ref('')
const currentPage = ref(1)
const hasMoreProducts = ref(true)
const perPage = 8
const loadMoreTrigger = ref<HTMLElement | null>(null)

const brands = ref<BrandApi[]>([])
const categories = ref<CategoryApi[]>([])
const collections = ref<DressTypeApi[]>([])

const onSlideChange = (swiper: any) => {
  const totalSlides = swiper.slides.length;

  swiper.slides.forEach((slide: any) => {
    slide.style.opacity = 1; // Reset opacity for all slides
  });

  const firstVisibleSlide = swiper.activeIndex - 2;
  const lastVisibleSlide = firstVisibleSlide + 4;

  if (swiper.slides[firstVisibleSlide]) {
    swiper.slides[firstVisibleSlide].style.opacity = 0.4;
  }
  if (swiper.slides[lastVisibleSlide] && lastVisibleSlide < totalSlides) {
    swiper.slides[lastVisibleSlide].style.opacity = 0.4;
  }
};

const topSellingProducts = computed(() => products.value.slice(0, 4))
const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

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

const resolveVisualImage = (input?: string, fallback = '/img/products/default_image.webp') => {
  if (!input) {
    return fallback
  }
  return resolveImageUrl(input)
}


const collectionItems = computed(() => {
  if (collections.value.length) {
    return collections.value.slice(0, 4) as DressTypeApi[]
  }
})

const getCollectionSpanClass = (index: number) => {
  const position = index % 4
  return position === 1 || position === 2 ? 'md:col-span-2' : ''
}

const mapProductToCard = (item: ProductApi): ProductCard => {
  const thumbnail = item.thumbnail?.image_url || item.images?.[0]?.image_url || ''
  const price = Number(item.price || 0)
  return {
    id: item.id,
    title: String(item.name || 'Untitled product'),
    price: Number.isFinite(price) ? price : 0,
    img: resolveImageUrl(thumbnail),
    discount_amount: 0,
    discount_type: undefined,
    stars_num: 5,
    rating_amount: 0,
  }
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
    // Keep loading visible briefly during infinite-scroll fetches.
    if (!reset) {
      await sleep(500)
    }

    const response: any = await $fetch(`${apiBase}/products`, {
      method: 'GET',
      query: {
        page: currentPage.value,
        per_page: perPage,
      }
    })

    const rows = Array.isArray(response?.data) ? response.data : []
    const mapped = rows.map((row: ProductApi) => mapProductToCard(row))
    products.value.push(...mapped)

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

const fetchBrands = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/brands`, { method: 'GET' })
    brands.value = Array.isArray(response?.data) ? response.data : []
  } catch {
    brands.value = []
  }
}

const fetchCategories = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/categories`, { method: 'GET' })
    categories.value = Array.isArray(response?.data) ? response.data : []
  } catch {
    categories.value = []
  }
}

const fetchDressTypes = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/collections`, { method: 'GET' })
    collections.value = Array.isArray(response?.data) ? response.data : []
  } catch {
    collections.value = []
  }
}

useInfiniteScroll(
  loadMoreTrigger,
  () => {
    fetchProducts()
  },
  {
    distance: 200,
    canLoadMore: () => hasMoreProducts.value && !isLoadingProducts.value,
  }
)

const viewProduct = (id: number | string) => {
  router.push('/frontend/product_detail/' + id);
}


const viewCollection = (slug: string | number | null | undefined) => {
  const normalizedSlug = String(slug || '').trim()
  const categoryPath = '/frontend/categories'

  if (!normalizedSlug || normalizedSlug === 'placeholder') {
    return router.push({ path: categoryPath })
  }

  return router.push({
    path: categoryPath,
    query: {
      collection: normalizedSlug,
      collection_only: '1',
      brand: undefined,
      brand_only: undefined,
      sub_category: undefined,
      price_min: undefined,
      price_max: undefined,
      search_txt: undefined,
    },
  })
}

// shopNow
const shopNow = () => {
  router.push('/frontend/categories')
}
//  viewAllProduct
const viewAllProduct = () => {
  router.push({
    path: '/frontend/categories',
    query: {
      collection: undefined,
      collection_only: undefined,
      brand: undefined,
      brand_only: undefined,
      sub_category: undefined,
      price_min: undefined,
      price_max: undefined,
      search_txt: undefined,
    },
  })
}
const getBrandRoute = (id: number | string) => {
  if (String(id) === 'placeholder') {
    return { path: '/frontend/categories' }
  }
  return {
    path: '/frontend/categories',
    query: { brand: String(id), brand_only: '1' },
  }
}

onMounted(async () => {
  await Promise.all([fetchBrands(), fetchCategories(), fetchDressTypes()])
  await fetchProducts(true)
})
</script>
<template>
  <main>
    <!-- Slide Section -->
    <section class="">
      <Swiper :modules="[SwiperAutoplay]" :space-between="1" :loop="true" :autoplay="{
        delay: 5000,
        disableOnInteraction: false,
      }">
        <SwiperSlide v-for="slide in 10" :key="slide" class="bg-gray">
          <div class="px-5  desktop:container flex flex-col lg:flex-row items-center gap-4 sm:gap-6 pt-5">
            <!-- Text Section -->
            <div class="w-full lg:w-1/2 xl:w-2/5 flex flex-col justify-center">
              <p class="font-Poppins text-5xl md:text-5xl font-bold leading-tight">
                FIND CLOTHES THAT MATCH YOUR STYLE
              </p>
              <p class="font-Lato font-thin mt-2">
                Browse through our diverse range of meticulously crafted
                garments, designed to bring out your individuality and cater to
                your sense of style.
              </p>
              <button class="bg-black text-white rounded-full py-3 px-8 mt-4 w-full desktop:w-[210px]" @click="shopNow">
                Shop Now
              </button>
            </div>

            <!-- Image Section -->
            <div class="w-full desktop:w-1/2 flex justify-center ml-auto">
              <NuxtImg sizes="sm:100vw md:400px lg:650px" src="/img/slide-1.png" format="webp" densities="x1"
                alt="Fashion Clothing" />
            </div>
          </div>
        </SwiperSlide>
      </Swiper>
    </section>

    <!-- Brand Section -->
    <section class="py-10 bg-black">
      <Swiper :modules="[SwiperAutoplay]" :slides-per-view="2" :space-between="8" :breakpoints="{
        640: { slidesPerView: 3, spaceBetween: 10 },
        1024: { slidesPerView: 4, spaceBetween: 12 },
      }" :loop="true" :autoplay="{
        delay: 5000,
        disableOnInteraction: true
      }">
        <SwiperSlide
          v-for="slide in brands.length ? brands : [{ id: 'placeholder', name: 'Brand', image_url: '/img/brand/brand1.png' }]"
          :key="slide.id">
          <NuxtLink :to="getBrandRoute(slide.id)" class="block rounded-xl bg-gray-200 h-[96px] md:h-[110px]">
            <div class="w-full h-full px-3 md:px-4 flex items-center justify-center">
              <NuxtImg :src="resolveVisualImage((slide as any).image_url, '/img/brand/default_image.webp')"
                :alt="slide.name"
                class="w-full h-full max-w-[170px] md:max-w-[220px] max-h-[64px] md:max-h-[72px] object-contain object-center"
                format="webp" densities="x1" />
            </div>
          </NuxtLink>
        </SwiperSlide>
      </Swiper>
    </section>
    <p>{{ cartStore.items }}</p>

    <!-- ALL PRODUCTS -->
    <section class="px-5 desktop:container py-10">
      <h1 class="font-Poppins text-4xl md:text-6xl leading-tight text-center py-4 font-extrabold">
        ALL PRODUCTS
      </h1>

      <!-- card -->
      <div class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4">
        <template v-for="item in products" :key="item.id">
          <FrontendCardProduct :title="item.title" :price="item.price" :img="item.img"
            :discount-amount="item.discount_amount" :discount-type="item.discount_type" :stars-num="item.stars_num"
            :rating-amount="item.rating_amount" @click="viewProduct(item.id)" />
        </template>
      </div>

      <div v-if="!products.length && !isLoadingProducts" class="mt-8 text-center text-gray-500">
        No products found.
      </div>

      <div class="mt-6 flex justify-center">
        <button v-if="productError"
          class="border rounded-[64px] px-5 py-2 outline-none bg-transparent text-black hover:bg-black hover:text-white"
          @click="fetchProducts()">
          Retry Loading
        </button>
      </div>

      <div class="mt-6 text-center text-sm text-gray-500">
        <div v-if="isLoadingProducts" class="flex items-center justify-center gap-2">
          <el-icon class="is-loading text-lg">
            <Loading />
          </el-icon>
          <span>Loading more products...</span>
        </div>
        <p v-else-if="!hasMoreProducts && products.length">You reached the end.</p>
      </div>

      <div ref="loadMoreTrigger" class="h-4"></div>

      <div class="border-b border-zinc-300 mt-10"></div>
    </section>

    <!-- TOP SELLING -->
    <section class="px-5 desktop:container py-10 border-b-gray">
      <h1 class="font-Poppins text-4xl md:text-6xl leading-tight text-center py-4 font-extrabold">
        TOP SELLING
      </h1>

      <!-- card -->
      <div class="grid gap-5 grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <template v-for="item in topSellingProducts" :key="`top-${item.id}`">
          <FrontendCardProduct :title="item.title" :price="item.price" :img="item.img"
            :discount-amount="item.discount_amount" :discount-type="item.discount_type" :stars-num="item.stars_num"
            :rating-amount="item.rating_amount" @click="viewProduct(item.id)" />
        </template>
      </div>
      <div class="flex justify-center mt-5">
        <button
          class="border rounded-[64px] p-4 w-full md:w-1/4 xl:w-1/6 outline-none bg-transparent text-black hover:bg-black hover:text-white"
          @click="viewAllProduct">
          View All
        </button>
      </div>
    </section>

    <!-- Shop by Category -->
    <!-- <section class="px-5 desktop:container py-10">
      <div class="bg-gray rounded-3xl text-center py-10">
        <h1 class="font-Poppins text-4xl md:text-5xl leading-tight text-center py-4 font-extrabold">
          SHOP BY CATEGORY
        </h1>

        <div class="grid gap-5 grid-cols-1 md:grid-cols-3 px-6 md:px-20 py-10">
          <div v-for="(category, index) in homeCategories" :key="category.id" :class="[
            'overflow-hidden bg-[#FFFFFF] flex flex-row items-center justify-center rounded-2xl relative h-[190px]',
            index === 1 || index === 2 ? 'col-span-1 md:col-span-2' : ''
          ]" @click="viewCategory(String(category.slug || category.id))">
            <h1 class="text-xl font-semibold mb-4 absolute top-10 text-black left-10">
              {{ category.name }}
            </h1>
            <NuxtImg sizes="sm:100vw md:669px"
              :src="resolveVisualImage(category.image_url, `/img/dress_styles/style${index + 1}.png`)" format="webp"
              densities="x1" :alt="category.name || 'Category'" class="w-full h-auto" />
          </div>
        </div>
      </div>
    </section> -->

    <section class="px-5 desktop:container pb-10">
      <div class="bg-white rounded-3xl text-center py-10 border border-zinc-200">
        <h1 class="font-Poppins text-4xl md:text-5xl leading-tight text-center py-4 font-extrabold">
          SHOP BY COLLECTION
        </h1>

        <div class="grid gap-5 grid-cols-1 md:grid-cols-3 px-6 md:px-20 py-6">
          <div v-for="(style, index) in collectionItems" :key="style.id"
            class="overflow-hidden bg-[#FFFFFF] flex flex-col items-center justify-center rounded-2xl relative h-[190px]"
            :class="getCollectionSpanClass(index)" @click="viewCollection(String(style.slug || style.id))">
            <h1 class="text-lg font-semibold mb-4 absolute top-8 text-black left-8">
              {{ style.name }}
            </h1>
            <NuxtImg sizes="sm:100vw md:420px"
              :src="resolveVisualImage(style.image_url, `/img/dress_styles/style${index + 1}.png`)" format="webp"
              densities="x1" :alt="style.name || 'Collection'" class="w-full h-auto" />
          </div>
        </div>
      </div>
    </section>

    <!-- customer reviews -->
    <section class="py-10">
      <h1
        class="px-2 desktop:container text-center font-Poppins text-[2rem] md:text-5xl leading-tight py-4 font-extrabold desktop:">
        OUR HAPPY CUSTOMERS
      </h1>
      <ClientOnly>
        <Swiper :modules="[SwiperAutoplay]" :slides-per-view="4.1" :space-between="20" :breakpoints="{
          '0': {
            slidesPerView: 1,
          },
          '375': {
            slidesPerView: 1.5,
            spaceBetween: 20,
          },
          '992': {
            slidesPerView: 4.1,
            spaceBetween: 20,
          },
        }" :loop="true" :centered-slides="true" @slideChange="onSlideChange" autoplay>
          <SwiperSlide v-for="(slide, index) in 10" :key="index">
            <div class="p-4 bg-gray flex justify-center flex-col">
              <SharesRating :stars-num="5" :rating-amount="0" />
              <h1 class="font-Poppins text-xl font-bold">Sarah M.</h1>
              <p class="font-Lato">
                I'm blown away by the quality and style of the clothes I received
                from Shop.co. From casual wear to elegant dresses, every piece
                I've bought has exceeded my expectations.”
              </p>
            </div>
          </SwiperSlide>
        </Swiper>
      </ClientOnly>
    </section>
  </main>
</template>

<style scoped>
/* Scoped styles if necessary */
.swiper-slide {
  /* Default style for swiper slide items */
  transition: opacity 0.3s ease;
}
</style>
