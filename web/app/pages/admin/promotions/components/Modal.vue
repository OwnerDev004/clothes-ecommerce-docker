<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import type { AdminVoucherRecord, VoucherForm, VoucherSubmitPayload } from '~/composables/useAdminVoucher'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    mode?: 'create' | 'edit'
    voucher?: AdminVoucherRecord | null
    loading?: boolean
  }>(),
  {
    mode: 'create',
    voucher: null,
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'submit', payload: VoucherSubmitPayload): void
}>()

const dialogOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

const form = reactive<VoucherForm>({
  code: '',
  name: '',
  is_active: true,
  is_signup_coupon: false,
  first_order_only: false,
  discount_type: 'percentage',
  discount_value: '',
  minimum_order_amount: '',
  max_order: '',
  max_uses_per_customer: '',
  expires_at: '',
})

const statusOptions = [
  { id: 1, label: 'Active' },
  { id: 0, label: 'Inactive' },
]

const typeOptions = [
  { id: 'percentage', label: 'Percentage' },
  { id: 'fixed_amount', label: 'Fixed amount' },
]

const activeSelectValue = computed<number | null>({
  get: () => (form.is_active ? 1 : 0),
  set: (value) => {
    form.is_active = Number(value) === 1
  },
})

const fillForm = () => {
  form.code = props.voucher?.code || ''
  form.name = props.voucher?.name || ''
  form.is_active = Boolean(props.voucher?.is_active ?? true)
  form.is_signup_coupon = Boolean(props.voucher?.is_signup_coupon)
  form.first_order_only = Boolean(props.voucher?.first_order_only)
  form.discount_type = props.voucher?.discount_type || 'percentage'
  form.discount_value = String(props.voucher?.discount_value ?? '')
  form.minimum_order_amount = String(props.voucher?.minimum_order_amount ?? '')
  form.max_order = String(props.voucher?.max_order ?? '')
  form.max_uses_per_customer = String(props.voucher?.max_uses_per_customer ?? '')
  form.expires_at = props.voucher?.expires_at || ''
}

const submitForm = () => {
  emit('submit', {
    mode: props.mode,
    voucherId: props.voucher?.id ?? null,
    form: { ...form },
  })
}

watch(
  () => dialogOpen.value,
  (open) => {
    if (open) {
      fillForm()
    }
  },
  { immediate: true },
)
</script>

<template>
  <BaseModal v-model="dialogOpen" :title="mode === 'edit' ? 'Edit Promotion' : 'Add Promotion'" width="1100px">
    <el-form label-position="top" class="grid gap-6 lg:grid-cols-2">
      <section class="space-y-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50/80 p-6">
        <h3 class="text-lg font-bold text-slate-950">Promotion Info</h3>

        <el-form-item label="Code">
          <BaseInput v-model="form.code" placeholder="SUMMER25" />
        </el-form-item>

        <el-form-item label="Name">
          <BaseInput v-model="form.name" placeholder="Summer Sale" />
        </el-form-item>

        <el-form-item label="Discount Type">
          <BaseSelect v-model="form.discount_type" :options="typeOptions" class="w-full" />
        </el-form-item>

        <el-form-item label="Discount Value">
          <BaseInput v-model="form.discount_value" type="number" min="0" placeholder="10" />
        </el-form-item>

        <el-form-item label="Expires At">
          <BaseInput v-model="form.expires_at" type="date" />
        </el-form-item>
      </section>

      <section class="space-y-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50/80 p-6">
        <h3 class="text-lg font-bold text-slate-950">Rules & Status</h3>

        <el-form-item label="Status">
          <BaseSelect v-model="activeSelectValue" :options="statusOptions" class="w-full" />
        </el-form-item>

        <el-form-item label="Minimum Order Amount">
          <BaseInput v-model="form.minimum_order_amount" type="number" min="0" placeholder="0" />
        </el-form-item>

        <el-form-item label="Max Order">
          <BaseInput v-model="form.max_order" type="number" min="0" placeholder="0" />
        </el-form-item>

        <el-form-item label="Max Uses Per Customer">
          <BaseInput v-model="form.max_uses_per_customer" type="number" min="0" placeholder="1" />
        </el-form-item>

        <div class="grid gap-3 sm:grid-cols-2">
          <el-switch v-model="form.is_signup_coupon" active-text="Signup coupon" />
          <el-switch v-model="form.first_order_only" active-text="First order only" />
        </div>
      </section>
    </el-form>

    <template #footer>
      <BaseButton @click="dialogOpen = false">Cancel</BaseButton>
      <BaseButton type="primary" :loading="loading" @click="submitForm">
        {{ mode === 'edit' ? 'Update Promotion' : 'Save Promotion' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
