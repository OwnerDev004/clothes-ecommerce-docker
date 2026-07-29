export const useAiFeature = () => {
  // config
  const config = useRuntimeConfig();
  //reactive
  const message_prompt = ref<string>("");
  const pending = ref<boolean>(false);
  const apiBase = config.public.apiBase;
  const router = useRouter();
  //functions
  const aiProductSearch = async () => {
    try {
      pending.value = true;
      await $fetch(`${apiBase}/ai-chat/product/filter`, {
        method: "POST",
        body: {
          message: message_prompt.value || "",
        },
      });
      const query = message_prompt.value.trim();
      if (query) {
        await router.push({
          path: "/frontend/categories",
          query: { search_txt: query, ai: "1" },
        });
      }
      message_prompt.value = "";
    } catch (error) {
      console.error("AI product search failed", error);
    } finally {
      pending.value = false;
    }
  };

  return {
    message_prompt,
    pending,
    aiProductSearch,
  };
};
