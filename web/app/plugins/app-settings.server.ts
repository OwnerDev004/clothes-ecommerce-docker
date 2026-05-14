import { useAppSettingStore } from "~/stores/appSettingStore";

export default defineNuxtPlugin(async () => {
  const store = useAppSettingStore();
  if (!store.loaded || !store.settings.shipping_rates?.length) {
    try {
      await store.fetchSettings();
    } catch (err) {
      // don't break SSR if settings fail
      // eslint-disable-next-line no-console
      console.warn("Failed to load app settings:", err);
    }
  }
});
