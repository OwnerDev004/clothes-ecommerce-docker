import { watchDebounced } from "@vueuse/core";
import { lowerCase } from "lodash";
import { ElMessage, ElMessageBox } from "element-plus";
import { computed, reactive, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type AdminCollectionsCategory = {
  id: number | string;
  name: string;
  slug: string;
};

export type AdminCollectionsProduct = {
  id: number | string;
  name: string;
  slug?: string;
  price?: number | string | null;
  image?: string | null;
};

export type AdminCollectionsRecord = {
  id: number | string;
  name: string;
  category_id?: number | string | null;
  category: AdminCollectionsCategory;
  slug: string;
  desc: string;
  sort_order: number;
  status: string;
  image_url: string;
  products_count?: number | null;
  products?: AdminCollectionsProduct[];
  created_at: string;
  updated_at: string;
};

type AdminCollectionListMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

type AdminCollectionsResponseList = {
  data: AdminCollectionsRecord[];
  meta: AdminCollectionListMeta;
};

type CollectionSubmitPayload = {
  mode: "create" | "edit";
  collectionId: string | number | null;
  form: {
    name: string;
    desc: string;
    categoryId: string | number | null;
    status: string;
    sort_order: string;
  };
  image: File | null;
  remove_image: boolean;
  productIds: Array<string | number>;
};
type SelectOption = {
  id: string | number | null;
  label: string;
  disabled?: boolean;
};

export const useAdminCollections = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const deletingId = ref<string | number | null>(null);
  const isCollectionModal = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const selectedData = ref<AdminCollectionsRecord | null>(null);
  const saving = ref(false);

  const filters = reactive({
    page: 1,
    per_page: 10,
    sort_by: "",
    search_txt: "",
    category: "" as string | number | "",
    status: "" as string | number | "",
  });

  const statusOptions = reactive<SelectOption[]>([
    { id: "draft", label: "Draft" },
    { id: "published", label: "Published" },
  ]);

  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  const defaultMeta = (): AdminCollectionListMeta => ({
    current_page: 1,
    last_page: 1,
    per_page: filters.per_page,
    total: 0,
  });

  const collectionsData = ref<AdminCollectionsResponseList>({
    data: [],
    meta: defaultMeta(),
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
      sort_by: filters.sort_by,
    };

    const add = (key: string, value: string | number | null | undefined) => {
      if (value !== null && value !== undefined && value !== "") {
        query[key] = value;
      }
    };

    add("search_txt", lowerCase(filters.search_txt).trim());
    add("category_id", filters.category);
    add("status", filters.status);

    return query;
  };

  const fetchCollections = async () => {
    if (!accessToken.value) {
      return;
    }

    const response: any = await $fetch(`${apiBase}/admin/collections`, {
      method: "GET",
      headers: resolveAuthHeaders(),
      query: buildQuery(),
    });

    collectionsData.value = {
      data: Array.isArray(response?.data) ? response.data : [],
      meta: response?.meta || defaultMeta(),
    };

    pagination.current_page = collectionsData.value.meta.current_page;
    pagination.last_page = collectionsData.value.meta.last_page;
    pagination.per_page = collectionsData.value.meta.per_page;
    pagination.total = collectionsData.value.meta.total;

    return collectionsData.value;
  };

  const fetchCollectionDetail = async (id: string | number) => {
    const response: any = await $fetch(`${apiBase}/admin/collections/${id}`, {
      method: "GET",
      headers: resolveAuthHeaders(),
    });

    return response?.data?.data || response?.data || null;
  };

  const addCollection = () => {
    selectedData.value = null;
    modalMode.value = "create";
    isCollectionModal.value = true;
  };

  const deleteCollection = async (id: string | number) => {
    if (id === "" || id === null || id === undefined) {
      throw new Error("Invalid collection id");
    }

    try {
      await ElMessageBox.confirm(
        "Are you sure to delete this collection?",
        "Confirm delete",
        {
          confirmButtonText: "Delete",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );

      deletingId.value = id;
      await $fetch(`${apiBase}/admin/collections/${id}`, {
        headers: resolveAuthHeaders(),
        method: "DELETE",
        credentials: "include",
      });

      ElMessage.success("Collection deleted successfully.");
      void fetchCollections();
    } catch (error) {
      if (error !== "cancel" && error !== "close") {
        ElMessage.error("Collection deleted fail.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const editCollection = async (collection: AdminCollectionsRecord) => {
    if (
      collection.id === "" ||
      collection.id === null ||
      collection.id === undefined
    ) {
      return new Error("Invalid collection id");
    }

    modalMode.value = "edit";

    try {
      const detailedCollection = await fetchCollectionDetail(collection.id);
      selectedData.value = detailedCollection || collection;
    } catch (error) {
      selectedData.value = collection;
    } finally {
      isCollectionModal.value = true;
    }
  };

  const appendFormValue = (
    formData: FormData,
    key: string,
    value: unknown,
  ): void => {
    if (value === null || value === undefined || value === "") {
      return;
    }

    if (value instanceof File) {
      formData.append(key, value);
      return;
    }

    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        appendFormValue(formData, `${key}[${index}]`, item);
      });
      return;
    }

    if (typeof value === "object") {
      Object.entries(value as Record<string, unknown>).forEach(
        ([childKey, childValue]) => {
          appendFormValue(formData, `${key}[${childKey}]`, childValue);
        },
      );
      return;
    }

    formData.append(key, String(value));
  };

  const buildCollectionFormData = (payload: CollectionSubmitPayload) => {
    const formData = new FormData();

    appendFormValue(formData, "category_id", payload.form.categoryId);
    appendFormValue(formData, "name", payload.form.name);
    appendFormValue(formData, "desc", payload.form.desc);
    appendFormValue(formData, "sort_order", payload.form.sort_order || 0);
    appendFormValue(formData, "status", payload.form.status);

    if (payload.remove_image) {
      formData.append("remove_image", "1");
    }

    if (payload.image) {
      formData.append("image", payload.image);
    }

    if (payload.productIds.length) {
      payload.productIds.forEach((productId) => {
        formData.append("product_ids[]", String(productId));
      });
    }

    return formData;
  };

  const createCollection = async (payload: CollectionSubmitPayload) => {
    const formData = buildCollectionFormData(payload);

    await $fetch(`${apiBase}/admin/collections`, {
      headers: resolveAuthHeaders(),
      method: "POST",
      body: formData,
    });
  };

  const updateCollection = async (payload: CollectionSubmitPayload) => {
    if (!payload.collectionId) {
      throw new Error("Missing collection id.");
    }

    const formData = buildCollectionFormData(payload);
    formData.append("_method", "PUT");

    await $fetch(`${apiBase}/admin/collections/${payload.collectionId}`, {
      headers: resolveAuthHeaders(),
      method: "POST",
      body: formData,
    });
  };

  const submitForm = async (payload: CollectionSubmitPayload) => {
    try {
      saving.value = true;
      if (payload.mode === "edit" && payload.collectionId) {
        await updateCollection(payload);
        ElMessage.success("Collection updated successfully.");
      } else {
        await createCollection(payload);
        ElMessage.success("Collection created successfully.");
      }

      isCollectionModal.value = false;
      selectedData.value = null;
      modalMode.value = "create";
      void fetchCollections();
    } catch (error) {
      console.error("Failed to save collection", error);
      ElMessage.error("Failed to save collection.");
    } finally {
      saving.value = false;
    }
  };

  watchDebounced(
    [
      () => filters.search_txt,
      () => filters.status,
      () => filters.sort_by,
      () => filters.category,
    ],
    () => {
      filters.page = 1;
      void fetchCollections();
    },
    {
      debounce: 300,
      maxWait: 600,
    },
  );

  watch(
    () => [filters.page, filters.per_page],
    () => {
      void fetchCollections();
    },
  );

  watch(
    () => accessToken.value,
    () => {
      void fetchCollections();
    },
    { immediate: true },
  );

  const collectionsMeta = computed(
    () => collectionsData.value.meta || defaultMeta(),
  );

  return {
    filters,
    collectionsData,
    collectionsMeta,
    pagination,
    statusOptions,
    deletingId,
    saving,
    isCollectionModal,
    modalMode,
    selectedData,
    addCollection,
    deleteCollection,
    editCollection,
    fetchCollections,
    submitForm,
  };
};
