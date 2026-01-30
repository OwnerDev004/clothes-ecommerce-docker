<template>
  <div class="flex items-center gap-16 flex-col desktop:flex-row mb-10">
    <div class="flex gap-14 flex-col-reverse desktop:flex-row items-center">
      <!-- Thumbnails -->
      <div class="grid grid-cols-3 desktop:grid-cols-1 gap-3">
        <button
          v-for="(image, imageIndex) in images"
          :key="imageIndex"
          class="focus:opacity-60 w-full desktop:w-[152px] h-full desktop:h-[167px] rounded-2lg"
          @click="
            fullSizeClick(image.fullsize);
            setCurrentIndex(imageIndex);
          "
          aria-label="View full-size image"
        >
          <NuxtImg
            class="rounded-md hover:opacity-70 w-full h-full object-cover"
            :src="image.thumbnail"
            :alt="`Thumbnail of image ${imageIndex + 1}`"
          />
        </button>
      </div>

      <!-- Main Image -->
      <div
        class="w-full desktop:w-[440px] h-full desktop:h-[530px] rounded-3xl"
      >
        <NuxtImg
          v-if="selectedImage"
          :src="selectedImage"
          @click="handleClick"
          class="rounded-3xl w-full h-full object-cover"
          alt="Selected product image"
        />
      </div>

      <!-- Lightbox Overlay -->
      <div
        v-show="isLightbox"
        @click="handleOverlayClick"
        class="flex items-center justify-center fixed inset-0 bg-black bg-opacity-50 z-50"
      >
        <div
          class="relative flex flex-col items-end w-full max-w-[90%] sm:max-w-[80%] md:max-w-[70%] lg:max-w-[60%] xl:max-w-[550px]"
        >
          <!-- Previous Button -->
          <NuxtImg
            src="/img/products_view/image1_2_thump.png"
            @click.stop="prevImage"
            class="hover:opacity-80 cursor-pointer bg-white rounded-full w-8 h-8 absolute left-2 sm:left-4 top-1/2 transform -translate-y-1/2 p-2"
            alt="Previous image"
          />

          <!-- Next Button -->
          <NuxtImg
            src="/img/products_view/image1_2_thump.png"
            @click.stop="nextImage"
            class="hover:opacity-80 cursor-pointer bg-white rounded-full w-8 h-8 absolute right-2 sm:right-4 top-1/2 transform -translate-y-1/2 p-2"
            alt="Next image"
          />

          <!-- Close Button (SVG) -->
          <svg
            class="fill-white active:fill-orange cursor-pointer absolute top-2 right-2 sm:top-4 sm:right-4"
            width="15"
            height="25"
            xmlns="http://www.w3.org/2000/svg"
            @click="handleClick"
          >
            <path
              d="m11.596.782 2.122 2.122L9.12 7.499l4.597 4.597-2.122 2.122L7 9.62l-4.595 4.597-2.122-2.122L4.878 7.5.282 2.904 2.404.782l4.595 4.596L11.596.782Z"
            />
          </svg>

          <!-- Lightbox Image -->
          <NuxtImg
            class="rounded-lg w-full h-auto object-contain mt-4"
            :src="images[currentIndex]?.fullsize"
            :alt="`Full-size image ${currentIndex + 1}`"
          />
        </div>
      </div>
    </div>

    <!-- Product Details -->
    <div class="w-full desktop:w-1/2">
      <section class="border-b py-4 border-b-black">
        <h1 class="text-4xl font-extrabold">One Life Graphic T-shirt</h1>
        <SharesRating :rating-amount="4" :stars-num="4" />
        <SharesDiscount
          :discount-amount="1"
          :price="20"
          :discount-type="2"
          class="!text-3xl font-extrabold"
          :discountPercentage="'text-xl'"
        />

        <p>
          These low-profile sneakers are your perfect casual wear companion.
          Featuring a durable rubber outer sole, they'll withstand everything
          the weather can offer.
        </p>
      </section>
      <section class="border-b py-4 border-b-black">
        <h2 class="pb-2">Select Colors</h2>
        <!-- Color Picker Options -->
        <div class="flex gap-4">
          <button
            v-for="(color, index) in colors"
            :key="index"
            :style="{ backgroundColor: color.hex }"
            @click="selectColor(color)"
            :aria-label="`Select color ${color.name}`"
            class="w-10 h-10 rounded-full border border-gray-300 cursor-pointer hover:opacity-80 relative"
          >
            <span
              v-if="selectedColor?.name === color.name"
              class="text-white font-bold absolute inset-0 flex items-center justify-center"
            >
              ✓
            </span>
          </button>
        </div>
      </section>
      <section class="border-b py-4 border-b-black">
        <h2 class="pb-2">Choose Size</h2>
        <div class="flex overflow-auto gap-2 hide-scrollbar">
          <div
            class="grid grid-flow-col gap-4 overflow-x-visible whitespace-nowrap"
          >
            <button
              v-for="(size, index) in sizes"
              :key="index"
              @click="selectSize(size)"
              :class="[
                'w-[100px] p-3 rounded-3xl cursor-pointer',
                selectedSize?.name === size.name
                  ? 'bg-black text-white'
                  : 'bg-gray',
                'hover:bg-black hover:text-white',
              ]"
            >
              {{ size.name }}
            </button>
          </div>
        </div>
      </section>
      <section class="border-b py-4 border-black">
  <div class="flex overflow-auto gap-2 hide-scrollbar">
    <div class="flex gap-3 items-center bg-gray rounded-2xl">
      <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-l-2xl cursor-pointer">
        <Icon name="ic:baseline-minus" class="text-base" @click="decrement"/>
      </button>
      <p class="mx-2">{{ qtyAmount }}</p> <!-- You can replace "1" with a variable to represent the count -->
      <button class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-r-2xl cursor-pointer" @click="increment">
        <Icon name="ic:round-plus" class="text-base" />
      </button>
    </div>
    <button class="p-3 border  bg-black hover:bg-white text-white hover:text-black rounded-2xl w-full">
      Add to Cart 
    </button>
  </div>
