<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    loading?: boolean
    animated?: boolean
    rows?: number
    count?: number
    embedded?: boolean
    titleWidth?: string
  }>(),
  {
    loading: true,
    animated: true,
    rows: 6,
    count: 1,
    embedded: false,
    titleWidth: '42%',
  },
)

</script>

<template>
  <section class="loading-page" :data-embedded="props.embedded">
    <div v-if="!props.embedded" class="loading-page__surface">
      <el-skeleton :loading="props.loading" :animated="props.animated" :rows="props.rows" :count="props.count"
        class="loading-page__skeleton">
        <template #template>
          <slot name="template">
            <div class="loading-page__content">
              <div class="loading-page__heading">
                <el-skeleton-item variant="h3" class="loading-page__title" :style="{ width: props.titleWidth }" />
                <el-skeleton-item variant="text" class="loading-page__subtitle" />
              </div>

              <div class="loading-page__grid">
                <el-skeleton-item v-for="item in 4" :key="item" variant="rect" class="loading-page__block" />
              </div>

              <div class="loading-page__footer">
                <el-skeleton-item variant="button" class="loading-page__button" />
                <el-skeleton-item variant="button" class="loading-page__button" />
              </div>
            </div>
          </slot>
        </template>
      </el-skeleton>
    </div>

    <el-skeleton v-else :loading="props.loading" :animated="props.animated" :rows="props.rows" :count="props.count"
      class="loading-page__skeleton loading-page__skeleton--embedded">
      <template #template>
        <slot name="template">
          <div class="loading-page__content loading-page__content--embedded">
            <div class="loading-page__heading">
              <el-skeleton-item variant="h3" class="loading-page__title" :style="{ width: props.titleWidth }" />
              <el-skeleton-item variant="text" class="loading-page__subtitle" />
            </div>

            <div class="loading-page__grid">
              <el-skeleton-item v-for="item in 4" :key="item" variant="rect" class="loading-page__block" />
            </div>

            <div class="loading-page__footer">
              <el-skeleton-item variant="button" class="loading-page__button" />
              <el-skeleton-item variant="button" class="loading-page__button" />
            </div>
          </div>
        </slot>
      </template>
    </el-skeleton>
  </section>
</template>

<style scoped>
.loading-page {
  width: 100%;
  min-height: 320px;
  padding: 24px 0;
}

.loading-page[data-embedded='false'] {
  display: grid;
  place-items: center;
  padding: 24px;
}

.loading-page__surface {
  width: 100%;
  max-width: 1120px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 24px;
  background: var(--el-bg-color);
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.05);
  padding: 24px;
}

.loading-page__skeleton {
  width: 100%;
}

.loading-page__content {
  display: grid;
  gap: 20px;
}

.loading-page__content--embedded {
  gap: 16px;
}

.loading-page__heading {
  display: grid;
  gap: 12px;
}

.loading-page__title {
  height: 28px;
  border-radius: 9999px;
}

.loading-page__subtitle {
  width: 72%;
  height: 16px;
  border-radius: 9999px;
}

.loading-page__grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.loading-page__block {
  height: 96px;
  border-radius: 18px;
}

.loading-page__footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

.loading-page__button {
  width: 120px;
  height: 40px;
  border-radius: 9999px;
}

.loading-page__skeleton--embedded .loading-page__block {
  height: 80px;
}

@media (max-width: 640px) {
  .loading-page {
    min-height: 240px;
    padding: 16px 0;
  }

  .loading-page__surface {
    padding: 16px;
    border-radius: 20px;
  }

  .loading-page__grid {
    grid-template-columns: 1fr;
  }
}
</style>
