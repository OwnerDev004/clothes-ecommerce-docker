<script setup lang="ts">
useSeoMeta({
  title: 'Clothes Shop LD',
  description: 'The ecommerce website modern nuxt js and laravel api.',
})
</script>
<template>
  <div class="min-h-screen bg-page text-text">
    <NuxtLayout>
      <NuxtLoadingIndicator color="green" :throttle="1" />
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>
