import { storeToRefs } from 'pinia'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

export type AdminDashboardSummary = {
  stats: {
    revenue_today: number
    revenue_this_week: number
    pending_orders: number
    active_products: number
    low_stock_items: number
    customers: number
  }
  trend: Array<{
    date: string
    total: number
  }>
  status_breakdown: Array<{
    status: string
    count: number
  }>
  recent_orders: Array<{
    id: number
    customer: string
    status: string
    payment_state: string
    amount: number
    item_count: number
    updated_at: string | null
  }>
  top_categories: Array<{
    id: number
    name: string
    product_count: number
    share: number
  }>
  low_stock_items: Array<{
    id: number
    product_id: number
    product_name: string
    size: string | null
    color: string | null
    stock_quantity: number
    sell_price: number
  }>
  activity: Array<{
    type: string
    title: string
    detail: string
    meta: string | null
  }>
  generated_at: string
}

export const useAdminDashboard = () => {
  const config = useRuntimeConfig()
  const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
  const adminAuthStore = useAdminAuthStore()
  const { accessToken } = storeToRefs(adminAuthStore)

  const fetchDashboard = async () => {
    const token = accessToken.value
    const response: any = await $fetch(`${apiBase}/admin/dashboard`, {
      method: 'GET',
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    })

    return (response?.data || null) as AdminDashboardSummary | null
  }

  const { data, pending, error, refresh } = useAsyncData<AdminDashboardSummary | null>(
    'admin-dashboard',
    fetchDashboard,
    {
      server: false,
      immediate: true,
      watch: [accessToken],
      getCachedData: () => undefined,
    }
  )

  return {
    dashboard: data,
    pending,
    error,
    refresh,
  }
}
