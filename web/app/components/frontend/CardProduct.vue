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
    <ProductCardSkeleton v-if="loading" :bg-card="bgCard" />

    <div v-else class="flex h-full flex-col rounded-2xl border border-border bg-surface p-3 shadow-sm transition hover:shadow-md sm:p-4" :class="[bgCard]">
        <section class="group cursor-pointer overflow-hidden rounded-2xl">
            <div class="aspect-[4/5] w-full overflow-hidden rounded-xl bg-white/60">
                <NuxtImg
                    :src="img"
                    format="webp"
                    densities="x1 x2"
                    sizes="(max-width: 575px) 100vw, (max-width: 992px) 50vw, 25vw"
                    class="h-full w-full object-contain p-2 transition duration-300 group-hover:scale-[1.03] sm:p-3"
                    :alt="title || 'product image'"
                />
            </div>
        </section>

        <div class="mt-3 flex flex-1 flex-col gap-2">
            <h2 class="line-clamp-2 text-sm font-semibold leading-5 text-text sm:text-base lg:text-lg">
                {{ title }}
            </h2>
            <SharesRating :rating-amount="ratingAmount" />
            <SharesDiscount
                :discount-amount="discountAmount"
                :price="price"
                :discount-type="discountType"
                :discountPercentage="'text-sm'"
            />
        </div>
    </div>
</template>
