<template>
 <select class="select w-full max-w-xs rounded-3xl bg-gray">
    <option value="" v-for="option in options" :key="option.id">
        {{ option.label }}
    </option>
</select>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  options: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: "",
  },
  placeholder: {
    type: String,
    default: "Select an option",
  },
  name: {
    type: String,
    default: "custom-select",
  },
});
</script>

<style scoped>
/* Additional custom styles (if needed) */
</style>
