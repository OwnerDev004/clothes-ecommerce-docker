<template>
  <div class="px-5 desktop:container">
    <BaseBreadcrumb :icon="ArrowRight">
      <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
      <el-breadcrumb-item>Cart</el-breadcrumb-item>
    </BaseBreadcrumb>

    <div class="flex flex-col desktop:flex-row gap-3">
      <div class="w-full desktop:w-[65%] border border-gray rounded-2xl">
        <div v-if="loadingCart" class="p-8 text-center text-gray-500">Loading cart...</div>
        <div v-else-if="!cartItems.length" class="p-8 text-center text-gray-500">Your cart is empty.</div>

        <template v-for="item in cartItems" :key="item.variant_id">
          <FrontendCartProduct :variant-id="item.variant_id" :name="item.product_name || 'Product'"
            :color="item.color || 'N/A'" :price="item.unit_price" :size="item.size || 'N/A'" :quantity="item.quantity"
            :img="item.product_image || 'product1.png'" @remove="removeProduct" @update-quantity="updateQuantity" />
        </template>
      </div>

      <div class="flex flex-col gap-5 border border-gray rounded-2xl w-full desktop:w-[35%] h-[80%] p-5">
        <h1 class="text-2xl font-semibold mb-3">Order Summary</h1>
        <div class="flex justify-between">
          <p class="text-slate-500">Subtotal</p>
          <p>${{ subtotal.toFixed(2) }}</p>
        </div>
        <div class="flex justify-between">
          <p class="text-slate-500">Discount</p>
          <p class="text-red">-${{ discountAmount.toFixed(2) }}</p>
        </div>
        <div class="flex justify-between">
          <p class="text-slate-500">Delivery Fee</p>
          <p>${{ shippingFee.toFixed(2) }}</p>
        </div>
        <hr class="text-gray">
        <div class="flex justify-between">
          <p>Total</p>
          <h2 class="text-xl font-semibold">${{ grandTotal.toFixed(2) }}</h2>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm text-slate-600">Shipping Province</label>
          <select v-model="shippingProvince" class="rounded-[16px] bg-gray px-4 outline-none py-3 w-full text-sm">
            <option v-for="province in shippingProvinceOptions" :key="province" :value="province">
              {{ province }}
            </option>
          </select>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm text-slate-600">Shipping Address (Optional)</label>
          <input v-model="shippingAddress" type="text"
            class="rounded-[16px] bg-gray px-4 outline-none py-3 w-full text-sm" placeholder="Street / house" />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm text-slate-600">Phone (Optional)</label>
          <input v-model="shippingPhone" type="text"
            class="rounded-[16px] bg-gray px-4 outline-none py-3 w-full text-sm" placeholder="Phone number" />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm text-slate-600">Payment Method</label>
          <select v-model="paymentMethod" class="rounded-[16px] bg-gray px-4 outline-none py-3 w-full text-sm">
            <option value="cash_on_delivery">Cash On Delivery</option>
            <option value="khqr">Online QR Payment</option>
          </select>
        </div>
        <div class="flex flex-col gap-2" v-if="isPaymentByKhrqr">
          <label class="text-sm text-slate-600">Payment Currency</label>
          <select v-model="paymentCurrency" class="rounded-[16px] bg-gray px-4 outline-none py-3 w-full text-sm">
            <option value="USD">USD</option>
            <option value="KHR">KHR</option>
          </select>
        </div>

        <div class="flex flex-col desktop:flex-row gap-3 mt-2 w-full">
          <div class="relative flex-1">
            <Icon name="ic:baseline-discount"
              class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg" />
            <input v-model="promoCode" type="text"
              class="rounded-[62px] bg-gray px-10 outline-none py-3 w-full text-sm desktop:text-lg"
              placeholder="Add Promo Code" />
          </div>
          <el-button class="bg-black rounded-3xl text-white p-3 lg:w-[110px]" :disabled="applyingCoupon"
            @click="applyCoupon()">
            {{ applyingCoupon ? 'Applying...' : 'Apply' }}
          </el-button>
        </div>
        <p v-if="appliedVoucherCode" class="text-xs text-emerald-600">
          Applied coupon: {{ appliedVoucherCode }}
        </p>
        <p v-if="autoCouponMessage" class="text-xs"
          :class="autoCouponMessageType === 'success' ? 'text-emerald-600' : 'text-amber-600'">
          {{ autoCouponMessage }}
        </p>

        <button class="bg-black rounded-3xl text-white w-full p-3 mt-2 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="checkingOut || loadingCart || !cartItems.length" @click="checkout">
          {{ checkingOut ? 'Processing...' : 'Go to Checkout' }}
          <Icon name="humbleicons:arrow-right" />
        </button>
      </div>
    </div>

    <el-dialog v-model="paymentDialogOpen" width="420px" :close-on-click-modal="false" title="Scan QR to Pay"
      @closed="handlePaymentDialogClosed">
      <div class="space-y-4">
        <div class="grid place-items-center rounded-3xl bg-[#F5F5F5] p-5">
          <div v-if="qrImageUrl" ref="qrImage" class="w-full max-w-[300px]">
            <div class="overflow-hidden rounded-2xl bg-white shadow-md">
              <div class="relative h-14 bg-[#D8141F]">
                <div
                  class="absolute inset-0 flex items-center justify-center text-lg font-bold tracking-[0.2em] text-white">
                  KHQR
                </div>
                <div
                  class="absolute right-0 top-0 h-0 w-0 border-l-[24px] border-l-transparent border-t-[24px] border-t-white">
                </div>
              </div>
              <div class="space-y-3 p-4">
                <div class="space-y-1">
                  <p class="text-xs font-semibold text-gray-800">
                    {{ merchantName || 'Merchant' }}
                  </p>
                  <div class="flex items-end gap-2 text-gray-900">
                    <span class="text-2xl font-extrabold">
                      {{ payableAmount.toFixed(2) }}
                    </span>
                    <span class="text-sm font-semibold uppercase text-gray-600">
                      {{ paymentCurrency }}
                    </span>
                  </div>
                </div>
                <div class="border-t border-dashed border-gray-300"></div>
                <div class="relative grid place-items-center">
                  <img :src="qrImageUrl" alt="Payment QR" class="h-[260px] w-[260px] object-contain">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex flex-col">
          <p class="text-sm text-gray-600 text-center">Order #{{ currentOrderId || '-' }} | Poll hash: {{ pollHash ||
            '-'
            }}
          </p>
          <p class="text-sm text-gray-600 text-center">Status: <span class="font-semibold">{{ pollStatus }}</span></p>
          <p class="text-sm text-amber-600 text-center">Time left: {{ timeLeftLabel }}</p>

          <a v-if="checkoutUrl" :href="checkoutUrl" target="_blank" rel="noopener noreferrer"
            class="block text-center text-sm text-blue-600 underline">
            Open checkout page
          </a>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-center gap-2">
          <el-button @click="pollPaymentOnce" :loading="pollingNow">Check Now</el-button>
          <el-button @click="downloadQr" :loading="downloadingQr">Download</el-button>
          <el-button @click="paymentDialogOpen = false">Close</el-button>

        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import { storeToRefs } from 'pinia'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'
