<script setup lang="ts">
import type { TabsPaneContext } from 'element-plus'
import { ArrowRight } from '@element-plus/icons-vue'
import { ref } from 'vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'

const {
  product,
  averageRating,
  relatedProducts,
  qtyAmount,
  selectedImage,
  selectedColor,
  selectedSizeId,
  activeIndex,
  productDeatil,
  ratingAndReviews,
  faqsDetail,
  pageLoading,
  pageErrorState,
  sectionLoading,
  reviewStats,
  reviewFilterDialogOpen,
  writeReviewDialogOpen,
  submittingReview,
  reviewFilters,
  reviewForm,
  sortBy,
  imageList,
  colorOptions,
  sizeOptions,
  selectedVariant,
  displayPrice,
  stockLabel,
  canAddToCart,
  increment,
  decrement,
  chooseColor,
  chooseSize,
  addToCart,
  openReviewFilterDialog,
  applyReviewFilter,
  openWriteReviewDialog,
  submitReview,
  refreshReviews,
  refreshProductDetail,
  viewProduct,
} = useProductDetail()

const tablists = ref([
  { id: 1, lable: "Product Details", name: 'pro_detail' },
  { id: 2, lable: "Rating & Reviews", name: 'rate_review' },
  { id: 3, lable: "FAQs", name: 'faqs' },
])

const dropdownOptions = ref([
  { id: 'latest', label: 'Latest' },
  { id: 'oldest', label: 'Oldest' },
])

const tabClick = (tab: TabsPaneContext) => {
  activeIndex.value = String(tab.paneName)
}

</script>

