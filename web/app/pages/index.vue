<script setup lang="ts">
import { onMounted } from 'vue'
import { useHomeProducts } from '~/composables/useHomeProducts'

const router = useRouter()
const {
  products,
  brands,
  collections,
  heroSlides,
  isLoadingHeroSlides,
  isLoadingProducts,
  isLoadingCatalogMeta,
  isLoadingCustomerReview,
  isLoadingTopSelling,
  productError,
  loadMoreTrigger,
  topSellingProducts,
  collectionItems,
  customers_review,
  fetchProducts,
  loadInitialHomeData,
  getCollectionSpanClass,
  resolveVisualImage,
} = useHomeProducts()

useHead({
  link: [
    {
      rel: 'preload',
      as: 'image',
      href: '/img/slide-1.webp',
      fetchpriority: 'high',
    },
  ],
})

onMounted(() => {
  void loadInitialHomeData()
})

// --- Navigation ---
const viewProduct = (id: number | string) => {
  router.push(`/frontend/product_detail/${id}`)
}

const viewCollection = (slug: string | number | null | undefined) => {
  const normalizedSlug = String(slug || '').trim()
  if (!normalizedSlug || normalizedSlug === 'placeholder') {
    return router.push({ path: '/frontend/categories' })
  }
  return router.push({
    path: '/frontend/categories',
    query: { collection: normalizedSlug },
  })
}

const viewAllProduct = () => {
  router.push({ path: '/frontend/categories' })
}

const getBrandRoute = (id: number | string) => {
  if (String(id) === 'placeholder') {
    return { path: '/frontend/categories' }
  }
  return { path: '/frontend/categories', query: { brand: String(id) } }
}

onMounted(() => {
  // --- Intersection Observer for entrance animations ---
  if (!import.meta.client) return
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in')
          observer.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.1 },
  )
  document.querySelectorAll('.section-animate').forEach((el) => {
    observer.observe(el)
  })
})
</script>

