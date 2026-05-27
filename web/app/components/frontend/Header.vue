<template>
  <header class="px-4 sm:px-5 py-4 border-b relative">
    <nav class="flex justify-between items-center desktop:container">
      <!-- Left Side: Logo & Menu -->
      <div class="flex items-center gap-3 sm:gap-4">
        <Icon name="ic:round-menu-open" class="text-2xl sm:text-[30px] block desktop:hidden cursor-pointer"
          @click="toggleMenu" />
        <NuxtLink to="/" class="flex items-center">
          <span class="text-2xl sm:text-[28p. x] desktop:text-3xl font-bold">SHOP.CO</span>
        </NuxtLink>
        <!-- Desktop Navigation -->
        <ul class="hidden desktop:flex items-center gap-8 ml-8">
          <!-- Shop Menu -->
          <li class="relative group py-2" @mouseenter="isDropdownOpen = true">
            <a href="#" class="flex items-center gap-1 text-gray-700 hover:text-black font-medium relative">
              Shop
              <Icon name="mdi:keyboard-arrow-down"
                class="text-lg transition-transform group-hover:rotate-180 duration-300" />
              <!-- Hover underline -->
              <span
                class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </a>
          </li>

          <!-- New Arrivals Link -->
          <li class="relative group py-2">
            <NuxtLink to="/" class="text-gray-700 hover:text-black font-medium relative inline-block">
              <span>New Arrivals</span>
              <span
                class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </NuxtLink>
          </li>

          <!-- Brands Link -->
          <li class="relative group py-2">
            <NuxtLink to="/" class="text-gray-700 hover:text-black font-medium relative inline-block">
              <span>Brands</span>
              <span
                class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </NuxtLink>
          </li>
        </ul>
      </div>

      <!-- Right Side: Search & Icons -->
      <div class="flex items-center gap-3 sm:gap-4">
        <!-- Desktop Search -->
        <div class="hidden lg:flex items-center w-64 xl:w-80 desktop-search-root">
          <div class="relative flex-1">
            <Icon name="mdi:search" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg" />
            <input ref="desktopSearchTriggerInput" v-model="desktopSearchKeyword" type="text"
              class="w-full rounded-element bg-gray-100 pl-12 pr-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black focus:ring-opacity-20 transition-all"
              placeholder="Search for products..." @focus="openDesktopSearch" @keyup.enter="submitDesktopSearch" />
          </div>
        </div>

        <!-- Mobile Search Icon -->
        <Icon name="mdi:search" class="text-xl sm:text-[25px] block desktop:hidden cursor-pointer"
          @click="toggleSearch" />

        <!-- Action Icons -->
        <div class="flex items-center gap-3 sm:gap-4 desktop:gap-6 pl-2 sm:pl-4">
          <!-- Wishlist -->
          <NuxtLink to="/frontend/favorites" class="relative">
            <Icon name="mdi:heart"
              class="text-xl sm:text-[25px] desktop:text-2xl hover:text-red-500 transition-colors" />
            <span v-if="favoriteCount > 0"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
              {{ favoriteCount }}
            </span>
          </NuxtLink>

          <!-- Cart -->
          <NuxtLink to="/frontend/cart" class="relative">
            <Icon name="mdi:cart" class="text-xl sm:text-[25px] desktop:text-2xl hover:text-black transition-colors" />
            <span v-if="cartCount > 0"
              class="absolute -top-2 -right-2 bg-red-500 text-black text-xs w-5 h-5 flex items-center justify-center rounded-full">
              {{ cartCount }}
            </span>
          </NuxtLink>
          <!-- Account -->
          <div class="relative account-menu-root">
            <NuxtLink v-if="!isAuthenticated" to="/auth/login"
              class="group flex h-10 w-10 items-center justify-center rounded-full border border-transparent bg-gray-100 text-gray-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-200 hover:bg-white hover:shadow-md">
              <Icon name="mdi:user" class="text-xl transition-colors group-hover:text-black" />
            </NuxtLink>

            <button v-else type="button"
              class="group flex items-center gap-2 rounded-full border border-gray-200 bg-white px-2 py-1 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
              @click.stop="toggleAccountMenu">
              <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full">
                <NuxtImg v-if="userAvatarUrl" format="webp" :src="userAvatarUrl" alt="Account avatar"
                  class="h-[32px] w-[32px] object-cover" />
                <div v-else
                  class="flex h-full w-full items-center justify-center rounded-full bg-gradient-to-br from-black via-gray-800 to-gray-600 text-xs font-semibold text-white">
                  {{ userInitialsHelper(userDisplayName) }}
                </div>
              </div>
              <span
                class="max-w-[120px] truncate text-sm font-medium text-gray-700 group-hover:text-black hidden  md:block">
                {{ userDisplayName }}
              </span>
              <Icon name="mdi:chevron-down"
                class="hidden  md:block text-lg text-gray-500 transition-transform duration-200 group-hover:text-black"
                :class="accountMenuOpen ? 'rotate-180' : ''" />
            </button>

            <Transition enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-2 scale-95">
              <div v-if="isAuthenticated && accountMenuOpen"
                class="absolute right-0 mt-3 w-72 origin-top-right rounded-2xl border border-gray-200 bg-white/90 p-3 shadow-xl z-50">
                <div class="mb-3 rounded-xl bg-surface-2 px-3 py-2 text-black">
                  <p class="text-xs text-black/80">Signed in as</p>
                  <p class="truncate text-sm font-semibold">{{ userDisplayName }}</p>
                </div>
                <!-- Profile Modal -->
                <button type="button" :class="accountActionClass" @click="openProfileModal">
                  <span class="flex items-center gap-2">
                    <Icon name="mdi:account-circle-outline" class="text-base" />
                    Profile
                  </span>
                  <Icon name="mdi:chevron-right" class="text-base opacity-60" />
                </button>

                <!-- Order_history -->
                <button type="button" :class="accountActionClass" @click="openOrderHistoryModal">
                  <span class="flex items-center gap-2">
                    <Icon name="mdi:clipboard-text-clock-outline" class="text-base" />
                    Order History
                  </span>
                  <Icon name="mdi:chevron-right" class="text-base opacity-60" />
                </button>

                <button type="button" :class="accountActionClass" :disabled="telegramLinked || connectingTelegram"
                  @click="connectTelegram">
                  <span class="flex items-center gap-2">
                    <Icon name="mdi:telegram" class="text-base" />
                    {{ telegramLinked ? 'Telegram Linked' : (connectingTelegram ? 'Connecting...' : 'Connect Telegram')
                    }}
                  </span>
                  <Icon v-if="!telegramLinked" name="mdi:chevron-right" class="text-base opacity-60" />
                  <Icon v-else name="mdi:check-circle" class="text-base text-emerald-600" />
                </button>

                <button type="button" :class="accountActionClass" @click="logout">
                  <span class="flex items-center gap-2">
                    <Icon name="mdi:logout-variant" class="text-base" />
                    Logout
                  </span>
                  <Icon name="mdi:chevron-right" class="text-base opacity-60" />
                </button>

                <p v-if="telegramStatusMessage" class="mt-2 text-xs text-gray-600">
                  {{ telegramStatusMessage }}
                </p>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </nav>

    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-3">
      <div v-if="showDesktopSearch"
        class="desktop-search-root hidden lg:block absolute left-0 right-0 top-full bg-white border-t border-gray-200 shadow-lg z-50">
        <div class="desktop:container px-5 py-5">
          <form class="flex items-center gap-3" @submit.prevent="submitDesktopSearch">
            <div class="relative flex-1">
              <Icon name="mdi:search"
                class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg" />
              <input ref="desktopSearchPanelInput" v-model="desktopSearchKeyword" type="text"
                class="w-full rounded-2xl border border-gray-200 bg-gray-50 pl-12 pr-4 py-3 text-sm outline-none focus:border-black focus:ring-2 focus:ring-black focus:ring-opacity-20 transition-all"
                placeholder="Type and press Enter to search products..." />
            </div>
            <button type="submit"
              class="rounded-2xl bg-black px-5 py-3 text-sm font-medium text-white hover:bg-gray-800 transition-colors whitespace-nowrap">
              Search all products
            </button>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Dropdown Menu Container (Positioned under header) -->
    <div
      class="absolute left-0 right-0 top-full bg-white border-t border-gray-200 shadow-lg z-50 overflow-hidden transition-all duration-300 ease-in-out"
      :class="isDropdownOpen ? 'max-h-[500px] opacity-100 visible' : 'max-h-0 opacity-0 invisible'"
      @mouseenter="isDropdownOpen = true" @mouseleave="isDropdownOpen = false">

      <!-- Dropdown Content -->
      <div class="desktop:container px-4 sm:px-5 py-8 grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- Shop by Category -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Category</h3>
          <ul class="space-y-3">
            <li v-for="category in categories" :key="category.id">
              <NuxtLink :to="`/frontend/categories/${category.slug || category.id}`"
                class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                {{ category.name }}
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Shop by Collection -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Collection</h3>
          <ul class="space-y-3">
            <li v-for="collection in collectionItems" :key="`desktop-collection-${collection.id}`">
              <NuxtLink :to="{ path: '/frontend/categories', query: { collection: collection.slug } }"
                class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                {{ collection.name }}
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Shop by Price -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Price</h3>
          <ul class="space-y-3">
            <li v-for="range in priceRanges" :key="`desktop-price-${range.slug}`">
              <NuxtLink :to="{ path: '/frontend/categories', query: range.query }"
                class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                {{ range.label }}
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </NuxtLink>
            </li>
          </ul>
        </div>

      </div>

      <!-- Featured Section -->
      <div class="bg-gray-50 border-t border-gray-200 py-6">
        <div class="desktop:container px-4 sm:px-5">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
              <h4 class="text-lg font-semibold text-gray-900">Featured Items</h4>
              <p class="text-gray-600 text-sm">Check out our best selling products</p>
            </div>
            <div class="flex gap-4">
              <a href="#"
                class="px-6 py-2 bg-black text-white rounded-full hover:bg-gray-800 transition-colors text-sm">
                View All Featured
              </a>
              <a href="#"
                class="px-6 py-2 border border-gray-300 rounded-full hover:border-black hover:text-black transition-colors text-sm">
                Sale Items
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Menu (Hidden by default) -->
    <div
      class="fixed inset-0 top-[73px] bg-white z-40 transform transition-transform duration-300 ease-in-out desktop:hidden"
      :class="isMenuOpen ? 'translate-x-0' : 'translate-x-full'" v-if="isMenuOpen" @click.self="toggleMenu">
      <div class="p-6 space-y-6 h-full overflow-y-auto">
        <div class="border-b pb-4">
          <div class="flex items-center justify-between cursor-pointer" @click="toggleShopMenu">
            <span class="font-medium text-lg">Shop</span>
            <Icon name="mdi:chevron-down" class="text-xl transition-transform duration-300"
              :class="isShopMenuOpen ? 'rotate-180' : ''" />
          </div>

          <div v-if="isShopMenuOpen" class="mt-4 space">
            <!-- Shop by Category -->

            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Category</h3>
              <ul class="space-y-3">
                <li v-for="category in categories" :key="`mobile-${category.id}`">
                  <NuxtLink :to="`/frontend/categories/${category.slug || category.id}`"
                    class="text-gray-600 hover:text-black transition-colors flex items-center group"
                    @click="toggleMenu">
                    <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                    {{ category.name }}
                    <Icon name="mdi:chevron-right"
                      class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
                  </NuxtLink>
                </li>
              </ul>
            </div>


            <!-- Shop by Collection -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Collection</h3>
              <ul class="space-y-3">
                <li v-for="collection in collectionItems" :key="`mobile-collection-${collection.id}`">
                  <NuxtLink :to="{ path: '/frontend/categories', query: { collection: collection.slug } }"
                    class="text-gray-600 hover:text-black transition-colors flex items-center group"
                    @click="toggleMenu">
                    <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                    {{ collection.name }}
                    <Icon name="mdi:chevron-right"
                      class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
                  </NuxtLink>
                </li>
              </ul>
            </div>

            <!-- Shop by Price -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Price</h3>
              <ul class="space-y-3">
                <li v-for="range in priceRanges" :key="`mobile-price-${range.slug}`">
                  <NuxtLink :to="{ path: '/frontend/categories', query: range.query }"
                    class="text-gray-600 hover:text-black transition-colors flex items-center group"
                    @click="toggleMenu">
                    <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                    {{ range.label }}
                    <Icon name="mdi:chevron-right"
                      class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
                  </NuxtLink>
                </li>
              </ul>
            </div>
          </div>
          <!-- <div v-if="isShopMenuOpen" class="mt-4 pl-4 space-y-3">
            <a href="#" class="block text-gray-600 hover:text-black py-2" @click="toggleMenu">Men's Clothing</a>
            <a href="#" class="block text-gray-600 hover:text-black py-2" @click="toggleMenu">Women's Clothing</a>
            <a href="#" class="block text-gray-600 hover:text-black py-2" @click="toggleMenu">Accessories</a>
            <a href="#" class="block text-gray-600 hover:text-black py-2" @click="toggleMenu">Shoes</a>
          </div> -->
        </div>
        <div class="border-b pb-4">
          <NuxtLink to="/" class="block font-medium text-lg hover:text-black py-2" @click="toggleMenu"><span>New
              Arrivals</span>
          </NuxtLink>
        </div>
        <div class="pb-4">
          <NuxtLink to="/" class="block font-medium text-lg hover:text-black py-2" @click="toggleMenu">
            <span>Brands</span>
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Mobile Search Bar -->
    <div v-if="showMobileSearch" class="fixed inset-0 top-[73px] bg-white z-50 p-4 desktop:hidden">
      <div class="relative">
        <Icon name="mdi:arrow-left"
          class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-xl cursor-pointer"
          @click="toggleSearch" />
        <input type="text" ref="mobileSearchInput"
          class="w-full rounded-full bg-gray-100 pl-12 pr-4 py-3 text-sm outline-none"
          placeholder="Search for products..." @keyup.enter="handleMobileSearch" />
        <Icon name="mdi:search" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg" />
      </div>
    </div>

    <ProfileDialog v-model="profileDialogOpen" />
    <OrderHistoryDialog v-model="orderHistoryDialogOpen" />

    <!-- Backdrop for mobile menu -->
    <div v-if="isMenuOpen || showMobileSearch" class="fixed inset-0 bg-black bg-opacity-50 z-30 desktop:hidden"
      @click="closeAll"></div>


  </header>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useAuthStore } from '~/stores/authStore'
