<script setup lang="ts">
withDefaults(
    defineProps<{
        img?: string
        title?: string
        price?: number
        discountType?: number
        discountAmount?: number
        starsNum?: number
        ratingAmount?: number
        bgCard?: string
        loading?: boolean
    }>(),
    {
        img: '',
        title: '',
        price: 0,
        discountAmount: 0,
        starsNum: 0,
        ratingAmount: 0,
        bgCard: 'bg-surface',
        loading: false,
    },
)
</script>
<template>
    <div v-if="loading" class="card-product p-4 rounded-2xl border border-border shadow-sm" :class="[bgCard]">
        <div class="rounded-2xl bg-white/40 p-3">
            <el-skeleton :loading="loading" animated>
                <template #template>
                    <el-skeleton-item variant="rect" class="card-product__image" />
                    <div class="mt-4 space-y-3">
                        <el-skeleton-item variant="text" class="card-product__title" />
                        <el-skeleton-item variant="text" class="card-product__rating" />
                        <el-skeleton-item variant="text" class="card-product__price" />
                    </div>
                </template>
            </el-skeleton>
        </div>
    </div>

    <div v-else class="p-4 rounded-2xl border border-border shadow-sm transition hover:shadow-md" :class="[bgCard]">
        <section class="rounded-2xl cursor-pointer hover:scale-95 translate-all duration-300">
            <div class="w-full aspect-[4/5] overflow-hidden rounded-xl bg-white/40">
                <NuxtImg sizes="sm:100vw md:500px" :src="img" format="webp" densities="x1"
                    class="w-full h-full object-cover" :alt="title || 'product image'" />
            </div>
        </section>
        <h2 class="text-lg font-semibold text-text my-2">{{ title }}</h2>
        <SharesRating :rating-amount="ratingAmount" />
        <SharesDiscount :discount-amount="discountAmount" :price="price" :discount-type="discountType"
            :discountPercentage="'text-sm'" />
    </div>
</template>


<style scoped>
.card-product__image {
    width: 100%;
    aspect-ratio: 4 / 5;
    border-radius: 0.75rem;
}

.card-product__title {
    height: 1.2rem;
    width: 78%;
    border-radius: 9999px;
}

.card-product__rating {
    height: 0.95rem;
    width: 52%;
    border-radius: 9999px;
}

.card-product__price {
    height: 1rem;
    width: 64%;
    border-radius: 9999px;
}
</style>
