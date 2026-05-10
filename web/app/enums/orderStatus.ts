export const orderStatus = {
  ORDER_CONFIRMING: "order_confirming",
  PAYMENT_CONFRIMED: "payment_confirmed",
  PROCESSING: "processing",
  SHIPPED: "shipped",
  DELIVERED: "delivered",
  COMPLETED: "completed",
  CANCELLED: "cancelled",
  REFUNDED: "refunded",
} as const;

type OrderStatusValue = (typeof orderStatus)[keyof typeof orderStatus];

export const OrderStatusList: {
  id: OrderStatusValue;
  label: string;
}[] = [
  { id: orderStatus.ORDER_CONFIRMING, label: "Order Confirming" },
  { id: orderStatus.PAYMENT_CONFRIMED, label: "Payment Confirmed" },
  { id: orderStatus.SHIPPED, label: "Shipped" },
  { id: orderStatus.DELIVERED, label: "Delivered" },
  { id: orderStatus.COMPLETED, label: "Completed" },
  { id: orderStatus.DELIVERED, label: "Order Cancelled" },
  { id: orderStatus.REFUNDED, label: "Order Refunded" },
];
