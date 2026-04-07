<script setup lang="ts">
defineOptions({ inheritAttrs: false })

withDefaults(
  defineProps<{
    headerClass?: string
    bodyClass?: string
  }>(),
  {
    headerClass: 'px-5 py-4 border-b border-border bg-surface',
    bodyClass: 'p-5',
  },
)
</script>

<template>
  <el-card v-bind="$attrs" class="admin-card">
    <template v-if="$slots.header" #header>
      <div :class="[headerClass, 'admin-card__header']">
        <slot name="header" />
      </div>
    </template>

    <div :class="[bodyClass, 'admin-card__body']">
      <slot />
    </div>
  </el-card>
</template>
