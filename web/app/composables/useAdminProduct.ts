export type AdminProductImageRecord = {
  id?: number | string;
  image_url?: string | null;
  cloudinary_public_id?: string | null;
  image_type?: "thumbnail" | "gallery" | null;
  sort_order?: number | string | null;
};

export type AdminProductVariantRecord = {
  id?: number | string;
  sku?: string | null;
  color?: string | null;
  size?: {
    id?: number | string;
    name?: string | null;
    sort_order?: number | null;
  } | null;
  stock_quantity?: number | string | null;
  sell_price?: number | string | null;
  cost_price?: number | string | null;
  size_id?: number | string | null;
};

export type AdminProductRecord = {
  sku?: string | null;
  slug?: string | null;
  name: string;
  id: number | string;
  desc?: string | null;
  price?: number | string | null;
  category_id?: number | string | null;
  thumbnail?: { image_url?: string | null } | null;
  images?: AdminProductImageRecord[];
  variants?: AdminProductVariantRecord[];
  category?: { name?: string | null } | null;
  subCategory?: { name?: string | null } | null;
  brand?: { name?: string | null } | null;
  collections?: Array<{ name?: string | null }> | null;
};

export type AdminProductTableRow = AdminProductRecord & {
  image: string;
  previewImages: string[];
  sale_price: number | string;
  cost_price: number | string;
  qty: number;
  stock: "In Stock" | "Out Stock";
};

export const useAdminProduct = () => {
  const normalizeAdminProductRow = (
    product: AdminProductRecord,
  ): AdminProductTableRow => {
    const variants = Array.isArray(product.variants) ? product.variants : [];
    const firstVariant = variants[0] || {};
    const quantity = variants.reduce((sum, variant) => {
      return sum + Number(variant?.stock_quantity || 0);
    }, 0);
    const image =
      product.thumbnail?.image_url || product.images?.[0]?.image_url || "";

    return {
      ...product,
      image,
      previewImages: product.images?.length
        ? product.images
            .map((item) => String(item?.image_url || ""))
            .filter(Boolean)
        : image
          ? [image]
          : [],
      sale_price: firstVariant?.sell_price ?? product.price ?? "-",
      cost_price: firstVariant?.cost_price ?? "-",
      qty: quantity,
      stock: quantity > 0 ? "In Stock" : "Out Stock",
    };
  };

  return {
    normalizeAdminProductRow,
  };
};
