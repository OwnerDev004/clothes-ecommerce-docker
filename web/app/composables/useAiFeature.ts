export const useAiFeature = () => {
  const config = useRuntimeConfig();
  const message_prompt = ref<string>("");
  const pending = ref<boolean>(false);
  const apiBase = config.public.apiBase;
  const router = useRouter();
  const isDialog = ref<boolean>(false);

  const aiProductSearch = async () => {
    const query = message_prompt.value.trim();
    if (!query) return;

    try {
      pending.value = true;
      const res: any = await $fetch(`${apiBase}/ai-chat/product/filter`, {
        method: "POST",
        body: { message: query },
      });

      const filters: Record<string, string> = {};

      if (res?.filters) {
        const f = res.filters;
        if (f.search_txt) filters.search_txt = f.search_txt;
        if (f.category) filters.category = f.category;
        if (f.sub_category) filters.sub_category = f.sub_category;
        if (f.collection) filters.collection = f.collection;
        if (f.brand) filters.brand = f.brand;
        if (f.color) filters.color = f.color;
        if (f.size) filters.size = f.size;
        if (f.price_min) filters.price_min = String(f.price_min);
        if (f.price_max) filters.price_max = String(f.price_max);
        if (f.sort_by) filters.sort_by = f.sort_by;
      }

      // If no structured filters, fall back to plain search_txt
      if (
        !filters.search_txt &&
        !filters.category &&
        !filters.color &&
        !filters.size &&
        !filters.brand &&
        !filters.price_min &&
        !filters.price_max
      ) {
        filters.search_txt = query;
      }

      filters.ai = "1";

      await router.push({
        path: "/frontend/categories",
        query: filters,
      });

      message_prompt.value = "";
    } catch (error) {
      console.error("AI product search failed", error);
      await router.push({
        path: "/frontend/categories",
        query: { search_txt: query, ai: "1" },
      });
    } finally {
      pending.value = false;
      isDialog.value = false;
    }
  };

  return {
    message_prompt,
    pending,
    isDialog,
    aiProductSearch,
  };
};
