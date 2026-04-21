// Types

import { useAdminAuthStore } from "~/stores/adminAuthStore";
import { storeToRefs } from "pinia";
import type { customerStatus } from "~/enums/customerStatus";
import { lowerCase } from "lodash";
import { watchDebounced } from "@vueuse/core";

export type MetaPage = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type customerRecord = {
  id: number | string;
  full_name?: string;
  gender?: "male" | "female";
  dob?: string;
  user_name: string;
  email?: string;
  phone: string;
  address?: string;
  avatar_url?: string;
  avatar_public_id?: string;
  telegram_username: string;
  enable_telegram_alerts: boolean;
  status: string | customerStatus.Active | customerStatus.Inactive;
  created_at: string;
  updated_at: string;
};
export type customerOauth = {
  id: number | string;
  provider: string;
  provider_user_id: string;
};
export type customerPayload = {
  id: number | string;
  full_name?: string;
  gender?: "male" | "female";
  dob?: string;
  user_name: string;
  email?: string;
  phone: string;
  address?: string;
  avatar_url?: string;
  avatar_public_id?: string;
  telegram_username: string;
  enable_telegram_alerts: boolean;
  status: string | customerStatus.Active | customerStatus.Inactive;
};
type customerDataResponse = {
  data: customerRecord[];
  meta: MetaPage;
};
export const useAdminCustomer = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  // resolved Header
  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  //build query
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

  // Reactive

  const filters = reactive({
    search_txt: "",
    sort_by: "",
    status: "",
    page: 1,
    per_page: 10,
  });
  const pagination = reactive<MetaPage>({
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
  const statusOptions = ref<customerStatus>();
  const pending = ref(false);
  const error = ref<Error | null>(null);
  const deletingId = ref<number | string | null>(null);
  const dataTable = ref<customerDataResponse>({
    data: [],
    meta: pagination,
  });
  const resetCustomerPassword = (customer: customerPayload) => {};
  const editCustomer = (customer: customerPayload) => {};
  const deleteCustomer = async (customer: customerPayload) => {
    if (
      customer.id === "" ||
      customer.id === null ||
      customer.id === undefined
    ) {
      throw new Error("Invalid customer id");
    }

    try {
      await ElMessageBox.confirm(
        "Are you sure to delete this customer?",
        "Confirm disable acoount",
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

      ElMessage.success("Cutomer deleted successfully.");
      void fetchCustomer();
    } catch (error) {
      if (error !== "cancel" && error !== "close") {
        ElMessage.error("Cutomer deleted fail.");
      }
    } finally {
      deletingId.value = null;
    }
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
        data: response?.data || [],
        meta: response?.meta || pagination,
      };
      pagination.current_page = response?.meta.current_page;
      pagination.last_page = response?.meta.last_page;
      pagination.per_page = response?.meta.per_page;
      pagination.total = response?.meta.total;
    } catch (err) {
      error.value = err as Error;
    } finally {
      pending.value = false;
    }
  };
  // watchDebounced
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

  //watch
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
    deletingId,
    dataTable,
    pagination,
    sortOptions,
    statusOptions,
    resetCustomerPassword,
    editCustomer,
    deleteCustomer,
  };
};
