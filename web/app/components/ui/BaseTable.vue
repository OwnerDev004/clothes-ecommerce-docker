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
    <el-table
      v-bind="$attrs"
      class="admin-table"
      :data="tableData"
      :stripe="stripe"
      :border="border"
      table-layout="auto"
    >
      <slot />

    </el-table>
  </div>
</template>

<style scoped>
.admin-table-shell {
  overflow-x: auto;
  border-radius: 18px;
  border: 1px solid var(--el-border-color-lighter);
  background: var(--el-bg-color);
}

.admin-table :deep(.el-table__inner-wrapper) {
  min-width: 100%;
}

.admin-table :deep(.el-table__body),
.admin-table :deep(.el-table__header) {
  min-width: 100%;
}
</style>
