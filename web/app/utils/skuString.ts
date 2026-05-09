export const generateSkuFallback = (name: string, index: number) => {
  const prefix =
    (name || "VAR")
      .replace(/[^a-zA-Z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .slice(0, 8)
      .toUpperCase() || "VAR";

  return `${prefix}-${Date.now().toString(36).toUpperCase()}-${index + 1}`;
};
