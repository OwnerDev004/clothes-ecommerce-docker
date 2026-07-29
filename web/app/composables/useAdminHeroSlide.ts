import { watchDebounced } from "@vueuse/core";
import { lowerCase } from "lodash";
import { computed, reactive, ref, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { storeToRefs } from "pinia";
import { useAdminAuthStore } from "~/stores/adminAuthStore";

export type AdminHeroSlideRecord = {
  id: number | string;
  title: string;
  subtitle?: string | null;
  description?: string | null;
  image_url?: string | null;
  gradient?: string | null;
  link_url?: string | null;
  link_text?: string | null;
  sort_order: number;
  status: boolean | number;
  created_at?: string;
};

export type HeroSlideMetaPage = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type AdminHeroSlideRecordTable = AdminHeroSlideRecord & {
  preview_image: string[];
};

export type AdminHeroSlideListResponse = {
  data: AdminHeroSlideRecord[];
  meta: HeroSlideMetaPage;
};

export type HeroSlideSubmitPayload = {
  mode: "create" | "edit";
  slideId: string | number | null;
  form: {
    title: string;
    subtitle: string;
    description: string;
    gradient: string;
    link_url: string;
    link_text: string;
    sort_order: number;
    status: boolean | number;
  };
  image: File | null;
  remove_image: boolean;
};

export const useAdminHeroSlide = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const pending = ref(false);
  const isFormModal = ref(false);
  const selectedSlide = ref<AdminHeroSlideRecord | null>(null);
  const modalMode = ref<"create" | "edit">("create");
  const saving = ref(false);
  const error = ref<Error | null>(null);
  const { accessToken } = storeToRefs(useAdminAuthStore());

  const defaultMetaPage = (): HeroSlideMetaPage => ({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  const slidesResponse = ref<AdminHeroSlideListResponse>({
    data: [],
    meta: defaultMetaPage(),
  });

  const filters = reactive({
    search_txt: "",
    status: null as number | null,
    sort_by: "",
    page: 1,
    per_page: 10,
  });

  const pagination = reactive<HeroSlideMetaPage>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });

  const deletingId = ref<number | string | null>(null);

  const statusOption = ref<any[]>([
    { id: null, label: "All status" },
    { id: 1, label: "Active" },
    { id: 0, label: "Inactive" },
  ]);

  const sortOptions = [
    { id: "latest", label: "Latest" },
    { id: "oldest", label: "Oldest" },
    { id: "sort_order", label: "Sort Order" },
  ];

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
    if (filters.search_txt) query.search_txt = lowerCase(filters.search_txt).trim();
    if (filters.status !== null && filters.status !== "") query.status = filters.status;
    return query;
  };

  const normalizeRow = (slide: AdminHeroSlideRecord): AdminHeroSlideRecordTable => {
    const img = slide?.image_url || "";
    return {
      ...slide,
      preview_image: [img],
      status: Number(slide?.status),
    };
  };

  const slideFetching = async () => {
    if (!accessToken.value) return;
    pending.value = true;
    error.value = null;
    try {
      const response: any = await $fetch(`${apiBase}/admin/hero-slides`, {
        method: "GET",
        headers: resolveAuthHeaders(),
        query: buildQuery(),
      });
      slidesResponse.value = {
        data: Array.isArray(response?.data) ? response.data : [],
        meta: response?.meta || defaultMetaPage(),
      };
    } catch (err) {
      error.value = err as Error;
      slidesResponse.value = { data: [], meta: defaultMetaPage() };
    } finally {
      pending.value = false;
    }
  };

  const resetFilters = () => {
    filters.search_txt = "";
    filters.status = null;
    filters.sort_by = "";
    filters.page = 1;
  };

  const addSlide = () => {
    selectedSlide.value = null;
    modalMode.value = "create";
    isFormModal.value = true;
  };

  const deleteSlide = async (id: string | number) => {
    try {
      await ElMessageBox.confirm(`Delete this hero slide?`, "Confirm", {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      });
      deletingId.value = id;
      await $fetch(`${apiBase}/admin/hero-slides/${id}`, {
        headers: resolveAuthHeaders(),
        method: "DELETE",
        credentials: "include",
      });
      ElMessage.success("Hero slide deleted.");
      void slideFetching();
    } catch (err) {
      if (err !== "cancel") {
        console.error("Failed to delete", err);
        ElMessage.error("Failed to delete.");
      }
    } finally {
      deletingId.value = null;
    }
  };

  const editSlide = (slide: AdminHeroSlideRecord) => {
    selectedSlide.value = slide;
    modalMode.value = "edit";
    isFormModal.value = true;
  };

  const buildFormData = (payload: HeroSlideSubmitPayload) => {
    const fd = new FormData();
    fd.append("title", payload.form.title);
    fd.append("subtitle", payload.form.subtitle);
    fd.append("description", payload.form.description);
    fd.append("gradient", payload.form.gradient);
    fd.append("link_url", payload.form.link_url);
    fd.append("link_text", payload.form.link_text);
    fd.append("sort_order", String(payload.form.sort_order));
    fd.append("status", payload.form.status ? "1" : "0");
    if (payload.remove_image) fd.append("remove_image", "1");
    if (payload.image) fd.append("image", payload.image);
    return fd;
  };

  const createSlide = async (payload: HeroSlideSubmitPayload) => {
    await $fetch(`${apiBase}/admin/hero-slides`, {
      headers: resolveAuthHeaders(),
      method: "POST",
      body: buildFormData(payload),
    });
  };

  const updateSlide = async (payload: HeroSlideSubmitPayload) => {
    const fd = buildFormData(payload);
    fd.append("_method", "PUT");
    await $fetch(`${apiBase}/admin/hero-slides/${payload.slideId}`, {
      headers: resolveAuthHeaders(),
      method: "POST",
      body: fd,
    });
  };

  const submitForm = async (payload: HeroSlideSubmitPayload) => {
    try {
      saving.value = true;
      if (payload.mode === "edit" && payload.slideId) {
        await updateSlide(payload);
        ElMessage.success("Hero slide updated.");
      } else {
        await createSlide(payload);
        ElMessage.success("Hero slide created.");
      }
      isFormModal.value = false;
      selectedSlide.value = null;
      modalMode.value = "create";
      void slideFetching();
    } catch (err) {
      console.error("Failed to save", err);
      ElMessage.error("Failed to save hero slide.");
    } finally {
      saving.value = false;
    }
  };

  const dataTable = computed<AdminHeroSlideListResponse>(() => ({
    data: slidesResponse.value?.data?.map(normalizeRow) ?? [],
    meta: slidesResponse.value?.meta,
  }));

  const slideStatusLabel = (status: number) => (status === 1 ? "Active" : "Inactive");

  watchDebounced(
    [() => filters.status, () => filters.sort_by, () => filters.search_txt, () => filters.page, () => filters.per_page],
    () => {
      filters.page = 1;
      void slideFetching();
    },
    { debounce: 300, maxWait: 600 },
  );

  watch(() => accessToken.value, () => void slideFetching(), { immediate: true });

  return {
    filters,
    pagination,
    deletingId,
    statusOption,
    sortOptions,
    dataTable,
    isFormModal,
    modalMode,
    saving,
    selectedSlide,
    slideStatusLabel,
    pending,
    error,
    submitForm,
    resetFilters,
    addSlide,
    deleteSlide,
    editSlide,
  };
};
