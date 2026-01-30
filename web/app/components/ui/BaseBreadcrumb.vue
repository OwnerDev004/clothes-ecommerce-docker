<template>
    <el-breadcrumb :separator-icon="icon" class="my-4 mx-4">
         <slot />
  </el-breadcrumb>
</template>

<script setup lang="ts">
    const props = defineProps({
         icon: {
    type: Object,
    required: true
  }
    });
</script>

<style scoped>
    .el-breadcrumb{
      font-size: 14px;
    }

</style>