<template>
  <div class="px-5 desktop:container">
    <BaseBreadcrumb :icon="ArrowRight">
      <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
      <el-breadcrumb-item>Product Detail</el-breadcrumb-item>
    </BaseBreadcrumb>

    <div v-if="pageLoading" class="animate-pulse py-10">
      <div class="grid gap-8 desktop:grid-cols-[440px_1fr]">
        <div class="flex gap-3">
          <div class="hidden desktop:flex flex-col gap-3">
            <div v-for="n in 3" :key="n" class="h-[96px] w-[96px] rounded-2xl bg-gray-200"></div>
          </div>
          <div class="h-[360px] w-full rounded-3xl bg-gray-200 desktop:h-[530px]"></div>
        </div>

        <div class="space-y-4">

          <div class="h-10 w-3/4 rounded-2xl bg-gray-200"></div>
          <div class="h-6 w-1/3 rounded-full bg-gray-200"></div>
          <div class="h-8 w-40 rounded-full bg-gray-200"></div>
          <div class="space-y-3 pt-4">
            <div class="h-4 w-full rounded-full bg-gray-200"></div>
            <div class="h-4 w-5/6 rounded-full bg-gray-200"></div>
            <div class="h-4 w-2/3 rounded-full bg-gray-200"></div>
          </div>
          <div class="flex gap-3 pt-4">
            <div v-for="n in 4" :key="n" class="h-10 w-10 rounded-full bg-gray-200"></div>
          </div>
          <div class="flex gap-3 pt-4">
            <div v-for="n in 4" :key="`size-${n}`" class="h-12 w-24 rounded-3xl bg-gray-200"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="pageErrorState" class="py-16 text-center">
      <p class="text-red-600">{{ pageErrorState }}</p>
      <BaseButton class="mt-4 rounded-full border px-5 py-2 hover:bg-black hover:text-white"
        @click="refreshProductDetail">
        Retry
      </BaseButton>
    </div>

    <div v-else-if="product" class="flex items-center gap-16 flex-col desktop:flex-row mb-10">
      <!-- Rest of your product display template remains the same -->
      <div class="flex gap-14 flex-col-reverse desktop:flex-row items-center">
        <div class="grid grid-cols-3 desktop:grid-cols-1 gap-3">
          <button v-for="(image, imageIndex) in imageList" :key="imageIndex"
            class="focus:opacity-60 w-full desktop:w-[152px] h-full desktop:h-[167px] rounded-2lg"
            @click="selectedImage = image">
            <NuxtImg class="rounded-md hover:opacity-70 w-full h-full object-cover" :src="image" />
          </button>
        </div>

        <div class="w-full desktop:w-[440px] h-full desktop:h-[530px] rounded-3xl">
          <NuxtImg :src="selectedImage" class="rounded-3xl w-full h-full object-cover" />
        </div>
      </div>
      <div class="w-full desktop:w-1/2">
        <section class="border-b py-4 border-b-black">
          <h1 class="text-4xl font-extrabold">{{ product.name }}</h1>
          <SharesRating :rating-amount="averageRating" />
          <SharesDiscount :discount-amount="0" :price="displayPrice" class="!text-3xl font-extrabold"
            :discountPercentage="'text-xl'" />
          <p>{{ product.desc || 'No description.' }}</p>
          <p class="mt-2 text-sm text-gray-500">{{ stockLabel }}</p>
        </section>

        <section class="border-b py-4 border-b-black">
          <h2 class="pb-2">Select Colors</h2>
          <div class="flex gap-4">
            <button v-for="color in colorOptions" :key="color" :style="{ backgroundColor: color || '#9ca3af' }"
              class="w-10 h-10 rounded-full border border-gray-300 cursor-pointer hover:opacity-80 relative"
              @click="chooseColor(color)">
              <span v-if="selectedColor === color"
                class="text-white font-bold absolute inset-0 flex items-center justify-center">
                ✓
              </span>
            </button>
          </div>
        </section>

        <section class="border-b py-4 border-b-black">
          <h2 class="pb-2">Choose Size</h2>
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="grid grid-flow-col gap-4 overflow-x-visible whitespace-nowrap">
              <button v-for="size in sizeOptions" :key="size.id" class="w-[100px] p-3 rounded-3xl cursor-pointer"
                :class="selectedSizeId === size.id ? 'bg-black text-white' : 'bg-gray hover:bg-black hover:text-white'"
                @click="chooseSize(size.id)">
                {{ size.name }}
              </button>
            </div>
          </div>
        </section>

        <section class="border-b py-4 border-black">
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="flex gap-3 items-center bg-gray rounded-2xl">
              <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-l-2xl"
                @click="decrement">
                <Icon name="ic:baseline-minus" class="text-base" />
              </button>
              <p class="mx-2">{{ qtyAmount }}</p>
              <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-r-2xl"
                @click="increment">
                <Icon name="ic:round-plus" class="text-base" />
              </button>
            </div>
            <button
              class="p-3 border bg-black hover:bg-white text-white hover:text-black rounded-2xl w-full disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!canAddToCart" @click="addToCart">
              Add to Cart
            </button>
          </div>
        </section>
      </div>
    </div>

    <!-- Rest of your template remains the same (tabs, related products, dialogs) -->
    <div v-if="product && !pageLoading" role="tablist" class="tabs tabs-bordered mt-6">
      <el-tabs v-model="activeIndex" class="demo-tabs" @tab-click="tabClick" default-value="pro_detail">
        <el-tab-pane v-for="tab in tablists" :key="tab.name" :label="tab.lable" :name="tab.name">
          <div class="w-full">
            <div class="p-3" v-if="activeIndex == 'pro_detail'">
              <h1>Product details</h1>
              <ul v-if="productDeatil.length" class="space-y-2">
                <li v-for="item in productDeatil" :key="item.key" class="text-sm leading-6">
                  <span class="font-semibold">{{ item.label }}:</span>
                  {{ item.value }}
                </li>
              </ul>
              <p v-else class="text-sm text-gray-500">No product details available.</p>
            </div>
            <div class="p-3" v-else-if="activeIndex == 'rate_review'">
              <div class="flex justify-between">
                <h1 class="text-lg sm:text-2xl">
                  All Reviews ({{ reviewStats.total_reviews }}) - Avg {{ reviewStats.average_rating }}/5
                </h1>
                <div class="flex gap-2">
                  <BaseButton class="bg-gray w-12 h-12 rounded-full text-2xl p-3" @click="openReviewFilterDialog">
                    <Icon name="lets-icons:filter" class="text-black" />
                  </BaseButton>
                  <BaseSelect v-model="sortBy" :options="dropdownOptions" class="hidden desktop:block" />
                  <BaseButton class="bg-black text-white text-xs lg:text-md w-auto px-1 desktop:w-[300px] rounded-3xl"
                    @click="openWriteReviewDialog">
                    Write a Review
                  </BaseButton>
                </div>
              </div>

              <ul v-if="ratingAndReviews.length" class="mt-4 space-y-3">
                <li v-for="review in ratingAndReviews" :key="review.id" class="rounded-lg border p-3">
                  <p class="font-semibold">{{ review.customer_name }}
                    <el-rate v-model="review.rating" :max="5" disabled />
                  </p>
                  <p class="text-sm text-gray-700">{{ review.comment }}</p>
                </li>
              </ul>
              <p v-else class="mt-4 text-sm text-gray-500">No reviews yet.</p>
            </div>
            <div class="bg-gray p-3" v-else>
              <h1>FAQs</h1>
              <ul v-if="faqsDetail.length" class="space-y-3">
                <li v-for="faq in faqsDetail" :key="faq.id" class="rounded-lg bg-white p-3">
                  <p class="font-semibold">{{ faq.question }}</p>
                  <p class="text-sm text-gray-700">{{ faq.answer }}</p>
                </li>
              </ul>
              <p v-else class="text-sm text-gray-500">No FAQs available.</p>
            </div>
            <p v-if="sectionLoading" class="text-sm text-gray-500">Loading section content...</p>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>

    <section class="py-10">
      <h1 class="font-Poppins text-2xl md:text-4xl leading-tight text-center py-4 font-extrabold">
        You might also like
      </h1>
      <div class="grid gap-4 sm:grid-cols-2 tablet:grid-cols-3 desktop:grid-cols-4 xl:gap-6">
        <FrontendCardProduct v-for="item in relatedProducts" :key="item.id" :title="item.title" :price="item.price"
          :img="item.img" :discount-amount="item.discount_amount" :discount-type="item.discount_type"
          :rating-amount="item.average_rating" @click="viewProduct(item.id)" />
      </div>
      <div class="border-b border-zinc-300 mt-10"></div>
    </section>

    <el-dialog v-model="reviewFilterDialogOpen" title="Filter Reviews" width="420px">
      <div class="space-y-4">
        <div>
          <p class="mb-2 text-sm font-semibold">Sort by</p>
          <el-select v-model="reviewFilters.sort_by" class="w-full">
            <el-option label="Latest" value="latest" />
            <el-option label="Oldest" value="oldest" />
            <el-option label="Highest Rating" value="rating_high" />
            <el-option label="Lowest Rating" value="rating_low" />
          </el-select>
        </div>
        <div>
          <p class="mb-2 text-sm font-semibold">Rating</p>
          <el-select v-model="reviewFilters.rating" class="w-full" clearable placeholder="All ratings">
            <el-option :value="5" label="5 stars" />
            <el-option :value="4" label="4 stars" />
            <el-option :value="3" label="3 stars" />
            <el-option :value="2" label="2 stars" />
            <el-option :value="1" label="1 star" />
          </el-select>
        </div>
        <div class="flex items-center justify-between">
          <p class="text-sm font-semibold">Only my reviews</p>
          <el-switch v-model="reviewFilters.mine_only" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="reviewFilterDialogOpen = false">Cancel</el-button>
          <el-button type="primary" @click="applyReviewFilter">Apply</el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog v-model="writeReviewDialogOpen" title="Write a Review" width="520px">
      <div class="space-y-4">
        <div>
          <p class="mb-2 text-sm font-semibold">Rating</p>
          <el-rate v-model="reviewForm.rating" :max="5" />
        </div>
        <div>
          <p class="mb-2 text-sm font-semibold">Comment</p>
          <el-input v-model="reviewForm.comment" type="textarea" :rows="5" maxlength="1000" show-word-limit />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="writeReviewDialogOpen = false">Cancel</el-button>
          <el-button type="primary" :loading="submittingReview" @click="submitReview">Submit Review</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.hide-scrollbar {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
