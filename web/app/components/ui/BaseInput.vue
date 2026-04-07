<script setup lang="ts">
defineOptions({ inheritAttrs: false })
const model = defineModel<string | number | null>()
</script>

<template>
  <el-input v-model="model" v-bind="$attrs" class="admin-input" />
</template>
