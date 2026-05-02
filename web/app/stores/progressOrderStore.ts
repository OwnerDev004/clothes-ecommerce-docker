import { defineStore } from "pinia";
import { computed, ref } from "vue";

export type OrderStatus =
  | "order_confirming"
  | "payment_confirmed"
  | "processing"
  | "shipped"
  | "delivered";

type BackendOrderStatus =
  | "order_confirming"
  | "payment_confirmed"
  | "processing"
  | "shipped"
  | "delivered";

export type OrderDetail = {
  id: number;
  customer_id: number | string;
  order_date: string;
  total_price: string;
  shipping_province: string;
  shipping_fee: string;
  shipping_address: string | null;
  shipping_phone: string | null;
  payment_method: string;
  payment_status: "pending" | "paid" | string;
  created_at: string;
  updated_at: string;
  subtotal_price: string;
  discount_amount: string;
  status: string;
  voucher_id: number | null;
  payment_provider: string;
  payment_reference: string;
  payment_expires_at: string;
  paid_at: string | null;
  cancelled_at: string | null;
  refunded_at: string | null;
  stock_restored_at: string | null;
  customer_name?: string;
  customer_email?: string;
  items?: any[];
  statusHistories?: any[];
  status_histories?: any[];
};

type OrderSnapshot = Partial<OrderDetail> & {
  [key: string]: any;
};

type ProgressStep = {
  key: OrderStatus;
  displayName: string;
  icon: string;
  buttonText: string;
  canCancel: boolean;
  description: string;
};

const steps: ProgressStep[] = [
  {
    key: "order_confirming",
    displayName: "Order Confirming",
    icon: "⏳",
    buttonText: "Confirm Order",
    canCancel: true,
    description: "Verify payment and order details",
  },
  {
    key: "payment_confirmed",
    displayName: "Payment Confirmed",
    icon: "💰",
    buttonText: "Start Processing",
    canCancel: true,
    description: "Payment verified, ready to pack",
  },
  {
    key: "processing",
    displayName: "Processing",
    icon: "🔵",
    buttonText: "Mark as Shipped",
    canCancel: true,
    description: "Packing the products",
  },
  {
    key: "shipped",
    displayName: "Shipped",
    icon: "🚚",
    buttonText: "Mark as Delivered",
    canCancel: false,
    description: "Order on the way",
  },
  {
    key: "delivered",
    displayName: "Delivered",
    icon: "✅",
    buttonText: "Order Complete",
    canCancel: false,
    description: "Customer received the order",
  },
];

const statusToStep: Record<string, number> = {
  order_confirming: 1,
  pending: 1,
  payment_confirmed: 2,
  paid: 2,
  processing: 3,
  shipping: 4,
  shipped: 4,
  delivering: 4,
  delivered: 5,
  cancelled: 1,
  refunded: 1,
};

const stepToBackendStatus: Record<number, BackendOrderStatus> = {
  1: "order_confirming",
  2: "payment_confirmed",
  3: "processing",
  4: "shipped",
  5: "delivered",
};

const buildEmptyTimestamps = () => ({
  order_confirming_at: null as string | null,
  payment_confirmed_at: null as string | null,
  processing_at: null as string | null,
  shipped_at: null as string | null,
  delivered_at: null as string | null,
});

const normalizeStatus = (value?: string | null) =>
  String(value || "pending").toLowerCase();

const getStatusHistoryList = (order: OrderSnapshot) => {
  return Array.isArray(order.statusHistories)
    ? order.statusHistories
    : Array.isArray(order.status_histories)
      ? order.status_histories
      : [];
};

const getHistoryTimestamp = (
  order: OrderSnapshot,
  targets: string[],
): string | null => {
  const histories = getStatusHistoryList(order);

  for (const history of histories) {
    const statusValue = normalizeStatus(history?.to_status || history?.status);

    if (!targets.includes(statusValue)) {
      continue;
    }

    return (
      history?.created_at ||
      history?.updated_at ||
      history?.occurred_at ||
      history?.timestamp ||
      null
    );
  }

  return null;
};