import { useAuthStore } from '~/stores/authStore'
import { useCartStore } from '~/stores/cartStore'
import { toPng } from 'html-to-image';
import { formatAnyDate } from '~/utils/date'
import { watchDebounced } from '@vueuse/core'

type CartItem = {
  variant_id: number
  product_id: number
  product_name?: string
  color?: string
  size?: string
  stock_quantity: number
  quantity: number
  unit_price: number
  line_total: number
  product_image?: string
}

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const authStore = useAuthStore()
const cartStore = useCartStore()
const { accessToken, isAuthenticated } = storeToRefs(authStore)
const router = useRouter()

const loadingCart = ref(false)
const checkingOut = ref(false)
const applyingCoupon = ref(false)
const cartItems = ref<CartItem[]>([])
const subtotal = ref(0)
const shippingFee = ref(0)
const discountAmount = ref(0)
const promoCode = ref('')
const appliedVoucherCode = ref('')
const autoCouponAttempted = ref(false)
const autoCouponMessage = ref('')
const autoCouponMessageType = ref<'success' | 'warning'>('warning')
const isCouponComplete = ref(false)

const shippingProvince = ref('Phnom Penh')
const shippingAddress = ref('')
const shippingPhone = ref('')
const paymentMethod = ref<'cash_on_delivery' | 'khqr'>('khqr')
const paymentCurrency = ref<'USD' | 'KHR'>('USD')
const isPaymentByKhrqr = ref<Boolean>(false)

