<script setup lang="ts">
defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    tableData: Record<string, any>[]
    stripe?: boolean
    border?: boolean
  }>(),
  {
    stripe: true,
    border: false,
  },
)
</script>

<template>
  <div class="admin-table-shell">
    <el-table v-bind="$attrs" class="admin-table" :data="tableData" :stripe="stripe" :border="border">
      <slot />

    </el-table>
  </div>
</template>
