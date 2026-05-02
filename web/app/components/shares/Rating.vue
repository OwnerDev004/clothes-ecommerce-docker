<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    ratingAmount: {
        type: Number,
        default: 0
    }
});

// Create a computed property that uses the prop
const ratingValue = computed({
    get: () => props.ratingAmount,
    set: (value) => {
        // Emit event if you want to allow changes from parent
        // emit('update:ratingAmount', value);
    }
});
</script>

<template>
    <div class="flex gap-1">
        <el-rate v-model="ratingValue" disabled text-color="#ff9900" score-template="{value} points" />
    </div>
</template>