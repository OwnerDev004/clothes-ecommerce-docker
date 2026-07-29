import { ElMessage, ElMessageBox } from "element-plus";
import { watchDebounced } from "@vueuse/core";
import { computed, reactive, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type AdminSubCategoryRecord = {
  id: number | string;
  category_id: number | string;
  parent_id?: number | string | null;
  name: string;
  slug?: string;
  des?: string | null;
  order_num?: number;
  image_url?: string | null;
  image_public_id?: string | null;
  status: boolean | number | string | null;
  level?: number | null;
  created_at?: string;
  updated_at?: string;
  category?: {
    id: number | string;
    name?: string | null;
    slug?: string | null;
  } | null;
  parent?: {
    id: number | string;
    name?: string | null;
    slug?: string | null;
  } | null;
};

export type AdminSubCategoryTableRow = AdminSubCategoryRecord & {
  preview_image: string[];
  category_label: string;
  parent_label: string;
  level_label: string;
  status_value: number;
};

export type SubCategoryMetaPage = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type AdminSubCategoryListResponse = {
  data: AdminSubCategoryRecord[];
  meta: SubCategoryMetaPage;
};

export type SubCategorySubmitPayload = {
  mode: "create" | "edit";
  subCategoryId: string | number | null;
  form: {
    name: string;
    category_id: string | number | null;
    parent_id: string | number | null;
    status: number;
    des: string;
    level: number;
  };
  image: File | null;
  remove_image: boolean;
};

type SelectOption = {
  id: string | number;
  label: string;
};

const defaultMetaPage = (): SubCategoryMetaPage => ({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
});

export const useAdminSubCategory = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const pending = ref(false);
  const saving = ref(false);
  const deletingId = ref<number | string | null>(null);
  const error = ref<Error | null>(null);
  const isFormModal = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const selectedSubCategory = ref<AdminSubCategoryRecord | null>(null);

  const filters = reactive({
    search_txt: "",
    status: null as number | null,
    sort_by: "latest",
    page: 1,
    per_page: 10,
  });

  const pagination = reactive<SubCategoryMetaPage>(defaultMetaPage());
  const statusOption = [
    { id: null, label: "All Status" },
    { id: 1, label: "Active" },
    { id: 0, label: "Inactive" },
  ];
  const sortOptions = [
    { id: "latest", label: "Latest" },
    { id: "oldest", label: "Oldest" },
    { id: "name_asc", label: "Name A-Z" },
    { id: "name_desc", label: "Name Z-A" },
  ];

  const subCategoriesResponse = ref<AdminSubCategoryListResponse>({
    data: [],
    meta: defaultMetaPage(),
  });
  const categoryOptions = ref<SelectOption[]>([]);
  const parentSubCategories = ref<AdminSubCategoryRecord[]>([]);

  const resolveAuthHeaders = () => {
    return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined;
  };

  const buildQuery = () => {
    const query: Record<string, string | number> = {
      page: filters.page,
      per_page: filters.per_page,
      sort_by: filters.sort_by,
    };

    if (filters.search_txt.trim() !== "") {
      query.search_txt = filters.search_txt.trim();
    }

    if (filters.status !== null && filters.status !== "") {
      query.status = filters.status;
    }

    return query;
  };

  const loadCategoryOptions = async () => {
    if (!accessToken.value) {
      categoryOptions.value = [];
      return;
    }

    try {
      const response: any = await $fetch(`${apiBase}/admin/categories`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: {
          per_page: 200,
          sort_by: "name_asc",
        },
      });

      categoryOptions.value = Array.isArray(response?.data)
        ? response.data.map((item: any) => ({
          id: item.id,
          label: item.name || "Category",
        }))
        : [];
    } catch {
      categoryOptions.value = [];
    }
  };

  const loadParentSubCategories = async () => {
    if (!accessToken.value) {
      parentSubCategories.value = [];
      return;
    }

    try {
      const response: any = await $fetch(`${apiBase}/admin/sub_categories`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: {
          page: 1,
          per_page: 200,
          sort_by: "name_asc",
        },
      });

      parentSubCategories.value = Array.isArray(response?.data) ? response.data : [];
    } catch {
      parentSubCategories.value = [];
    }
  };

  const refreshLookups = async () => {
    await Promise.allSettled([loadCategoryOptions(), loadParentSubCategories()]);
  };

  const loadSubCategories = async () => {
    if (!accessToken.value) {
      subCategoriesResponse.value = {
        data: [],
        meta: defaultMetaPage(),
      };
      return;
    }

    pending.value = true;
    error.value = null;

    try {
      const response: any = await $fetch(`${apiBase}/admin/sub_categories`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });

      subCategoriesResponse.value = {
        data: Array.isArray(response?.data) ? response.data : [],
        meta: response?.meta || defaultMetaPage(),
      };
      Object.assign(pagination, subCategoriesResponse.value.meta);
    } catch (err) {
      error.value = err as Error;
      subCategoriesResponse.value = {
        data: [],
        meta: defaultMetaPage(),
      };
      Object.assign(pagination, defaultMetaPage());
    } finally {
      pending.value = false;
    }
  };

  const normalizeSubCategoryData = (subCategory: AdminSubCategoryRecord): AdminSubCategoryTableRow => {
    const image = subCategory?.image_url ? subCategory.image_url : "";
    const statusValue = Number(subCategory?.status ?? 0);

    return {
      ...subCategory,
      preview_image: image ? [image] : [],
      category_label: subCategory?.category?.name || "-",
      parent_label: subCategory?.parent?.name || "-",
      level_label: Number(subCategory?.level || (subCategory?.parent_id ? 2 : 1)) === 2 ? "Child" : "Top level",
      status_value: statusValue,
      status: statusValue,
    };
  };

  const dataTable = computed<AdminSubCategoryTableRow[]>(() => {
    return (subCategoriesResponse.value?.data || []).map(normalizeSubCategoryData);
  });

  const resetFilters = () => {
    filters.search_txt = "";
    filters.status = null;
    filters.sort_by = "latest";
    filters.page = 1;
  };

  const addSubCategory = () => {
    selectedSubCategory.value = null;
    modalMode.value = "create";
    isFormModal.value = true;
  };

  const editSubCategory = (subCategory: AdminSubCategoryRecord) => {
    selectedSubCategory.value = subCategory;
    modalMode.value = "edit";
    isFormModal.value = true;
  };

  const buildSubCategoryFormData = (payload: SubCategorySubmitPayload) => {
    const formData = new FormData();
    formData.append("name", payload.form.name);
    formData.append("category_id", String(payload.form.category_id ?? ""));
    formData.append("des", payload.form.des || "");
    formData.append("status", payload.form.status ? "1" : "0");
    formData.append("level", String(payload.form.level || 1));

    if (payload.form.parent_id !== null && payload.form.parent_id !== "") {
      formData.append("parent_id", String(payload.form.parent_id));
    }

    if (payload.remove_image) {
      formData.append("remove_image", "1");
    }

    if (payload.image) {
      formData.append("image", payload.image);
    }

    return formData;
  };

  const createSubCategory = async (payload: SubCategorySubmitPayload) => {
    const formData = buildSubCategoryFormData(payload);

    await $fetch(`${apiBase}/admin/sub_categories`, {
      method: "POST",
      headers: resolveAuthHeaders(),
      body: formData,
    });
  };

  const updateSubCategory = async (payload: SubCategorySubmitPayload) => {
    if (!payload.subCategoryId) {
      throw new Error("Missing sub category id.");
    }

    const formData = buildSubCategoryFormData(payload);
    formData.append("_method", "PUT");

    await $fetch(`${apiBase}/admin/sub_categories/${payload.subCategoryId}`, {
      method: "POST",
      headers: resolveAuthHeaders(),
      body: formData,
    });
  };

  const submitSubCategory = async (payload: SubCategorySubmitPayload) => {
    try {
      saving.value = true;

      if (payload.mode === "edit" && payload.subCategoryId) {
        await updateSubCategory(payload);
        ElMessage.success("Sub category updated successfully.");
      } else {
        await createSubCategory(payload);
        ElMessage.success("Sub category created successfully.");
      }

      isFormModal.value = false;
      selectedSubCategory.value = null;
      modalMode.value = "create";
      await Promise.all([loadSubCategories(), loadParentSubCategories()]);
    } catch (err) {
      console.error("Failed to save sub category", err);
      ElMessage.error("Failed to save sub category.");
    } finally {
      saving.value = false;
    }
  };

  const deleteSubCategory = async (id: string | number) => {
    if (id === "" || id === null || typeof id === "undefined") {
      throw new Error("Sub category id is required.");
    }

    try {
      await ElMessageBox.confirm(
        "Delete this sub category? Any linked products will keep their current reference until updated.",
        "Confirm delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = id;
      await $fetch(`${apiBase}/admin/sub_categories/${id}`, {
        method: "DELETE",
        headers: resolveAuthHeaders(),
      });

      ElMessage.success("Sub category deleted successfully.");
      await Promise.all([loadSubCategories(), loadParentSubCategories()]);
    } catch (err) {
      if (err !== "cancel" && err !== "close") {
        console.error("Failed to delete sub category", err);
        ElMessage.error("Failed to delete sub category.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const categoryStatus = (status: number | string | boolean | null) => {
    return Number(status) === 1 ? "Active" : "Inactive";
  };

  watchDebounced(
    [
      () => filters.search_txt,
      () => filters.status,
      () => filters.sort_by,
      () => filters.per_page,
    ],
    () => {
      if (filters.page !== 1) {
        filters.page = 1;
        return;
      }

      void loadSubCategories();
    },
    { debounce: 300, maxWait: 600 },
  );

  watch(
    () => filters.page,
    () => {
      void loadSubCategories();
    },
  );

  watch(
    () => accessToken.value,
    async () => {
      await refreshLookups();
      await loadSubCategories();
    },
    { immediate: true },
  );

  return {
    filters,
    pagination,
    pending,
    saving,
    deletingId,
    error,
    statusOption,
    sortOptions,
    dataTable,
    categoryOptions,
    parentSubCategories,
    isFormModal,
    modalMode,
    selectedSubCategory,
    categoryStatus,
    loadSubCategories,
    refreshLookups,
    resetFilters,
    addSubCategory,
    editSubCategory,
    deleteSubCategory,
    submitSubCategory,
  };
};