const paymentDialogOpen = ref(false)
const qrString = ref('')
const pollHash = ref('')
const checkoutUrl = ref('')
const merchantName = ref('')
const payableAmount = ref(0)
const currentOrderId = ref<number | null>(null)
const pollStatus = ref('pending')
const pollingNow = ref(false)
const pollDeadlineAt = ref<number | null>(null)
const timeLeftSeconds = ref(60)
const downloadingQr = ref(false)
const qrImage = ref<HTMLElement | null>(null)
let pollTimer: ReturnType<typeof setInterval> | null = null
let countdownTimer: ReturnType<typeof setInterval> | null = null

const shippingProvinceOptions = [
  'Phnom Penh',
  'Kandal',
  'Siem Reap',
  'Battambang',
  'Preah Sihanouk',
  'Other',
]

const shippingRateByProvince: Record<string, number> = {
  'phnom penh': 1.5,
  kandal: 2.0,
  'siem reap': 2.5,
  battambang: 2.5,
  'preah sihanouk': 3.0,
  other: 3.5,
}

const grandTotal = computed(() => {
  return Math.max(0, subtotal.value - discountAmount.value + shippingFee.value)
})

const normalizeProvince = (value: string) => String(value || '').trim().toLowerCase()

const computeShippingFee = (province: string) => {
  const normalized = normalizeProvince(province)
  return shippingRateByProvince[normalized] ?? 3.5
}

const parseKhqrAmountFromQrString = (rawQr: string) => {
  const qr = String(rawQr || '').trim()
  if (!qr) {
    return null
  }

  let cursor = 0
  while (cursor + 4 <= qr.length) {
    const tag = qr.slice(cursor, cursor + 2)
    const lengthRaw = qr.slice(cursor + 2, cursor + 4)
    const valueLength = Number(lengthRaw)

    if (!Number.isInteger(valueLength) || valueLength < 0) {
      break
    }

    const valueStart = cursor + 4
    const valueEnd = valueStart + valueLength
    if (valueEnd > qr.length) {
      break
    }

    const value = qr.slice(valueStart, valueEnd)
    if (tag === '54') {
      const amount = Number(value)
      if (Number.isFinite(amount) && amount >= 0) {
        return Math.round(amount * 100) / 100
      }
      return null
    }

    cursor = valueEnd
  }

  return null
}

const qrImageUrl = computed(() => {
  if (!qrString.value) {
    return ''
  }
  return `https://api.qrserver.com/v1/create-qr-code/?size=360x360&ecc=H&margin=2&data=${encodeURIComponent(qrString.value)}`
})

const timeLeftLabel = computed(() => {
  const total = Math.max(0, timeLeftSeconds.value)
  const mm = Math.floor(total / 60).toString().padStart(2, '0')
  const ss = (total % 60).toString().padStart(2, '0')
  return `${mm}:${ss}`
})

const getAuthHeaders = () => {
  return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined
}

