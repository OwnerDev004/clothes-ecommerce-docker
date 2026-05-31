<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
  ratingAmount: {
    type: Number,
    default: 0,
  },
})

const stars = computed(() => {
  const normalized = Number.isFinite(props.ratingAmount) ? props.ratingAmount : 0
  return Array.from({ length: 5 }, (_, index) => index < Math.round(normalized))
})
</script>

<template>
  <div class="flex items-center gap-1" :aria-label="`Rating ${ratingAmount} out of 5`">
    <span
      v-for="(filled, index) in stars"
      :key="index"
      class="text-sm leading-none"
      :class="filled ? 'text-amber-400' : 'text-slate-300'"
      aria-hidden="true"
    >
      ★
    </span>
    <span class="ml-1 text-xs text-slate-500">
      {{ Number.isFinite(ratingAmount) ? ratingAmount.toFixed(1) : '0.0' }}
    </span>
  </div>
</template>
