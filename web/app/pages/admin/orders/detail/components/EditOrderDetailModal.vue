<template>
    <div>
        <BaseModal v-model="model" width="1100px" body-class="p-0" footer-class="px-6 pb-6 pt-0">
            <el-form label-position="top" class="w-full" autocomplete="off">
                <div class="grid gap-6 px-6 pb-6">
                    <header
                        class="rounded-3xl border border-dashed border-muted bg-gradient-to-br from-slate-50 via-white to-slate-100 p-6">
                        <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Order details
                        </p>
                        <div class="mt-3 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-slate-950">Update customer information</h3>
                                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                                    Edit shipping and note details for this order without leaving the page.
                                </p>
                            </div>
                            <p class="text-xs font-medium text-slate-500">
                                Changes apply when you press Update Order Information.
                            </p>
                        </div>
                    </header>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-6">
                            <div class="mb-5">
                                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                    Customer info
                                </p>
                                <h4 class="mt-2 text-base font-semibold text-slate-950">Contact details</h4>
                            </div>

                            <div class="grid gap-4">
                                <el-form-item label="Customer Phone" prop="shipping_phone">
                                    <BaseInput v-model="form.customer.shipping_phone"
                                        placeholder="Enter customer phone" />
                                </el-form-item>

                                <el-form-item label="Shipping Province" prop="shipping_province">
                                    <BaseSelect v-model="form.customer.shipping_province"
                                        :options="shippingProvinceOptions" placeholder="Select shipping province"
                                        class="w-full text-black" />
                                </el-form-item>

                                <p class="m-0 text-sm text-slate-600">
                                    Shipping fee:
                                    <span class="font-semibold text-slate-950">
                                        ${{ shippingFee.toFixed(2) }}
                                    </span>
                                </p>
                            </div>
                        </section>

                        <section class="rounded-3xl border border-dashed border-muted bg-surface-2/10 p-6">
                            <div class="mb-5">
                                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                    Delivery info
                                </p>
                                <h4 class="mt-2 text-base font-semibold text-slate-950">Shipping notes</h4>
                            </div>

                            <div class="grid gap-4">
                                <el-form-item label="Shipping Address" prop="shipping_address">
                                    <BaseInput v-model="form.customer.shipping_address" type="textarea" :rows="6"
                                        placeholder="Enter the full shipping address" />
                                </el-form-item>

                                <el-form-item label="Order Note" prop="order_note">
                                    <BaseInput v-model="form.order_note" type="textarea" :rows="6"
                                        placeholder="Add a short note for this order" />
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
                        Update Order Information
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
import { useAdminAuthStore } from '~/stores/adminAuthStore'
import { ElMessage } from 'element-plus'
import BaseSelect from '~/components/ui/BaseSelect.vue';

type CustomerInfoEdit = {
    shipping_phone: string,
    shipping_province: string
    shipping_address: string,
    shipping_fee: number
}


// model
const model = defineModel<boolean>()
//define Props
const props = withDefaults(
    defineProps<{
        orderId: string | number | undefined,
        form: any
    }>(),
    {
        orderId: '',
        form: {
            customer: {
                shipping_phone: '',
                shipping_address: '',
                shipping_province: '',
                shipping_fee: 0,
            },
            order_note: ''
        }
    }
)
const config = useRuntimeConfig();
const apiBase = (config.public.apiBase || "").replace(/\/$/, "");
const { accessToken } = storeToRefs(useAdminAuthStore());

const shippingProvinceOptions = ref([
    {
        id: 'phnom_penh',
        label: 'Phnom Penh',
    },
    {
        id: 'kandal',
        label: 'Kandal'
    },
    {
        id: 'siem_reap',
        label: 'Siem Reap'
    },
    {
        id: 'battambang',
        label: 'Battambang'
    },
    {
        id: 'preah_sihanouk',
        label: 'Preah sihanouk'
    },
    {
        id: 'other',
        label: 'Other'
    }
])

const shippingRateByProvince: Record<string, number> = {
    phnom_penh: 1.5,
    kandal: 2.0,
    siem_reap: 2.5,
    battambang: 2.5,
    preah_sihanouk: 3.0,
    other: 3.5,
}

const slugifyProvince = (value: string) =>
    String(value || '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/_+/g, '-')

const form = reactive<{ customer: CustomerInfoEdit, order_note: string }>({
    customer: {
        shipping_phone: '',
        shipping_address: '',
        shipping_province: '',
        shipping_fee: 0
    },
    order_note: ''

})
const loading = ref<boolean>(false)

// functions
const resolveAuthHeaders = () => {
    return accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined;
}

const shippingFee = computed(() => {
    const province = form.customer.shipping_province;
    return Number(
        shippingRateByProvince[province] ??
        shippingRateByProvince[slugifyProvince(province)] ??
        form.customer.shipping_fee ??
        0
    );
});


// submit
const submitForm = async () => {
    loading.value = true
    try {
        await $fetch(`${apiBase}/admin/orders/${props.orderId}`, {
            method: 'PATCH',
            credentials: "include",
            headers: resolveAuthHeaders(),
            body: {
                shipping_phone: form.customer.shipping_phone,
                shipping_province: form.customer.shipping_province,
                shipping_address: form.customer.shipping_address,
                shipping_fee: shippingFee.value,
                order_note: form.order_note
            },
        });


        ElMessage.success("Order information updated.");
    } catch (error) {
        ElMessage.error("Failed to update order information.");
    }
    finally {
        loading.value = false
        model.value = false
    }
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
