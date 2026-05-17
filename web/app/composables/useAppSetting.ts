import { storeToRefs } from "pinia";
import { computed, ref } from "vue";
import { useAppSettingStore } from "~/stores/appSettingStore";
import {
  convertCurrencyAmount,
  formatMoney,
  normalizeCurrencyCode,
} from "~/utils/currency";

export type appSetting = {
  app_name: string;
  shipping_defaul_fee: number;
  shipping_rates: any[];
  base_currency_code: string;
  default_currency_code: string;
  exchange_rate: number;
};
export const useAppSetting = () => {
  const store = useAppSettingStore();
  const { settings, loaded } = storeToRefs(store);

  const appSetting = computed<appSetting>(() => ({
    app_name: settings.value.app_name,
    shipping_defaul_fee: Number(settings.value.shipping_defaul_fee || 0),
    shipping_rates: Array.isArray(settings.value.shipping_rates)
      ? settings.value.shipping_rates
      : [],
    base_currency_code: normalizeCurrencyCode(
      settings.value.base_currency_code || settings.value.default_currency_code,
    ),
    default_currency_code: normalizeCurrencyCode(
      settings.value.default_currency_code || settings.value.base_currency_code,
    ),
    exchange_rate: Number(settings.value.exchange_rate || 0),
  }));

  const defaultCurrencyCode = computed(() =>
    normalizeCurrencyCode(appSetting.value.default_currency_code),
  );
  const exchangeRate = computed(() =>
    Number(appSetting.value.exchange_rate || 0),
  );
  const supportedPaymentCurrencies = computed(() => {
    const defaultCurrency = defaultCurrencyCode.value;
    const alternateCurrency = defaultCurrency === "USD" ? "KHR" : "USD";

    return [
      {
        code: defaultCurrency,
        label: `${defaultCurrency} (Default)`,
      },
      {
        code: alternateCurrency,
        label: alternateCurrency,
      },
    ];
  });

  const shippingProvince = ref("");
  const shippingRateByProvince = computed(() => {
    const rateMap: Record<string, number> = {};
    appSetting.value.shipping_rates.forEach((item: any) => {
      if (!item?.slug) {
        return;
      }
      rateMap[String(item.slug)] = Number(item.fee || 0);
    });
    return rateMap;
  });

  const computeShippingFee = (province: string): number => {
    const fee = shippingRateByProvince.value[String(province || "")];
    if (fee != null && !Number.isNaN(fee)) {
      return fee;
    }
    return Number(appSetting.value.shipping_defaul_fee || 0);
  };

  const convertAmount = (
    amount: unknown,
    fromCurrency: unknown,
    toCurrency: unknown,
  ) => {
    return convertCurrencyAmount(
      amount,
      fromCurrency,
      toCurrency,
      exchangeRate.value,
    );
  };

  const formatAppMoney = (amount: unknown, currency?: unknown) => {
    return formatMoney(amount, currency || defaultCurrencyCode.value);
  };

  const shippingFee = computed(() =>
    computeShippingFee(shippingProvince.value),
  );

  let pendingFetch: Promise<void> | null = null;
  const fetchAppSetting = async (force = false) => {
    if (loaded.value && !force) {
      return settings.value;
    }

    if (!pendingFetch) {
      pendingFetch = store.fetchSettings().finally(() => {
        pendingFetch = null;
      });
    }

    await pendingFetch;
    return settings.value;
  };

  return {
    shippingRateByProvince,
    appSetting,
    shippingProvince,
    shippingFee,
    computeShippingFee,
    defaultCurrencyCode,
    exchangeRate,
    supportedPaymentCurrencies,
    convertAmount,
    formatAppMoney,
    fetchAppSetting,
  };
};
