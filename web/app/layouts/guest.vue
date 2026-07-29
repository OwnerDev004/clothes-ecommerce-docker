<script setup lang="ts">
import { useAppSetting } from '~/composables/useAppSetting';

const config = useRuntimeConfig();
const { appSetting, fetchAppSetting } = useAppSetting();

await useAsyncData('app-setting', () => fetchAppSetting(true));

watchEffect(() => {
    useHead({
        title: appSetting.value?.app_name || config.public.appName,
    });
});
</script>
<template>
    <div>
        <slot />
    </div>
</template>
<style></style>
