<template>
   <div class="px-5 desktop:container ">
    <BaseBreadcrumb :icon="ArrowRight">
    <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
    <el-breadcrumb-item>Cart</el-breadcrumb-item>
    </BaseBreadcrumb>
  <div class="flex flex-col desktop:flex-row gap-3">
   <div class="w-full desktop:w-[65%] border border-gray rounded-2xl">
    <template v-for="(item, index) in items" :key="item.id">
        <FrontendCartProduct :name="item.name" :color="item.color" :price="item.price" :size="item.size" :img="item.img" @remove="removeProduct(index)"/>
    </template>
   </div>
   <div class="flex flex-col gap-5 border border-gray rounded-2xl w-full desktop:w-[35%] h-[80%] p-5 ">
         <h1 class="text-2xl font-semibold mb-3">Order Summary</h1>
         <div class="flex justify-between">
            <p class="text-slate-500">Subtotal</p>
            <p>$565</p>
         </div>
         <div class="flex justify-between">
            <p class="text-slate-500">Discount (-20%)</p>
            <p class="text-red">-$113</p>
         </div>
         <div class="flex justify-between">
            <p class="text-slate-500">Delivery Fee</p>
            <p>$15</p>
         </div>
         <hr class="text-gray">
         <div class="flex justify-between">
            <p>Total</p>
            <h2 class="text-xl font-semibold">$467</h2>
         </div>

         <div class="flex flex-col desktop:flex-row gap-3 mt-4 w-full">
            <div class="relative">
    <!-- Search Icon -->
    <Icon  name="ic:baseline-discount" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg" />

    <!-- Input Field -->
    <input
      type="text"
      class="rounded-[62px] bg-gray px-10 outline-none py-3 w-full text-sm desktop:text-lg"
      placeholder="Add Promo Code"
    />

        </div>
        <button class="bg-black rounded-3xl text-white p-3 lg:w-[100px]">
               Apply
             </button>
        </div>
             <!-- <div class="bg-gray rounded-3xl px-2 text-slate-500 flex  items-center py-2 w-">
                <Icon name="ic:baseline-discount" />
                <input type="text" class="bg-gray rounded-3xl focus:outline-none px-3  text-lg text-slate-500" placeholder="Add Promo Code">
             </div>
             <button class="bg-black rounded-3xl text-white lg:w-[100px]">
               Apply
             </button>
         </div> -->
         <button class="bg-black rounded-3xl text-white w-full p-3 mt-4">
               Go to Checkout <Icon name="humbleicons:arrow-right" />
             </button>
   </div>
  </div>
    </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'


const items = reactive([
    {
        id:1,
        name: 'Gradient Graphic T-shirt',
        size: 'XL',
        color: 'White',
        price: '100',
        img: 'product1.png'
    },
    {
        id:2,
        name: 'CHECKERED SHIRT',
        size: 'XL',
        color: 'Red',
        price: '180',
        img: 'product2.png'
    },
    {
        id:3,
        name: 'SKINNY FIT JEANS',
        size: 'XL',
        color: 'White',
        price: '240',
         img: 'product3.png'
    },
    {
        id:4,
        name: 'Gradient Graphic T-shirt',
        size: 'XL',
        color: 'White',
        price: '100',
         img: 'product4.png'
    },
    {
        id:5,
        name: 'Gradient Graphic T-shirt',
        size: 'XL',
        color: 'White',
        price: '100',
         img: 'product3.png'
    },
])


const removeProduct = (index:number) =>{
         items.splice(index, 1)
}

</script>

<style scoped>

</style>a