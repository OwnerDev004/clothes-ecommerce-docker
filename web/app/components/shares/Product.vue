<template>
  <div class="flex items-center gap-16 flex-col desktop:flex-row mb-10">
    <div class="flex gap-14 flex-col-reverse desktop:flex-row items-center">
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

let resizeListenerBound = false

// Add event listener to check screen size on resize
onMounted(() => {
  if (!import.meta.client) {
    return
  }
  checkScreenSize();
  window.addEventListener("resize", checkScreenSize);
  resizeListenerBound = true
});

onBeforeUnmount(() => {
  if (!resizeListenerBound || !import.meta.client) {
    return
  }
  window.removeEventListener("resize", checkScreenSize);
  resizeListenerBound = false
});
</script>

<style scoped>
/* Add any additional styles here */
.hide-scrollbar {
  scrollbar-width: none;
  /* For Firefox */
  -ms-overflow-style: none;
  /* For Internet Explorer and Edge */
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
  /* For Chrome, Safari, and Opera */
}

/* Optional: Ensure the container has a maximum width to see the scrolling effect */
</style>
