import { ElMessage } from "element-plus";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import { useOrderRealtimeStore } from "~/stores/orderRealtimeStore";
import { formatAnyDate } from "~/utils/date";
import type {
  AdminOrderItem,
  AdminOrderRecord,
} from "~/composables/useAdminOrders";

type OrderTimelineKey =
  | "order_confirming"
  | "payment_confirmed"
  | "processing"
  | "shipping"
  | "delivered";

export type OrderTimelineState = "done" | "current" | "upcoming";

export type OrderTimelineStep = {
  key: OrderTimelineKey;
  title: string;
  description: string;
  time: string | null;
  actor?: string | null;
  state: OrderTimelineState;
  label: string;
};

export type AdminOrderProductRow = {
  id: number | string;
  name: string;
  size: string;
  color: string;
  qty: number;
  price: number;
  amount: number;
  thumbnail: string;
  preview_images: string[];
};

export type AdminOrderDetailSummary = {
  order_id: string;
  order_detail: string;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  payment_status: string;
  status: string;
  payment_method: string;
  payment_provider: string;
  shipping_address: string;
  shipping_phone: string;
  subtotal_price: number;
  discount_amount: number;
  shipping_fee: number;
  total_price: number;
  created_at: string;
};
export type refundOrderPayload = {
  id: number | string | undefined;
  order_note: string;
  status: string;
};

const buildDefaultSummary = (): AdminOrderDetailSummary => ({
  order_id: "",
  order_detail: "",
  customer_name: "",
  customer_email: "",
  customer_phone: "",
  payment_status: "",
  status: "",
  payment_method: "",
  payment_provider: "",
  shipping_address: "",
  shipping_phone: "",
  subtotal_price: 0,
  discount_amount: 0,
  shipping_fee: 0,
  total_price: 0,
  created_at: "",
});

const normalizeMoney = (value: unknown) => Number(value || 0);

const resolveCustomer = (order: any) => {
  const customer = order?.customer;

  if (!customer || typeof customer !== "object") {
    return {
      name: String(order?.customer_name || order?.customer || ""),
      email: "",
      phone: "",
    };
  }

  return {
    name:
      customer.full_name ||
      customer.user_name ||
      customer.email ||
      `Customer #${order?.customer_id ?? order?.id}`,
    email: customer.email || "",
    phone: customer.phone || "",
  };
};

const resolveStatusIndex = (status: string) => {
  switch (status) {
    case "pending":
      return 0;
    case "paid":
      return 1;
    case "processing":
      return 2;
    case "shipping":
    case "shipped":
    case "delivering":
      return 3;
    case "delivered":
    case "completed":
      return 4;
    default:
      return 0;
  }
};

const resolveProgressIndex = (order: any) => {
  const status = String(order?.status || "order_confirming").toLowerCase();

  const paymentState = String(order?.payment_status || "pending").toLowerCase();

  const statusIndex = resolveStatusIndex(status);

  const paymentIndex = paymentState === "paid" ? 1 : 0;

  return Math.max(statusIndex, paymentIndex);
};

const getStepState = (
  stepIndex: number,
  currentIndex: number,
): OrderTimelineState => {
  if (stepIndex < currentIndex && currentIndex !== 4) return "done";
  if (stepIndex === currentIndex && currentIndex !== 4) return "current";
  if (currentIndex === 4) return "done";

  return "upcoming";
};

const buildTimeline = (order: any): OrderTimelineStep[] => {
  const status = String(order?.status || "order_confirming").toLowerCase();

  const paymentState = String(order?.payment_status || "pending").toLowerCase();

  const currentIndex = resolveProgressIndex(order);

  const createdAt = formatAnyDate(
    order?.created_at || order?.order_date,
    "MMM D, YYYY h:mm A",
    "en-US",
    "",
  );

  return [
    {
      key: "order_confirming",
      title: "Order Confirming",
      description: "The order has been created and is waiting for review.",
      time: createdAt || null,
      actor: "System",
      state: getStepState(0, currentIndex),
      label: currentIndex >= 0 ? "Done" : "Next",
    },
    {
      key: "payment_confirmed",
      title: "Payment Confirmed",
      description: "Payment has been confirmed and the order can move forward.",
      time: paymentState === "paid" ? createdAt || null : null,
      actor: paymentState === "paid" ? "Payment received" : "Awaiting payment",
      state: getStepState(1, currentIndex),
      label: getStepState(1, currentIndex) === "upcoming" ? "Next" : "Live",
    },
    {
      key: "processing",
      title: "Processing",
      description: "The warehouse is preparing items for fulfillment.",
      time: ["processing", "shipped", "delivered"].includes(status)
        ? createdAt || null
        : null,
      actor: ["processing", "shipped", "delivered"].includes(status)
        ? "Warehouse team"
        : "Pending fulfillment",
      state: getStepState(2, currentIndex),
      label: getStepState(2, currentIndex) === "upcoming" ? "Next" : "Live",
    },
    {
      key: "shipping",
      title: "Shipping",
      description: "The order has been packed and handed to the courier.",
      time: ["shipped", "delivered"].includes(status)
        ? createdAt || null
        : null,
      actor: ["shipped", "delivered"].includes(status)
        ? "Courier"
        : "Waiting for shipment",
      state: getStepState(3, currentIndex),
      label: getStepState(3, currentIndex) === "upcoming" ? "Next" : "Live",
    },
    {
      key: "delivered",
      title: "Delivered",
      description: "The order reached the customer and is fully completed.",
      time: ["delivered", "completed"].includes(status)
        ? createdAt || null
        : null,
      actor: ["delivered", "completed"].includes(status)
        ? "Customer received"
        : "Pending delivery",
      state: getStepState(4, currentIndex),
      label: ["delivered", "completed"].includes(status) ? "Done" : "Later",
    },
  ];
};

