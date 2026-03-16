<template>
  <div class="px-5 desktop:container">
    <BaseBreadcrumb :icon="ArrowRight">
      <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
      <el-breadcrumb-item>Favorited</el-breadcrumb-item>
    </BaseBreadcrumb>

    <div class="flex flex-col desktop:flex-row gap-3">
      <div class="w-full desktop:w-[65%] border border-gray rounded-2xl">
        <div v-if="isEmpty" class="p-8 text-center text-gray-500">No favorites yet.</div>
        <div v-else class="divide-y divide-gray-100">
          <div v-for="item in items" :key="`${item.id}-${item.size || ''}-${item.color || ''}`"
            class="p-4 flex gap-4 items-start">
            <NuxtImg :src="item.image || '/img/products/default_image.webp'"
              class="max-w-[80px] sm:max-w-[120px] md:max-w-[140px] lg:max-w-[150px] h-auto rounded-2xl" />
            <div class="flex-1">
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="text-md font-semibold text-gray-900">{{ item.name }}</h3>
                  <p class="text-xs text-gray-500">Size: {{ item.size || 'N/A' }}</p>
                  <p class="text-xs text-gray-500">Color: {{ item.color || 'N/A' }}</p>
                </div>
                <button class="text-red text-sm" @click="removeFavorite(item)">Remove</button>
              </div>
              <div class="mt-3 flex items-center justify-between">
                <span class="text-lg font-semibold">${{ Number(item.price).toFixed(2) }}</span>
                <button class="bg-black rounded-3xl text-white px-4 py-2 text-sm" @click="addSingleToCart(item)">
                  Add to Cart
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-5 border border-gray rounded-2xl w-full desktop:w-[35%] h-[80%] p-5">
        <div>
          <h2 class="text-xl font-semibold">Favorites Summary</h2>
          <p class="text-sm text-gray-500">{{ totalItems }} item(s)</p>
        </div>

        <div class="flex flex-col gap-3 mt-4 w-full">
          <button class="bg-black rounded-3xl text-white p-3 w-full" :disabled="isEmpty" @click="addAllToCart">
            Add All to Cart
          </button>
          <button class="border border-gray rounded-3xl text-black p-3 w-full" :disabled="isEmpty"
            @click="clearFavorites">
            Clear Favorites
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'
import { storeToRefs } from 'pinia'
import { useFavoritesStore } from '~/stores/favoritesStore'

const favoritesStore = useFavoritesStore()
const { items, totalItems, isEmpty } = storeToRefs(favoritesStore)

const removeFavorite = (item: any) => {
  favoritesStore.removeFavorite(item.id, item.size, item.color)
}

const addSingleToCart = (item: any) => {
  favoritesStore.addToCart([{ id: item.id, quantity: 1, size: item.size, color: item.color }])
}

const addAllToCart = () => {
  favoritesStore.addToCart(
    items.value.map((item: any) => ({
      id: item.id,
      quantity: 1,
      size: item.size,
      color: item.color,
    }))
  )
}

const clearFavorites = () => {
  favoritesStore.clearFavorites()
}
</script>

<style scoped></style>
