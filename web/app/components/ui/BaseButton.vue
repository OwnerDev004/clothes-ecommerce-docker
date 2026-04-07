<script setup lang="ts">
defineOptions({ inheritAttrs: false })
</script>

<template>
  <el-button v-bind="$attrs">
    <slot />
  </el-button>
</template>