const normalizeProducts = (
  items: AdminOrderItem[] | undefined,
): AdminOrderProductRow[] => {
  return (items || []).map((item) => {
    const thumbnail = item.variant?.product?.thumbnail?.image_url || "";
    const preview_images = (item.variant?.product?.images || [])
      .map((image) => String(image?.image_url || ""))
      .filter(Boolean);
    const variant = item.variant || {};
    const productName = variant.product?.name || "Product";
    const size = variant.size?.name || "-";
    const color = variant.color || "-";
    const qty = Number(item.quantity || 0);
    const price = normalizeMoney(item.unit_price ?? item.total_price ?? 0);
    const amount = normalizeMoney(item.total_price ?? 0);

    return {
      id: item.id,
      name: productName,
      size,
      color,
      qty,
      price,
      amount,
      thumbnail,
      preview_images,
    };
  });
};

const normalizeOrder = (order: any): AdminOrderRecord => {
  const items = Array.isArray(order?.items) ? order.items : [];
  const totalPrice = normalizeMoney(order?.total_price);
  const customer = resolveCustomer(order);

  return {
    id: order?.id,
    order_id: `#${order?.id}`,
    customer: customer.name,
    total: totalPrice,
    payment_status: String(order?.payment_status || "pending"),
    status: String(order?.status || "order_confirming"),
    items: items.length,
    delivery_number: String(order?.payment_reference || `#${order?.id}`),
    created_at: String(order?.created_at || order?.order_date || ""),
    order_date: order?.order_date ?? null,
    subtotal_price: order?.subtotal_price ?? null,
    discount_amount: order?.discount_amount ?? null,
    shipping_fee: order?.shipping_fee ?? null,
    shipping_address: order?.shipping_address ?? null,
    shipping_phone: order?.shipping_phone ?? null,
    shipping_province: order?.shipping_province ?? null,
    order_note: order?.order_note ?? null,
    payment_method: order?.payment_method ?? null,
    payment_provider: order?.payment_provider ?? null,
    customer_id: order?.customer_id ?? null,
    customer_details: {
      id: order?.customer?.id ?? order?.customer_id ?? null,
      full_name: customer.name,
      user_name: order?.customer?.user_name ?? null,
      email: customer.email,
      phone: customer.phone,
      address: order?.customer?.address ?? order?.shipping_address ?? null,
    },
    items_detail: items,
    payment_transactions: Array.isArray(order?.paymentTransactions)
      ? order.paymentTransactions
      : Array.isArray(order?.payment_transactions)
        ? order.payment_transactions
        : [],
  };
};

