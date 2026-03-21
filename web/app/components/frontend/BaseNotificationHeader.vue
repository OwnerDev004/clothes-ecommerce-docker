<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useAuthStore } from '~/stores/authStore'

const authStore = useAuthStore()
const route = useRoute()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const isNotificationVisible = ref(true)
const hasTrackedImpression = ref(false)
const offerDiscountLabel = ref('20%')

const shouldShowNotification = computed(() => {
  return !authStore.isAuthenticated && isNotificationVisible.value
})

type NotificationAction = 'notification_impression' | 'notification_cta_click' | 'notification_dismiss'

const trackNotificationEvent = (action: NotificationAction) => {
  if (!import.meta.client) return

  const payload = {
    event: 'notification_header',
    action,
    campaign: 'signup_first_order_20_off',
    placement: 'top_header',
    path: route.fullPath,
  }

  const win = window as Window & { dataLayer?: Array<Record<string, unknown>> }
  win.dataLayer = win.dataLayer || []
  win.dataLayer.push(payload)
}

const trackCtaClick = () => {
  trackNotificationEvent('notification_cta_click')
}

const loadSignupOffer = async () => {
  try {
    const response: any = await $fetch(`${apiBase}/vouchers/signup-offer`, {
      method: 'GET',
    })
    const offer = response?.data
    const discountType = String(offer?.discount_type || '')
    const discountValue = Number(offer?.discount_value || 0)

    if (!offer || !discountType || !Number.isFinite(discountValue) || discountValue <= 0) {
      return
    }

    if (discountType === 'percentage') {
      offerDiscountLabel.value = `${discountValue}%`
      return
    }

    offerDiscountLabel.value = `$${discountValue.toFixed(0)}`
  } catch {
    // Keep fallback label when API is unavailable.
  }
}

const closeNotification = () => {
  trackNotificationEvent('notification_dismiss')
  isNotificationVisible.value = false
}

const trackImpressionIfNeeded = () => {
  if (!shouldShowNotification.value || hasTrackedImpression.value) return
  hasTrackedImpression.value = true
  trackNotificationEvent('notification_impression')
}

watch(shouldShowNotification, () => {
  trackImpressionIfNeeded()
})

onMounted(() => {
  loadSignupOffer()
  trackImpressionIfNeeded()
})
</script>

<template>
  <transition name="notify" mode="out-in">
    <div v-if="shouldShowNotification"
      class="bg-slate-900 text-white font-Lato text-xs desktop:text-md py-2 px-10 flex justify-between items-center">
      <!-- Centered Text Section -->
      <div class="w-full text-center">
        <p>
          Sign up and get {{ offerDiscountLabel }} off your first order.
          <NuxtLink to="/auth/signup" class="underline text-sm desktop:text-base" @click="trackCtaClick">Sign Up
            Now</NuxtLink>
        </p>
      </div>

      <!-- Icon Section -->
      <button type="button" class="text-base ml-4 cursor-pointer" aria-label="Close notification"
        @click="closeNotification">
        <Icon name="mdi:close-circle" />
      </button>
    </div>
  </transition>
</template>

<style scoped>
.notify-enter-active,
.notify-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
  will-change: opacity, transform;
}

.notify-enter-from,
.notify-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.notify-enter-to,
.notify-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
