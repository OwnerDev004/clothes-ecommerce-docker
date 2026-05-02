<script setup lang="ts">
defineOptions({ inheritAttrs: false })
defineProps({
  disabled: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <el-button v-bind="$attrs" :disabled="disabled">
    <slot />
  </el-button>
</template>
