<template>
  <div class="filter-section" :class="{ 'py-2': !noPadding }">
    <button
      type="button"
      class="filter-section__header w-full flex items-center justify-between gap-2 px-1 py-2 text-sm font-semibold text-slate-800 hover:text-slate-900 transition-colors rounded-lg hover:bg-slate-50"
      @click="toggleOpen"
    >
      <span class="flex items-center gap-2">
        <Icon
          :name="isOpen ? 'mdi:chevron-down' : 'mdi:chevron-right'"
          class="text-base text-slate-400 transition-transform duration-200"
          :class="{ 'rotate-0': isOpen, '-rotate-90': !isOpen }"
        />
        {{ title }}
        <span
          v-if="count !== undefined && count > 0"
          class="text-[11px] text-slate-400 font-normal bg-slate-100 px-1.5 py-0.5 rounded-full"
        >
          {{ count }}
        </span>
      </span>
      <Icon
        v-if="isOpen"
        name="mdi:chevron-up"
        class="text-sm text-slate-300"
      />
      <Icon
        v-else
        name="mdi:chevron-down"
        class="text-sm text-slate-300"
      />
    </button>
    <Transition name="filter-slide">
      <div v-if="isOpen" class="filter-section__body px-1 pb-1">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

withDefaults(defineProps<{
  title: string
  collapsible?: boolean
  defaultOpen?: boolean
  count?: number
  mobile?: boolean
  noPadding?: boolean
}>(), {
  collapsible: true,
  defaultOpen: true,
  mobile: false,
  noPadding: false,
})

const isOpen = ref(true)

const toggleOpen = () => {
  isOpen.value = !isOpen.value
}
</script>

<style scoped>
.filter-slide-enter-active {
  transition: all 0.2s ease-out;
}
.filter-slide-leave-active {
  transition: all 0.15s ease-in;
}
.filter-slide-enter-from {
  opacity: 0;
  max-height: 0;
  transform: translateY(-4px);
}
.filter-slide-enter-to {
  opacity: 1;
  max-height: 500px;
  transform: translateY(0);
}
.filter-slide-leave-from {
  opacity: 1;
  max-height: 500px;
}
.filter-slide-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-4px);
}
</style>
