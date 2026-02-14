<template>
  <header class="px-4 sm:px-5 py-4 border-b relative">
    <nav class="flex justify-between items-center desktop:container">
      <!-- Left Side: Logo & Menu -->
      <div class="flex items-center gap-3 sm:gap-4">
        <Icon 
          name="ic:round-menu-open" 
          class="text-2xl sm:text-[30px] block desktop:hidden cursor-pointer" 
          @click="toggleMenu"
        />
        <NuxtLink to="/" class="flex items-center">
          <span class="text-2xl sm:text-[28px] desktop:text-3xl font-bold">SHOP.CO</span>
        </NuxtLink>
        
        <!-- Desktop Navigation -->
        <ul class="hidden desktop:flex items-center gap-8 ml-8">
          <!-- Shop Menu -->
          <li class="relative group py-2" @mouseenter="isDropdownOpen = true">
            <a 
              href="#" 
              class="flex items-center gap-1 text-gray-700 hover:text-black font-medium relative"
            >
              Shop 
              <Icon name="mdi:keyboard-arrow-down" class="text-lg transition-transform group-hover:rotate-180 duration-300" />
              <!-- Hover underline -->
              <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </a>
          </li>
          
          <!-- New Arrivals Link -->
          <li class="relative group py-2">
            <NuxtLink 
              to="/" 
              class="text-gray-700 hover:text-black font-medium relative inline-block"
            >
              New Arrivals
              <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </NuxtLink>
          </li>
          
          <!-- Brands Link -->
          <li class="relative group py-2">
            <NuxtLink 
              to="/" 
              class="text-gray-700 hover:text-black font-medium relative inline-block"
            >
              Brands
              <span class="absolute left-0 bottom-0 w-0 h-0.5 bg-black group-hover:w-full transition-all duration-300"></span>
            </NuxtLink>
          </li>
        </ul>
      </div>
      
      <!-- Right Side: Search & Icons -->
      <div class="flex items-center gap-3 sm:gap-4">
        <!-- Desktop Search -->
        <div class="hidden lg:flex items-center w-64 xl:w-80">
          <div class="relative flex-1">
            <Icon 
              name="mdi:search" 
              class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg" 
            />
            <input
              type="text"
              class="w-full rounded-full bg-gray-100 pl-12 pr-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black focus:ring-opacity-20 transition-all"
              placeholder="Search for products..."
            />
          </div>
        </div>
        
        <!-- Mobile Search Icon -->
        <Icon 
          name="mdi:search" 
          class="text-xl sm:text-[25px] block desktop:hidden cursor-pointer" 
          @click="toggleSearch"
        />
        
        <!-- Action Icons -->
        <div class="flex items-center gap-3 sm:gap-4 desktop:gap-6 pl-2 sm:pl-4">
          <!-- Wishlist -->
          <NuxtLink to="/frontend/favorites" class="relative">
            <Icon name="mdi:heart" class="text-xl sm:text-[25px] desktop:text-2xl hover:text-red-500 transition-colors" />
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">1</span>
          </NuxtLink>
          
          <!-- Cart -->
          <NuxtLink to="/frontend/cart" class="relative">
            <Icon name="mdi:cart" class="text-xl sm:text-[25px] desktop:text-2xl hover:text-black transition-colors" />
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">1</span>
          </NuxtLink>
          
          <!-- Account -->
          <NuxtLink to="/" class="hidden sm:block">
            <Icon name="mdi:user" class="text-xl sm:text-[25px] desktop:text-2xl hover:text-black transition-colors" />
          </NuxtLink>
        </div>
      </div>
    </nav>
    
    <!-- Dropdown Menu Container (Positioned under header) -->
    <div class="absolute left-0 right-0 top-full bg-white border-t border-gray-200 shadow-lg z-50 overflow-hidden transition-all duration-300 ease-in-out"
         :class="isDropdownOpen ? 'max-h-[500px] opacity-100 visible' : 'max-h-0 opacity-0 invisible'"
         @mouseenter="isDropdownOpen = true"
         @mouseleave="isDropdownOpen = false">
      
      <!-- Dropdown Content -->
      <div class="desktop:container px-4 sm:px-5 py-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Shop by Category -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Category</h3>
          <ul class="space-y-3">
            <li>
              <NuxtLink to="/frontend/categories/mens-clothing" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Men's Clothing
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </NuxtLink>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Women's Clothing
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Accessories
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Shoes
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
          </ul>
        </div>
        
        <!-- Shop by Collection -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Collection</h3>
          <ul class="space-y-3">
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Summer Collection
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Winter Collection
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Spring Essentials
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Fall Fashion
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
          </ul>
        </div>
        
        <!-- Shop by Price -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Price</h3>
          <ul class="space-y-3">
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Under $50
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                $50 - $100
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                $100 - $200
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Over $200
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
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
              <a href="#" class="px-6 py-2 bg-black text-white rounded-full hover:bg-gray-800 transition-colors text-sm">
                View All Featured
              </a>
              <a href="#" class="px-6 py-2 border border-gray-300 rounded-full hover:border-black hover:text-black transition-colors text-sm">
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
      :class="isMenuOpen ? 'translate-x-0' : 'translate-x-full'"
      v-if="isMenuOpen"
      @click.self="toggleMenu"
    >
      <div class="p-6 space-y-6 h-full overflow-y-auto">
        <div class="border-b pb-4">
          <div class="flex items-center justify-between cursor-pointer" @click="toggleShopMenu">
            <span class="font-medium text-lg">Shop</span>
            <Icon name="mdi:chevron-down" class="text-xl transition-transform duration-300" :class="isShopMenuOpen ? 'rotate-180' : ''" />
          </div>
          
            <div v-if="isShopMenuOpen" class="mt-4 space">
           <!-- Shop by Category -->
            
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Category</h3>
          <ul class="space-y-3">
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Men's Clothing
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Women's Clothing
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Accessories
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Shoes
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
          </ul>
        </div>

            
        <!-- Shop by Collection -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Collection</h3>
          <ul class="space-y-3">
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Summer Collection
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Winter Collection
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Spring Essentials
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Fall Fashion
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
          </ul>
        </div>
        
        <!-- Shop by Price -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Shop by Price</h3>
          <ul class="space-y-3">
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Under $50
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                $50 - $100
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                $100 - $200
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
            </li>
            <li>
              <a href="#" class="text-gray-600 hover:text-black transition-colors flex items-center group">
                <span class="w-1 h-1 bg-gray-400 rounded-full mr-3 group-hover:bg-black transition-colors"></span>
                Over $200
                <Icon name="mdi:chevron-right" class="ml-auto text-gray-400 group-hover:text-black transition-colors" />
              </a>
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
          <NuxtLink to="/" class="block font-medium text-lg hover:text-black py-2" @click="toggleMenu">New Arrivals</NuxtLink>
        </div>
        <div class="pb-4">
          <NuxtLink to="/" class="block font-medium text-lg hover:text-black py-2" @click="toggleMenu">Brands</NuxtLink>
        </div>
      </div>
    </div>
    
    <!-- Mobile Search Bar -->
    <div 
      v-if="showMobileSearch"
      class="fixed inset-0 top-[73px] bg-white z-50 p-4 desktop:hidden"
    >
      <div class="relative">
        <Icon 
          name="mdi:arrow-left" 
          class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-xl cursor-pointer" 
          @click="toggleSearch"
        />
        <input
          type="text"
          ref="mobileSearchInput"
          class="w-full rounded-full bg-gray-100 pl-12 pr-4 py-3 text-sm outline-none"
          placeholder="Search for products..."
          @keyup.enter="handleMobileSearch"
        />
        <Icon 
          name="mdi:search" 
          class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg" 
        />
      </div>
    </div>
    
    <!-- Backdrop for mobile menu -->
    <div 
      v-if="isMenuOpen || showMobileSearch"
      class="fixed inset-0 bg-black bg-opacity-50 z-30 desktop:hidden"
      @click="closeAll"
    ></div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
// State variables
const isMenuOpen = ref(false) // Mobile menu visibility
const isDropdownOpen = ref(false) // Desktop dropdown visibility
const isShopMenuOpen = ref(false) // Mobile shop submenu
const showMobileSearch = ref(false) // Mobile search visibility
const mobileSearchInput = ref<HTMLInputElement | null>(null)

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
}

// Close dropdown when clicking outside (optional)
const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.group') && !target.closest('.absolute.left-0.right-0.top-full')) {
    isDropdownOpen.value = false
  }
}

// Close dropdown on ESC key
const onKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    isDropdownOpen.value = false
    closeAll()
  }
}

// Close dropdown when scrolling
const handleScroll = () => {
  isDropdownOpen.value = false
}

// Set up event listeners
onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleClickOutside)
})

// Clean up event listeners
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
})

// Import nextTick for DOM updates
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
