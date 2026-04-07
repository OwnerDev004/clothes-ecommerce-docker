<script setup lang="ts">
defineOptions({ inheritAttrs: false })

type SelectOption = {
  id: string | number
  label: string
}

withDefaults(
  defineProps<{
    options: SelectOption[]
    placeholder?: string
  }>(),
  {
    placeholder: 'Select an option',
  },
)

const model = defineModel<string | number | null>()
</script>

<template>
  <select v-model="model" class="select w-full max-w-xs rounded-3xl bg-gray" v-bind="$attrs">
    <option value="">{{ placeholder }}</option>
    <option v-for="option in options" :key="option.id" :value="option.id">
      {{ option.label }}
    </option>
  </select>
</template>

<style scoped>
/* Additional custom styles (if needed) */
</style>
