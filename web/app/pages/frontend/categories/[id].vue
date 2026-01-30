<template>
 <div>
  <div class="px-5 desktop:container relative">
    <BaseBreadcrumb :icon="ArrowRight">
      <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
      <el-breadcrumb-item>Categories</el-breadcrumb-item>
    </BaseBreadcrumb>
 
    <!-- section -->
    <section class="flex gap-6">
      <div class="border rounded-2xl w-[28%] p-6 hidden lg:block">
        <section
          class="flex justify-between items-center border-b border-b-gray"
        >
          <h1 class="text-lg font-bold font-Poppins">Filters</h1>
          <button
            class="bg-gray-800 w-12 h-12 rounded-full text-2xl p-3 flex items-center justify-center"
          >
            <Icon name="lets-icons:filter" />
          </button>
        </section>
        <section
          class="flex justify-between items-center border-b border-b-gray"
        >
          <ul class="leading-10">
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl w-[200px]"
            >
              <p>T-shirts</p>
              <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Shorts <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Shirts <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Hoodie <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Jeans <Icon name="weui:arrow-filled" />
            </li>
          </ul>
        </section>
        <section class="border-b border-b-gray py-3">
          <h1 class="text-lg font-bold font-Poppins">Price</h1>
          <div class="card flex justify-center">
            <el-slider
              v-model="value"
              range
              placement="bottom"
              style="--el-slider-main-bg-color: black"
            />
            <p class="pt-4">{{ value[0] }}$ - {{ value[1] }}$</p>
          </div>
        </section>
        <section class="border-b border-b-gray py-3">
          <h1 class="text-lg font-bold font-Poppins">Colors</h1>

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
        <section class="border-b border-b-gray py-3">
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="grid grid-cols-2 gap-4">
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
        <section class="border-b border-b-gray py-3">
          <h1 class="text-lg font-bold font-Poppins">Dress Style</h1>

          <ul class="leading-10">
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl w-[200px]"
            >
              <p>Casual</p>
              <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Formal <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Party <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Gym <Icon name="weui:arrow-filled" />
            </li>
          </ul>
        </section>
        <button
          class="border rounded-[64px] p-4 w-full outline-none bg-transparent text-black hover:bg-black hover:text-white"
        >
          Apply Filter
        </button>
      </div>
      <div class="w-full rounded-lg">
        <div class="flex justify-between pb-5">
          <h2 class="text-black text-3xl font-semibold">Casual</h2>
          <button
            class="bg-gray-800 w-12 h-12 rounded-full text-2xl p-3 flex items-center justify-center lg:hidden bg-gray"
           @click="toggleFilter"
          >
          
            <Icon name="lets-icons:filter" />
          </button>
          <div class="hidden lg:block">
            <span class="font-Poppins text-zinc-400">
              Sort By:
            </span>
            <ClientOnly>
            <el-select
                v-model="sort_by_val"
                placeholder="Select"
                size="large"
                style="width: 240px"
              >
                <el-option
                  v-for="item in sortBy"
                  :key="item.id"
                  :label="item.label"
                  :value="item.id"
                />
              </el-select>
            </ClientOnly>
          </div>
        </div>

        <!-- card -->
        <div
          class="grid gap-5 grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4"
        >
          <template v-for="item in items">
            <FrontendCardProduct
              :title="item.title"
              :price="item.price"
              :img="item.img"
              :discount-amount="item.discount_amount"
              :discount-type="item.discount_type"
              :stars-num="item.stars_num"
              :rating-amount="item.rating_amount"
            />
          </template>
        </div>
        <div class="flex justify-center">
          <el-pagination
            :pager-count="5"
            layout="prev, pager, next"
            prev-text="⬅ Previous"
            next-text="Next ➞"
            :total="100"
          />
        </div>
      </div>
    </section>
   

  </div>
  <!-- dialog filters -->
  <el-dialog
    v-model="isToggleFilter"
    title="Filters"
    width="auto"
    class="!rounded-t-3xl !-bottom-[100px] !pt-5"
    
  >
  <div>
      <section
          class="flex justify-between items-center border-b border-b-gray"
        >
      
        </section>
        <section
          class="flex justify-between items-center border-b border-b-gray"
        >
          <ul class="leading-10">
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl w-[200px]"
            >
              <p>T-shirts</p>
              <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Shorts <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Shirts <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Hoodie <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Jeans <Icon name="weui:arrow-filled" />
            </li>
          </ul>
        </section>
        <section class="border-b border-b-gray py-3">
          <h1 class="text-lg font-bold font-Poppins">Price</h1>
          <div class="card flex justify-center">
            <el-slider
              v-model="value"
              range
              placement="bottom"
              style="--el-slider-main-bg-color: black"
            />
            <p class="pt-4">{{ value[0] }}$ - {{ value[1] }}$</p>
          </div>
        </section>
        <!-- Colors -->
        <section class="border-b border-b-gray py-3 ">
          <h1 class="text-lg font-bold font-Poppins">Colors</h1>

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
        <!-- Sizes -->
        <section class="border-b border-b-gray py-3">
          <div class="flex overflow-auto gap-2 hide-scrollbar">
            <div class="grid grid-cols-3 gap-4">
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
        <section class="border-b border-b-gray py-3">
          <h1 class="text-lg font-bold font-Poppins">Dress Style</h1>

          <ul class="leading-10">
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl w-[200px]"
            >
              <p>Casual</p>
              <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Formal <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Party <Icon name="weui:arrow-filled" />
            </li>
            <li
              class="flex justify-between cursor-pointer hover:bg-gray items-center px-2 rounded-xl"
            >
              Gym <Icon name="weui:arrow-filled" />
            </li>
          </ul>
        </section>
     </div>
    <template #footer>
      <div class="dialog-footer">
        <el-button
        @click="isToggleFilter = false"
          class="border rounded-[64px] p-4 w-full outline-none bg-transparent text-black hover:bg-black hover:text-white"
        >
          Apply Filter
        </el-button>
      </div>
    </template>
  </el-dialog>
 </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue';

