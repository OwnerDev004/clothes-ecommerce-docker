import { useInfiniteScroll } from "@vueuse/core";
import { computed, ref } from "vue";
import {
  normalizeProductCard,
  resolveApiImageUrl,
  type ProductApiRecord,
  type ProductCardItem,
} from "~/utils/product";

type BrandRecord = {
  id: number | string;
  name?: string;
  image_url?: string;
};

type CategoryRecord = {
  id: number | string;
  name?: string;
  slug?: string;
  image_url?: string;
};

type CollectionRecord = {
  id: number | string;
  name?: string;
  slug?: string;
  image_url?: string;
};

type HomeCatalogPayload = {
  brands: BrandRecord[];
  categories: CategoryRecord[];
  collections: CollectionRecord[];
};
type CustomerReviewFeedback = {
  id: number | string;
  rating: number;
  customer: any;
  comment?: string;
};

export type HeroSlideItem = {
  id: number | string;
  title: string;
  subtitle?: string | null;
  description?: string | null;
  image_url?: string | null;
  gradient?: string | null;
  link_url?: string | null;
  link_text?: string | null;
  sort_order: number;
  status: boolean | number;
};

export const useHomeProducts = () => {
  const config = useRuntimeConfig();
  const apiBase = (config.public.apiBase || "").replace(/\/$/, "");

  const products = useState<ProductCardItem[]>("home-products", () => []);
  const brands = useState<BrandRecord[]>("home-brands", () => []);
  const categories = useState<CategoryRecord[]>("home-categories", () => []);
  const collections = useState<CollectionRecord[]>(
    "home-collections",
    () => [],
  );
  const customers_review = useState<CustomerReviewFeedback[]>(
    "home-customer-reviews",
    () => [],
  );
  const isLoadingProducts = useState<boolean>(
    "home-loading-products",
    () => false,
  );
  const isLoadingCatalogMeta = useState<boolean>(
    "home-loading-catalog-meta",
    () => false,
  );
  const isLoadingCustomerReview = useState<boolean>(
    "home-loading-customer-reviews",
    () => false,
  );
  const productError = useState<string>("home-product-error", () => "");
  const currentPage = useState<number>("home-current-page", () => 1);
  const hasMoreProducts = useState<boolean>(
    "home-has-more-products",
    () => true,
  );
  const perPage = 8;
  const loadMoreTrigger = ref<HTMLElement | null>(null);

  const topSellingProducts = useState<ProductCardItem[]>(
    "home-top-selling",
    () => [],
  );
  const isLoadingTopSelling = useState<boolean>(
    "home-loading-top-selling",
    () => false,
  );
  const heroSlides = useState<HeroSlideItem[]>("home-hero-slides", () => []);
  const isLoadingHeroSlides = useState<boolean>("home-loading-hero-slides", () => false);

  const collectionItems = computed(() => collections.value.slice(0, 4));

  const resolveVisualImage = (
    input?: string,
    fallback = "/img/products/default_image.webp",
  ) => {
    return resolveApiImageUrl(apiBase, input, fallback);
  };

  const getCollectionSpanClass = (index: number) => {
    const position = index % 4;
    return position === 1 || position === 2 ? "md:col-span-2" : "";
  };

  const fetchProducts = async (reset = false) => {
    if (isLoadingProducts.value) {
      return;
    }

    if (!hasMoreProducts.value && !reset) {
      return;
    }

    if (reset) {
      currentPage.value = 1;
      hasMoreProducts.value = true;
      products.value = [];
    }

    isLoadingProducts.value = true;
    productError.value = "";

    try {
      const response: any = await $fetch(`${apiBase}/products`, {
        method: "GET",
        query: {
          page: currentPage.value,
          per_page: perPage,
        },
      });

      const rows = Array.isArray(response?.data) ? response.data : [];
      products.value.push(
        ...rows.map((row: ProductApiRecord) =>
          normalizeProductCard(row, apiBase),
        ),
      );

      const current = Number(response?.meta?.current_page || currentPage.value);
      const last = Number(response?.meta?.last_page || current);
      hasMoreProducts.value = current < last;
      currentPage.value = current + 1;
    } catch (error: any) {
      productError.value = error?.data?.message || "Failed to load products.";
    } finally {
      setTimeout(() => {
        isLoadingProducts.value = false;
      }, 150);
    }
  };

  const fetchCatalogMeta = async () => {
    isLoadingCatalogMeta.value = true;
    try {
      const [brandResponse, categoryResponse, collectionResponse] =
        await Promise.allSettled([
          $fetch(`${apiBase}/brands`, { method: "GET" }),
          $fetch(`${apiBase}/categories`, { method: "GET" }),
          $fetch(`${apiBase}/collections`, { method: "GET" }),
        ]);

      const parseRows = <T>(result: PromiseSettledResult<any>): T[] => {
        return result.status === "fulfilled" &&
          Array.isArray(result.value?.data)
          ? result.value.data
          : [];
      };

      const payload: HomeCatalogPayload = {
        brands: parseRows<BrandRecord>(brandResponse),
        categories: parseRows<CategoryRecord>(categoryResponse),
        collections: parseRows<CollectionRecord>(collectionResponse),
      };

      brands.value = payload.brands;
      categories.value = payload.categories;
      collections.value = payload.collections;
    } catch (error) {
      console.warn("Failed to load home catalog meta:", error);
    } finally {
      await new Promise((resolve) => setTimeout(resolve, 5000));
      isLoadingCatalogMeta.value = false;
    }
  };

  const fetchCustomerReview = async () => {
    isLoadingCustomerReview.value = true;
    try {
      const response: any = await $fetch(`${apiBase}/products/top_review`, {
        method: "GET",
      });
      customers_review.value = response.data;
    } catch (error: any) {
      customers_review.value = [];
      console.warn("Failed to load customer reviews:", error);
    } finally {
      isLoadingCustomerReview.value = false;
    }
  };

  const fetchHeroSlides = async () => {
    isLoadingHeroSlides.value = true;
    try {
      const response: any = await $fetch(`${apiBase}/hero-slides`, {
        method: "GET",
      });
      heroSlides.value = Array.isArray(response?.data) ? response.data : [];
    } catch (error) {
      console.warn("Failed to load hero slides:", error);
      heroSlides.value = [];
    } finally {
      isLoadingHeroSlides.value = false;
    }
  };

  const fetchTopSellingProducts = async () => {
    if (isLoadingTopSelling.value) return;
    isLoadingTopSelling.value = true;
    try {
      const response: any = await $fetch(`${apiBase}/products/top-selling`, {
        method: "GET",
      });
      const rows = Array.isArray(response?.data) ? response.data : [];
      topSellingProducts.value = rows.map((row: ProductApiRecord) =>
        normalizeProductCard(row, apiBase),
      );
    } catch (error) {
      console.warn("Failed to load top selling products:", error);
      topSellingProducts.value = [];
    } finally {
      isLoadingTopSelling.value = false;
    }
  };

  const loadInitialHomeData = async () => {
    await Promise.allSettled([
      fetchHeroSlides(),
      fetchCatalogMeta(),
      fetchProducts(true),
      fetchCustomerReview(),
      fetchTopSellingProducts(),
    ]);
  };

  useInfiniteScroll(
    loadMoreTrigger,
    () => {
      void fetchProducts();
    },
    {
      distance: 200,
      canLoadMore: () => hasMoreProducts.value && !isLoadingProducts.value,
    },
  );

  return {
    products,
    brands,
    categories,
    collections,
    heroSlides,
    isLoadingHeroSlides,
    isLoadingProducts,
    isLoadingCatalogMeta,
    isLoadingCustomerReview,
    isLoadingTopSelling,
    productError,
    currentPage,
    hasMoreProducts,
    loadMoreTrigger,
    topSellingProducts,
    collectionItems,
    customers_review,
    fetchProducts,
    fetchTopSellingProducts,
    loadInitialHomeData,
    getCollectionSpanClass,
    resolveVisualImage,
  };
};
