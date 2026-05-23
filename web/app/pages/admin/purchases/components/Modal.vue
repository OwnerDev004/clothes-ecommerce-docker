<template>
    <BaseModal
        v-model="model"
        :title="mode === 'edit' ? 'Edit Purchase' : 'Add Purchase'"
        width="min(1100px, 96vw)"
        body-class="p-0"
        footer-class="px-4 pb-4 pt-0 sm:px-6 sm:pb-6"
    >
        <el-form label-position="top" class="w-full" autocomplete="off">
            <div class="grid gap-4 px-4 pb-4 sm:px-6 sm:pb-6 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-6">
                <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-5 sm:p-6">
                    <div class="space-y-4">
                        <div>
                            <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Stock Purchase
                            </p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950 sm:text-xl">
                                {{ mode === 'edit' ? 'Update purchase record' : 'Add stock to inventory' }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Record the stock purchase and keep inventory in sync with one simple form.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="m-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                Current selection
                            </p>
                            <p class="m-0 mt-2 text-sm font-semibold text-slate-950">
                                {{ selectedVariant?.product_name || 'No variant selected' }}
                            </p>
                            <p class="m-0 mt-1 text-xs leading-6 text-slate-500">
                                {{
                                    selectedVariant
                                        ? `${selectedVariant.size} • ${selectedVariant.color}`
                                        : 'Choose a product variant to see details.'
                                }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-amber-50/80 p-4">
                            <p class="m-0 text-xs font-semibold uppercase tracking-[0.12em] text-amber-800">
                                Stock recycle
                            </p>
                            <p class="m-0 mt-2 text-sm leading-6 text-amber-900">
                                If you edit or delete a purchase, the stock will be rolled back automatically.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-5 sm:p-6">
                    <div class="grid gap-5">
                        <el-form-item label="Product Variant" prop="product_variant_id">
                            <BaseSelect v-model="form.product_variant_id" :options="variantOptions"
                                placeholder="Select product variant" class="w-full" />
                        </el-form-item>

                        <el-form-item label="Quantity" prop="quantity">
                            <BaseInput v-model="form.quantity" type="number" min="1" placeholder="Enter quantity" />
                        </el-form-item>

                        <el-form-item label="Cost Price" prop="cost_price">
                            <BaseInput v-model="form.cost_price" type="number" min="0" step="0.01"
                                placeholder="Enter cost price" />
                        </el-form-item>

                        <el-form-item label="Note" prop="note">
                            <BaseInput v-model="form.note" type="textarea" :rows="6"
                                placeholder="Add a note about this purchase" />
                        </el-form-item>

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-4">
                                <p class="m-0 text-sm text-slate-500">Estimated total</p>
                                <p class="m-0 text-lg font-bold text-slate-950">${{ estimatedTotal.toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </el-form>

        <template #footer>
            <div class="flex justify-end gap-3 px-6 pb-6 pt-0">
                <BaseButton @click="closeModal">Cancel</BaseButton>
                <BaseButton type="primary" :loading="loading" @click="submitForm">
                    {{ mode === 'edit' ? 'Update Purchase' : 'Save Purchase' }}
                </BaseButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import type { AdminPurchaseRecord, AdminPurchaseVariantOption } from '~/composables/useAdminPurchases'

const model = defineModel<boolean>()

const props = withDefaults(
    defineProps<{
        loading?: boolean
        mode?: 'create' | 'edit'
        purchase?: AdminPurchaseRecord | null
        variantOptions: AdminPurchaseVariantOption[]
    }>(),
    {
        loading: false,
        mode: 'create',
        purchase: null,
        variantOptions: () => [],
    },
)

const emit = defineEmits<{
    (e: 'submit', payload: {
        mode: 'create' | 'edit'
        purchaseId: number | string | null
        form: {
            product_variant_id: number | string | null
            quantity: number
            cost_price: number
            note: string
        }
    }): void
}>()

const form = reactive({
    product_variant_id: null as number | string | null,
    quantity: 1,
    cost_price: 0,
    note: '',
})

const selectedVariant = computed(() => {
    return props.variantOptions.find((item) => String(item.id) === String(form.product_variant_id)) || null
})

const estimatedTotal = computed(() => {
    return Number(form.quantity || 0) * Number(form.cost_price || 0)
})

const resetForm = () => {
    form.product_variant_id = null
    form.quantity = 1
    form.cost_price = 0
    form.note = ''
}

const syncFromPurchase = () => {
    if (props.mode === 'edit' && props.purchase) {
        form.product_variant_id = props.purchase.product_variant_id ?? null
        form.quantity = Number(props.purchase.quantity || 1)
        form.cost_price = Number(props.purchase.cost_price || 0)
        form.note = props.purchase.note || ''
        return
    }

    resetForm()
}

const fillDefaultPrice = () => {
    if (!selectedVariant.value) {
        return
    }

    if (!form.cost_price || Number(form.cost_price) === 0) {
        form.cost_price = Number(selectedVariant.value.cost_price || 0)
    }
}

const submitForm = () => {
    emit('submit', {
        mode: props.mode === 'edit' ? 'edit' : 'create',
        purchaseId: props.purchase?.id ?? null,
        form: {
            product_variant_id: form.product_variant_id,
            quantity: Number(form.quantity || 0),
            cost_price: Number(form.cost_price || 0),
            note: form.note.trim(),
        },
    })
}

const closeModal = () => {
    model.value = false
}

watch(
    () => model.value,
    (open) => {
        if (open) {
            syncFromPurchase()
            return
        }

        resetForm()
    },
    { immediate: true },
)

// Ensure form syncs if the parent updates the `purchase` prop after opening the modal
watch(
    () => props.purchase,
    (p) => {
        if (props.mode === 'edit' && model.value && p) {
            syncFromPurchase()
        }
    },
)

watch(
    () => form.product_variant_id,
    () => {
        fillDefaultPrice()
    },
)
</script>
