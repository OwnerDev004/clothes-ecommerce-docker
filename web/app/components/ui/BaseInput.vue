<script setup lang="ts">
defineOptions({ inheritAttrs: false })
const model = defineModel<string | number | null>()
const props = withDefaults(
  defineProps<{
    prefixIcon?: any
  }>(),
  {
    prefixIcon: ''

  })
</script>

<template>
  <el-input v-model="model" v-bind="$attrs" class="admin-input" :prefix-icon="prefixIcon" />
</template>
