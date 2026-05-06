import { computed, reactive, ref, watch } from "vue";
import { watchDebounced } from "@vueuse/core";
import { ElMessage, ElMessageBox } from "element-plus";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type AdminVoucherRecord = {
  id: number | string;
  code: string;
  name: string;
  is_active: boolean;
  is_signup_coupon: boolean;
  first_order_only: boolean;
  discount_type: "percentage" | "fixed_amount";
  discount_value: number | string;
  minimum_order_amount?: number | string | null;
  max_order?: number | null;
  max_uses_per_customer?: number | null;
  expires_at?: string | null;
  uses_count?: number;
  created_at?: string;
  updated_at?: string;
};

export type VoucherForm = {
  code: string;
  name: string;
  is_active: boolean;
  is_signup_coupon: boolean;
  first_order_only: boolean;
  discount_type: "percentage" | "fixed_amount";
  discount_value: string;
  minimum_order_amount: string;
  max_order: string;
  max_uses_per_customer: string;
  expires_at: string;
};

export type VoucherSubmitPayload = {
  mode: "create" | "edit";
  voucherId: number | string | null;
  form: VoucherForm;
};

export const useAdminVoucher = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const filters = reactive({
    search_txt: "",
    is_active: null as number | null,
  });

  const statusOptions = [
    { id: "", label: "All Status" },
    { id: 1, label: "Active" },
    { id: 0, label: "Inactive" },
  ];

  const typeOptions = [
    { id: "percentage", label: "Percentage" },
    { id: "fixed_amount", label: "Fixed Amount" },
  ];

  const voucherResponse = ref<AdminVoucherRecord[]>([]);
  const pending = ref(false);
  const error = ref<Error | null>(null);
  const isFormModal = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const selectedVoucher = ref<AdminVoucherRecord | null>(null);
  const saving = ref(false);
  const deletingId = ref<number | string | null>(null);

  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  const buildQuery = () => {
    const query: Record<string, string | number> = {};

    if (filters.search_txt.trim() !== "") {
      query.search_txt = filters.search_txt.trim();
    }

    if (filters.is_active !== null && filters.is_active !== undefined) {
      query.is_active = filters.is_active;
    }

    return query;
  };

  const fetchVouchers = async () => {
    if (!accessToken.value) {
      return;
    }

    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/vouchers`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });

      voucherResponse.value = Array.isArray(response?.data)
        ? response.data
        : [];
    } catch (err) {
      error.value = err as Error;
      voucherResponse.value = [];
    } finally {
      pending.value = false;
    }
  };

  const resetFilters = () => {
    filters.search_txt = "";
    filters.is_active = null;
  };

  const addVoucher = () => {
    selectedVoucher.value = null;
    modalMode.value = "create";
    isFormModal.value = true;
  };

  const editVoucher = (voucher: AdminVoucherRecord) => {
    selectedVoucher.value = voucher;
    modalMode.value = "edit";
    isFormModal.value = true;
  };

  const createVoucher = async (payload: VoucherSubmitPayload) => {
    await $fetch(`${apiBase}/admin/vouchers`, {
      method: "POST",
      headers: resolveAuthHeaders(),
      body: {
        code: payload.form.code.trim(),
        name: payload.form.name.trim(),
        is_active: payload.form.is_active,
        is_signup_coupon: payload.form.is_signup_coupon,
        first_order_only: payload.form.first_order_only,
        discount_type: payload.form.discount_type,
        discount_value: payload.form.discount_value,
        minimum_order_amount: payload.form.minimum_order_amount || null,
        max_order: payload.form.max_order || null,
        max_uses_per_customer: payload.form.max_uses_per_customer || null,
        expires_at: payload.form.expires_at || null,
      },
    });
  };

  const updateVoucher = async (payload: VoucherSubmitPayload) => {
    if (!payload.voucherId) {
      throw new Error("Missing voucher id.");
    }

    await $fetch(`${apiBase}/admin/vouchers/${payload.voucherId}`, {
      method: "PUT",
      headers: resolveAuthHeaders(),
      body: {
        code: payload.form.code.trim(),
        name: payload.form.name.trim(),
        is_active: payload.form.is_active,
        is_signup_coupon: payload.form.is_signup_coupon,
        first_order_only: payload.form.first_order_only,
        discount_type: payload.form.discount_type,
        discount_value: payload.form.discount_value,
        minimum_order_amount: payload.form.minimum_order_amount || null,
        max_order: payload.form.max_order || null,
        max_uses_per_customer: payload.form.max_uses_per_customer || null,
        expires_at: payload.form.expires_at || null,
      },
    });
  };

  const submitForm = async (payload: VoucherSubmitPayload) => {
    try {
      saving.value = true;

      if (payload.mode === "edit" && payload.voucherId) {
        await updateVoucher(payload);
        ElMessage.success("Voucher updated successfully.");
      } else {
        await createVoucher(payload);
        ElMessage.success("Voucher created successfully.");
      }

      isFormModal.value = false;
      selectedVoucher.value = null;
      modalMode.value = "create";
      void fetchVouchers();
    } catch (err) {
      console.error("Failed to save voucher", err);
      ElMessage.error("Failed to save voucher.");
    } finally {
      saving.value = false;
    }
  };

  const deleteVoucher = async (voucher: AdminVoucherRecord) => {
    if (!voucher?.id) {
      return;
    }

    try {
      await ElMessageBox.confirm(
        `Delete "${voucher.code}"?`,
        "Confirm delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = voucher.id;
      await $fetch(`${apiBase}/admin/vouchers/${voucher.id}`, {
        method: "DELETE",
        headers: resolveAuthHeaders(),
      });

      ElMessage.success("Voucher deleted successfully.");
      void fetchVouchers();
    } catch (err) {
      if (err !== "cancel" && err !== "close") {
        console.error("Failed to delete voucher", err);
        ElMessage.error("Failed to delete voucher.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const tableData = computed(() => voucherResponse.value || []);

  watchDebounced(
    [() => filters.search_txt, () => filters.is_active],
    () => {
      void fetchVouchers();
    },
    { debounce: 300, maxWait: 600 },
  );

  watch(
    () => accessToken.value,
    () => {
      void fetchVouchers();
    },
    { immediate: true },
  );

  return {
    filters,
    statusOptions,
    typeOptions,
    tableData,
    pending,
    error,
    isFormModal,
    modalMode,
    selectedVoucher,
    saving,
    deletingId,
    resetFilters,
    addVoucher,
    editVoucher,
    deleteVoucher,
    submitForm,
    fetchVouchers,
  };
};
