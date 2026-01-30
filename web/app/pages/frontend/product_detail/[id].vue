<script setup lang="ts">
import CardProduct from '~/components/frontend/CardProduct.vue';

const tablists = ref([
  {
    id: 1,
    name: "Product Details",
  },
  {
    id: 2,
    name: "Rating & Reviews",
  },
  {
    id: 3,
    name: "FAQs",
  },
]);
    // items
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
    ]);
const dropdownOptions = ref([
  {
    id: 1,
    label: "Latest",
  },
  {
    id: 2,
    label: "Oldest",
  },
]);
const sortBy = ref("");
const tabIndex = ref(0);
const tabClick = (index: number) => {
  tabIndex.value = index;
};
</script>
<template>
  <div class="px-5 desktop:container">
    <div class="breadcrumbs text-sm">
      <ul>
        <li><a>Home</a></li>
        <li><a>Documents</a></li>
        <li>Add Document</li>
      </ul>
    </div>

    <!-- products -->
    <SharesProduct />

    <!-- tabs -->
    <div role="tablist" class="tabs tabs-bordered">
      <a
        role="tab"
        class="tab"
        :class="tabIndex == index ? 'tab-active' : ''"
        v-for="(tab, index) in tablists"
        :key="tab.id"
        @click="tabClick(index)"
      >
        <h1>{{ tab.name }}</h1>
      </a>
    </div>
    <!-- contents -->
    <div class="w-full">
      <!-- content1 -->
      <div class="bg-red p-3" v-if="tabIndex == 0">
        <h1>All Reviews (402)</h1>
      </div>
      <!-- content2 -->
      <div class="p-3" v-if="tabIndex == 1">
        <div class="flex justify-between">
          <h1 class="text-lg sm:text-2xl">All Reviews (402)</h1>
          <div class="flex gap-2">
            <button class="bg-gray w-12 h-12 rounded-full text-2xl p-3">
              <Icon name="lets-icons:filter" class="text-black" />
            </button>
            <!-- <SharesDropdown :title="'Lastest'" :options="['Item 1', 'Item 2', 'Item 3']" /> -->
            <SharesDropdown
              v-model="sortBy"
              :options="dropdownOptions"
              class="hidden desktop:block"
            />
            <button
              class="bg-black text-white text-xs lg:text-md w-auto px-1 desktop:w-[300px] rounded-3xl"
            >
              Write a Review
            </button>
          </div>
        </div>

        <!-- contents -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-10">
          <div class="border border-gray rounded-2xl p-5">
            <SharesRating :rating-amount="0" :stars-num="4" />
            <h1 class="font-extrabold text-2xl font-Poppins">Samantha D.</h1>
            <p class="font-Lato">
              "I absolutely love this t-shirt! The design is unique and the
              fabric feels so comfortable. As a fellow designer, I appreciate
              the attention to detail. It's become my favorite go-to shirt."
            </p>
            <p class="pt-4 font-Lato">Posted on August 14, 2023</p>
          </div>
          <div class="border border-gray rounded-2xl p-5">
            <SharesRating :rating-amount="0" :stars-num="4" />
            <h1 class="font-extrabold text-2xl font-Poppins">Samantha D.</h1>
            <p class="font-Lato">
              "I absolutely love this t-shirt! The design is unique and the
              fabric feels so comfortable. As a fellow designer, I appreciate
              the attention to detail. It's become my favorite go-to shirt."
            </p>
            <p class="pt-4 font-Lato">Posted on August 14, 2023</p>
          </div>
          <div class="border border-gray rounded-2xl p-5">
            <SharesRating :rating-amount="0" :stars-num="4" />
            <h1 class="font-extrabold text-2xl font-Poppins">Samantha D.</h1>
            <p class="font-Lato">
              "I absolutely love this t-shirt! The design is unique and the
              fabric feels so comfortable. As a fellow designer, I appreciate
              the attention to detail. It's become my favorite go-to shirt."
            </p>
            <p class="pt-4 font-Lato">Posted on August 14, 2023</p>
          </div>
          <div class="border border-gray rounded-2xl p-5">
            <SharesRating :rating-amount="0" :stars-num="4" />
            <h1 class="font-extrabold text-2xl font-Poppins">Samantha D.</h1>
            <p class="font-Lato">
              "I absolutely love this t-shirt! The design is unique and the
              fabric feels so comfortable. As a fellow designer, I appreciate
              the attention to detail. It's become my favorite go-to shirt."
            </p>
            <p class="pt-4 font-Lato">Posted on August 14, 2023</p>
          </div>
        </div>
        <div class="flex justify-center pt-5">
          <button
            class="border rounded-[64px] p-4 w-full sm:w-[500px] outline-none bg-transparent text-black hover:bg-black hover:text-white"
          >
            Load More Reviews
          </button>
        </div>
      </div>
      <!-- content3 -->
      <div class="bg-gray p-3" v-if="tabIndex == 2">
        <h1>All Reviews (402)</h1>
      </div>
    </div>
    <!-- end tabs -->

    <!-- you might also like -->
    <section class=" py-10">
      <h1
        class="font-Poppins text-2xl md:text-4xl leading-tight text-center py-4 font-extrabold"
      >
      You might also like
      </h1>

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
    
      <div class="border-b border-zinc-300 mt-10"></div>
    </section>

  </div>
</template>

<style scoped></style>
