import { defineStore } from 'pinia'
import { ref } from 'vue'

export type OrderRealtimeAlert = {
  kind: 'admin.order_alert' | 'customer.order_alert'
  event_type: string
  title: string
  message: string
  order: {
    id: number | string
    order_id: string
    customer_id?: number | string | null
    customer?: string
    status?: string | null
    payment_status?: string | null
    total?: number
    created_at?: string | null
    event_type?: string
  }
}

export const useOrderRealtimeStore = defineStore('order-realtime', () => {
  const adminAlertTick = ref(0)
  const customerAlertTick = ref(0)
  const lastAdminAlert = ref<OrderRealtimeAlert | null>(null)
  const lastCustomerAlert = ref<OrderRealtimeAlert | null>(null)

  const pushAdminAlert = (payload: OrderRealtimeAlert) => {
    lastAdminAlert.value = payload
    adminAlertTick.value += 1
  }

  const pushCustomerAlert = (payload: OrderRealtimeAlert) => {
    lastCustomerAlert.value = payload
    customerAlertTick.value += 1
  }

  return {
    adminAlertTick,
    customerAlertTick,
    lastAdminAlert,
    lastCustomerAlert,
    pushAdminAlert,
    pushCustomerAlert,
  }
})
