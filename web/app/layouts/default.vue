<template>
    <FrontendBaseNotificationHeader />
    <FrontendHeader />
    <slot />
    <FrontendFooter />
</template>

<script setup lang="ts">
import { useAppSetting } from '~/composables/useAppSetting';

const config = useRuntimeConfig();
const { appSetting, fetchAppSetting } = useAppSetting();

watchEffect(() => {
    useHead({
        title: appSetting.value?.app_name || config.public.appName,
    });
});

onMounted(async () => {
    await fetchAppSetting();
});
</script>
<style></style>
