import { defineStore } from "pinia";
import { ref } from "vue";

export type AppSetting = {
  app_name: string;
  shipping_defaul_fee: number;
  shipping_rates: any;
  base_currency_code: string;
};

export const useAppSettingStore = defineStore(
  "appSetting",
  () => {
    const settings = ref<AppSetting>({
      app_name: "",
      shipping_defaul_fee: 0,
      shipping_rates: {},
      base_currency_code: "",
    });

    const loaded = ref(false);

    const setSettings = (payload: any) => {
<<<<<<< Updated upstream
      settings.value.app_name =
        payload?.data?.app_name ?? settings.value.app_name;
      settings.value.shipping_defaul_fee =
        payload?.data?.shipping_fee ?? settings.value.shipping_defaul_fee;
      settings.value.shipping_rates =
        payload?.data?.shipping_rates ?? settings.value.shipping_rates;
=======
      const setting = normalizeSetting(payload);
      if (!setting) {
        loaded.value = true;
        return;
      }

      settings.value.app_name = setting.app_name ?? settings.value.app_name;
      settings.value.shipping_defaul_fee =
        setting.shipping_fee ?? settings.value.shipping_defaul_fee;
      settings.value.shipping_rates = Array.isArray(setting.shipping_rates)
        ? setting.shipping_rates
        : settings.value.shipping_rates;
>>>>>>> Stashed changes
      settings.value.base_currency_code =
        payload?.data?.currency_code ?? settings.value.base_currency_code;
      loaded.value = true;
    };

    const fetchSettings = async () => {
      const config = useRuntimeConfig();
      const apiBase = `${config.public.apiBase}`;
      const response: any = await $fetch(`${apiBase}/app_setting`);
      setSettings(response);
    };

    return {
      settings,
      loaded,
      setSettings,
      fetchSettings,
    };
  },
  {
    persist: {
      key: "app_setting",
      storage: piniaPluginPersistedstate.localStorage(),
    },
  },
);
