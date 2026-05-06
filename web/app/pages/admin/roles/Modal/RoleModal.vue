<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import type { roleForm, roleRecord, roleSubmitPayload } from '~/composables/useAdminRoles'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        mode?: 'create' | 'edit' | 'view'
        roles?: roleRecord | null
        loading?: boolean
    }>(),
    {
        mode: 'create',
        roles: null,
        loading: false,
    },
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit', payload: roleSubmitPayload): void
}>()

const dialogOpen = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
})

const form = reactive<roleForm>({
    name: '',
    desc: '',
    status: false,
    is_system: false
})

const statusOptions = [
    { id: 1, label: 'Active' },
    { id: 0, label: 'Inactive' },
]


const fillForm = () => {
    form.name = props.roles?.name || ''
    form.desc = props.roles?.desc || ''
    form.is_system = Boolean(props.roles?.is_system)
    form.status = Boolean(props.roles?.status)
}

const submitForm = () => {
    if (props.mode === 'view') {
        dialogOpen.value = false
        return
    }

    emit('submit', {
        mode: props.mode,
        id: props.roles?.id ?? null,
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
    <BaseModal v-model="dialogOpen"
        :title="mode === 'edit' ? 'Edit Role' : mode === 'view' ? 'Role Detail' : 'Add Role'" width="500px">
        <el-form label-position="top">
            <section class="space-y-3 rounded-element border border-dashed border-primary  p-3">
                <el-form-item label="Role Name">
                    <BaseInput v-model="form.name" placeholder=" Enter Role Name" :disabled="mode === 'view'" />
                </el-form-item>
                <template class="grid grid-cols-2">
                    <el-form-item label="Role Active">
                        <el-switch v-model="form.status" :disabled="mode === 'view'" />
                    </el-form-item>
                    <el-form-item label="This role is system?">
                        <el-switch v-model="form.is_system" :disabled="mode === 'view'" />
                    </el-form-item>
                </template>
                <el-form-item label="Role Description">
                    <BaseInput v-model="form.desc" placeholder="Enter Role Description" type="textarea" rows="8"
                        :disabled="mode === 'view'" />
                </el-form-item>
            </section>
        </el-form>

        <template #footer>
            <BaseButton @click="dialogOpen = false">Cancel</BaseButton>
            <BaseButton v-if="mode !== 'view'" type="primary" :loading="loading" @click="submitForm">
                {{ mode === 'edit' ? 'Update Role' : 'Save Role' }}
            </BaseButton>
        </template>
    </BaseModal>
</template>
