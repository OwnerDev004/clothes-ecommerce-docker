import { watchDebounced } from "@vueuse/core";
import { lowerCase } from "lodash";
import { ElMessage } from "element-plus";
import { computed, reactive, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import { useOrderRealtimeStore } from "~/stores/orderRealtimeStore";
export type AdminOrderItem = {
  id: number | string;
  quantity: number;
  total_price: number | string;
  unit_price?: number | string;
  variant?: {
    id?: number | string;
    sku?: string | null;
    color?: string | null;
    size?: {
      id?: number | string;
      name?: string | null;
    } | null;
    product?: {
      id?: number | string;
      name?: string | null;
      slug?: string | null;
      images?: Record<string, string>[];
      thumbnail?: Record<string, string>;
    } | null;
  } | null;
};

export type AdminOrderRecord = {
  id: number | string;
  order_id: string;
  customer: string;
  total: number | string;
  payment_status: string;
  status?: string | null;
  items: number;
  delivery_number: string;
  created_at: string;
  order_date?: string | null;
  subtotal_price?: number | string | null;
  discount_amount?: number | string | null;
  shipping_fee?: number | string | null;
  shipping_address?: string | null;
  shipping_phone?: string | null;
  shipping_province?: string | null;
  payment_method?: string | null;
  payment_provider?: string | null;
  customer_id?: number | string | null;
  order_note?: string | null;
  customer_details?: {
    id?: number | string;
    full_name?: string | null;
    user_name?: string | null;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
  } | null;
  items_detail?: AdminOrderItem[];
  payment_transactions?: Array<{
    id?: number | string;
    provider?: string | null;
    provider_payment_id?: string | null;
    status?: string | null;
    amount?: number | string | null;
    currency?: string | null;
    checkout_url?: string | null;
    created_at?: string | null;
  }>;
};

export type AdminOrderMetaPage = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

type AdminOrderListResponse = {
  data: AdminOrderRecord[];
  meta: AdminOrderMetaPage;
};

export type AdminOrderQueryFilters = {
  search_txt: string;
  status: string;
  payment_status: string;
  customer_id: string | number | null;
  page: number;
  per_page: number;
};

const defaultMetaPage = (): AdminOrderMetaPage => ({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});

const normalizeMoney = (value: unknown) => Number(value || 0);

const resolveCustomerLabel = (order: AdminOrderRecord) => {
  const details = order.customer_details;

  if (details?.full_name) return details.full_name;
  if (details?.user_name) return details.user_name;
  if (details?.email) return details.email;
  if (order.customer) return order.customer;

  return `Customer #${order.customer_id ?? order.id}`;
};

const normalizeOrderRecord = (order: any): AdminOrderRecord => {
  const items: AdminOrderItem[] = Array.isArray(order?.items)
    ? order.items
    : [];
  const customerDetails = order?.customer ?? order?.customer_details ?? null;
  const subtotal = normalizeMoney(order?.subtotal_price);
  const discount = normalizeMoney(order?.discount_amount);
  const shippingFee = normalizeMoney(order?.shipping_fee);
  const total = normalizeMoney(
    order?.total_price || subtotal - discount + shippingFee,
  );
  const customerLabel =
    typeof customerDetails === "string" ? customerDetails : "";

  return {
    id: order?.id,
    order_id: `#${order?.id}`,
    customer: resolveCustomerLabel({
      id: order?.id,
      order_id: `#${order?.id}`,
      customer: customerLabel,
      total: total,
      payment_status: String(order?.payment_status || "pending"),
      items: items.length,
      delivery_number: String(
        order?.payment_reference || order?.delivery_number || `#${order?.id}`,
      ),
      created_at: String(order?.created_at || order?.order_date || ""),
      customer_id: order?.customer_id,
      customer_details: customerDetails,
    }),
    total,
    payment_status: String(order?.payment_status || "pending"),
    status: String(order?.status || "order_confirming"),
    items: items.length,
    delivery_number: String(
      order?.payment_reference || order?.delivery_number || `#${order?.id}`,
    ),
    created_at: String(order?.created_at || order?.order_date || ""),
    order_date: order?.order_date ?? null,
    subtotal_price: order?.subtotal_price ?? null,
    discount_amount: order?.discount_amount ?? null,
    shipping_fee: order?.shipping_fee ?? null,
    shipping_address: order?.shipping_address ?? null,
    shipping_phone: order?.shipping_phone ?? null,
    payment_method: order?.payment_method ?? null,
    payment_provider: order?.payment_provider ?? null,
    order_note: order?.order_note ?? null,
    customer_id: order?.customer_id ?? null,
    customer_details: customerDetails,
    items_detail: items,
    payment_transactions: Array.isArray(order?.payment_transactions)
      ? order.payment_transactions
      : Array.isArray(order?.paymentTransactions)
        ? order.paymentTransactions
        : [],
  };
};

export const useAdminOrders = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const router = useRouter();
  const route = useRoute();
  const { accessToken } = storeToRefs(useAdminAuthStore());
  const realtimeStore = useOrderRealtimeStore();

  const pending = ref(false);
  const detailPending = ref(false);
  const savingStatus = ref(false);
  const error = ref<Error | null>(null);
  const detailError = ref<Error | null>(null);
  const updatingOrderId = ref<number | string | null>(null);

  const filters = reactive<AdminOrderQueryFilters>({
    search_txt: "",
    status: "",
    payment_status: "",
    customer_id: null,
    page: 1,
    per_page: 20,
  });

  const pagination = reactive<AdminOrderMetaPage>(defaultMetaPage());
  const tableData = ref<AdminOrderRecord[]>([]);
  const selectedOrder = ref<AdminOrderRecord | null>(null);

  const orderStatus = [
    { id: "", label: "All Status" },
    { id: "order_confirming", label: "Pending" },
    { id: "payment_confirmed", label: "Paid" },
    { id: "processing", label: "Processing" },
    { id: "shipped", label: "Shipped" },
    { id: "delivered", label: "Delivered" },
    { id: "cancelled", label: "Cancelled" },
    { id: "refunded", label: "Refunded" },
  ];

  const ordersStates = computed(() => {
    const rows = tableData.value;
    const countBy = (matcher: (order: AdminOrderRecord) => boolean) =>
      rows.filter(matcher).length;

    return [
      {
        id: 1,
        title: "Order Confirming",
        amount: countBy((order) => order.status === "order_confirming"),
        icon: "solar:chat-round-money-broken",
      },
      {
        id: 2,
        title: "Order Payment Confirmed",
        amount: countBy((order) => order.status === "payment_confirmed"),
        icon: "solar:cart-cross-broken",
      },
      {
        id: 3,
        title: "Order Processing",
        amount: countBy((order) => order.status === "processing"),
        icon: "solar:box-outline",
      },
      {
        id: 4,
        title: "Order Shipped",
        amount: countBy((order) => order.status === "shipped"),
        icon: "solar:bus-outline",
      },
      {
        id: 5,
        title: "Order Delivered",
        amount: countBy((order) => order.status === "delivered"),
        icon: "solar:clipboard-remove-broken",
      },
      {
        id: 6,
        title: "Pending Payment",
        amount: countBy(
          (order) =>
            order.payment_status === "failed" ||
            order.payment_status === "pending",
        ),
        icon: "solar:clock-circle-broken",
      },
      {
        id: 7,
        title: "Order Cancelled",
        amount: countBy((order) => order.status === "cancelled"),
        icon: "solar:clipboard-check-broken",
      },
      {
        id: 8,
        title: "Order Refunded",
        amount: countBy((order) => order.status === "refunded"),
        icon: "solar:inbox-archive-broken",
      },
    ];
  });

  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  const buildQuery = () => {
    const query: Record<string, string | number> = {
      page: filters.page,
      per_page: filters.per_page,
    };

    const add = (key: string, value: string | number | null | undefined) => {
      if (value !== null && value !== undefined && value !== "") {
        query[key] = value;
      }
    };

    add("search_txt", lowerCase(filters.search_txt).trim());
    add("status", filters.status);
    add("payment_status", filters.payment_status);
    add("customer_id", filters.customer_id);

    return query;
  };

  const applyPaginatedResponse = (response: any) => {
    const rows = Array.isArray(response?.data) ? response.data : [];
    tableData.value = rows.map(normalizeOrderRecord);

    pagination.current_page = Number(
      response?.meta?.current_page || filters.page || 1,
    );
    pagination.last_page = Number(response?.meta?.last_page || 1);
    pagination.per_page = Number(
      response?.meta?.per_page || filters.per_page || 20,
    );
    pagination.total = Number(response?.meta?.total || 0);
  };

  const fetchOrders = async () => {
    if (!accessToken.value) {
      tableData.value = [];
      pagination.current_page = 1;
      pagination.last_page = 1;
      pagination.per_page = filters.per_page;
      pagination.total = 0;
      return;
    }

    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/orders`, {
        method: "GET",
        credentials: "include",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });

      applyPaginatedResponse(response);
    } catch (err) {
      error.value = err as Error;
      tableData.value = [];
      ElMessage.error("Failed to load orders.");
    } finally {
      pending.value = false;
    }
  };

  const fetchOrderDetail = async (id: number | string) => {
    if (id === "" || id === null || id === undefined) {
      throw new Error("Invalid order id");
    }

    detailPending.value = true;
    detailError.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/orders/${id}`, {
        method: "GET",
        credentials: "include",
        headers: resolveAuthHeaders(),
      });

      const order = normalizeOrderRecord(response?.data || {});
      selectedOrder.value = order;
      return order;
    } catch (err) {
      detailError.value = err as Error;
      ElMessage.error("Failed to load order detail.");
      throw err;
    } finally {
      detailPending.value = false;
    }
  };

  const updateOrderStatus = async (id: number | string, status: string) => {
    if (id === "" || id === null || id === undefined) {
      throw new Error("Invalid order id");
    }

    if (!status) {
      throw new Error("Missing order status");
    }

    savingStatus.value = true;
    updatingOrderId.value = id;

    try {
      const response: any = await $fetch(
        `${apiBase}/admin/orders/${id}/status`,
        {
          method: "PATCH",
          credentials: "include",
          headers: resolveAuthHeaders(),
          body: { status },
        },
      );

      const order = normalizeOrderRecord(response?.data || {});
      selectedOrder.value = order;
      await fetchOrders();
      ElMessage.success("Order status updated.");
      return order;
    } catch (err: any) {
      ElMessage.error(err?.data?.message || "Failed to update order status.");
      throw err;
    } finally {
      savingStatus.value = false;
      updatingOrderId.value = null;
    }
  };

  const viewOrderDetail = async (id: number | string) => {
    await router.push(`/admin/orders/detail/${id}`);
  };

  const resetFilters = () => {
    Object.assign(filters, {
      search_txt: "",
      status: "",
      payment_status: "",
      customer_id: null,
      page: 1,
      per_page: 20,
    });
    void fetchOrders();
  };

  const setPage = (page: number) => {
    filters.page = page;
  };

  watchDebounced(
    [
      () => filters.search_txt,
      () => filters.status,
      () => filters.payment_status,
      () => filters.customer_id,
      () => filters.per_page,
    ],
    () => {
      filters.page = 1;
      void fetchOrders();
    },
    { debounce: 300, maxWait: 600 },
  );

  watch(
    () => filters.page,
    () => {
      void fetchOrders();
    },
  );

  watch(
    () => accessToken.value,
    () => {
      void fetchOrders();
    },
    { immediate: true },
  );

  watch(
    () => realtimeStore.adminAlertTick,
    () => {
      if (accessToken.value) {
        void fetchOrders();
      }
    },
  );

  //   watch order detail page
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

  //watch Order detail page

  return {
    filters,
    pagination,
    pending,
    detailPending,
    savingStatus,
    error,
    detailError,
    tableData,
    selectedOrder,
    orderStatus,
    ordersStates,
    updatingOrderId,
    fetchOrders,
    fetchOrderDetail,
    updateOrderStatus,
    viewOrderDetail,
    resetFilters,
    setPage,
  };
};
