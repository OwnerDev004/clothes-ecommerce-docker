<template>
    <div>
        <HeaderBreadCrumb title="Customers">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/customers' }">Customers</el-breadcrumb-item>
            <el-breadcrumb-item>All Customers</el-breadcrumb-item>
        </HeaderBreadCrumb>
    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';

definePageMeta({
    layout: 'admin',
    middleware: ['admin-auth'],
})

</script>

<style scoped></style>