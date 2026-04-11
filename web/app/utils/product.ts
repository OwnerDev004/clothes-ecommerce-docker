export type ProductImageRecord = {
  image_url?: string | null
}

export type ProductCardItem = {
  id: number | string
  title: string
  price: number
  img: string
  discount_amount: number
  discount_type?: number
  stars_num: number
  rating_amount: number
}

export type ProductApiRecord = {
  id: number | string
  name?: string | null
  price?: number | string | null
  category_id?: number | string | null
  thumbnail?: ProductImageRecord | null
  images?: ProductImageRecord[] | null
}

export const getBackendOrigin = (apiBase: string) => {
  return apiBase.replace(/\/api\/v\d+\/?$/, '')
}

export const resolveApiImageUrl = (
  apiBase: string,
  input?: string | null,
  fallback = '/img/products/default_image.webp',
) => {
  const backendOrigin = getBackendOrigin(apiBase)

  if (!input) {
    return fallback
  }

  if (/^https?:\/\//i.test(input)) {
    return input
  }

  if (input.startsWith('/')) {
    return `${backendOrigin}${input}`
  }

  return `${backendOrigin}/${input}`
}

export const extractResponseData = <T>(response: any): T | null => {
  const payload = response?.data?.data || response?.data || response
  return payload && typeof payload === 'object' ? (payload as T) : null
}

export const normalizeProductCard = (item: ProductApiRecord, apiBase: string): ProductCardItem => {
  const thumbnail = item?.thumbnail?.image_url || item?.images?.[0]?.image_url || ''
  const price = Number(item?.price || 0)

  return {
    id: item?.id,
    title: String(item?.name || 'Untitled product'),
    price: Number.isFinite(price) ? price : 0,
    img: resolveApiImageUrl(apiBase, thumbnail),
    discount_amount: 0,
    discount_type: undefined,
    stars_num: 5,
    rating_amount: 0,
  }
}

export const normalizeImageList = (apiBase: string, images: Array<ProductImageRecord | undefined | null>) => {
  const rows = images
    .map((image) => image?.image_url || '')
    .filter(Boolean)

  return Array.from(new Set(rows)).map((path) => resolveApiImageUrl(apiBase, path))
}
