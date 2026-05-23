<script setup lang="ts">
withDefaults(
  defineProps<{
    bgCard?: string
  }>(),
  {
    bgCard: 'bg-surface',
  },
)
</script>

<template>
  <div class="product-card-skeleton flex h-full flex-col rounded-2xl border border-border bg-surface p-3 shadow-sm sm:p-4" :class="[bgCard]">
    <div class="rounded-2xl bg-white/40 p-3">
      <el-skeleton animated>
        <template #template>
          <el-skeleton-item variant="rect" class="product-card-skeleton__image" />
          <div class="mt-4 space-y-3">
            <el-skeleton-item variant="text" class="product-card-skeleton__title" />
            <el-skeleton-item variant="text" class="product-card-skeleton__rating" />
            <el-skeleton-item variant="text" class="product-card-skeleton__price" />
          </div>
        </template>
      </el-skeleton>
    </div>
  </div>
</template>

<style scoped>
.product-card-skeleton__image {
  width: 100%;
  aspect-ratio: 4 / 5;
  border-radius: 0.75rem;
}

.product-card-skeleton__title {
  height: 1.2rem;
  width: 78%;
  border-radius: 9999px;
}

.product-card-skeleton__rating {
  height: 0.95rem;
  width: 52%;
  border-radius: 9999px;
}

.product-card-skeleton__price {
  height: 1rem;
  width: 64%;
  border-radius: 9999px;
}
</style>
