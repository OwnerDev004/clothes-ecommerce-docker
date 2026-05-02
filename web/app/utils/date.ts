import { useDateFormat } from "@vueuse/core";

export type DateLike = Date | number | string | null | undefined;

const DEFAULT_FORMAT = "MMM D, YYYY h:mm A";

export const formatAnyDate = (
  value: DateLike,
  format = DEFAULT_FORMAT,
  locales: Intl.LocalesArgument = "en-US",
  fallback = "",
) => {
  if (!value) {
    return fallback;
  }

  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) {
    return fallback;
  }

  return useDateFormat(date, format, { locales }).value;
};