import { useFavoritesStore } from '~/stores/favoritesStore'
import { useCartStore } from '~/stores/cartStore'
import ProfileDialog from './Modal/ProfileDialog.vue'
import OrderHistoryDialog from './Modal/OrderHistoryDialog.vue'

const authStore = useAuthStore()
const { isAuthenticated, accessToken, userProfile } = storeToRefs(authStore)
const favoritesStore = useFavoritesStore()
const { totalItems: favoriteCount } = storeToRefs(favoritesStore)
const cartStore = useCartStore()
const { totalItems } = storeToRefs(cartStore)
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

// State variables
const isMenuOpen = ref(false) // Mobile menu visibility
const isDropdownOpen = ref(false) // Desktop dropdown visibility
const isShopMenuOpen = ref(false) // Mobile shop submenu
const showMobileSearch = ref(false) // Mobile search visibility
const mobileSearchInput = ref<HTMLInputElement | null>(null)
const showDesktopSearch = ref(false)
const desktopSearchKeyword = ref('')
const desktopSearchTriggerInput = ref<HTMLInputElement | null>(null)
const desktopSearchPanelInput = ref<HTMLInputElement | null>(null)
const accountMenuOpen = ref(false)
const connectingTelegram = ref(false)
const telegramLinked = ref(false)
const telegramStatusMessage = ref('')
const profileDialogOpen = ref(false)
const orderHistoryDialogOpen = ref(false)
const router = useRouter()
type CategoryOption = { id: number | string; name: string; slug?: string }
type DressTypeOption = { id: number | string; name: string; slug?: string }
const cartCount = computed(() => totalItems.value || 0)
const categories = ref<CategoryOption[]>([])
const collectionItems = ref<DressTypeOption[]>([])
const priceRanges = [
  { slug: 'under-50', label: 'Under $50', query: { price_min: 0, price_max: 50 } },
  { slug: '50-100', label: '$50 - $100', query: { price_min: 50, price_max: 100 } },
  { slug: '100-200', label: '$100 - $200', query: { price_min: 100, price_max: 200 } },
  { slug: 'over-200', label: 'Over $200', query: { price_min: 200 } },
]

