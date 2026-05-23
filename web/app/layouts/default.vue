<template>
    <div class="min-h-screen ">
        <FrontendBaseNotificationHeader />
        <FrontendHeader />
        <main class="flex-1">
            <slot />
        </main>
        <div class="mt-auto">
            <FrontendFooter />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppSetting } from '~/composables/useAppSetting';

const config = useRuntimeConfig();
const { appSetting } = useAppSetting();

watchEffect(() => {
    useHead({
        title: appSetting.value?.app_name || config.public.appName,
    });
});
</script>
<style></style>
