import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useCartStore } from './cartStore'

interface FavoriteItem {
  id: string | number
  name: string
  price: number
  image: string
  size?: string
  color?: string
}

export const useFavoritesStore = defineStore('favorites', () => {
  const items = ref<FavoriteItem[]>([])

  // Computed
  const totalItems = computed(() => items.value.length)

  const isEmpty = computed(() => items.value.length === 0)

  const isFavorite = (id: string | number, size?: string, color?: string) => {
    return items.value.some(
      item =>
        item.id === id &&
        item.size === size &&
        item.color === color
    )
  }

  // Actions
  const addFavorite = (product: FavoriteItem) => {
    const exists = items.value.find(
      item =>
        item.id === product.id &&
        item.size === product.size &&
        item.color === product.color
    )

    if (!exists) {
      items.value.push(product)
    }
  }

  const removeFavorite = (id: string | number, size?: string, color?: string) => {
    const index = items.value.findIndex(
      item =>
        item.id === id &&
        item.size === size &&
        item.color === color
    )
    if (index > -1) {
      items.value.splice(index, 1)
    }
  }

  const toggleFavorite = (product: FavoriteItem) => {
    if (isFavorite(product.id, product.size, product.color)) {
      removeFavorite(product.id, product.size, product.color)
    } else {
      addFavorite(product)
    }
  }

  const addToCart = (itemsToAdd: Array<{ id: string | number; quantity?: number; size?: string; color?: string }>) => {
    const cartStore = useCartStore()
    const itemsArray = Array.isArray(itemsToAdd) ? itemsToAdd : [itemsToAdd]

    itemsArray.forEach(({ id, quantity = 1, size, color }) => {
      const favoriteItem = items.value.find(
        item =>
          item.id === id &&
          item.size === size &&
          item.color === color
      )

      if (favoriteItem) {
        cartStore.addItem({
          id: favoriteItem.id,
          name: favoriteItem.name,
          price: favoriteItem.price,
          image: favoriteItem.image,
          size: favoriteItem.size,
          color: favoriteItem.color,
        }, quantity)
      }
    })
  }

  const clearFavorites = () => {
    items.value = []
  }

  const getFavorite = (id: string | number, size?: string, color?: string) => {
    return items.value.find(
      item =>
        item.id === id &&
        item.size === size &&
        item.color === color
    )
  }

  return {
    items,
    totalItems,
    isEmpty,
    addFavorite,
    removeFavorite,
    toggleFavorite,
    addToCart,
    clearFavorites,
    getFavorite,
    isFavorite,
  }
})