<template>
  <main>
    <!-- ════════════════════════════════════════════ -->
    <!-- 1. HERO CAROUSEL (el-carousel)                -->
    <!-- ════════════════════════════════════════════ -->
    <section class="px-5 desktop:container pt-6">
      <div class="hero-carousel-wrapper rounded-[32px] border border-zinc-200 overflow-hidden">
        <el-carousel
          interval="5500"
          arrow="hover"
          height="560px"
          autoplay
          pause-on-hover
        >
          <el-carousel-item v-if="isLoadingHeroSlides">
            <div class="flex min-h-[560px] items-center justify-center">
              <div class="flex items-center gap-3 text-zinc-400">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-900" />
                <span class="text-sm">Loading slides...</span>
              </div>
            </div>
          </el-carousel-item>
          <el-carousel-item v-else-if="!heroSlides.length">
            <div class="flex min-h-[560px] items-center justify-center bg-gradient-to-br from-zinc-50 to-zinc-100">
              <div class="text-center">
                <Icon name="mdi:image-off-outline" class="text-5xl text-zinc-300" />
                <p class="mt-2 text-sm text-zinc-400">No slides available</p>
              </div>
            </div>
          </el-carousel-item>
          <el-carousel-item v-for="(slide, index) in heroSlides" :key="slide.id">
            <div
              class="grid min-h-[560px] items-center gap-8 px-6 py-8 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 xl:px-14"
              :style="{ background: slide.gradient || 'linear-gradient(135deg,#f7f7f7_0%,#ffffff_45%,#f1f5f9_100%)' }"
            >
              <div class="max-w-2xl">
                <p v-if="slide.subtitle" class="inline-flex rounded-full border border-zinc-300 bg-white/80 backdrop-blur-sm px-4 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-600 shadow-sm">
                  {{ slide.subtitle }}
                </p>
                <h1 class="mt-5 font-Poppins text-5xl font-extrabold leading-[1.02] text-zinc-950 md:text-6xl xl:text-7xl">
                  {{ slide.title }}
                </h1>
                <p v-if="slide.description" class="mt-5 max-w-xl text-base leading-7 text-zinc-600 md:text-lg">
                  {{ slide.description }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                  <button
                    class="rounded-full bg-zinc-950 px-8 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-zinc-800 hover:shadow-lg hover:shadow-zinc-900/20 active:scale-95"
                    @click="viewAllProduct">
                    {{ slide.link_text || 'Shop Now' }}
                  </button>
                  <button
                    class="rounded-full border border-zinc-300 bg-white/80 backdrop-blur-sm px-8 py-3 text-sm font-semibold text-zinc-900 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 active:scale-95"
                    @click="viewAllProduct">
                    View All Products
                  </button>
                </div>

                <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-2 border-t border-zinc-200 pt-6">
                  <div>
                    <p class="text-2xl font-extrabold text-zinc-950">200+</p>
                    <p class="text-xs text-zinc-500">International Brands</p>
                  </div>
                  <div class="hidden h-10 w-px bg-zinc-200 sm:block" />
                  <div>
                    <p class="text-2xl font-extrabold text-zinc-950">2,000+</p>
                    <p class="text-xs text-zinc-500">High-Quality Products</p>
                  </div>
                  <div class="hidden h-10 w-px bg-zinc-200 md:block" />
                  <div>
                    <p class="text-2xl font-extrabold text-zinc-950">5,000+</p>
                    <p class="text-xs text-zinc-500">Happy Customers</p>
                  </div>
                </div>
              </div>

              <div class="relative flex justify-center lg:justify-end">
                <div class="absolute inset-0 rounded-[28px] bg-[radial-gradient(circle_at_center,rgba(0,0,0,0.06),transparent_60%)]" />
                <template v-if="slide.image_url">
                  <img
                    :src="slide.image_url"
                    :alt="slide.title"
                    width="669" height="663"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'auto'"
                    decoding="async"
                    class="relative z-10 w-full max-w-[620px] object-contain"
                  />
                </template>
                <div v-else class="relative z-10 flex w-full max-w-[400px] items-center justify-center rounded-2xl bg-white/40 py-20">
                  <div class="text-center">
                    <Icon name="mdi:image-off-outline" class="text-5xl text-zinc-300" />
                    <p class="mt-2 text-xs text-zinc-400">No image</p>
                  </div>
                </div>
              </div>
            </div>
          </el-carousel-item>
        </el-carousel>
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 2. TRUST BAR (NEW)                          -->
    <!-- ════════════════════════════════════════════ -->
    <section class="px-5 desktop:container pt-10">
      <div class="grid grid-cols-2 divide-x divide-zinc-100 overflow-hidden rounded-2xl border border-zinc-100 bg-white md:grid-cols-4">
        <div class="flex flex-col items-center gap-2 px-4 py-6 text-center transition-colors duration-200 hover:bg-zinc-50 sm:px-6 sm:py-7">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-950 text-white">
            <Icon name="mdi:truck-delivery" class="text-xl" />
          </div>
          <h3 class="text-sm font-bold text-zinc-900">Free Shipping</h3>
          <p class="text-xs leading-relaxed text-zinc-500">On orders over $50</p>
        </div>
        <div class="flex flex-col items-center gap-2 px-4 py-6 text-center transition-colors duration-200 hover:bg-zinc-50 sm:px-6 sm:py-7">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-950 text-white">
            <Icon name="mdi:refresh" class="text-xl" />
          </div>
          <h3 class="text-sm font-bold text-zinc-900">Easy Returns</h3>
          <p class="text-xs leading-relaxed text-zinc-500">30-day return policy</p>
        </div>
        <div class="flex flex-col items-center gap-2 px-4 py-6 text-center transition-colors duration-200 hover:bg-zinc-50 sm:px-6 sm:py-7">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-950 text-white">
            <Icon name="mdi:shield-check" class="text-xl" />
          </div>
          <h3 class="text-sm font-bold text-zinc-900">Secure Payment</h3>
          <p class="text-xs leading-relaxed text-zinc-500">100% protected checkout</p>
        </div>
        <div class="flex flex-col items-center gap-2 px-4 py-6 text-center transition-colors duration-200 hover:bg-zinc-50 sm:px-6 sm:py-7">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-950 text-white">
            <Icon name="mdi:headset" class="text-xl" />
          </div>
          <h3 class="text-sm font-bold text-zinc-900">24/7 Support</h3>
          <p class="text-xs leading-relaxed text-zinc-500">Dedicated assistance</p>
        </div>
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 3. BRAND CAROUSEL                           -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-10 bg-black">
      <div v-if="isLoadingCatalogMeta" class="desktop:container px-5">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
          <div v-for="item in 4" :key="item" class="h-[96px] animate-pulse rounded-xl bg-white/10 md:h-[110px]" />
        </div>
      </div>
      <div v-else-if="brands.length != 0">
        <ClientOnly>
          <Swiper
            :modules="[SwiperAutoplay]"
            :slides-per-view="2"
            :space-between="8"
            :breakpoints="{
              640: { slidesPerView: 3, spaceBetween: 10 },
              1024: { slidesPerView: 4, spaceBetween: 12 },
            }"
            :autoplay="{ delay: 5000, disableOnInteraction: true }"
          >
            <SwiperSlide v-for="slide in brands" :key="slide.id">
              <NuxtLink :to="getBrandRoute(slide.id)" class="block rounded-xl bg-gray-200 h-[96px] md:h-[110px] transition-all duration-200 hover:scale-[1.02] hover:shadow-lg">
                <div class="w-full h-full px-3 md:px-4 flex items-center justify-center">
                  <NuxtImg
                    :src="resolveVisualImage((slide as any).image_url, '/img/brand/default_image.webp')"
                    :alt="slide.name"
                    class="w-full h-full max-w-[170px] md:max-w-[220px] max-h-[64px] md:max-h-[72px] object-contain object-center"
                    format="webp" densities="x1"
                  />
                </div>
              </NuxtLink>
            </SwiperSlide>
          </Swiper>
        </ClientOnly>
      </div>
      <div v-else class="text-sm flex justify-center text-white/50">
        Not available Brands from customers
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 4. ALL PRODUCTS                             -->
    <!-- ════════════════════════════════════════════ -->
    <section class="section-animate px-5 desktop:container py-12 transition-all duration-700 delay-100">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
        <div>
          <h2 class="font-Poppins text-3xl font-extrabold text-zinc-950 md:text-4xl">
            All Products
          </h2>
          <p class="mt-1 text-sm text-zinc-500">
            Browse our full collection &mdash; {{ products.length }}+ styles available
          </p>
        </div>
        <button
          class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-5 py-2 text-sm font-medium text-zinc-700 transition-all duration-200 hover:border-zinc-900 hover:bg-zinc-900 hover:text-white hover:shadow-lg active:scale-95"
          @click="viewAllProduct"
        >
          View All
          <Icon name="mdi:arrow-right" class="text-base" />
        </button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6">
        <template v-if="isLoadingProducts && !products.length">
          <FrontendCardProduct v-for="item in 8" :key="item" loading />
        </template>
        <template v-else>
          <FrontendCardProduct
            v-for="item in products" :key="item.id"
            :title="item.title" :price="item.price"
            :img="item.img" :discount-amount="item.discount_amount"
            :discount-type="item.discount_type" :rating-amount="item.average_rating"
            @click="viewProduct(item.id)"
          />
        </template>
      </div>

      <div v-if="!products.length && !isLoadingProducts" class="mt-8 text-center text-gray-500">
        No products found.
      </div>

      <div class="mt-6 flex justify-center">
        <button
          v-if="productError"
          class="border rounded-[64px] px-5 py-2 outline-none bg-transparent text-black hover:bg-black hover:text-white transition-all duration-200"
          @click="fetchProducts()"
        >
          Retry Loading
        </button>
      </div>

      <!-- Infinite scroll trigger & end indicator -->
      <div class="mt-8 text-center">
        <p v-if="!hasMoreProducts && products.length" class="text-xs text-zinc-400">
          You&rsquo;ve viewed all {{ products.length }} products
        </p>
        <div v-if="isLoadingProducts && products.length" class="flex items-center justify-center gap-2 text-sm text-zinc-500">
          <div class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-900" />
          Loading more&hellip;
        </div>
      </div>

      <div v-if="products.length && isLoadingProducts" ref="loadMoreTrigger" class="h-4" />
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 5. TOP SELLING (Redesigned)                 -->
    <!-- ════════════════════════════════════════════ -->
    <section class="section-animate px-5 desktop:container pb-12 transition-all duration-700 delay-300">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-8">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="font-Poppins text-3xl font-extrabold text-zinc-950 md:text-4xl">
              Top Selling
            </h2>
            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-3 py-0.5 text-xs font-semibold text-red-500">
              <Icon name="mdi:fire" class="text-sm" />
              Hot
            </span>
          </div>
          <p class="mt-1 text-sm text-zinc-500">
            Our most popular picks right now
          </p>
        </div>
        <button
          class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-5 py-2 text-sm font-medium text-zinc-700 transition-all duration-200 hover:border-zinc-900 hover:bg-zinc-900 hover:text-white hover:shadow-lg active:scale-95"
          @click="viewAllProduct"
        >
          View All
          <Icon name="mdi:arrow-right" class="text-base" />
        </button>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="isLoadingTopSelling" class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6">
        <FrontendCardProduct v-for="item in 4" :key="`top-load-${item}`" loading />
      </div>

      <div v-else-if="topSellingProducts.length" class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6">
        <div
          v-for="(item) in topSellingProducts" :key="`top-${item.id}`"
          class="relative"
        >
          <!-- Badge -->
          <div class="absolute -top-1.5 -left-1.5 z-10 flex items-center gap-1 rounded-full bg-red-500 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-lg">
            <Icon name="mdi:crown" class="text-xs" />
            Best Seller
          </div>
          <FrontendCardProduct
            :title="item.title" :price="item.price"
            :img="item.img" :discount-amount="item.discount_amount"
            :discount-type="item.discount_type" :rating-amount="item.average_rating"
            @click="viewProduct(item.id)"
          />
        </div>
      </div>
      <div v-else class="text-center py-8 text-zinc-400">
        No top selling products yet.
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 7. SHOP BY COLLECTION (Redesigned)          -->
    <!-- ════════════════════════════════════════════ -->
    <section class="section-animate px-5 desktop:container pb-12 transition-all duration-700 delay-[400ms]">
      <div class="bg-white rounded-3xl border border-zinc-200 overflow-hidden">
        <div class="px-6 pt-10 pb-2 text-center md:px-10">
          <h2 class="font-Poppins text-3xl font-extrabold text-zinc-950 md:text-4xl">
            Shop by Collection
          </h2>
          <p class="mt-1 text-sm text-zinc-500">
            Curated looks for every occasion
          </p>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-3 md:p-8 md:gap-6">
          <div
            v-for="(style, index) in collectionItems" :key="style.id"
            class="group relative cursor-pointer overflow-hidden rounded-2xl bg-zinc-100 transition-all duration-500 hover:shadow-2xl hover:shadow-zinc-900/15"
            :class="getCollectionSpanClass(index) ? 'md:col-span-2 min-h-[220px] md:min-h-[300px]' : 'min-h-[220px] md:min-h-[300px]'"
            @click="viewCollection(String(style.slug || style.id))"
          >
            <NuxtImg
              :src="resolveVisualImage(style.image_url, `/img/dress_styles/style${(index % 4) + 1}.png`)"
              :alt="style.name || 'Collection'"
              class="h-full w-full object-cover transition-all duration-700 group-hover:scale-110"
              format="webp" densities="x1"
              loading="lazy"
            />
            <!-- Dark gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent transition-all duration-500 group-hover:from-black/90" />
            <!-- Content -->
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8">
              <h3 class="text-xl font-bold text-white md:text-2xl transition-all duration-300 group-hover:translate-y-[-4px]">
                {{ style.name }}
              </h3>
              <div class="mt-1 flex items-center gap-3 text-sm text-white/60">
                <span>Explore collection</span>
              </div>
              <!-- Shop Now button (hidden by default, shows on hover) -->
              <div class="mt-3 translate-y-4 opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 text-xs font-semibold text-zinc-900 shadow-lg hover:bg-zinc-100 transition-colors">
                  Shop Now
                  <Icon name="mdi:arrow-right" class="text-sm" />
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 8. OUR HAPPY CUSTOMERS (Enhanced)           -->
    <!-- ════════════════════════════════════════════ -->
    <section class="section-animate px-5 desktop:container pb-12 transition-all duration-700 delay-500">
      <div class="text-center mb-8">
        <h2 class="font-Poppins text-3xl font-extrabold text-zinc-950 md:text-4xl">
          Our Happy Customers
        </h2>
        <p class="mt-1 text-sm text-zinc-500">
          Hear from people who love shopping with us
        </p>
      </div>

      <div v-if="isLoadingCustomerReview">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div v-for="item in 4" :key="item" class="h-[200px] animate-pulse rounded-2xl bg-slate-100" />
        </div>
      </div>
      <div v-else-if="customers_review.length !== 0">
        <ClientOnly>
          <Swiper
            :modules="[SwiperAutoplay]"
            :slides-per-view="1"
            :space-between="20"
            :breakpoints="{
              640: { slidesPerView: 1.5, spaceBetween: 20 },
              1024: { slidesPerView: 3, spaceBetween: 24 },
              1440: { slidesPerView: 4, spaceBetween: 24 },
            }"
            :autoplay="{ delay: 4000, disableOnInteraction: true, pauseOnMouseEnter: true }"
            class="pb-2"
          >
            <SwiperSlide v-for="(slide) in customers_review" :key="slide.id">
              <div class="flex h-full flex-col rounded-2xl border border-zinc-100 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                <!-- Star Rating -->
                <div class="flex items-center gap-0.5">
                  <Icon
                    v-for="star in 5" :key="star"
                    :name="star <= slide.rating ? 'mdi:star' : 'mdi:star-outline'"
                    class="text-base"
                    :class="star <= slide.rating ? 'text-amber-400' : 'text-zinc-200'"
                  />
                </div>
                <!-- Customer Info -->
                <div class="mt-3 flex items-center gap-2.5">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-800 text-xs font-bold text-white uppercase">
                    {{ (slide.customer?.full_name || '?').charAt(0) }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-zinc-900 flex items-center gap-1">
                      {{ slide.customer?.full_name || 'Anonymous' }}
                      <Icon name="mdi:check-circle" class="text-sm text-emerald-500" />
                    </p>
                    <p class="text-[11px] text-zinc-400">Verified buyer</p>
                  </div>
                </div>
                <!-- Review Quote -->
                <div class="mt-3 flex-1">
                  <p class="text-sm leading-relaxed text-zinc-600 italic">
                    &ldquo;{{ slide.comment || 'Great products and fast shipping!' }}&rdquo;
                  </p>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </ClientOnly>
      </div>
      <div v-else class="flex justify-center text-sm text-zinc-400">
        Not available feedback from customers
      </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- 9. NEWSLETTER (already in Footer)           -->
    <!-- ════════════════════════════════════════════ -->
  </main>
</template>

<style scoped>
/* Entrance animation — subtle rise on scroll into view */
.section-animate {
  opacity: 0;
  transform: translateY(24px);
}
.section-animate.animate-in {
  opacity: 1;
  transform: translateY(0);
}

@media (prefers-reduced-motion: reduce) {
  .section-animate {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Hero Carousel - el-carousel overrides */
.hero-carousel-wrapper :deep(.el-carousel__container) {
  border-radius: 32px;
  overflow: hidden;
}
.hero-carousel-wrapper :deep(.el-carousel__item) {
  overflow: hidden;
}
.hero-carousel-wrapper :deep(.el-carousel__indicators) {
  bottom: 20px;
}
.hero-carousel-wrapper :deep(.el-carousel__indicator .el-carousel__button) {
  width: 24px;
  height: 3px;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
.hero-carousel-wrapper :deep(.el-carousel__indicator.is-active .el-carousel__button) {
  width: 36px;
  background: #0f172a;
}
.hero-carousel-wrapper :deep(.el-carousel__arrow) {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(8px);
  border: none;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
  font-size: 18px;
}
.hero-carousel-wrapper :deep(.el-carousel__arrow:hover) {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.08);
}
</style>
