<script setup lang="ts">
import {
  Box,
  CircleCheckFilled,
  Coin,
  DataLine,
  Goods,
  Histogram,
  ShoppingCart,
  Tickets,
  WarningFilled,
} from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import { formatAnyDate } from '~/utils/date'
import { getOrderStatusTagType } from '~/utils/orderStatusTheme'

import { useAdminDashboard, type AdminDashboardSummary } from '~/composables/useAdminDashboard'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

const { dashboard, pending, error, refresh } = useAdminDashboard()

const dashboardState = computed<AdminDashboardSummary>(() => {
  return (
    dashboard.value || {
      stats: {
        revenue_today: 0,
        revenue_this_week: 0,
        pending_orders: 0,
        active_products: 0,
        low_stock_items: 0,
        customers: 0,
      },
      trend: [],
      status_breakdown: [],
      recent_orders: [],
      top_categories: [],
      low_stock_items: [],
      activity: [],
      generated_at: '',
    }
  )
})

const formatMoney = (value: number) =>
  new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))

const formatDateLabel = (value: string) =>
  formatAnyDate(value, 'ddd', 'en-US', '---')

const formatDateTime = (value: string | null) =>
  formatAnyDate(value, 'MMM D, YYYY h:mm A', 'en-US', 'Just now')

const statusType = (status: string) => {
  return getOrderStatusTagType(status)
}

const quickActions = [
  { label: 'Add product', icon: Goods },
  { label: 'Create category', icon: Histogram },
  { label: 'Review orders', icon: ShoppingCart },
  { label: 'Launch promo', icon: Tickets },
]

const statCards = computed(() => [
  {
    label: 'Revenue today',
    value: formatMoney(dashboardState.value.stats.revenue_today),
    note: 'Paid orders completed today',
    delta: formatMoney(dashboardState.value.stats.revenue_this_week),
    icon: Coin,
  },
  {
    label: 'Pending orders',
    value: String(dashboardState.value.stats.pending_orders),
    note: 'Waiting for fulfillment',
    delta: 'Live queue',
    icon: ShoppingCart,
  },
  {
    label: 'Active products',
    value: String(dashboardState.value.stats.active_products),
    note: 'Across all categories and collections',
    delta: 'Catalog',
    icon: Box,
  },
  {
    label: 'Low stock alerts',
    value: String(dashboardState.value.stats.low_stock_items),
    note: 'Variants below threshold',
    delta: 'Watch list',
    icon: WarningFilled,
  },
])

const trendMax = computed(() => Math.max(...dashboardState.value.trend.map((item) => item.total), 1))
</script>

