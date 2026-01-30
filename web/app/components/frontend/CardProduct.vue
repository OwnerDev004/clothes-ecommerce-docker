<script setup lang="ts">
const props = defineProps({
    img:{
        type: String,
        default: ''
    },
    title:{
        type: String,
        default: ''
    },
    price:{
        type: Number,
        default: 0
    },
    discountType:{
        type: Number,
    },
    discountAmount:{
        type: Number,
        default: 0
    },
    starsNum:{
        type: Number,
        default: 0
    },
    ratingAmount:{
        type: Number,
        default: 0
    }
});


</script>
<template>
    <div>
       <section class="bg-gray p-5 flex justify-center rounded-2xl cursor-pointer hover:scale-95 translate-all duration-300">
        <NuxtImg sizes="sm:100vw md:500px" :src="img" format="webp" densities="x1" />
       </section>
       <h1 >{{ title }}</h1>
       <SharesRating :stars-num="starsNum" :rating-amount="ratingAmount"/>
       <SharesDiscount :discount-amount="discountAmount" :price="price" :discount-type="discountType" :discountPercentage="'text-sm'"/>
    </div>
</template>


<style scoped>

</style>