const userDisplayName = computed(() => {
  const profile = userProfile.value || {}
  return (
    (profile.full_name as string | undefined) ||
    (profile.name as string | undefined) ||
    (profile.email as string | undefined) ||
    'Account'
  )
})


const accountActionClass = computed(() => [
  'group flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-medium transition-all duration-200',
  'bg-surface text-gray-700 hover:bg-black hover:text-white',
  'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 disabled:hover:bg-gray-100 disabled:hover:text-gray-400',
])
const userAvatarUrl = computed(() => {
  const profile = (userProfile.value || {}) as Record<string, any>
  return String(profile.avatar_url || '')
})

const getAuthHeaders = () => {
  return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined
}

// Toggle mobile menu
const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
  if (!isMenuOpen.value) {
    showMobileSearch.value = false
  }
}

// Toggle mobile shop submenu
const toggleShopMenu = () => {
  isShopMenuOpen.value = !isShopMenuOpen.value
}

// Toggle mobile search
const toggleSearch = () => {
  showMobileSearch.value = !showMobileSearch.value
  if (showMobileSearch.value) {
    isMenuOpen.value = false
    // Focus the search input when it appears
    nextTick(() => {
      if (mobileSearchInput.value) {
        mobileSearchInput.value.focus()
      }
    })
  }
}