const mapCartPayload = (payload: any) => {
  subtotal.value = Number(payload?.subtotal || 0)
  cartItems.value = (payload?.items || []).map((row: any) => ({
    ...row,
    unit_price: Number(row?.unit_price || 0),
    line_total: Number(row?.line_total || 0),
  }))
  cartStore.setFromApi(payload || {})
}

const ensureAuth = async () => {
  if (!isAuthenticated.value && !accessToken.value) {
    ElMessage.warning('Please login first.')
    await router.push('/auth/login')
    return false
  }
  return true
}

const fetchCart = async () => {
  if (!(await ensureAuth())) {
    return
  }
  loadingCart.value = true
  try {
    const response: any = await $fetch(`${apiBase}/cart`, {
      method: 'GET',
      credentials: 'include',
      headers: getAuthHeaders(),
    })
    mapCartPayload(response?.data || {})
  } catch (error: any) {
    const statusCode = error?.statusCode ?? error?.status
    if (statusCode === 401 || statusCode === 403) {
      authStore.resetAuth()
      await router.push('/auth/login')
      return
    }
    ElMessage.error(error?.data?.message || 'Failed to fetch cart.')
  } finally {
    loadingCart.value = false
  }
}

const fetchSignupOfferCode = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/vouchers/signup-offer`, {
      method: 'GET',
      credentials: 'include',
      headers: getAuthHeaders(),
    })
    const code = String(response?.data?.code || '').trim()
    return code
  } catch {
    return ''
  }
}

const removeProduct = async (variantId: number) => {
  try {
    const response: any = await $fetch(`${apiBase}/cart/items/${variantId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: getAuthHeaders(),
    })
    mapCartPayload(response?.data || {})
    ElMessage.success('Item removed.')
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to remove item.')
  }
}

const updateQuantity = async ({ variantId, quantity }: { variantId: number; quantity: number }) => {
  try {
    const response: any = await $fetch(`${apiBase}/cart/items/${variantId}`, {
      method: 'PUT',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: { quantity }
    })
    mapCartPayload(response?.data || {})
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to update quantity.')
    await fetchCart()
  }
}

const applyCoupon = async (silent = false) => {
  if (!promoCode.value.trim()) {
    if (!silent) {
      ElMessage.warning('Please enter promo code.')
    }
    return { ok: false, message: 'Please enter promo code.' }
  }
  applyingCoupon.value = true
  try {
    const response: any = await $fetch(`${apiBase}/vouchers/apply`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: { code: promoCode.value.trim() }
    })
    discountAmount.value = Number(response?.data?.discount || 0)
    appliedVoucherCode.value = String(response?.data?.voucher?.code || promoCode.value.trim())
    if (!silent) {
      ElMessage.success('Coupon applied.')
    }
    isCouponComplete.value = true
    return { ok: true, message: 'Coupon applied.' }
  } catch (error: any) {
    appliedVoucherCode.value = ''
    discountAmount.value = 0
    const errMessage = String(error?.data?.message || 'Invalid coupon.')
    if (!silent) {
      ElMessage.error(errMessage)
    }
    return { ok: false, message: errMessage }
  } finally {
    applyingCoupon.value = false
  }
}

const autoApplySignupCoupon = async () => {
  if (autoCouponAttempted.value) return
  if (!cartItems.value.length) return
  if (appliedVoucherCode.value || promoCode.value.trim()) return

  autoCouponAttempted.value = true
  const signupCode = await fetchSignupOfferCode()
  if (!signupCode) {
    autoCouponMessageType.value = 'warning'
    autoCouponMessage.value = 'No active signup voucher is available right now.'
    return
  }

  promoCode.value = signupCode
  const result = await applyCoupon(true)
  if (result?.ok) {
    autoCouponMessageType.value = 'success'
    autoCouponMessage.value = `Signup offer applied automatically: ${signupCode}`
    isCouponComplete.value = true
    return
  }

  autoCouponMessageType.value = 'warning'
  autoCouponMessage.value = `Auto-apply failed: ${result?.message || 'Invalid coupon.'}`
}

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  pollingNow.value = false
}

