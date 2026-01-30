
<script setup lang="ts">
    //  props
    const props = defineProps({
        starsNum:{
             type: Number,
             default: 0
        },
        ratingAmount:{
            type: Number,
            default:0
        }
    });
</script>

<template>
    <div class="flex gap-1">
        <template v-for="star in starsNum">
            <Icon name="mdi:star" class="text-yellow-400" />
        </template>
        <p v-if="ratingAmount !=0">{{ ratingAmount }}</p>
    </div>
</template>


<style scoped>

</style>