const openDesktopSearch = () => {
  showDesktopSearch.value = true
  isDropdownOpen.value = false
  nextTick(() => {
    desktopSearchPanelInput.value?.focus()
  })
}

const closeDesktopSearch = () => {
  showDesktopSearch.value = false
}

const submitDesktopSearch = async () => {
  const keyword = desktopSearchKeyword.value.trim()
  if (!keyword) {
    return
  }
  await router.push({
    path: '/frontend/categories',
    query: { search_txt: keyword },
  })
  closeDesktopSearch()
}

// Handle mobile search
const handleMobileSearch = () => {
  if (mobileSearchInput.value) {
    const searchTerm = mobileSearchInput.value.value
    if (searchTerm.trim()) {
      // Navigate to search results or perform search
      console.log('Searching for:', searchTerm)
      // You can add your search logic here
      toggleSearch()
    }
  }
}

// Close all mobile overlays
const closeAll = () => {
  isMenuOpen.value = false
  showMobileSearch.value = false
  showDesktopSearch.value = false
  accountMenuOpen.value = false
}

// Close dropdown when clicking outside (optional)
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.group') && !target.closest('.absolute.left-0.right-0.top-full')) {
    isDropdownOpen.value = false
  }

  if (!target.closest('.account-menu-root')) {
    accountMenuOpen.value = false
  }

  if (!target.closest('.desktop-search-root')) {
    closeDesktopSearch()
  }
}

