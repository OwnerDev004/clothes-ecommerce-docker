<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import ProductDetailView from '~/components/product/ProductDetailView.vue'
import type { AdminProductRecord } from '~/composables/useAdminProduct'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    product: AdminProductRecord | null
    loading?: boolean
  }>(),
  {
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const openState = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})
</script>

<template>
  <BaseModal v-model="openState" title="Product Detail" width="1200px" :show-footer="false" body-class="p-0">
    <div v-if="loading" class="grid place-items-center px-6 py-20 text-slate-500">
      Loading product detail...
    </div>
    <ProductDetailView v-else :product="product" />
  </BaseModal>
</template>
