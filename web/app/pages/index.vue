<script setup lang="ts">
   import { useRouter } from 'nuxt/app'
    const router = useRouter()
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

const brands = reactive([
  {
    id: 1,
    name: "versace",
    img: "/img/brand/brand1.png",
  },
  {
    id: 2,
    name: "zara",
    img: "/img/brand/brand2.png",
  },
  {
    id: 3,
    name: "gucci",
    img: "/img/brand/brand3.png",
  },
  {
    id: 4,
    name: "prada",
    img: "/img/brand/brand4.png",
  },
  {
    id: 5,
    name: "calvin klein",
    img: "/img/brand/brand5.png",
  },
]);

const onSlideChange = (swiper: any) => {
  const totalSlides = swiper.slides.length;

  swiper.slides.forEach((slide: any) => {
    slide.style.opacity = 1; // Reset opacity for all slides
  });

  const firstVisibleSlide = swiper.activeIndex - 2;
  const lastVisibleSlide = firstVisibleSlide + 4;

  if (swiper.slides[firstVisibleSlide]) {
    swiper.slides[firstVisibleSlide].style.opacity = 0.4;
  }
  if (swiper.slides[lastVisibleSlide] && lastVisibleSlide < totalSlides) {
    swiper.slides[lastVisibleSlide].style.opacity = 0.4;
  }
};

