<script setup lang="ts">
import type { Component } from 'vue'

type NavItem = {
  index: string
  label: string
  icon: Component
  badge?: string
  disabled?: boolean
}

type NavGroup = {
  title: string
  items: NavItem[]
}

withDefaults(
  defineProps<{
    appName: string
    groups: NavGroup[]
  }>(),
  {
    appName: 'Clothes Shop',
  },
)

const emit = defineEmits<{
  close: []
}>()
</script>

<template>
  <div class="flex h-full min-h-0 flex-col">
    <div class="flex items-center gap-3 rounded-2xl px-3 pb-4 pt-2">
      <div
        class="grid h-11 w-11 place-items-center rounded-[16px] bg-[linear-gradient(145deg,#f8fafc,#c7d2fe)] font-extrabold tracking-[0.08em] text-slate-950 shadow-[0_14px_30px_rgba(96,165,250,0.22)]">
        CS
      </div>
      <div>
        <p class="m-0 text-[1rem] font-bold">{{ appName }}</p>
        <p class="m-0 mt-1 text-sm text-white/55">Admin console</p>
      </div>
    </div>

    <div class="flex-1 space-y-4 overflow-y-auto pr-1">
      <div v-for="group in groups" :key="group.title" class="space-y-2">
        <p class="m-0 px-3 text-[0.72rem] uppercase tracking-[0.12em] text-white/50">
          {{ group.title }}
        </p>

        <nav class="space-y-1">
          <template v-for="item in group.items" :key="item.index">
            <NuxtLink
              v-if="!item.disabled"
              :to="item.index"
              class="flex h-12 w-full items-center gap-3 rounded-2xl px-3 text-left text-sm text-white/75 transition hover:bg-white/10 hover:text-white"
              @click="emit('close')"
            >
              <el-icon class="text-base">
                <component :is="item.icon" />
              </el-icon>
              <span class="flex-1">{{ item.label }}</span>
              <span
                v-if="item.badge"
                class="rounded-full bg-white/10 px-2.5 py-1 text-[0.7rem] font-semibold tracking-wide text-white"
              >
                {{ item.badge }}
              </span>
            </NuxtLink>

            <button
              v-else
              type="button"
              class="flex h-12 w-full cursor-not-allowed items-center gap-3 rounded-2xl px-3 text-left text-sm text-white/30 opacity-60"
            >
              <el-icon class="text-base">
                <component :is="item.icon" />
              </el-icon>
              <span class="flex-1">{{ item.label }}</span>
              <span
                v-if="item.badge"
                class="rounded-full bg-white/10 px-2.5 py-1 text-[0.7rem] font-semibold tracking-wide text-white"
              >
                {{ item.badge }}
              </span>
            </button>
          </template>
        </nav>
      </div>
    </div>

    <div class="pt-4">
      <div class="mx-1 rounded-[20px] border border-white/10 bg-white/[0.08] p-4">
        <p class="m-0 text-[0.72rem] uppercase tracking-[0.12em] text-white/50">This week</p>
        <div class="mt-3 grid grid-cols-2 gap-3">
          <div>
            <strong class="block text-[1.1rem]">128</strong>
            <span class="text-sm text-white/55">orders</span>
          </div>
          <div>
            <strong class="block text-[1.1rem]">24</strong>
            <span class="text-sm text-white/55">low stock</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
