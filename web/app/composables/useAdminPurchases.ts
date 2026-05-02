import { watchDebounced } from "@vueuse/core";
import { ElMessage, ElMessageBox } from "element-plus";
import { storeToRefs } from "pinia";
import { computed, reactive, ref, watch } from "vue";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type AdminPurchaseVariantOption = {
  id: number | string;
  label: string;
  product_name: string;
  size: string;
  color: string;
  stock_quantity: number;
  sell_price: number;
  cost_price: number;
};

export type AdminPurchaseRecord = {
  id: number | string;
  product_variant_id: number | string;
  quantity: number;
  cost_price: number;
  total_cost: number;
  note: string | null;
  created_at: string;
  creator?: {
    id?: number | string;
    user_name?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    email?: string | null;
  } | null;
  variant?: {
    id?: number | string;
    color?: string | null;
    stock_quantity?: number | string | null;
    product?: {
      name?: string | null;
    } | null;
    size?: {
      name?: string | null;
    } | null;
  } | null;
};

export type AdminPurchaseSubmitPayload = {
  mode: "create" | "edit";
  purchaseId: number | string | null;
  form: {
    product_variant_id: number | string | null;
    quantity: number;
    cost_price: number;
    note: string;
  };
};

type AdminPurchaseListMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

type AdminPurchaseListResponse = {
  data: AdminPurchaseRecord[];
  meta?: AdminPurchaseListMeta;
};

const normalizeMoney = (value: unknown) => Number(value || 0);

const formatVariantLabel = (variant: any) => {
  const productName = String(variant?.product?.name || "Product");
  const size = String(variant?.size?.name || "-");
  const color = String(variant?.color || "-");
  const stock = Number(variant?.stock_quantity || 0);

  return `${productName} • ${size} • ${color} • stock ${stock}`;
};

const normalizeVariant = (variant: any): AdminPurchaseVariantOption => ({
  id: variant?.id,
  label: formatVariantLabel(variant),
  product_name: String(variant?.product?.name || "Product"),
  size: String(variant?.size?.name || "-"),
  color: String(variant?.color || "-"),
  stock_quantity: Number(variant?.stock_quantity || 0),
  sell_price: normalizeMoney(variant?.sell_price),
  cost_price: normalizeMoney(variant?.cost_price),
});

const normalizePurchase = (purchase: any): AdminPurchaseRecord => ({
  id: purchase?.id,
  product_variant_id: purchase?.product_variant_id,
  quantity: Number(purchase?.quantity || 0),
  cost_price: normalizeMoney(purchase?.cost_price),
  total_cost: normalizeMoney(purchase?.total_cost),
  note: purchase?.note ?? null,
  created_at: String(purchase?.created_at || ""),
  creator: purchase?.creator ?? null,
  variant: purchase?.variant ?? null,
});

