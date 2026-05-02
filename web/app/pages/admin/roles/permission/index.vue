<template>
    <div>
        <HeaderBreadCrumb title="Admin Roles Permission">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/roles' }">Admin Roles Permission</el-breadcrumb-item>
            <el-breadcrumb-item>All Roles Permission</el-breadcrumb-item>
        </HeaderBreadCrumb>
    </div>
</template>

<script setup lang="ts">
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue';

definePageMeta({
    layout: 'admin',
    middleware: 'admin-auth',
})
</script>

<style scoped></style>