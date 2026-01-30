<template>
   <div class="px-5 desktop:container ">
    <BaseBreadcrumb :icon="ArrowRight">
    <el-breadcrumb-item :to="{ path: '/' }">Home</el-breadcrumb-item>
    <el-breadcrumb-item>Favorited</el-breadcrumb-item>
    </BaseBreadcrumb>
  <div class="flex flex-col desktop:flex-row gap-3">
   <div class="w-full desktop:w-[65%] border border-gray rounded-2xl">
    <template v-for="(item, index) in items" :key="item.id">
        <FrontendCartProduct :name="item.name" :color="item.color" :price="item.price" :size="item.size" :img="item.img" @remove="removeProduct(index)"/>
    </template>
   </div>
   <div class="flex flex-col gap-5 border border-gray rounded-2xl w-full desktop:w-[35%] h-[80%] p-5 ">
       

         <div class="flex flex-col desktop:flex-row gap-3 mt-4 w-full">
        <button class="bg-black rounded-3xl text-white p-3 w-full">
               Add to Cart
             </button>
        </div>
   </div>
  </div>
    </div>
</template>

<script setup lang="ts">
import { ArrowRight } from '@element-plus/icons-vue'
import BaseBreadcrumb from '~/components/ui/BaseBreadcrumb.vue'
interface BreadcrumbItem{
  label: string
  icon?: string
  to?: string
}
const breadcrumbs = ref<BreadcrumbItem[]>([
  {
    label: 'Home',
    icon: 'i-lucide-book-open',
    to: '/'
  },
  {
    label: 'Fav',
    icon: 'i-lucide-box',
    to: '/frontend/favorites'
  }
])
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
        // toast.error({ rtl:true, position:'topRight', message: 'Product removed from favorited'});
}

</script>

<style scoped>

</style>a