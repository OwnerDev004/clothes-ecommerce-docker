<template>
    <HeaderBreadCrumb title="All Products">
        <el-breadcrumb-item :to="{ path: '/admin' }">Dashboard</el-breadcrumb-item>
        <el-breadcrumb-item :to="{ path: '/admin/products' }">Product</el-breadcrumb-item>
        <el-breadcrumb-item>All Proucts</el-breadcrumb-item>
    </HeaderBreadCrumb>

</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';


definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth']
})
</script>

<style scoped></style>