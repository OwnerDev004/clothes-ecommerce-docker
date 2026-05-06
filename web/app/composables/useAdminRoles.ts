import { ElMessage, ElMessageBox } from "element-plus";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type roleForm = {
  name: string;
  desc?: string;
  status: boolean;
  is_system: boolean;
};

export type roleRecord = {
  id: number | string | null;
  name: string;
  desc: string;
  status: boolean;
  is_system: boolean;
  slug: string;
  created_at: string;
  updated_at: string;
};

export type roleSubmitPayload = {
  mode: "create" | "edit";
  id: string | number | null;
  form: roleForm;
};

type rolesMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

type roleListResponse = {
  data: roleRecord[];
  meta?: rolesMeta;
};

export const useAdminRoles = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const adminAuthStore = useAdminAuthStore();
  const { accessToken } = storeToRefs(adminAuthStore);

  const filters = reactive({
    search_txt: "",
    sort_by: "latest",
    page: 1,
    per_page: 10,
  });

  const roleListResponse = ref<roleListResponse>({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: filters.per_page,
      total: 0,
    },
  });

  const tableData = computed(() => roleListResponse.value.data || []);
  const pending = ref(false);
  const loading = ref(false);
  const deletingId = ref<number | string | null>(null);
  const isModalRole = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const selectedRole = ref<roleRecord | null>(null);

  const pagination = computed(() => ({
    current_page: roleListResponse.value.meta?.current_page || 1,
    last_page: roleListResponse.value.meta?.last_page || 1,
    per_page: roleListResponse.value.meta?.per_page || filters.per_page,
    total: roleListResponse.value.meta?.total || 0,
  }));

  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined;
  };

  const buildQuery = () => ({
    page: filters.page,
    per_page: filters.per_page,
    sort_by: filters.sort_by,
    search_txt: filters.search_txt.trim(),
  });

  const fetchRoles = async () => {
    const response: any = await $fetch(`${apiBase}/admin/roles`, {
      method: "GET",
      credentials: "include",
      headers: resolveAuthHeaders(),
      query: buildQuery(),
    });

    return {
      data: Array.isArray(response?.data) ? response.data : [],
      meta: response?.meta || {
        current_page: 1,
        last_page: 1,
        per_page: filters.per_page,
        total: 0,
      },
    } as roleListResponse;
  };

  const loadRoles = async () => {
    if (!accessToken.value) {
      return;
    }

    pending.value = true;
    try {
      roleListResponse.value = await fetchRoles();
    } catch (error) {
      console.error("Failed to load roles", error);
      roleListResponse.value = {
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: filters.per_page,
          total: 0,
        },
      };
    } finally {
      pending.value = false;
    }
  };

  const openCreate = () => {
    selectedRole.value = null;
    modalMode.value = "create";
    isModalRole.value = true;
  };

  const openEdit = (role: roleRecord) => {
    selectedRole.value = role;
    modalMode.value = "edit";
    isModalRole.value = true;
  };

  const deleteRole = async (role: roleRecord) => {
    if (!role.id) {
      return;
    }

    try {
      await ElMessageBox.confirm(
        `Delete role "${role.name}"?`,
        "Confirm delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = role.id;
      await $fetch(`${apiBase}/admin/roles/${role.id}`, {
        method: "DELETE",
        headers: resolveAuthHeaders(),
      });
      ElMessage.success("Role deleted successfully.");
      await loadRoles();
    } catch (error: any) {
      if (error !== "cancel" && error !== "close") {
        ElMessage.error("Failed to delete role.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const handleSubmit = async (payload: roleSubmitPayload) => {
    loading.value = true;
    try {
      const body = {
        name: payload.form.name,
        desc: payload.form.desc || null,
        status: payload.form.status,
        is_system: payload.form.is_system,
      };

      if (payload.mode === "create") {
        await $fetch(`${apiBase}/admin/roles`, {
          method: "POST",
          headers: resolveAuthHeaders(),
          body,
        });
        ElMessage.success("Role created successfully.");
      } else if (payload.id) {
        await $fetch(`${apiBase}/admin/roles/${payload.id}`, {
          method: "PUT",
          headers: resolveAuthHeaders(),
          body,
        });
        ElMessage.success("Role updated successfully.");
      }

      isModalRole.value = false;
      await loadRoles();
    } catch (error: any) {
      ElMessage.error(error?.data?.message || "Failed to save role.");
    } finally {
      loading.value = false;
    }
  };

  const resetFilters = () => {
    filters.search_txt = "";
    filters.sort_by = "latest";
    filters.page = 1;
  };

  const setPage = (page: number) => {
    filters.page = page;
  };

  watch(
    () => [filters.page, filters.per_page, filters.sort_by, filters.search_txt],
    () => {
      void loadRoles();
    },
  );

  return {
    tableData,
    filters,
    pending,
    loading,
    deletingId,
    isModalRole,
    modalMode,
    selectedRole,
    pagination,
    handleSubmit,
    openCreate,
    openEdit,
    deleteRole,
    resetFilters,
    setPage,
    loadRoles,
  };
};
