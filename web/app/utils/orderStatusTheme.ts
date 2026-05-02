export type ElTagType = "success" | "warning" | "danger" | "info" | "primary";

const normalizeStatus = (status?: string | null) =>
  String(status || "").toLowerCase();

export const getPaymentStatusTagType = (status?: string | null): ElTagType => {
  switch (normalizeStatus(status)) {
    case "paid":
      return "success";
    case "pending":
      return "warning";
    case "failed":
    case "expired":
    case "canceled":
    case "cancelled":
      return "danger";
    case "refunded":
      return "info";
    default:
      return "info";
  }
};

export const getOrderStatusTagType = (status?: string | null): ElTagType => {
  switch (normalizeStatus(status)) {
    case "order_confirming":
      return "warning";
    case "payment_confirmed":
      return "info";
    case "processing":
      return "primary";
    case "shipped":
      return "warning";
    case "delivered":
    case "completed":
      return "success";
    case "cancelled":
      return "danger";
    case "refunded":
      return "info";
    default:
      return "info";
  }
};