export const useProgressOrderStore = defineStore(
  "progress-order",
  () => {
    const orderDetail = ref<OrderSnapshot | null>(null);
    const currentStep = ref<number>(1);
    const trackingNumber = ref<string>("");
    const statusTimestamps = ref(buildEmptyTimestamps());

    const resetTimestamps = () => {
      statusTimestamps.value = buildEmptyTimestamps();
    };

    const currentStepObject = computed(() => steps[currentStep.value - 1]);

    const currentStatus = computed<OrderStatus>(() => {
      return steps[currentStep.value - 1]?.key || "order_confirming";
    });

    const currentBackendStatus = computed<BackendOrderStatus>(() => {
      return stepToBackendStatus[currentStep.value] || "order_confirming";
    });

    const progressPercentage = computed(() => {
      return ((currentStep.value - 1) / (steps.length - 1)) * 100;
    });

    const currentButton = computed(() => {
      if (currentStep.value >= steps.length) {
        return { text: "Order Complete", action: null, disabled: true };
      }

      return {
        text: steps[currentStep.value - 1]?.buttonText || "Next",
        action: getNextAction(),
        disabled: false,
      };
    });

    const showCancelButton = computed(() => {
      return steps[currentStep.value - 1]?.canCancel ?? false;
    });

    const currentStepDisplayName = computed(() => {
      return steps[currentStep.value - 1]?.displayName || "Unknown";
    });

    const currentStepIcon = computed(() => {
      return steps[currentStep.value - 1]?.icon || "📦";
    });

    const statusBadgeClass = computed(() => {
      const classes: Record<number, string> = {
        1: "badge-warning",
        2: "badge-info",
        3: "badge-primary",
        4: "badge-warning",
        5: "badge-success",
      };

      return classes[currentStep.value] || "badge-default";
    });

    const statusText = computed(() => {
      const texts: Record<number, string> = {
        1: "Order Confirming ⏳",
        2: "Payment Confirmed ✅",
        3: "Processing 🔵",
        4: "Shipped 🚚",
        5: "Delivered ✅",
      };

      return texts[currentStep.value] || "Unknown";
    });

    const subStatusText = computed(() => {
      const texts: Record<number, string> = {
        1: "Awaiting admin confirmation",
        2: "Payment verified, ready to process",
        3: "Packing in progress",
        4: "In transit with courier",
        5: "Order complete",
      };

      return texts[currentStep.value] || "";
    });

    const isOrderCancellable = computed(() => {
      return currentStep.value <= 3;
    });

    const stepsWithStatus = computed(() => {
      return steps.map((step, index) => ({
        ...step,
        stepNumber: index + 1,
        isCompleted: currentStep.value > index + 1,
        isCurrent: currentStep.value === index + 1,
        isPending: currentStep.value < index + 1,
        timestamp: getTimestampForStep(step.key),
      }));
    });

    const isStepCompleted = (stepNumber: number) => {
      return currentStep.value > stepNumber;
    };

    const isStepCurrent = (stepNumber: number) => {
      return currentStep.value === stepNumber;
    };

    const getTimestampForStep = (stepKey: OrderStatus) => {
      const timestampMap: Record<OrderStatus, string | null> = {
        order_confirming: statusTimestamps.value.order_confirming_at,
        payment_confirmed: statusTimestamps.value.payment_confirmed_at,
        processing: statusTimestamps.value.processing_at,
        shipped: statusTimestamps.value.shipped_at,
        delivered: statusTimestamps.value.delivered_at,
      };

      return timestampMap[stepKey];
    };

    const getNextAction = () => {
      const actions: Record<number, () => void> = {
        1: confirmOrder,
        2: confirmPaid,
        3: confirmProcessing,
        4: confirmShipped,
      };

      return actions[currentStep.value] || (() => {});
    };

    const setTimestampForCurrentStep = () => {
      const now = new Date().toISOString();

      switch (currentStatus.value) {
        case "order_confirming":
          statusTimestamps.value.order_confirming_at = now;
          break;
        case "payment_confirmed":
          statusTimestamps.value.payment_confirmed_at = now;
          break;
        case "processing":
          statusTimestamps.value.processing_at = now;
          break;
        case "shipped":
          statusTimestamps.value.shipped_at = now;
          break;
        case "delivered":
          statusTimestamps.value.delivered_at = now;
          break;
      }
    };

    const initFromOrder = (order: OrderSnapshot) => {
      orderDetail.value = order;
      resetTimestamps();

      const orderStatus = normalizeStatus(order.status);
      currentStep.value = statusToStep[orderStatus] || 1;

      statusTimestamps.value.order_confirming_at =
        order.created_at || order.order_date || null;
      statusTimestamps.value.payment_confirmed_at =
        order.paid_at ||
        getHistoryTimestamp(order, ["paid", "payment_confirmed"]) ||
        null;
      statusTimestamps.value.processing_at =
        getHistoryTimestamp(order, ["processing"]) || null;
      statusTimestamps.value.shipped_at =
        getHistoryTimestamp(order, ["shipping", "shipped", "delivering"]) ||
        null;
      statusTimestamps.value.delivered_at =
        order.refunded_at ||
        getHistoryTimestamp(order, ["delivered", "completed"]) ||
        null;
    };

    const resetStore = () => {
      orderDetail.value = null;
      currentStep.value = 1;
      trackingNumber.value = "";
      resetTimestamps();
    };

    const confirmOrder = async () => {
      if (currentStep.value !== 1) {
        return false;
      }

      currentStep.value = 2;
      statusTimestamps.value.payment_confirmed_at = new Date().toISOString();
      return true;
    };

    const confirmPaid = async () => {
      if (currentStep.value !== 2) {
        return false;
      }

      currentStep.value = 3;
      statusTimestamps.value.processing_at = new Date().toISOString();
      return true;
    };

    const confirmProcessing = async () => {
      if (currentStep.value !== 3) {
        return false;
      }

      currentStep.value = 4;
      statusTimestamps.value.shipped_at = new Date().toISOString();
      return true;
    };

    const confirmShipped = async () => {
      if (currentStep.value !== 4) {
        return false;
      }

      currentStep.value = 5;
      statusTimestamps.value.delivered_at = new Date().toISOString();
      return true;
    };

    const confirmDelivered = () => {
      return currentStep.value === 5;
    };

    const cancelOrder = async () => {
      if (!isOrderCancellable.value) {
        return false;
      }

      currentStep.value = 1;
      return true;
    };

    const setTrackingNumber = (tracking: string) => {
      trackingNumber.value = tracking;
    };

    const setStep = (step: number) => {
      if (step < 1 || step > 5) {
        return;
      }

      currentStep.value = step;
      setTimestampForCurrentStep();
    };

    const nextBackendStatus = computed(() => {
      return stepToBackendStatus[currentStep.value] || "pending";
    });

    return {
      orderDetail,
      currentStep,
      trackingNumber,
      statusTimestamps,
      currentStepObject,
      currentStatus,
      currentBackendStatus,
      nextBackendStatus,
      progressPercentage,
      currentButton,
      showCancelButton,
      currentStepDisplayName,
      currentStepIcon,
      statusBadgeClass,
      statusText,
      subStatusText,
      isOrderCancellable,
      stepsWithStatus,
      isStepCompleted,
      isStepCurrent,
      initFromOrder,
      resetStore,
      confirmOrder,
      confirmPaid,
      confirmProcessing,
      confirmShipped,
      confirmDelivered,
      cancelOrder,
      setTrackingNumber,
      setStep,
    };
  },
  {
    persist: {
      key: "progress_order",
      storage: typeof window !== "undefined" ? window.localStorage : undefined,
    },
  },
);
