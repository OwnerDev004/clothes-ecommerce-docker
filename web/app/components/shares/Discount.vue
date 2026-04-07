<script setup lang="ts">

const props = defineProps({
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
    discountPercentage: {
        type: String,
        default: 'text-xl'
    }
});
const discountTypes = computed(() => {
    switch (props.discountType) {
        case 1: return `-${props.discountAmount} %`
            break;
        case 2: return `-${props.discountAmount}$`
            break;
        default: return '';
    }
})
const totalDiscountAmount = computed(() => {
    if (props.discountType && props.discountAmount) {
        switch (props.discountType) {
            // percentage discount
            case 1: return `${props.price - props.price * (props.discountAmount / 100)} $`
                break;
            // amount discount
            case 2: return `${props.price - props.discountAmount}$`
                break;
            default: return ``;
        }
    }
    return `${props.price} $`;
})
</script>

<template>
    <strong class="flex gap-4"><span class="text-text text-bold text-xl">{{ totalDiscountAmount }}</span> <span
            class="line-through" v-if="discountTypes">${{ price }}</span> <span v-if="discountTypes"
            class="decoration-slice text-red bg-red-50 rounded-[62px] px-3 py-1" :class="`${discountPercentage}`">{{
                discountTypes }}</span></strong>
</template>
<style scoped></style>