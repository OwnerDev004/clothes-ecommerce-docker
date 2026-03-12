<template>
  <div class="font-Poppins">
    <NuxtLayout>
      <NuxtLoadingIndicator :throttle="0" />
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
