<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import { useAdminAuthStore } from '~/stores/adminAuthStore'
import type { adminForm, adminRecord, adminSubmitPayload } from '~/composables/useAdminAdmins'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    mode?: 'create' | 'edit'
    admin?: adminRecord | null
    loading?: boolean
  }>(),
  {
    mode: 'create',
    admin: null,
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'submit', payload: adminSubmitPayload): void
}>()

const dialogOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const adminAuthStore = useAdminAuthStore()
const { accessToken } = storeToRefs(adminAuthStore)
const isSuperAdmin = computed(() => adminAuthStore.isSuperAdmin)
const roleOptions = ref<Array<{ id: string; label: string }>>([])
const loadingRoles = ref(false)

const resolveAuthHeaders = () => {
  return accessToken.value
    ? { Authorization: `Bearer ${accessToken.value}` }
    : undefined
}

const form = reactive<adminForm>({
  first_name: '',
  last_name: '',
  gender: 'male',
  dob: '',
  user_name: '',
  phone: '',
  email: '',
  password: '',
  role: '',
})

const genderOptions = [
  { id: 'male', label: 'Male' },
  { id: 'female', label: 'Female' },
]

const fetchRoles = async () => {
  if (!dialogOpen.value) {
    return
  }

  loadingRoles.value = true
  try {
    const response: any = await $fetch(`${apiBase}/admin/admins/roles`, {
      method: 'GET',
      headers: resolveAuthHeaders(),
      query: {
        page: 1,
        per_page: 200,
        sort_by: 'name_asc',
        status: true,
      },
    })

    roleOptions.value = Array.isArray(response?.data)
      ? response.data.map((role: { slug?: string | null; name?: string | null }) => ({
        id: String(role.slug || ''),
        label: role.name || String(role.slug || ''),
      })).filter((role: { id: string }) => isSuperAdmin.value || role.id !== 'super_admin')
      : []

    if (!form.role && roleOptions.value.length) {
      form.role = roleOptions.value[0]?.id || ''
    }
  } finally {
    loadingRoles.value = false
  }
}

const fillForm = () => {
  form.first_name = props.admin?.first_name || ''
  form.last_name = props.admin?.last_name || ''
  form.gender = props.admin?.gender || 'male'
  form.dob = props.admin?.dob || ''
  form.user_name = props.admin?.user_name || ''
  form.phone = props.admin?.phone || ''
  form.email = props.admin?.email || ''
  form.password = ''
  form.role = props.admin?.role || ''
}

const submitForm = () => {
  emit('submit', {
    mode: props.mode,
    id: props.admin?.id ?? null,
    form: { ...form },
  })
}

watch(
  () => dialogOpen.value,
  (open) => {
    if (open) {
      fillForm()
      void fetchRoles()
    }
  },
  { immediate: true },
)
</script>

<template>
  <BaseModal v-model="dialogOpen" :title="mode === 'edit' ? 'Edit Admin' : 'Add Admin'" width="720px">
    <el-form label-position="top">
      <div class="grid gap-5 md:grid-cols-2">
        <el-form-item label="First Name">
          <BaseInput v-model="form.first_name" placeholder="Enter first name" />
        </el-form-item>

        <el-form-item label="Last Name">
          <BaseInput v-model="form.last_name" placeholder="Enter last name" />
        </el-form-item>

        <el-form-item label="Username">
          <BaseInput v-model="form.user_name" placeholder="Enter username" />
        </el-form-item>

        <el-form-item label="Email">
          <BaseInput v-model="form.email" placeholder="Enter email" />
        </el-form-item>

        <el-form-item label="Phone">
          <BaseInput v-model="form.phone" placeholder="Enter phone" />
        </el-form-item>

        <el-form-item label="Date of Birth">
          <BaseInput v-model="form.dob" type="date" />
        </el-form-item>

        <el-form-item label="Gender">
          <BaseSelect v-model="form.gender" :options="genderOptions" placeholder="Select gender" class="w-full" />
        </el-form-item>

        <el-form-item label="Role">
          <BaseSelect v-model="form.role" :options="roleOptions" placeholder="Select role" class="w-full" />
        </el-form-item>

        <el-form-item label="Password" class="md:col-span-2">
          <BaseInput v-model="form.password" type="password"
            :placeholder="mode === 'edit' ? 'Leave blank to keep current password' : 'Enter password'" show-password />
        </el-form-item>
      </div>
    </el-form>

    <template #footer>
      <BaseButton @click="dialogOpen = false">Cancel</BaseButton>
      <BaseButton type="primary" :loading="loading" @click="submitForm">
        {{ mode === 'edit' ? 'Update Admin' : 'Save Admin' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