export const useAdminPurchases = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const filters = reactive({
    search_txt: "",
    product_variant_id: null as number | string | null,
    page: 1,
    per_page: 10,
  });

  const purchasesResponse = ref<AdminPurchaseListResponse>({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: filters.per_page,
      total: 0,
    },
  });

  const pending = ref(false);
  const error = ref<Error | null>(null);
  const saving = ref(false);
  const deletingId = ref<number | string | null>(null);
  const modalOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const selectedPurchase = ref<AdminPurchaseRecord | null>(null);
  const purchases = ref<AdminPurchaseRecord[]>([]);
  const variantOptions = ref<AdminPurchaseVariantOption[]>([]);

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

    const add = (key: string, value: string | number | null) => {
      if (value !== null && value !== "") {
        query[key] = value;
      }
    };

    add("search_txt", filters.search_txt.trim());
    add("product_variant_id", filters.product_variant_id);

    return query;
  };

  const fetchVariants = async (silent = false) => {
    try {
      const response: any = await $fetch(`${apiBase}/admin/product_variants`, {
        method: "GET",
        headers: resolveAuthHeaders(),
      });

      const rows = Array.isArray(response?.data) ? response.data : response?.data || [];
      variantOptions.value = rows.map(normalizeVariant);
      return variantOptions.value;
    } catch (err: any) {
      if (!silent) {
        ElMessage.error(err?.data?.message || "Failed to load product variants.");
      }

      throw err;
    }
  };

  const fetchPurchases = async (silent = false) => {
    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/purchases`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });

      const rows = Array.isArray(response?.data) ? response.data : [];
      purchasesResponse.value = {
        data: rows.map(normalizePurchase),
        meta: response?.meta || {
          current_page: 1,
          last_page: 1,
          per_page: filters.per_page,
          total: 0,
        },
      };

      purchases.value = purchasesResponse.value.data;
      return purchases.value;
    } catch (err: any) {
      if (!silent) {
        error.value = err as Error;
        purchasesResponse.value = {
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: filters.per_page,
            total: 0,
          },
        };
        purchases.value = [];
        ElMessage.error(err?.data?.message || "Failed to load purchases.");
      }
      throw err;
    } finally {
      pending.value = false;
    }
  };

  const refreshAll = async (silent = false) => {
    await Promise.all([fetchPurchases(silent), fetchVariants(silent)]);
  };

  const resetFilters = () => {
    filters.search_txt = "";
    filters.product_variant_id = null;
    filters.page = 1;
  };

  const openPurchaseModal = () => {
    selectedPurchase.value = null;
    modalMode.value = "create";
    modalOpen.value = true;
  };

  const editPurchase = (purchase: AdminPurchaseRecord) => {
    selectedPurchase.value = purchase;
    modalMode.value = "edit";
    modalOpen.value = true;
  };

  const closePurchaseModal = () => {
    modalOpen.value = false;
    selectedPurchase.value = null;
    modalMode.value = "create";
  };

  const savePurchase = async (payload: AdminPurchaseSubmitPayload) => {
    saving.value = true;

    try {
      const isEdit = payload.mode === "edit" && payload.purchaseId !== null && payload.purchaseId !== undefined;
      const url = isEdit
        ? `${apiBase}/admin/purchases/${payload.purchaseId}`
        : `${apiBase}/admin/purchases`;
      const method = isEdit ? "PUT" : "POST";

      await $fetch(url, {
        method,
        headers: resolveAuthHeaders(),
        body: {
          product_variant_id: payload.form.product_variant_id,
          quantity: payload.form.quantity,
          cost_price: payload.form.cost_price,
          note: payload.form.note || null,
        },
      });

      ElMessage.success(isEdit ? "Purchase updated successfully." : "Purchase created successfully.");
      void refreshAll(true).catch(() => undefined);
      return true;
    } catch (err: any) {
      ElMessage.error(err?.data?.message || "Failed to save purchase.");
      throw err;
    } finally {
      saving.value = false;
    }
  };

  const deletePurchase = async (purchase: AdminPurchaseRecord) => {
    if (!purchase?.id) {
      return;
    }

    try {
      await ElMessageBox.confirm(
        "Delete this purchase and recycle the stock back to the variant?",
        "Confirm delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = purchase.id;

      await $fetch(`${apiBase}/admin/purchases/${purchase.id}`, {
        method: "DELETE",
        headers: resolveAuthHeaders(),
      });

      ElMessage.success("Purchase deleted successfully.");
      void refreshAll(true).catch(() => undefined);
    } catch (err: any) {
      if (err !== "cancel" && err !== "close") {
        ElMessage.error(err?.data?.message || "Failed to delete purchase.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const variantFilterOptions = computed(() => [
    { id: null, label: "All Variants" },
    ...variantOptions.value,
  ]);

  const pagination = computed(() => ({
    current_page: purchasesResponse.value?.meta?.current_page || 1,
    last_page: purchasesResponse.value?.meta?.last_page || 1,
    per_page: purchasesResponse.value?.meta?.per_page || filters.per_page,
    total: purchasesResponse.value?.meta?.total || 0,
  }));

  watchDebounced(
    () => filters.search_txt,
    () => {
      filters.page = 1;
      void fetchPurchases().catch(() => undefined);
    },
    { debounce: 300, maxWait: 800 },
  );

  watch(
    () => filters.product_variant_id,
    () => {
      filters.page = 1;
      void fetchPurchases().catch(() => undefined);
    },
  );

  watch(
    () => filters.per_page,
    () => {
      filters.page = 1;
      void fetchPurchases().catch(() => undefined);
    },
  );

  const setPage = (page: number) => {
    filters.page = page;
    void fetchPurchases().catch(() => undefined);
  };

  const selectedVariantLabel = computed(() => {
    return (id: number | string | null | undefined) => {
      const variant = variantOptions.value.find((item) => String(item.id) === String(id));
      return variant?.label || "Select a variant";
    };
  });

  return {
    filters,
    pending,
    error,
    saving,
    deletingId,
    purchases,
    pagination,
    variantOptions,
    variantFilterOptions,
    modalOpen,
    modalMode,
    selectedPurchase,
    openPurchaseModal,
    editPurchase,
    closePurchaseModal,
    fetchPurchases,
    refreshAll,
    savePurchase,
    deletePurchase,
    setPage,
    selectedVariantLabel,
  };
};
