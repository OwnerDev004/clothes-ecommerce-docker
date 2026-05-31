<script setup lang="ts">
import { computed } from 'vue'
import { useAppSetting } from '~/composables/useAppSetting'
import { formatMoney, normalizeCurrencyCode } from '~/utils/currency'

const props = defineProps({
  price: {
    type: Number,
    default: 0,
  },
  discountType: {
    type: Number,
  },
  discountAmount: {
    type: Number,
    default: 0,
  },
  discountPercentage: {
    type: String,
    default: 'text-xl',
  },
})

const { appSetting, defaultCurrencyCode, convertAmount } = useAppSetting()

const baseCurrencyCode = computed(() =>
  normalizeCurrencyCode(
    appSetting.value.base_currency_code || defaultCurrencyCode.value,
  ),
)

const targetCurrencyCode = computed(() => defaultCurrencyCode.value)

const displayPrice = computed(() =>
  convertAmount(
    props.price,
    baseCurrencyCode.value,
    targetCurrencyCode.value,
  ),
)

const displayDiscountAmount = computed(() =>
  convertAmount(
    props.discountAmount,
    baseCurrencyCode.value,
    targetCurrencyCode.value,
  ),
)

const discountTypes = computed(() => {
  switch (props.discountType) {
    case 1:
      return `-${props.discountAmount} %`
    case 2:
      return `-${formatMoney(displayDiscountAmount.value, targetCurrencyCode.value)}`
    default:
      return ''
  }
})

const totalDiscountAmount = computed(() => {
  if (props.discountType && props.discountAmount) {
    switch (props.discountType) {
      case 1:
        return formatMoney(
          displayPrice.value - displayPrice.value * (props.discountAmount / 100),
          targetCurrencyCode.value,
        )
      case 2:
        return formatMoney(
          displayPrice.value - displayDiscountAmount.value,
          targetCurrencyCode.value,
        )
      default:
        return ''
    }
  }

  return formatMoney(displayPrice.value, targetCurrencyCode.value)
})
</script>

<template>
  <strong class="flex gap-4">
    <span class="text-text text-bold text-xl">{{ totalDiscountAmount }}</span>
    <span v-if="discountTypes" class="line-through">
      {{ formatMoney(displayPrice, targetCurrencyCode) }}
    </span>
    <span
      v-if="discountTypes"
      class="decoration-slice text-red bg-red-50 rounded-[62px] px-3 py-1"
      :class="`${discountPercentage}`"
    >
      {{ discountTypes }}
    </span>
  </strong>
</template>
