<template>
  <div class="min-h-screen bg-page text-text">
    <NuxtLayout>
      <NuxtLoadingIndicator color="red" :throttle="0" />
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>
<style>
.page-enter-active,
.page-leave-active {
  transition: all 0.4s;
}

.page-enter-from,
.page-leave-to {
  opacity: 0;
  filter: blur(0.1s);
}
</style>
