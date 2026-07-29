<template>
    <el-dialog v-model="model" width="680px" align-center :close-on-click-modal="false" title="Ai Search"
        class="profile-dialog" @closed="onAiSearchDialogClosed">
        <div class="space-y-4">
            <base-input type="textarea" :rows="4" v-model="message_prompt" />
            <div class="place-self-end">
                <ui-base-button type="primary" :loading="pending" @click="aiProductSearch">
                    Search

                    <Icon name="mingcute:ai-fill" />
                </ui-base-button>
                <ui-base-button type="plain" @click="model = false">
                    Cancel
                </ui-base-button>
            </div>
        </div>

    </el-dialog>
</template>

<script setup lang="ts">
import BaseInput from '~/components/ui/BaseInput.vue';
import { useAiFeature } from '~/composables/useAiFeature';
const { message_prompt, pending, aiProductSearch } = useAiFeature()

const model = defineModel<boolean | undefined>()
const onAiSearchDialogClosed = () => {
    message_prompt.value = ''
}

</script>

<style scoped></style>
