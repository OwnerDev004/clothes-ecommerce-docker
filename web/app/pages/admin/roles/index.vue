<template>
    <div>
        <HeaderBreadCrumb title="Admin Roles">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/roles' }">Admin Roles</el-breadcrumb-item>
            <el-breadcrumb-item>All Roles</el-breadcrumb-item>
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