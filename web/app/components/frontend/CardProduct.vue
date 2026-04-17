<script setup lang="ts">
const props = defineProps({
    img: {
        type: String,
        default: ''
    },
    title: {
        type: String,
        default: ''
    },
    price: {
        type: Number,
        default: 0
    },
    discountType: {
        type: Number,
    },
    discountAmount: {
        type: Number,
        default: 0
    },
    starsNum: {
        type: Number,
        default: 0
    },
    ratingAmount: {
        type: Number,
        default: 0
    },
    bgCard: {
        type: String,
        default: 'bg-surface'
    }
});


</script>
<template>
    <div class="p-4 rounded-2xl border border-border shadow-sm transition hover:shadow-md" :class="[bgCard]">
        <section class="rounded-2xl cursor-pointer hover:scale-95 translate-all duration-300">
            <div class="w-full aspect-[4/5] overflow-hidden rounded-xl bg-white/40">
                <NuxtImg sizes="sm:100vw md:500px" :src="img" format="webp" densities="x1"
                    class="w-full h-full object-cover" />
            </div>
        </section>
        <h2 class="text-lg font-semibold text-text my-2">{{ title }}</h2>
        <SharesRating :stars-num="starsNum" :rating-amount="ratingAmount" />
        <SharesDiscount :discount-amount="discountAmount" :price="price" :discount-type="discountType"
            :discountPercentage="'text-sm'" />
    </div>
</template>


<style scoped></style>
