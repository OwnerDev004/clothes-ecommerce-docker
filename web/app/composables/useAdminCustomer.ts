import { watchDebounced } from "@vueuse/core";
import { lowerCase } from "lodash";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, reactive, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";
import {
  customerStatus,
  getDisplayCustomerStatus,
} from "~/enums/customerStatus";

export type CustomerMetaPage = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type customerOauth = {
  id: number | string;
  provider: string;
  provider_user_id?: string | null;
  email?: string | null;
  avatar_url?: string | null;
  expires_at?: string | null;
};

export type customerRecord = {
  id: number | string;
  full_name?: string | null;
  gender?: "male" | "female" | null;
  dob?: string | null;
  user_name: string;
  email?: string | null;
  phone: string;
  address?: string | null;
  avatar_url?: string | null;
  avatar_public_id?: string | null;
  telegram_username?: string | null;
  enable_telegram_alerts: boolean;
  status: "active" | "inactive";
  oauth_accounts?: customerOauth[];
  created_at: string;
  updated_at: string;
};

export type customerPayload = customerRecord;

type customerDataResponse = {
  data: customerRecord[];
  meta: CustomerMetaPage;
};

type customerDetailResponse = {
  data: customerRecord | null;
};

type CustomerSubmitPayload = {
  customerId: string | number | null;
  form: {
    full_name: string;
    gender: "male" | "female" | "";
    dob: string;
    user_name: string;
    email: string;
    phone: string;
    address: string;
    telegram_username: string;
    enable_telegram_alerts: boolean;
    status: "active" | "inactive";
  };
  image: File | null;
  remove_image: boolean;
};

type SelectOption = {
  id: string | number;
  label: string;
};

