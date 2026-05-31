import { defineStore } from "pinia";
import { ref } from "vue";

export type AppSetting = {
  app_name: string;
  shipping_defaul_fee: number;
  shipping_rates: any[];
  base_currency_code: string;
  default_currency_code: string;
  exchange_rate: number;
};

const slugifyProvince = (value: string) =>
  String(value || "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "-")
    .replace(/_+/g, "-");

const normalizeShippingRate = (item: any) => {
  const province = String(item?.province || "").trim();
  const slug = String(item?.slug || slugifyProvince(province)).trim();

  return {
    ...item,
    province,
    slug,
    fee: Number(item?.fee || 0),
  };
};

export const useAppSettingStore = defineStore(
  "appSetting",
  () => {
    const settings = ref<AppSetting>({
      app_name: "",
      shipping_defaul_fee: 0,
      shipping_rates: [],
      base_currency_code: "",
      default_currency_code: "",
      exchange_rate: 0,
    });

    const loaded = ref(false);

    const normalizeSetting = (payload: any) => {
      if (Array.isArray(payload?.data)) {
        return payload.data[0] ?? null;
      }
      return payload?.data ?? null;
    };

    const setSettings = (payload: any) => {
      const setting = normalizeSetting(payload);
      if (!setting) {
        loaded.value = true;
        return;
      }

      settings.value.app_name = setting.app_name ?? settings.value.app_name;
      settings.value.shipping_defaul_fee =
        setting.shipping_fee ?? settings.value.shipping_defaul_fee;
      settings.value.shipping_rates = Array.isArray(setting.shipping_rates)
        ? setting.shipping_rates.map(normalizeShippingRate)
        : settings.value.shipping_rates;
      settings.value.base_currency_code =
        setting.base_currency_code ??
        setting.default_currency_code ??
        setting.currency_code ??
        settings.value.base_currency_code;
      settings.value.default_currency_code =
        setting.default_currency_code ??
        setting.currency_code ??
        settings.value.default_currency_code;
      settings.value.exchange_rate = Number(
        setting.exchange_rate ?? settings.value.exchange_rate ?? 0,
      );
      loaded.value = true;
    };

    const fetchSettings = async () => {
      const config = useRuntimeConfig();
      const apiBase = process.server
        ? `${config.apiBaseInternal || config.public.apiBase}`
        : `${config.public.apiBase}`;
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