const viewProduct = (id:number | string) =>{
  router.push('/frontend/product_detail/'+id);
}
const viewCategory = (id:number | string) =>{
  router.push('/frontend/categories/'+id);
}
</script>
<template>
  <main>
    <!-- Slide Section -->
    <section class="">
      <Swiper
        :modules="[SwiperAutoplay]"
        :space-between="1"
        :loop="true"
        :autoplay="{
          delay: 5000,
          disableOnInteraction: false,
        }"
      >
        <SwiperSlide v-for="slide in 10" :key="slide" class="bg-gray">
          <div class="px-5  desktop:container flex flex-col lg:flex-row items-center gap-4 sm:gap-6 pt-5">
            <!-- Text Section -->
            <div class="w-full lg:w-1/2 xl:w-2/5 flex flex-col justify-center">
              <p
                class="font-Poppins text-5xl md:text-5xl font-bold leading-tight"
              >
                FIND CLOTHES THAT MATCH YOUR STYLE
              </p>
              <p class="font-Lato font-thin mt-2">
                Browse through our diverse range of meticulously crafted
                garments, designed to bring out your individuality and cater to
                your sense of style.
              </p>
              <button
                class="bg-black text-white rounded-full py-3 px-8 mt-4 w-full desktop:w-[210px]"
              >
                Shop Now
              </button>
            </div>

            <!-- Image Section -->
            <div class="w-full desktop:w-1/2 flex justify-center ml-auto">
              <NuxtImg
                sizes="sm:100vw md:400px lg:650px"
                src="/img/slide-1.png"
                format="webp"
                densities="x1"
                alt="Fashion Clothing"
              />
            </div>
          </div>
        </SwiperSlide>
      </Swiper>
    </section>

    <!-- Brand Section -->
    <section class="py-10 bg-black">
      <Swiper
        :modules="[SwiperAutoplay]"
        :slides-per-view="4"
        :space-between="1"
        :loop="true"
        :autoplay="{
          delay: 5000,
          disableOnInteraction: false,
        }"
      >
        <SwiperSlide v-for="slide in brands" :key="slide.id">
          <div class="p-4 bg-gray-200 flex justify-center">
            <NuxtImg
              :src="slide.img"
              :alt="slide.name"
              class="w-[100px] md:w-[150px]"
              format="webp"
              densities="x1"
            />
          </div>
        </SwiperSlide>
      </Swiper>
    </section>

    <!-- NEW ARRIVALS -->
    <section class="px-5 desktop:container py-10">
      <h1
        class="font-Poppins text-4xl md:text-6xl leading-tight text-center py-4 font-extrabold"
      >
        NEW ARRIVALS
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
           @click="viewProduct(item.id)"
          />
        </template>
      </div>
      <div class="flex justify-center mt-5">
        <button
          class="border rounded-[64px] p-4 w-full md:w-1/4 xl:w-1/6 outline-none bg-transparent text-black hover:bg-black hover:text-white"
        >
          View All
        </button>
      </div>
      <div class="border-b border-zinc-300 mt-10"></div>
    </section>

    <!-- TOP SELLING -->
    <section class="px-5 desktop:container py-10 border-b-gray">
      <h1
        class="font-Poppins text-4xl md:text-6xl leading-tight text-center py-4 font-extrabold"
      >
        TOP SELLING
      </h1>

      <!-- card -->
      <div
        class="grid gap-5 grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
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
            @click="viewProduct(item.id)"
          />
        </template>
      </div>
      <div class="flex justify-center mt-5">
        <button
          class="border rounded-[64px] p-4 w-full md:w-1/4 xl:w-1/6 outline-none bg-transparent text-black hover:bg-black hover:text-white"
        >
          View All
        </button>
      </div>
    </section>

    <!-- Dress Styles -->

    <section class="px-5 desktop:container  py-10">
      <div class="bg-gray rounded-3xl text-center py-10">
        <h1
          class="font-Poppins text-4xl md:text-5xl leading-tight text-center py-4 font-extrabold"
        >
          BROWSE BY DRESS STYLE
        </h1>

        <div class="grid gap-5 grid-cols-1 md:grid-cols-3 px-6 md:px-20 py-10">
          <!-- Casual Style Card -->
          <div
            class="overflow-hidden bg-[#FFFFFF] flex flex-row items-center justify-center rounded-2xl relative h-[190px]"
             @click="viewCategory(1)"
            >
            <h1
              class="text-xl font-semibold mb-4 absolute top-10 text-black left-10"
            >
              Casual
            </h1>
            <NuxtImg
              sizes="sm:100vw md:669px"
              src="/img/dress_styles/style1.png"
              format="webp"
              densities="x1"
              alt="Casual Style Image"
              class="w-full h-auto"
            />
          </div>

          <!-- Formal Style Card (Spans 2 columns) -->
          <div
            class="overflow-hidden bg-[#FFFFFF] flex flex-row items-center justify-center col-span-1 md:col-span-2 rounded-2xl relative h-[190px]"
            @click="viewCategory(2)"
            >
            <h1
              class="text-xl font-semibold mb-4 absolute top-10 text-black left-10"
            >
              Formal
            </h1>
            <NuxtImg
              sizes="sm:100vw md:669px"
              src="/img/dress_styles/style2.png"
              format="webp"
              densities="x1"
              alt="Casual Style Image"
              class="w-full h-auto"
            />
          </div>

          <!-- Casual Style Card (Spans 2 columns) -->
          <div
            class="overflow-hidden bg-[#FFFFFF] flex items-center justify-center col-span-1 md:col-span-2 rounded-2xl p-4 relative h-[190px]"
          @click="viewCategory(3)"
            >
            <h1
              class="text-xl font-semibold mb-4 absolute top-10 text-black left-10"
            >
              Casual
            </h1>
            <NuxtImg
              sizes="sm:100vw md:669px"
              src="/img/dress_styles/style3.png"
              format="webp"
              densities="x1"
              alt="Casual Style Image"
              class="w-full h-auto"
            />
          </div>

          <!-- Formal Style Card -->
          <div
            class="overflow-hidden bg-[#FFFFFF] flex flex-row items-center justify-center rounded-2xl relative h-[190px]"
          @click="viewCategory(4)"
            >
            <h1
              class="text-xl font-semibold mb-4 absolute top-10 text-black left-10"
            >
              Gym
            </h1>
            <NuxtImg
              sizes="sm:100vw md:669px"
              src="/img/dress_styles/style4.png"
              format="webp"
              densities="x1"
              alt="Casual Style Image"
              class="w-full h-auto"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- customer reviews -->
    <section class="py-10">
      <h1
        class="px-2 desktop:container text-center font-Poppins text-[2rem] md:text-5xl leading-tight py-4 font-extrabold desktop:"
      >
        OUR HAPPY CUSTOMERS
      </h1>
      <Swiper
        :modules="[SwiperAutoplay]"
        :slides-per-view="4.1"
        :space-between="20"
        :breakpoints="{
          '0':{
            slidesPerView: 1,
          },
          '375': {
            slidesPerView: 1.5,
            spaceBetween: 20,
          },
          '992': {
            slidesPerView: 4.1,
            spaceBetween: 20,
          },
        }"
        :loop="true"
        :centered-slides="true"
        @slideChange="onSlideChange"
        autoplay
      >
        <SwiperSlide v-for="(slide, index) in 10" :key="index">
          <div class="p-4 bg-gray flex justify-center flex-col">
            <SharesRating :stars-num="5" :rating-amount="0" />
            <h1 class="font-Poppins text-xl font-bold">Sarah M.</h1>
            <p class="font-Lato">
              I'm blown away by the quality and style of the clothes I received
              from Shop.co. From casual wear to elegant dresses, every piece
              I've bought has exceeded my expectations.”
            </p>
          </div>
        </SwiperSlide>
      </Swiper>
    </section>
  </main>
</template>

<style scoped>
/* Scoped styles if necessary */
.swiper-slide {
  /* Default style for swiper slide items */
  transition: opacity 0.3s ease;
}
</style>