export const useAdminCustomer = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());
  const router = useRouter();

  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  const filters = reactive({
    search_txt: "",
    sort_by: "",
    status: null as "active" | "inactive" | null,
    page: 1,
    per_page: 10,
  });

  const pagination = reactive<CustomerMetaPage>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  const sortOptions = reactive([
    { id: "latest", label: "Latest" },
    { id: "oldest", label: "Oldest" },
    { id: "name_asc", label: "Full Name A-Z" },
    { id: "name_desc", label: "Full Name Z-A" },
  ]);

  const statusOptions = reactive<SelectOption[]>([
    { id: "", label: "All Status" },
    { id: customerStatus.Active, label: "Active" },
    { id: customerStatus.Inactive, label: "Disable Account" },
  ]);

  const pending = ref(false);
  const saving = ref(false);
  const error = ref<Error | null>(null);
  const deletingId = ref<number | string | null>(null);
  const savingStatusId = ref<number | string | null>(null);
  const sendingMailId = ref<number | string | null>(null);

  const dataTable = ref<customerDataResponse>({
    data: [],
    meta: pagination,
  });

  const customerDetail = ref<customerRecord | null>(null);

  const buildQuery = () => {
    const query: Record<string, string | number> = {
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sort_by,
    };

    const add = (key: string, value: string | number | null | undefined) => {
      if (value !== null && value !== undefined && value !== "") {
        query[key] = value;
      }
    };

    add("search_txt", lowerCase(filters.search_txt).trim());
    add("status", filters.status);

    return query;
  };

  const fetchCustomer = async () => {
    if (!accessToken.value) {
      return;
    }

    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/customers`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });

      dataTable.value = {
        data: Array.isArray(response?.data) ? response.data : [],
        meta: response?.meta || pagination,
      };

      pagination.current_page = response?.meta?.current_page ?? 1;
      pagination.last_page = response?.meta?.last_page ?? 1;
      pagination.per_page = response?.meta?.per_page ?? filters.per_page;
      pagination.total = response?.meta?.total ?? 0;
    } catch (err) {
      error.value = err as Error;
    } finally {
      pending.value = false;
    }
  };

  const fetchCustomerDetail = async (id: string | number) => {
    if (id === "" || id === null || id === undefined) {
      throw new Error("Invalid customer id");
    }

    const response: customerDetailResponse | any = await $fetch(
      `${apiBase}/admin/customers/${id}`,
      {
        method: "GET",
        headers: resolveAuthHeaders(),
      },
    );

    const detail = response?.data ?? null;
    customerDetail.value = detail;
    return detail as customerRecord | null;
  };

  const buildCustomerFormData = (payload: CustomerSubmitPayload) => {
    const formData = new FormData();
    const { form } = payload;

    const append = (key: string, value: unknown) => {
      if (value === null || value === undefined || value === "") {
        return;
      }
      formData.append(key, String(value));
    };

    append("full_name", form.full_name.trim());
    append("gender", form.gender);
    append("dob", form.dob);
    append("user_name", form.user_name.trim());
    append("email", form.email.trim());
    append("phone", form.phone.trim());
    append("address", form.address.trim());
    append("telegram_username", form.telegram_username.trim());
    append("enable_telegram_alerts", form.enable_telegram_alerts ? "1" : "0");
    append("status", form.status);

    if (payload.remove_image) {
      formData.append("remove_image", "1");
    }

    if (payload.image) {
      formData.append("profile", payload.image);
    }

    return formData;
  };

  const updateCustomer = async (payload: CustomerSubmitPayload) => {
    if (!payload.customerId) {
      throw new Error("Missing customer id.");
    }

    const formData = buildCustomerFormData(payload);
    formData.append("_method", "PUT");

    await $fetch(`${apiBase}/admin/customers/${payload.customerId}`, {
      headers: resolveAuthHeaders(),
      method: "POST",
      body: formData,
    });
  };

  const toggleCustomerStatus = async (
    customer: customerPayload,
    status: "active" | "inactive",
  ) => {
    if (!customer?.id) {
      throw new Error("Invalid customer id");
    }

    const label = status === customerStatus.Active ? "activate" : "deactivate";

    try {
      await ElMessageBox.confirm(
        `Are you sure you want to ${label} this customer?`,
        "Confirm status change",
        {
          confirmButtonText: label === "activate" ? "Activate" : "Deactivate",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      savingStatusId.value = customer.id;
      const formData = new FormData();
      formData.append("_method", "PUT");
      formData.append("status", status);

      await $fetch(`${apiBase}/admin/customers/${customer.id}`, {
        headers: resolveAuthHeaders(),
        method: "POST",
        body: formData,
      });

      ElMessage.success(
        status === customerStatus.Active
          ? "Customer activated successfully."
          : "Customer deactivated successfully.",
      );
      await fetchCustomer();
    } catch (err) {
      if (err !== "cancel" && err !== "close") {
        ElMessage.error("Failed to update customer status.");
      }
      await fetchCustomer();
    } finally {
      savingStatusId.value = null;
    }
  };

  const sendResetLink = async (customer: customerPayload) => {
    if (!customer?.id) {
      throw new Error("Invalid customer id");
    }

    try {
      sendingMailId.value = customer?.id;
      await $fetch(
        `${apiBase}/admin/customers/${customer.id}/send-reset-link`,
        {
          headers: resolveAuthHeaders(),
          method: "POST",
        },
      );

      ElMessage.success("Password reset link sent successfully.");
    } catch (err) {
      ElMessage.error("Failed to send password reset link.");
    } finally {
      sendingMailId.value = null;
    }
  };

  const resetCustomerPassword = (customer: customerPayload) => {
    return sendResetLink(customer);
  };

  const editCustomer = (customer: customerPayload) => {
    if (
      customer?.id === "" ||
      customer?.id === null ||
      customer?.id === undefined
    ) {
      return;
    }

    return router.push(`/admin/customers/edit/${customer.id}`);
  };

  const viewCustomer = (customer: customerPayload) => {
    if (
      customer?.id === "" ||
      customer?.id === null ||
      customer?.id === undefined
    ) {
      return;
    }

    return router.push(`/admin/customers/${customer.id}`);
  };

  const deleteCustomer = async (customer: customerPayload) => {
    if (!customer?.id) {
      throw new Error("Invalid customer id");
    }

    try {
      await ElMessageBox.confirm(
        "Are you sure to disable this customer?",
        "Confirm disable account",
        {
          confirmButtonText: "Disable",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = customer.id;
      await $fetch(`${apiBase}/admin/customers/${customer.id}`, {
        headers: resolveAuthHeaders(),
        method: "DELETE",
        credentials: "include",
      });

      ElMessage.success("Customer deactivated successfully.");
      await fetchCustomer();
    } catch (err) {
      if (err !== "cancel" && err !== "close") {
        ElMessage.error("Customer deactivation failed.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const submitCustomer = async (payload: CustomerSubmitPayload) => {
    try {
      saving.value = true;
      await updateCustomer(payload);
      ElMessage.success("Customer updated successfully.");
      await fetchCustomer();
    } catch (err) {
      ElMessage.error("Failed to update customer.");
      throw err;
    } finally {
      saving.value = false;
    }
  };

  const dataTableComputed = computed<customerDataResponse>(() => {
    return {
      data: dataTable.value?.data || [],
      meta: dataTable.value?.meta || pagination,
    };
  });

  watchDebounced(
    [
      () => filters.search_txt,
      () => filters.per_page,
      () => filters.sort_by,
      () => filters.status,
    ],
    () => {
      filters.page = 1;
      void fetchCustomer();
    },
    {
      debounce: 300,
      maxWait: 600,
    },
  );

  watch(
    () => accessToken.value,
    () => {
      void fetchCustomer();
    },
    {
      immediate: true,
    },
  );

  return {
    filters,
    pagination,
    pending,
    error,
    saving,
    deletingId,
    savingStatusId,
    dataTable: dataTableComputed,
    customerDetail,
    sortOptions,
    statusOptions,
    sendingMailId,
    getDisplayCustomerStatus,
    fetchCustomer,
    fetchCustomerDetail,
    editCustomer,
    deleteCustomer,
    sendResetLink,
    resetCustomerPassword,
    viewCustomer,
    toggleCustomerStatus,
    submitCustomer,
  };
};
