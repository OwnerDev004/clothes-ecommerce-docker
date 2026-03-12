import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface CartItem {
  id: string | number
  name: string
  price: number
  image: string
  quantity: number
  size?: string
  color?: string
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])

  // Computed
  const totalItems = computed(() =>{
    return items.value.length;
  }
  )
  const totalQtyItems = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const totalPrice = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  const isEmpty = computed(() => items.value.length === 0)

  // Actions
  const addItem = (product: Omit<CartItem, 'quantity'>, quantity: number = 1) => {
    const existingItem = items.value.find(
      item =>
        item.id === product.id &&
        item.size === product.size &&
        item.color === product.color
    )

    if (existingItem) {
      existingItem.quantity += quantity
    } else {
      items.value.push({
        ...product,
        quantity,
      })
    }
  }

  const removeItem = (id: string | number, size?: string, color?: string) => {
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

  const updateQuantity = (id: string | number, quantity: number, size?: string, color?: string) => {
    const item = items.value.find(
      item =>
        item.id === id &&
        item.size === size &&
        item.color === color
    )
    if (item) {
      if (quantity <= 0) {
        removeItem(id, size, color)
      } else {
        item.quantity = quantity
      }
    }
  }

  const clearCart = () => {
    items.value = []
  }

  const setFromApi = (payload: any) => {
    const rows = Array.isArray(payload?.items) ? payload.items : []
    items.value = rows.map((row: any) => ({
      id: row?.variant_id ?? row?.id,
      name: String(row?.product_name || row?.name || 'Product'),
      price: Number(row?.unit_price || row?.price || 0),
      image: String(row?.product_image || row?.image || ''),
      quantity: Number(row?.quantity || 0),
      size: row?.size || undefined,
      color: row?.color || undefined,
    }))
  }

  const getItem = (id: string | number, size?: string, color?: string) => {
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
    totalQtyItems,
    totalPrice,
    isEmpty,
    addItem,
    removeItem,
    updateQuantity,
    clearCart,
    setFromApi,
    getItem,
  }
}, {
  persist: true,
})
