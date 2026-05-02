<template>
    <div>
        <HeaderBreadCrumb title="Setting">
            <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
            <el-breadcrumb-item :to="{ path: '/admin/setting' }">Setting</el-breadcrumb-item>
            <el-breadcrumb-item>App Setting</el-breadcrumb-item>
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