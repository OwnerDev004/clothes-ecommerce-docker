<template>
    <div>
        <BaseModal
            v-model="model"
            width="min(500px, 96vw)"
            body-class="p-0"
            footer-class="px-4 pb-4 pt-0 sm:px-6 sm:pb-6"
        >
            <el-form label-position="top" class="w-full" autocomplete="off">
                <div class="grid gap-4 px-4 pb-4 sm:px-6 sm:pb-6">
                    <header class="rounded-3xl border border-border bg-surface p-5">
                        <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Are you sure to Cancel ?
                        </p>

                    </header>
                    <div class="grid gap-6">
                        <section class="rounded-3xl border border-border bg-surface-2 p-5">

                            <div class="grid gap-4">
                                <el-form-item label="Reason" prop="order_note">
                                    <BaseInput v-model="form.order_note" placeholder="Enter customer phone"
                                        type="textarea" :rows="6" />
                                </el-form-item>
                            </div>
                        </section>
                    </div>
                </div>
            </el-form>
            <template #footer>
                <div class="flex justify-end gap-3 px-6 pb-6 pt-0">
                <BaseButton @click="closeModal">Cancel</BaseButton>
                    <BaseButton type="primary" :loading="loading" @click="submitForm">
                        Cancel Order
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

    </div>
</template>

<script setup lang="ts">
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import BaseModal from '~/components/ui/BaseModal.vue';

const model = defineModel<boolean>()
const props = withDefaults(
    defineProps<{
        loading: boolean,
        orderId: string | number | undefined,
        form: any
    }>(),
    {
        loading: false,
        orderId: '',
        form: {
            order_note: ''
        }
    }
)
const emit = defineEmits(['submit'])
const form = reactive({
    order_note: ''
})
const submitForm = () => {
    emit('submit', {
        id: props.orderId,
        order_note: form.order_note.trim(),
        status: 'cancelled'
    });
}
const closeModal = () => {
    model.value = false
}

const fillForm = () => {
    Object.assign(form, props.form)
}
// watch
watch(() => model.value,
    (value) => {
        if (value) {
            fillForm()
        }
    }, {
    immediate: true
})

</script>

<style scoped></style>