const cancelPendingOrder = async () => {
  if (!currentOrderId.value) {
    return
  }
  if (paymentMethod.value !== 'khqr') {
    return
  }
  if (pollStatus.value === 'paid') {
    return
  }

  try {
    await $fetch(`${apiBase}/payments/khrqr/cancel`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: { order_id: currentOrderId.value },
    })
    currentOrderId.value = null
    qrString.value = ''
    pollHash.value = ''
    checkoutUrl.value = ''
    payableAmount.value = 0
    pollStatus.value = 'pending'
    await fetchCart()
  } catch (error: any) {
    const statusCode = error?.statusCode ?? error?.status
    if (statusCode === 401 || statusCode === 403) {
      authStore.resetAuth()
      await router.push('/auth/login')
      return
    }
    ElMessage.error(error?.data?.message || 'Failed to cancel payment.')
  }
}

const handlePaymentDialogClosed = async () => {
  stopPolling()
  await cancelPendingOrder()
}

const pollPaymentOnce = async () => {
  if (!pollHash.value) {
    return
  }
  pollingNow.value = true
  try {
    const response: any = await $fetch(`${apiBase}/payments/khrqr/check/${pollHash.value}`, {
      method: 'GET',
      credentials: 'include',
      headers: getAuthHeaders(),
    })
    const data = response?.data || {}
    const paymentStatus = String(data?.payment_status || '').toLowerCase()
    pollStatus.value = paymentStatus || String(data?.status || 'pending')

    if (paymentStatus === 'paid') {
      stopPolling()
      paymentDialogOpen.value = false
      ElMessage.success('Payment successful.')
      await fetchCart()
      return router.replace('/')
    }

    if (['failed', 'expired', 'canceled', 'cancelled', 'refunded'].includes(paymentStatus)) {
      stopPolling()
      ElMessage.error(`Payment ${paymentStatus}.`)
      await cancelPendingOrder()
    }
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to check payment status.')
  } finally {
    pollingNow.value = false
  }
}

const startPolling = () => {
  stopPolling()
  pollDeadlineAt.value = Date.now() + 60_000
  timeLeftSeconds.value = 60

  countdownTimer = setInterval(() => {
    if (!pollDeadlineAt.value) {
      return
    }
    const remaining = Math.max(0, Math.ceil((pollDeadlineAt.value - Date.now()) / 1000))
    timeLeftSeconds.value = remaining
    if (remaining <= 0) {
      stopPolling()
      pollStatus.value = 'expired'
      ElMessage.warning('Payment session expired after 1 minute.')
    }
  }, 1000)

  pollTimer = setInterval(() => {
    if (timeLeftSeconds.value <= 0) {
      return
    }
    pollPaymentOnce()
  }, 3000)
}

const extractApiErrorDetails = (error: any) => {
  const rootMessage = String(error?.data?.message || error?.message || 'Request failed')
  const errors = error?.data?.errors
  if (!errors || typeof errors !== 'object') {
    return rootMessage
  }

  const lines: string[] = []
  Object.values(errors).forEach((value: any) => {
    if (Array.isArray(value)) {
      value.forEach((item) => lines.push(String(item)))
      return
    }
    lines.push(String(value))
  })

  if (!lines.length) {
    return rootMessage
  }

  return `${rootMessage}: ${lines.join(', ')}`
}

