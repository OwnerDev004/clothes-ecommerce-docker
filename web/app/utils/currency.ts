export type CurrencyCode = "USD" | "KHR";

export const normalizeCurrencyCode = (value: unknown): CurrencyCode => {
  return String(value || "USD")
    .trim()
    .toUpperCase() === "KHR"
    ? "KHR"
    : "USD";
};

export const convertCurrencyAmount = (
  value: unknown,
  fromCurrency: unknown,
  toCurrency: unknown,
  exchangeRate: unknown,
) => {
  const amount = Number(value || 0);
  const from = normalizeCurrencyCode(fromCurrency);
  const to = normalizeCurrencyCode(toCurrency);
  const rate = Number(exchangeRate || 0);

  if (!Number.isFinite(amount)) {
    return 0;
  }

  if (from === to) {
    return amount;
  }

  if (!Number.isFinite(rate) || rate <= 0) {
    return amount;
  }

  if (from === "USD" && to === "KHR") {
    return amount * rate;
  }

  if (from === "KHR" && to === "USD") {
    return amount / rate;
  }

  return amount;
};

export const formatMoney = (value: unknown, currency: unknown = "USD") => {
  const currencyCode = normalizeCurrencyCode(currency);

  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currencyCode,
  }).format(Number(value || 0));
};