// Close dropdown on ESC key
const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    isDropdownOpen.value = false
    closeDesktopSearch()
    closeAll()
  }
}

// Close dropdown when scrolling
const handleScroll = () => {
  isDropdownOpen.value = false
  closeDesktopSearch()
  accountMenuOpen.value = false
}

const toggleAccountMenu = () => {
  accountMenuOpen.value = !accountMenuOpen.value
}

const openProfileModal = () => {
  accountMenuOpen.value = false
  profileDialogOpen.value = true
}

const openOrderHistoryModal = () => {
  accountMenuOpen.value = false
  orderHistoryDialogOpen.value = true
}

const hydrateProfile = async () => {
  if (!accessToken.value && !isAuthenticated.value) {
    return
  }

  try {
    const headers = accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined

    const response: any = await $fetch(`${apiBase}/profile`, {
      method: 'GET',
      credentials: 'include',
      headers
    })
    authStore.setAuthenticated(true)
    authStore.setUserProfile(response?.data || null)
    telegramLinked.value = !(
      response?.data?.telegram_user_id === undefined &&
      response?.data?.telegram_chat_id === undefined
    )
  } catch (err: any) {
    const statusCode = err?.statusCode ?? err?.status
    if (statusCode === 401 || statusCode === 403) {
      authStore.resetAuth()
      telegramLinked.value = false
    }
  }
}

