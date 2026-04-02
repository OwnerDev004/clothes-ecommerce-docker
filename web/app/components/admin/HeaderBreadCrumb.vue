<template>
    <!-- Header -->
    <section class="flex justify-between mb-4 sm:mb-4 lg:mb-7">
        <h3 class="font-bold text-2xl">{{ title }}</h3>
        <el-breadcrumb :separator-icon="ElIconArrowRight" class="!text-xs">
            <slot />
        </el-breadcrumb>
    </section>
</template>

<script setup lang="ts">

defineProps<{
    title: String
}>()
</script>

<style scoped></style>