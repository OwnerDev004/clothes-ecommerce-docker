<template>
    <!-- Header -->
    <section class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:mb-7">
        <h3 class="text-xl font-bold sm:text-2xl">{{ title }}</h3>
        <el-breadcrumb :separator-icon="ElIconArrowRight" class="!text-xs sm:!text-sm">
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
