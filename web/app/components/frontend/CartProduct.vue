<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAppSetting } from '~/composables/useAppSetting'
import { formatMoney, normalizeCurrencyCode } from '~/utils/currency'

const props = defineProps({
  variantId: {
    type: Number,
    required: true
  },
  name: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: ''
  },
  color: {
    type: String,
    default: ''
  },
  price: {
    type: Number,
    default: 0
  },
  quantity: {
    type: Number,
    default: 1
  },
  img: {
    type: String,
    default: ''
  }
})

const { appSetting, defaultCurrencyCode, convertAmount } = useAppSetting()

const baseCurrencyCode = computed(() =>
  normalizeCurrencyCode(
    appSetting.value.base_currency_code || defaultCurrencyCode.value,
  ),
)

const displayPrice = computed(() =>
  convertAmount(
    props.price,
    baseCurrencyCode.value,
    defaultCurrencyCode.value,
  ),
)
const qtyAmount = ref(1);

// increment
const increment = () => {
  qtyAmount.value += 1;
  emit('update-quantity', { variantId: props.variantId, quantity: qtyAmount.value });
};
// decrement
const decrement = () => {
  if (qtyAmount.value > 1) {
    qtyAmount.value -= 1;
    emit('update-quantity', { variantId: props.variantId, quantity: qtyAmount.value });
  }
};

const emit = defineEmits(['remove', 'update-quantity'])

function removeProduct() {
  // Emit a 'remove' event to the parent
  emit('remove', props.variantId)
}

const displayImg = computed(() => {
  if (!props.img) {
    return '/img/products/default_image.webp'
  }
  if (props.img.startsWith('http://') || props.img.startsWith('https://') || props.img.startsWith('/')) {
    return props.img
  }
  return `/img/products/${props.img}`
})

watch(() => props.quantity, (value) => {
  qtyAmount.value = value > 0 ? value : 1
}, { immediate: true })
</script>
<template>
  <div class="p-4 border-b">
    <div class="flex gap-4">
      <NuxtImg :src="displayImg"
        class="max-w-[80px] sm:max-w-[120px] md:max-w-[140px] lg:max-w-[150px] h-auto rounded-2xl" />
      <section class="flex-1">

        <div class="flex">
          <div>
            <h1 class="text-md font-bold">{{ name }}</h1>
            <p class="text-xs md:text-xs">
              Size: <span class="text-muted">{{ size }}</span>
            </p>
            <p class="text-xs md:text-xs">
              Color: <span class="text-muted">{{ color }}</span>
            </p>
          </div>
          <Icon name="ep:delete-filled" class="text-red ml-auto cursor-pointer " @click="removeProduct" />
        </div>
        <div class="flex justify-between mt-12">
          <h3 class="text-xl font-semibold">
            {{ formatMoney(displayPrice, defaultCurrencyCode) }}
          </h3>

          <div class="flex gap-3 items-center border rounded-2xl">
            <button
              class="bg-surface-2 hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-l-2xl cursor-pointer"
              @click="decrement">
              <Icon name="ic:baseline-minus" class="text-base" />
            </button>
            <p class="mx-2">{{ qtyAmount }}</p>
            <!-- You can replace "1" with a variable to represent the count -->
            <button
              class="bg-surface-2 hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-r-2xl cursor-pointer"
              @click="increment">
              <Icon name="ic:round-plus" class="text-base" />
            </button>
          </div>
        </div>
      </section>
    </div>

  </div>
</template>

<style scoped></style>
