import { useAdminAuthStore } from "~/stores/adminAuthStore";
export type appSetting = {
  app_name: string;
  shipping_defaul_fee: number;
  shipping_rates: any[];
  base_currency_code: string;
};
export const useAppSetting = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
  const { accessToken } = storeToRefs(useAdminAuthStore());
  const appSetting = ref<appSetting>({
    app_name: "",
    shipping_defaul_fee: 0,
    shipping_rates: [],
    base_currency_code: "",
  });
  const shippingProvince = ref("phnom-penh");
  const shippingFee = ref(0);
  const normalizeShippingRates = (payload: any) => {
    return [...payload];
  };
  const fetchAppSetting = async () => {
    const response: any = await $fetch(`${apiBase}/app_setting`, {
      method: "GET",
    });

    appSetting.value.app_name = response?.data[0].app_name;
    appSetting.value.shipping_defaul_fee = response?.data[0].shipping_fee;
    appSetting.value.shipping_rates = normalizeShippingRates(
      response?.data[0].shipping_rates,
    );
    appSetting.value.base_currency_code = response?.data[0].currency_code;
    // set initial shipping fee once after settings load
    shippingFee.value = computeShippingFee(shippingProvince.value) || 0;
  };

  const shippingRateByProvince = computed(() => {
    const rateMap: Record<string, number> = {};

    appSetting.value.shipping_rates?.forEach((e: any) => {
      rateMap[e.slug] = e.fee;
    });

    return rateMap;
  });
  const computeShippingFee = (province: string): number => {
    const fee: any = shippingRateByProvince.value[province];
    if (fee != null && fee !== "") return Number(fee);
    return Number(appSetting.value.shipping_defaul_fee || 0);
  };

  watch(
    () => accessToken.value,
    () => {
      void fetchAppSetting();
    },
    { immediate: true },
  );

  let stopShippingWatch: (() => void) | undefined;
  stopShippingWatch = watch(
    () => shippingProvince.value,
    (value: any) => {
      if (value) {
        shippingFee.value = computeShippingFee(value) || 0;
      }
    },
  );

  return {
    shippingRateByProvince,
    appSetting,
    shippingProvince,
    shippingFee,
    computeShippingFee,
    fetchAppSetting,
  };
};