</section>

 
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
interface sizeInterface {
  id: Number;
  name: String;
}

const price = 125;
const isLightbox = ref(false);
const qtyAmount = ref(0);
const currentIndex = ref(0);
const selectedImage = ref("/img/products_view/image1_full.png");
const images = ref([
  {
    thumbnail: "/img/products_view/image1_1_thump.png",
    fullsize: "/img/products_view/image1_full.png",
  },
  {
    thumbnail: "/img/products_view/image1_2_thump.png",
    fullsize: "/img/products_view/image1_2_thump.png",
  },
  {
    thumbnail: "/img/products_view/image1_3_thump.png",
    fullsize: "/img/products_view/image1_3_thump.png",
  },
]);
const colors = ref([
  { id: 1, name: "Red", hex: "#EF4444" },
  { id: 2, name: "Blue", hex: "#3B82F6" },
  { id: 3, name: "Green", hex: "#10B981" },
  { id: 4, name: "Yellow", hex: "#F59E0B" },
]);

// Track the selected color
const selectedColor = ref(colors.value[0]);
// size
const sizes = ref([
  { id: 1, name: "Small" },
  { id: 2, name: "Medium" },
  { id: 3, name: "Large" },
  { id: 4, name: "X-Large" },
]);
const selectedSize = ref(<sizeInterface>sizes.value[0]);

// Function to change the selected color
const selectColor = (color: any) => {
  selectedColor.value = color;
};
// Function to change the selected size
const selectSize = (size: sizeInterface) => {
  selectedSize.value = size;
};
const increment = () => {
  qtyAmount.value += 1;
};

const decrement = () => {
  qtyAmount.value = qtyAmount.value > 0 ? qtyAmount.value - 1 : 0;
};

const handleClick = () => {
  isLightbox.value = !isLightbox.value;
};

const handleOverlayClick = (event: any) => {
  if (event.target === event.currentTarget) {
    isLightbox.value = false;
  }
};

const fullSizeClick = (image: string) => {
  selectedImage.value = image;
};

const setCurrentIndex = (index: number) => {
  currentIndex.value = index;
};

const nextImage = () => {
  currentIndex.value = (currentIndex.value + 1) % images.value.length;
};

const prevImage = () => {
  currentIndex.value =
    (currentIndex.value - 1 + images.value.length) % images.value.length;
};

const isLargeScreen = ref(false);

const checkScreenSize = () => {
  isLargeScreen.value = window.innerWidth >= 768; // Tailwind's `md` breakpoint is 768px
};

// Add event listener to check screen size on resize
onMounted(() => {
  checkScreenSize();
  window.addEventListener("resize", checkScreenSize);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", checkScreenSize);
});
</script>

<style scoped>
/* Add any additional styles here */
.hide-scrollbar {
  scrollbar-width: none; /* For Firefox */
  -ms-overflow-style: none; /* For Internet Explorer and Edge */
}

.hide-scrollbar::-webkit-scrollbar {
  display: none; /* For Chrome, Safari, and Opera */
}

/* Optional: Ensure the container has a maximum width to see the scrolling effect */
</style>