export const useAdminOrderDetail = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const pending = ref(false);

  const error = ref<Error | null>(null);
  const selectedOrder = ref<AdminOrderRecord | null>(null);
  const order_summary = ref<AdminOrderDetailSummary>(buildDefaultSummary());
  const products_order = ref<AdminOrderProductRow[]>([]);
  const order_timeline = ref<OrderTimelineStep[]>([]);
  const realtimeStore = useOrderRealtimeStore();

  //   route
  const route = useRoute();
  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  const syncDerivedState = (order: any) => {
    console.log(order);

    const customer = resolveCustomer(order);
    const subtotal = normalizeMoney(order?.subtotal_price);
    const discount = normalizeMoney(order?.discount_amount);
    const shippingFee = normalizeMoney(order?.shipping_fee);
    const totalPrice = normalizeMoney(
      order?.total_price || subtotal - discount + shippingFee,
    );

    order_summary.value = {
      order_id: `#${order?.id || ""}`,
      order_detail: `Order ${order?.id || ""}`,
      customer_name: customer.name,
      customer_email: customer.email,
      customer_phone: customer.phone,
      payment_status: String(order?.payment_status || "pending"),
      status: String(order?.status || "order_confirming"),
      payment_method: String(order?.payment_method || ""),
      payment_provider: String(order?.payment_provider || ""),
      shipping_address: String(
        order?.shipping_address || order?.customer?.address || "",
      ),
      shipping_phone: String(order?.shipping_phone || customer.phone || ""),
      subtotal_price: subtotal,
      discount_amount: discount,
      shipping_fee: shippingFee,
      total_price: totalPrice,
      created_at: formatAnyDate(
        order?.created_at || order?.order_date,
        "MMM D, YYYY h:mm A",
        "en-US",
        "",
      ),
    };

    products_order.value = normalizeProducts(order?.items);
    order_timeline.value = buildTimeline(order);
  };

  const fetchOrderDetail = async (id: number | string) => {
    if (id === null || id === undefined || id === "") {
      throw new Error("Invalid order id");
    }

    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/orders/${id}`, {
        method: "GET",
        credentials: "include",
        headers: resolveAuthHeaders(),
      });

      const order = response?.data || null;
      selectedOrder.value = order ? normalizeOrder(order) : null;
      if (order) {
        syncDerivedState(order);
      }

      return selectedOrder.value;
    } catch (err: any) {
      error.value = err as Error;
      ElMessage.error(err?.data?.message || "Failed to load order detail.");
      throw err;
    } finally {
      pending.value = false;
    }
  };

  const resetOrderDetail = () => {
    selectedOrder.value = null;
    order_summary.value = buildDefaultSummary();
    products_order.value = [];
    order_timeline.value = [];
    error.value = null;
  };

  const refreshOrderDetail = async (id: number | string) => {
    await fetchOrderDetail(id);
  };

  // refundOrder
  const refundOrder = async (payload: refundOrderPayload) => {
    const id = payload.id ?? null;
    const status = payload.status ?? null;
    const order_note = payload.order_note ?? null;
    if (id === "" || id === null || id === undefined) {
      throw new Error("Invalid order id");
    }

    if (!status) {
      throw new Error("Missing order status");
    }

    try {
      //Parallel
      await Promise.all([
        $fetch(`${apiBase}/admin/orders/${id}/status`, {
          method: "PATCH",
          credentials: "include",
          headers: resolveAuthHeaders(),
          body: { status: status },
        }),
        $fetch(`${apiBase}/admin/orders/${id}`, {
          method: "PATCH",
          credentials: "include",
          headers: resolveAuthHeaders(),
          body: {
            order_note: order_note,
          },
        }),
      ]);

      ElMessage.success("Order refunded.");
    } catch (error) {
      ElMessage.error("Failed to refund order.");
      throw new Error(`${error}`);
    } finally {
    }
  };

  // cancelOrder
  const cancelOrder = async (payload: refundOrderPayload) => {
    const status = payload.status ?? null;

    if (payload.id == "" || payload.id == null || payload.id == undefined) {
      throw new Error("Invalid order id");
    }
    if (payload.status == "") {
      throw new Error("Status data missing");
    }
    pending.value = true;

    try {
      await Promise.all([
        $fetch(`${apiBase}/admin/orders/${payload?.id}/status`, {
          method: "PATCH",
          headers: resolveAuthHeaders(),
          credentials: "include",
          body: { status: status },
        }),
        $fetch(`${apiBase}/admin/orders/${payload?.id}`, {
          method: "PATCH",
          headers: resolveAuthHeaders(),
          credentials: "include",
          body: { order_note: payload?.order_note },
        }),
      ]);
    } catch (error) {
      ElMessage.error("Failed to refund order.");
      throw new Error(`${error}`);
    } finally {
      pending.value = false;
    }
    //Parallel Request //Sequential Request // Server-Side Delegation
  };

  const detailOrderId = computed(() => {
    if (!route.path.includes("/admin/orders/detail")) {
      return null;
    }

    const value = route.params.id;
    return Array.isArray(value) ? (value[0] ?? null) : (value ?? null);
  });

  watch(
    detailOrderId,
    (id) => {
      if (!id) {
        selectedOrder.value = null;
        return;
      }

      void fetchOrderDetail(id);
    },
    { immediate: true },
  );

  watch(
    () => realtimeStore.adminAlertTick,
    () => {
      if (!detailOrderId.value) {
        return;
      }

      void fetchOrderDetail(detailOrderId.value);
    },
  );
  return {
    error,
    pending,
    selectedOrder,
    order_summary,
    products_order,
    order_timeline,
    refundOrder,
    cancelOrder,
    fetchOrderDetail,
    refreshOrderDetail,
    resetOrderDetail,
  };
};