const createPaymentIntent = async (orderId: number) => {
  let response: any
  try {
    response = await $fetch(`${apiBase}/payments/intent`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: {
        order_id: orderId,
        provider: 'khrqr',
        currency: paymentCurrency.value,
      }
    })
  } catch (error: any) {
    throw new Error(extractApiErrorDetails(error))
  }
  qrString.value = String(response?.data?.qr_string || '')
  pollHash.value = String(response?.data?.poll_hash || '')
  checkoutUrl.value = String(response?.data?.checkout_url || '')
  merchantName.value = String(response?.data?.merchant_name || response?.data?.mechant_name || '')
  const parsedAmount = parseKhqrAmountFromQrString(qrString.value)
  payableAmount.value = parsedAmount ?? Number(response?.data?.amount || 0)

  pollStatus.value = 'pending'
  timeLeftSeconds.value = 60

  paymentDialogOpen.value = true
  if (pollHash.value) {
    startPolling()
  } else {
    ElMessage.warning('Poll hash missing. Use checkout link and manual check.')
  }
}

const checkout = async () => {
  if (!cartItems.value.length) {
    ElMessage.warning('Cart is empty.')
    return
  }
  if (!shippingProvince.value.trim()) {
    ElMessage.warning('Shipping province is required.')
    return
  }

  checkingOut.value = true
  try {
    const checkoutResponse: any = await $fetch(`${apiBase}/checkout`, {
      method: 'POST',
      credentials: 'include',
      headers: getAuthHeaders(),
      body: {
        shipping_province: shippingProvince.value.trim(),
        shipping_address: shippingAddress.value.trim() || undefined,
        shipping_phone: shippingPhone.value.trim() || undefined,
        payment_method: paymentMethod.value,
        voucher_code: appliedVoucherCode.value || undefined,
        grand_total: Number(grandTotal.value.toFixed(2)),
      }
    })

    const summary = checkoutResponse?.data?.summary || {}
    shippingFee.value = Number(summary?.shipping_fee || 0)
    discountAmount.value = Number(summary?.discount || discountAmount.value)

    const orderId = Number(checkoutResponse?.data?.order?.id || 0)
    currentOrderId.value = orderId || null
    if (!orderId) {
      throw new Error('Invalid order id.')
    }

    if (paymentMethod.value === 'cash_on_delivery') {
      ElMessage.success('Checkout successful. Order placed with Cash on Delivery.')
      discountAmount.value = 0
      appliedVoucherCode.value = ''
      promoCode.value = ''
      await fetchCart()
      return
    }

    await createPaymentIntent(orderId)
    ElMessage.success('Payment intent created. Please scan QR.')
    await fetchCart()
  } catch (error: any) {
    ElMessage.error(extractApiErrorDetails(error))
  } finally {
    checkingOut.value = false
  }
}
// downloadQr
const downloadQr = () => {
  if (qrImage.value == null) {
    return
  }
  const appName = String(config.public.NUXT_PUBLIC_APP_NAME || 'Invoice').trim() || 'Invoice'
  const orderLabel = currentOrderId.value ? `order-${currentOrderId.value}` : 'order'
  const userLocale = navigator.language || 'en-US'
  const timestamp = formatAnyDate(new Date(), 'YYYY-MM-DD-HH-mm-ss', userLocale, 'timestamp')
  const filename = `${appName}-invoice-${orderLabel}-${timestamp}.png`
  toPng<any>(qrImage.value, { cacheBust: true })
    .then((dataUrl) => {
      const link = document.createElement('a')
      link.download = filename
      link.href = dataUrl
      link.click()
    })
    .catch((err) => {
      console.log(err)
    })

}
// checkPromoCode

watchDebounced(
  promoCode,
  () => {
    applyCoupon()
  },
  { debounce: 500, maxWait: 1000 },
)

watch(paymentMethod, (value) => {
  isPaymentByKhrqr.value = value === 'khqr'
}, { immediate: true })

watch(
  shippingProvince,
  (value) => {
    shippingFee.value = computeShippingFee(value)
  },
  { immediate: true }
)

watch(
  () => cartItems.value.length,
  () => {
    autoApplySignupCoupon()
  }
)

onMounted(() => {
  fetchCart().then(() => autoApplySignupCoupon())
})

onBeforeUnmount(() => {
  stopPolling()
})
</script>

<style scoped></style>
