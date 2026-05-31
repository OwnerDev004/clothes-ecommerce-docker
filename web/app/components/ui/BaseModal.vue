<script setup lang="ts">
import BaseButton from './BaseButton.vue';

defineOptions({ inheritAttrs: false })

const model = defineModel<Boolean | undefined>()

withDefaults(
    defineProps<{
        title?: string
        width?: string | number
        closeOnClickModal?: boolean
        closeOnPressEscape?: boolean
        destroyOnClose?: boolean
        showFooter?: boolean
        bodyClass?: string
        footerClass?: string
    }>(),
    {
        title: '',
        width: '560px',
        closeOnClickModal: false,
        closeOnPressEscape: true,
        destroyOnClose: true,
        showFooter: true,
        bodyClass: 'p-6',
        footerClass: 'px-6 pb-6 pt-0',
    },
)

const closeModal = () => {
    model.value = false
}
</script>

<template>
    <el-dialog v-model="model" class="admin-modal size-fit" v-bind="$attrs" align-center :title="title" :width="width"
        :close-on-click-modal="closeOnClickModal" :close-on-press-escape="closeOnPressEscape"
        :destroy-on-close="destroyOnClose">
        <div :class="bodyClass">
            <slot />
        </div>

        <template v-if="$slots.footer || showFooter" #footer>
            <div :class="footerClass">
                <slot name="footer" :close="closeModal">
                    <div class="flex justify-end gap-3">
                        <BaseButton @click="closeModal">Cancel</BaseButton>
                        <BaseButton type="primary" @click="closeModal">
                            Confirm
                        </BaseButton>
                    </div>
                </slot>
            </div>
        </template>
    </el-dialog>
</template>

<style>
.admin-modal {
    margin: 0;
    width: min(var(--el-dialog-width, 560px), calc(100vw - 1rem));
    max-width: calc(100vw - 1rem);
    max-height: calc(100dvh - 1rem);
    border-radius: 24px;
    overflow: hidden;
}

.admin-modal .el-dialog__body {
    padding: 0;
    max-height: calc(100dvh - 8rem);
    overflow-y: auto;
}

.admin-modal .el-dialog__header {
    padding-bottom: 0;
}

.admin-modal .el-dialog__footer {
    padding: 0;
}

@media (max-width: 640px) {
    .admin-modal {
        width: calc(100vw - 0.75rem) !important;
        max-width: calc(100vw - 0.75rem);
        border-radius: 18px;
    }

    .admin-modal .el-dialog__header {
        padding: 14px 14px 0;
    }

    .admin-modal .el-dialog__body {
        max-height: calc(100dvh - 7rem);
    }
}
</style>