interface sizeInterface {
  id: Number;
  name: String;
}
const items = reactive([
  {
    id: 1,
    title: "T-SHIRT WITH TAPE DETAILS",
    price: 4.5,
    img: "/img/products/product1.png",
    discount_amount: 20,
    discount_type: 1,
    stars_num: 1,
    rating_amount: 4,
  },
  {
    id: 2,
    title: "SKINNY FIT JEANS",
    price: 4.5,
    img: "/img/products/product2.png",
    discount_amount: 5,
    stars_num: 3,
    rating_amount: 23,
  },
  {
    id: 3,
    title: "CHECKERED SHIRT",
    price: 4.5,
    img: "/img/products/product3.png",
    discount_amount: 1,
    discount_type: 2,
    stars_num: 4,
    rating_amount: 26,
  },
  {
    id: 4,
    title: "SLEEVE STRIPED T-SHIRT",
    price: 4.5,
    img: "/img/products/product4.png",
    discount_amount: 20,
    discount_type: 1,
    stars_num: 2,
    rating_amount: 10,
  },
  {
    id: 1,
    title: "T-SHIRT WITH TAPE DETAILS",
    price: 4.5,
    img: "/img/products/product1.png",
    discount_amount: 20,
    discount_type: 1,
    stars_num: 1,
    rating_amount: 4,
  },
  {
    id: 2,
    title: "SKINNY FIT JEANS",
    price: 4.5,
    img: "/img/products/product2.png",
    discount_amount: 5,
    stars_num: 3,
    rating_amount: 23,
  },
  {
    id: 3,
    title: "CHECKERED SHIRT",
    price: 4.5,
    img: "/img/products/product3.png",
    discount_amount: 1,
    discount_type: 2,
    stars_num: 4,
    rating_amount: 26,
  },
  {
    id: 4,
    title: "SLEEVE STRIPED T-SHIRT",
    price: 4.5,
    img: "/img/products/product4.png",
    discount_amount: 20,
    discount_type: 1,
    stars_num: 2,
    rating_amount: 10,
  },
]);
const value = ref([20, 80]);

const colors = ref([
  { id: 1, name: "Red", hex: "#EF4444" },
  { id: 2, name: "Blue", hex: "#3B82F6" },
  { id: 3, name: "Green", hex: "#10B981" },
  { id: 4, name: "Yellow", hex: "#F59E0B" },
]);
const sizes = ref([
  { id: 1, name: "Small" },
  { id: 2, name: "Medium" },
  { id: 3, name: "Large" },
  { id: 4, name: "X-Large" },
]);

type sortByOption = {
  id: number;
  label: string;
};
const sortBy = ref([
  { id: 1, label: "Most Popular" },
  { id: 2, label: "New Items" },
  { id: 3, label: "Price (Hight to Low)" },
  { id: 4, label: "Price (Low to Hight)" },
]);
const sort_by_val = ref();

// Track the selected color
const selectedColor = ref(colors.value[0]);
const selectedSize = ref(<sizeInterface>sizes.value[0]);

// Function to change the selected color
const selectColor = (color: any) => {
  selectedColor.value = color;
};

const selectSize = (size: sizeInterface) => {
  selectedSize.value = size;
};
const isToggleFilter = ref(false)
    // toggle
    const toggleFilter = () =>{
      isToggleFilter.value = true;
      
    }
const checkScreenSize = () => {
  if(window.innerWidth >= 720) isToggleFilter.value = false ; // Tailwind's `md` breakpoint is 768px
};

// Add event listener to check screen size on resize
onMounted(() => {
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkScreenSize);
});

</script>

<style>
.example-showcase .el-dropdown-link {
  cursor: pointer;
  color: var(--el-color-primary);
  display: flex;
  align-items: center;
}
.el-pagination {
  width: 100% !important;
  display: flex !important;
  justify-content: space-between;
}

.el-pagination.is-background .btn-next {
  align-self: flex-start !important;
}

.el-pagination.is-background .btn-prev {
  align-self: flex-end !important;
}
</style>