const fetchCategories = async () => {
  try {
    const [categoryResponse, filterResponse]: any = await Promise.all([
      $fetch(`${apiBase}/categories`, {
        method: 'GET',
        credentials: 'include'
      }),
      $fetch(`${apiBase}/products/filters`, {
        method: 'GET',
        credentials: 'include'
      }),
    ])
    categories.value = categoryResponse?.data || []
    collectionItems.value = filterResponse?.data?.collections || []

    if (accessToken.value || isAuthenticated.value) {
      const cartResponse: any = await $fetch(`${apiBase}/cart`, {
        method: 'GET',
        credentials: 'include',
        headers: getAuthHeaders(),
      })
      cartStore.setFromApi(cartResponse?.data || {})
    } else {
      cartStore.clearCart()
    }
  }
  catch (err: any) {
    console.error('Failed to fetch header menu data:', err)
  }
}

const connectTelegram = async () => {
  telegramStatusMessage.value = ''
  connectingTelegram.value = true
  try {
    const response: any = await $fetch(`${apiBase}/telegram/connect-link`, {
      method: 'POST',
      credentials: 'include'
    })

    const deepLink = response?.data?.deep_link
    if (!deepLink) {
      telegramStatusMessage.value = 'Unable to generate Telegram link.'
      return
    }

    window.open(deepLink, '_blank', 'noopener,noreferrer')
    telegramStatusMessage.value = 'Telegram opened. Tap Start in bot to complete linking.'
  } catch (err: any) {
    telegramStatusMessage.value = err?.data?.message || 'Failed to connect Telegram.'
  } finally {
    connectingTelegram.value = false
  }
}

const logout = async () => {
  authStore.resetAuth()
  accountMenuOpen.value = false
  telegramLinked.value = false
  telegramStatusMessage.value = ''
  await router.push('/auth/login')
}

let headerListenersBound = false

// Set up event listeners
onMounted(() => {
  if (!import.meta.client) {
    return
  }
  hydrateProfile()
  fetchCategories()
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleClickOutside)
  headerListenersBound = true
})

// Clean up event listeners
onBeforeUnmount(() => {
  if (!headerListenersBound || !import.meta.client) {
    return
  }
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
  headerListenersBound = false
})

watch(accessToken, (token) => {
  if (token) {
    hydrateProfile()
  }
})
</script>

<style scoped>
/* Custom scrollbar for mobile menu */
.fixed.inset-0.top-\[73px\].bg-white.z-40 {
  scrollbar-width: thin;
  scrollbar-color: #d1d5db transparent;
}

.fixed.inset-0.top-\[73px\].bg-white.z-40::-webkit-scrollbar {
  width: 6px;
}

.fixed.inset-0.top-\[73px\].bg-white.z-40::-webkit-scrollbar-track {
  background: transparent;
}

.fixed.inset-0.top-\[73px\].bg-white.z-40::-webkit-scrollbar-thumb {
  background-color: #d1d5db;
  border-radius: 3px;
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Ensure dropdown appears above content */
.z-50 {
  z-index: 50;
}

.z-40 {
  z-index: 40;
}

.z-30 {
  z-index: 30;
}
</style>
