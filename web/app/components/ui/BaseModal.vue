<script setup lang="ts">
import BaseButton from './BaseButton.vue';

defineOptions({ inheritAttrs: false })

const model = defineModel<boolean>()

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
    <el-dialog v-model="model" class="admin-modal" v-bind="$attrs" :title="title" :width="width"
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
