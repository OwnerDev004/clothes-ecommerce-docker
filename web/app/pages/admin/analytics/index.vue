<script setup lang="ts">
import {
  Box,
  Coin,
  Goods,
  Histogram,
  ShoppingCart,
  Tickets,
  TrendCharts,
  User,
  WarningFilled,
} from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import { useAdminAnalytics } from '~/composables/useAdminAnalytics'
import type { AdminDashboardSummary } from '~/composables/useAdminDashboard'

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

const { analytics, pending, error, refresh } = useAdminAnalytics()

const analyticsState = computed<AdminDashboardSummary>(() => {
  return (
    analytics.value || {
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

const formatDateTime = (value: string | null) => {
  if (!value) return 'Just now'

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  }).format(new Date(value))
}

const formatDateLabel = (value: string) =>
  new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(new Date(value))

const orderStatusType = (status: string) => {
  const normalized = status.toLowerCase()

  if (normalized === 'paid' || normalized === 'completed') return 'success'
  if (normalized === 'shipped') return 'warning'
  if (normalized === 'processing' || normalized === 'pending') return 'primary'
  if (normalized === 'canceled' || normalized === 'cancelled' || normalized === 'refunded') return 'info'

  return 'info'
}

const trendMax = computed(() => Math.max(...analyticsState.value.trend.map((item) => item.total), 1))
const statusMax = computed(() => Math.max(...analyticsState.value.status_breakdown.map((item) => item.count), 1))
const activeShare = computed(() => {
  const total = analyticsState.value.status_breakdown.reduce((sum, item) => sum + item.count, 0)

  if (!total) return 0

  const completed = analyticsState.value.status_breakdown.find((item) => {
    const status = item.status.toLowerCase()
    return status === 'completed' || status === 'paid'
  })?.count || 0

  return Math.round((completed / total) * 100)
})

const statCards = computed(() => [
  {
    label: 'Revenue today',
    value: formatMoney(analyticsState.value.stats.revenue_today),
    note: 'Orders paid today',
    icon: Coin,
  },
  {
    label: 'Revenue this week',
    value: formatMoney(analyticsState.value.stats.revenue_this_week),
    note: 'Seven-day revenue total',
    icon: TrendCharts,
  },
  {
    label: 'Orders pending',
    value: String(analyticsState.value.stats.pending_orders),
    note: 'Needs fulfillment or review',
    icon: ShoppingCart,
  },
  {
    label: 'Customers',
    value: String(analyticsState.value.stats.customers),
    note: 'Registered customer accounts',
    icon: User,
  },
  {
    label: 'Active products',
    value: String(analyticsState.value.stats.active_products),
    note: 'Available for purchase',
    icon: Box,
  },
  {
    label: 'Low stock',
    value: String(analyticsState.value.stats.low_stock_items),
    note: 'Variants close to reorder',
    icon: WarningFilled,
  },
])

const pipelineItems = computed(() => [
  {
    label: 'Sales conversion',
    value: `${activeShare.value}%`,
    detail: 'Share of orders that reached paid or completed status.',
  },
  {
    label: 'Catalog coverage',
    value: `${analyticsState.value.stats.active_products} live`,
    detail: 'Products ready for browsing in the storefront.',
  },
  {
    label: 'Restock pressure',
    value: String(analyticsState.value.stats.low_stock_items),
    detail: 'Variants below threshold that need attention.',
  },
])
</script>

<template>
  <div class="space-y-6 text-slate-900">
    <HeaderBreadCrumb title="Analytics">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item>Analytics</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <section
      class="grid gap-5 rounded-[28px] border border-slate-200 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.14),transparent_20%),linear-gradient(135deg,#ffffff,#ecfeff)] p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] lg:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)] lg:p-7">
      <div>
        <p class="m-0 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
          Analytics
        </p>
        <h1 class="mt-2 max-w-[16ch] text-[clamp(1.9rem,2.8vw,3.3rem)] font-semibold leading-[0.95] text-slate-950">
          See store performance as a moving picture.
        </h1>
        <p class="m-0 mt-4 max-w-[62ch] leading-7 text-slate-500">
          Track revenue, orders, customers, and stock pressure in one focused view so you can spot
          trends without digging through every section of the admin.
        </p>
      </div>

      <div class="grid gap-3 self-start">
        <div class="rounded-[20px] border border-slate-200 bg-white/80 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">Updated</span>
          <strong class="mt-1 block text-lg text-slate-950">
            {{ analyticsState.generated_at ? formatDateTime(analyticsState.generated_at) : 'Loading...' }}
          </strong>
        </div>
        <div class="rounded-[20px] border border-slate-200 bg-white/80 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">Weekly revenue</span>
          <strong class="mt-1 block text-lg text-slate-950">
            {{ formatMoney(analyticsState.stats.revenue_this_week) }}
          </strong>
        </div>
        <div class="rounded-[20px] border border-slate-200 bg-white/80 p-4 shadow-[0_14px_40px_rgba(15,23,42,0.06)]">
          <span class="block text-sm text-slate-500">Orders pending</span>
          <strong class="mt-1 block text-lg text-slate-950">
            {{ analyticsState.stats.pending_orders }}
          </strong>
        </div>
      </div>
    </section>

    <section v-if="error" class="rounded-[24px] border border-red-200 bg-red-50 p-5 text-red-700">
      <div class="flex items-center justify-between gap-4">
        <div>
          <strong class="block">Analytics failed to load.</strong>
          <p class="m-0 mt-1 text-sm">{{ error?.message || 'Please try again.' }}</p>
        </div>
        <BaseButton
          class="rounded-2xl bg-danger px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
          @click="refresh">
          Retry
        </BaseButton>
      </div>
    </section>

    <section v-if="pending" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <div v-for="item in 6" :key="item" class="h-[170px] animate-pulse rounded-[24px] bg-white/70"></div>
    </section>

    <template v-else>
      <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="card in statCards" :key="card.label"
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="flex items-center justify-between gap-3">
            <div
              class="grid h-11 w-11 place-items-center rounded-[16px] bg-[linear-gradient(135deg,#ecfeff,#cffafe)] text-cyan-700">
              <el-icon>
                <component :is="card.icon" />
              </el-icon>
            </div>
            <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-[0.75rem] font-bold text-cyan-700">
              Live
            </span>
          </div>
          <p class="m-0 mt-4 text-sm text-slate-500">{{ card.label }}</p>
          <strong class="mt-2 block text-[2rem] leading-none text-slate-950">{{ card.value }}</strong>
          <span class="mt-2 block text-sm leading-6 text-slate-500">{{ card.note }}</span>
        </article>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5 flex items-start justify-between gap-4">
            <div>
              <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
                Revenue pulse
              </p>
              <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Weekly performance</h2>
            </div>
            <div class="text-right">
              <span class="block text-sm text-slate-500">This week</span>
              <strong class="text-[1.15rem] text-slate-950">
                {{ formatMoney(analyticsState.stats.revenue_this_week) }}
              </strong>
            </div>
          </div>

          <div class="grid h-[240px] grid-cols-7 items-end gap-3 pb-4">
            <div v-for="bar in analyticsState.trend" :key="bar.date" class="grid justify-items-center gap-2">
              <div
                class="flex h-[200px] w-full items-end overflow-hidden rounded-[18px] bg-gradient-to-b from-slate-50 to-cyan-50">
                <div
                  class="w-full rounded-t-[18px] bg-gradient-to-b from-cyan-600 to-blue-600 shadow-[0_12px_30px_rgba(8,145,178,0.26)]"
                  :style="{ height: `${Math.max((bar.total / trendMax) * 100, 4)}%` }"></div>
              </div>
              <span class="text-[0.82rem] text-slate-500">{{ formatDateLabel(bar.date) }}</span>
            </div>
          </div>

          <div class="grid gap-3 md:grid-cols-3">
            <div v-for="item in pipelineItems" :key="item.label" class="rounded-[18px] bg-slate-50 p-4">
              <p class="m-0 text-xs uppercase tracking-[0.12em] text-slate-500">{{ item.label }}</p>
              <strong class="mt-2 block text-lg text-slate-950">{{ item.value }}</strong>
              <p class="m-0 mt-1 text-sm leading-6 text-slate-500">{{ item.detail }}</p>
            </div>
          </div>
        </article>

        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-4">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
              Order pipeline
            </p>
            <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Status breakdown</h2>
          </div>

          <div class="space-y-4">
            <div v-for="item in analyticsState.status_breakdown" :key="item.status"
              class="space-y-2 rounded-[18px] bg-slate-50 p-4">
              <div class="flex items-center justify-between gap-3">
                <span class="capitalize text-slate-600">{{ item.status }}</span>
                <strong class="text-slate-950">{{ item.count }}</strong>
              </div>
              <div class="h-2.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-600 to-blue-500"
                  :style="{ width: `${Math.max((item.count / statusMax) * 100, 4)}%` }"></div>
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <article
          class="overflow-hidden rounded-[24px] border border-slate-200 bg-white/90 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="px-5 pb-4 pt-5">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
              Orders
            </p>
            <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Recent checkout activity</h2>
          </div>

          <el-table :data="analyticsState.recent_orders" class="w-full" :header-cell-style="{
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
                <el-tag :type="orderStatusType(row.status)" effect="light">
                  {{ row.status }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </article>

        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
              Watchlist
            </p>
            <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Low stock items</h2>
          </div>

          <div class="space-y-3">
            <div v-for="item in analyticsState.low_stock_items" :key="item.id"
              class="rounded-[18px] border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <strong class="block text-slate-950">{{ item.product_name }}</strong>
                  <p class="m-0 mt-1 text-sm text-slate-500">
                    {{ [item.size, item.color].filter(Boolean).join(' • ') || 'Variant' }}
                  </p>
                </div>
                <el-tag type="warning" effect="light">{{ item.stock_quantity }} left</el-tag>
              </div>
              <div class="mt-3 flex items-center justify-between text-sm text-slate-500">
                <span>{{ formatMoney(item.sell_price) }}</span>
                <span>Product #{{ item.product_id }}</span>
              </div>
            </div>

            <div v-if="!analyticsState.low_stock_items.length"
              class="rounded-[18px] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
              No low stock alerts right now.
            </div>
          </div>
        </article>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.9fr)]">
        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5 flex items-center justify-between gap-3">
            <div>
              <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
                Catalog mix
              </p>
              <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Top categories</h2>
            </div>
            <el-tag effect="light">{{ analyticsState.top_categories.length }} categories</el-tag>
          </div>

          <div class="space-y-4">
            <div v-for="category in analyticsState.top_categories" :key="category.id"
              class="grid grid-cols-[minmax(0,1fr)_minmax(140px,1.4fr)_auto] items-center gap-3">
              <div>
                <strong class="block text-slate-950">{{ category.name }}</strong>
                <span class="block leading-6 text-slate-500">{{ category.product_count }} products</span>
              </div>
              <div class="h-2.5 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-600 to-blue-500"
                  :style="{ width: `${category.share}%` }"></div>
              </div>
              <span class="text-sm font-bold text-slate-700">{{ category.share }}%</span>
            </div>
          </div>
        </article>

        <article
          class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
          <div class="mb-5">
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
              Activity
            </p>
            <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Latest updates</h2>
          </div>

          <div class="space-y-4">
            <article v-for="item in analyticsState.activity" :key="item.title" class="flex gap-3">
              <div
                class="mt-1 h-2.5 w-2.5 rounded-full bg-gradient-to-b from-cyan-600 to-blue-500 shadow-[0_0_0_6px_rgba(8,145,178,0.08)]">
              </div>
              <div>
                <strong class="block text-slate-950">{{ item.title }}</strong>
                <p class="m-0 leading-6 text-slate-500">{{ item.detail }}</p>
                <span class="block text-sm text-slate-500">{{ formatDateTime(item.meta) }}</span>
              </div>
            </article>
          </div>
        </article>
      </section>

      <section class="rounded-[24px] border border-slate-200 bg-white/90 p-5 shadow-[0_20px_50px_rgba(15,23,42,0.07)]">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <p class="m-0 mb-2 text-[0.75rem] font-extrabold uppercase tracking-[0.16em] text-cyan-700">
              Admin shortcuts
            </p>
            <h2 class="m-0 text-[1.25rem] font-semibold text-slate-950">Move faster from here</h2>
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <NuxtLink to="/admin/products"
            class="flex items-center gap-3 rounded-[18px] border border-slate-200 bg-slate-50 px-4 py-3 text-slate-950 transition hover:-translate-y-px hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
            <el-icon class="text-base text-cyan-700">
              <Goods />
            </el-icon>
            <span>Manage products</span>
          </NuxtLink>
          <NuxtLink to="/admin/categories"
            class="flex items-center gap-3 rounded-[18px] border border-slate-200 bg-slate-50 px-4 py-3 text-slate-950 transition hover:-translate-y-px hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
            <el-icon class="text-base text-cyan-700">
              <Histogram />
            </el-icon>
            <span>Review categories</span>
          </NuxtLink>
          <NuxtLink to="/admin/customers"
            class="flex items-center gap-3 rounded-[18px] border border-slate-200 bg-slate-50 px-4 py-3 text-slate-950 transition hover:-translate-y-px hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
            <el-icon class="text-base text-cyan-700">
              <User />
            </el-icon>
            <span>Customer support</span>
          </NuxtLink>
          <NuxtLink to="/admin/promotions"
            class="flex items-center gap-3 rounded-[18px] border border-slate-200 bg-slate-50 px-4 py-3 text-slate-950 transition hover:-translate-y-px hover:shadow-[0_10px_24px_rgba(15,23,42,0.08)]">
            <el-icon class="text-base text-cyan-700">
              <Tickets />
            </el-icon>
            <span>Launch promotions</span>
          </NuxtLink>
        </div>
      </section>
    </template>
  </div>
</template>
