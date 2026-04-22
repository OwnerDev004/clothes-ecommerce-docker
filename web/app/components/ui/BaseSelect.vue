<script setup lang="ts">
type SelectOption = {
    id: string | number | null
    label: string
    disabled?: boolean
}

defineOptions({ inheritAttrs: false })
withDefaults(
    defineProps<{
        options: SelectOption[]
        placeholder?: string
    }>(),
    {
        placeholder: 'Select one',
    },
)

const model = defineModel<string | number | boolean | null>()
</script>

<template>
    <el-select v-model="model" v-bind="$attrs" :placeholder="placeholder">
        <el-option v-for="option in options" :key="option.id" :label="option.label" :value="option.id"
            :disabled="option.disabled" />
    </el-select>
</template>