<template>
  <div class="grid gap-6 text-slate-900">
    <section
      class="grid gap-5 rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(129,140,248,0.18),transparent_22%),linear-gradient(135deg,#ffffff,#eef2ff)] p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.9fr)] lg:p-7">
      <div>
        <p class="m-0 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
          Today at a glance
        </p>
        <h2 class="mt-2 max-w-[16ch] text-[clamp(1.8rem,2.8vw,3.2rem)] font-semibold leading-[0.95] text-slate-950">
          Keep store operations calm, clear, and on time.
        </h2>
        <p class="m-0 mt-4 max-w-[62ch] leading-7 text-slate-500">
          Monitor products, categories, orders, and promotions from one clean overview that matches
          the way your store actually works.
        </p>
      </div>

      <div class="grid gap-3 self-start">
        <div class="rounded-[20px] border border-slate-200 bg-white/75 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">Dashboard sync</span>
          <strong class="mt-1 block text-lg text-slate-950">{{ dashboardState.generated_at ?
            formatDateTime(dashboardState.generated_at) : 'Loading...' }}</strong>
        </div>
        <div class="rounded-[20px] border border-slate-200 bg-white/75 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">This week revenue</span>
          <strong class="mt-1 block text-lg text-slate-950">{{ formatMoney(dashboardState.stats.revenue_this_week)
            }}</strong>
        </div>
        <div class="rounded-[20px] border border-slate-200 bg-white/75 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">Customers</span>
          <strong class="mt-1 block text-lg text-slate-950">{{ dashboardState.stats.customers }}</strong>
        </div>
      </div>
    </section>

    <section v-if="error" class="rounded-[24px] border border-red-200 bg-red-50 p-5 text-red-700">
      <div class="flex items-center justify-between gap-4">
        <div>
          <strong class="block">Dashboard failed to load.</strong>
          <p class="m-0 mt-1 text-sm">{{ error?.message || 'Please try again.' }}</p>
        </div>
        <BaseButton
          class="rounded-2xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
          @click="refresh">
          Retry</BaseButton>
      </div>
    </section>

    <section v-if="pending" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div v-for="item in 4" :key="item" class="h-[170px] animate-pulse rounded-[24px] bg-white/70"></div>
    </section>

    <template v-else>
      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in statCards" :key="card.label"
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="flex items-center justify-between gap-3">
            <div
              class="grid h-11 w-11 place-items-center rounded-[16px] bg-[linear-gradient(135deg,#eef2ff,#dbeafe)] text-indigo-700">
              <el-icon>
                <component :is="card.icon" />
              </el-icon>
            </div>
            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[0.75rem] font-bold text-emerald-700">
              {{ card.delta }}
            </span>
          </div>
          <p class="m-0 mt-4 text-sm text-slate-500">{{ card.label }}</p>
          <strong class="mt-2 block text-[2rem] leading-none text-slate-950">{{ card.value }}</strong>
          <span class="mt-2 block text-sm leading-6 text-slate-500">{{ card.note }}</span>
        </article>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5 flex items-start justify-between gap-4">
            <div>
              <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
                Revenue pulse
              </p>
              <h3 class="m-0 text-[1.25rem] font-semibold text-slate-950">Weekly performance</h3>
            </div>
            <div class="text-right">
              <span class="block text-sm text-slate-500">Sales velocity</span>
              <strong class="text-[1.15rem] text-slate-950">{{ formatMoney(dashboardState.stats.revenue_this_week)
                }}</strong>
            </div>
          </div>

          <div class="grid h-[220px] grid-cols-7 items-end gap-3 pb-4">
            <div v-for="bar in dashboardState.trend" :key="bar.date" class="grid justify-items-center gap-2">
              <div
                class="flex h-[190px] w-full items-end overflow-hidden rounded-[18px] bg-gradient-to-b from-slate-50 to-indigo-50">
                <div
                  class="w-full rounded-t-[18px] bg-gradient-to-b from-indigo-600 to-violet-600 shadow-[0_12px_30px_rgba(79,70,229,0.26)]"
                  :style="{ height: `${Math.max((bar.total / trendMax) * 100, 4)}%` }"></div>
              </div>
              <span class="text-[0.82rem] text-slate-500">{{ formatDateLabel(bar.date) }}</span>
            </div>
          </div>

          <div class="grid gap-3 md:grid-cols-2">
            <div class="flex gap-3 rounded-[18px] bg-slate-50 p-4">
              <el-icon class="mt-0.5 text-[1.25rem] text-indigo-700">
                <CircleCheckFilled />
              </el-icon>
              <div>
                <strong class="block mb-1 text-slate-950">{{ dashboardState.stats.pending_orders }} orders
                  queued</strong>
                <p class="m-0 leading-6 text-slate-500">Waiting for fulfillment and shipping.</p>
              </div>
            </div>
            <div class="flex gap-3 rounded-[18px] bg-slate-50 p-4">
              <el-icon class="mt-0.5 text-[1.25rem] text-indigo-700">
                <DataLine />
              </el-icon>
              <div>
                <strong class="block mb-1 text-slate-950">{{ dashboardState.stats.active_products }} active
                  products</strong>
                <p class="m-0 leading-6 text-slate-500">Catalog coverage is healthy across the shop.</p>
              </div>
            </div>
          </div>
        </article>

        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-4">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
              Quick actions
            </p>
            <h3 class="m-0 text-[1.25rem] font-semibold text-slate-950">Move fast</h3>
          </div>

          <div class="grid gap-2">
            <button v-for="action in quickActions" :key="action.label" type="button"
              class="flex w-full items-center gap-3 rounded-[18px] border border-slate-200 bg-gradient-to-b from-white to-slate-50 px-4 py-3 text-left text-slate-950 transition hover:-translate-y-px hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
              <el-icon class="text-base text-indigo-700">
                <component :is="action.icon" />
              </el-icon>
              <span>{{ action.label }}</span>
            </button>
          </div>

          <div class="mt-4 flex gap-3 rounded-[18px] bg-amber-100/80 p-4 text-amber-800">
            <el-icon class="mt-0.5">
              <WarningFilled />
            </el-icon>
            <div>
              <strong class="block">{{ dashboardState.stats.low_stock_items }} low stock alerts</strong>
              <p class="m-0 mt-1 leading-6">Variants are close to the reorder threshold.</p>
            </div>
          </div>

          <div class="mt-4 rounded-[18px] border border-slate-200 bg-slate-50 p-4">
            <p class="m-0 mb-3 text-[0.72rem] font-bold uppercase tracking-[0.14em] text-slate-500">
              Order pipeline
            </p>
            <div class="space-y-2">
              <div v-for="item in dashboardState.status_breakdown" :key="item.status"
                class="flex items-center justify-between gap-3 text-sm">
                <span class="capitalize text-slate-600">{{ item.status }}</span>
                <strong class="text-slate-950">{{ item.count }}</strong>
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
        <article
          class="overflow-hidden rounded-[24px] border border-slate-200 bg-white/90 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="px-5 pb-4 pt-5">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
              Orders
            </p>
            <h3 class="m-0 text-[1.25rem] font-semibold text-slate-950">Recent checkout activity</h3>
          </div>

          <el-table :data="dashboardState.recent_orders" class="w-full" :header-cell-style="{
            background: '#f8fafc',
            color: '#64748b',
            fontWeight: '700',
          }" :cell-style="{ background: 'transparent' }">
            <el-table-column prop="id" label="Order" width="100" />
            <el-table-column prop="customer" label="Customer" />
            <el-table-column prop="item_count" label="Items" width="90" />
            <el-table-column prop="amount" label="Total" width="120">
              <template #default="{ row }">
                {{ formatMoney(row.amount) }}
              </template>
            </el-table-column>
            <el-table-column label="Status" width="130">
              <template #default="{ row }">
                <el-tag :type="statusType(row.status)" effect="light">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </article>

        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
              Catalog mix
            </p>
            <h3 class="m-0 text-[1.25rem] font-semibold text-slate-950">Top categories</h3>
          </div>

          <div class="space-y-4">
            <div v-for="category in dashboardState.top_categories" :key="category.id"
              class="grid grid-cols-[minmax(0,1fr)_minmax(140px,1.4fr)_auto] items-center gap-3">
              <div>
                <strong class="block text-slate-950">{{ category.name }}</strong>
                <span class="block leading-6 text-slate-500">{{ category.product_count }} products</span>
              </div>
              <div class="h-2.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500"
                  :style="{ width: `${category.share}%` }"></div>
              </div>
              <span class="text-sm font-bold text-slate-700">{{ category.share }}%</span>
            </div>
          </div>

          <div class="mt-5 border-t border-slate-200 pt-4">
            <div class="mb-3">
              <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-indigo-600">
                Activity
              </p>
              <h3 class="m-0 text-[1.25rem] font-semibold text-slate-950">Latest updates</h3>
            </div>

            <div class="space-y-4">
              <article v-for="item in dashboardState.activity" :key="item.title" class="flex gap-3">
                <div
                  class="mt-1 h-2.5 w-2.5 rounded-full bg-gradient-to-b from-indigo-600 to-cyan-500 shadow-[0_0_0_6px_rgba(79,70,229,0.08)]">
                </div>
                <div>
                  <strong class="block text-slate-950">{{ item.title }}</strong>
                  <p class="m-0 leading-6 text-slate-500">{{ item.detail }}</p>
                  <span class="block text-sm text-slate-500">{{ formatDateTime(item.meta) }}</span>
                </div>
              </article>
            </div>
          </div>
        </article>
      </section>
    </template>
  </div>
</template>
