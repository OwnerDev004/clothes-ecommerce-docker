<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'

defineOptions({ inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    modelValue?: string
    accept?: string
    maxSizeMB?: number
    width?: string
    height?: string
    disabled?: boolean
    className?: string
  }>(),
  {
    modelValue: '',
    accept: 'image/jpeg,image/png,image/webp,image/gif',
    maxSizeMB: 2,
    width: '178px',
    height: '178px',
    disabled: false,
    className: '',
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', file: File | null): void
}>()

const fileInputRef = ref<HTMLInputElement | null>(null)
const objectUrl = ref('')
const selectedFile = ref<File | null>(null)
const previewVisible = ref(false)
const previewImageUrl = ref('')

const preview = computed(() => props.modelValue || objectUrl.value)
const uploadFile = computed(() => ({
  name: selectedFile.value?.name || 'image',
  raw: selectedFile.value,
  url: preview.value,
}))

const openPicker = () => {
  if (props.disabled) {
    return
  }

  fileInputRef.value?.click()
}

const clearPreview = () => {
  if (objectUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(objectUrl.value)
  }

  objectUrl.value = ''
  selectedFile.value = null
  previewVisible.value = false
  previewImageUrl.value = ''
  emit('update:modelValue', '')
  emit('change', null)
}

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement | null
  const rawFile = input?.files?.[0] || null

  if (!rawFile) {
    return
  }

  if (!rawFile.type.startsWith('image/')) {
    ElMessage.error('Please upload a valid image file.')
    if (input) {
      input.value = ''
    }
    return
  }

  if (rawFile.size / 1024 / 1024 > props.maxSizeMB) {
    ElMessage.error(`Image size cannot exceed ${props.maxSizeMB}MB.`)
    if (input) {
      input.value = ''
    }
    return
  }

  if (objectUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(objectUrl.value)
  }

  objectUrl.value = URL.createObjectURL(rawFile)
  selectedFile.value = rawFile
  emit('update:modelValue', objectUrl.value)
  emit('change', rawFile)

  if (input) {
    input.value = ''
  }
}

const handlePictureCardPreview = (file?: { url?: string }) => {
  previewImageUrl.value = file?.url || preview.value
  previewVisible.value = Boolean(previewImageUrl.value)
}

const handleDownload = (file?: { url?: string }) => {
  const url = file?.url || preview.value
  if (!url) {
    return
  }

  const link = document.createElement('a')
  link.href = url
  link.download = selectedFile.value?.name || 'image'
  link.rel = 'noopener'
  document.body.appendChild(link)
  link.click()
  link.remove()
}

const handleRemove = (file?: { url?: string }) => {
  clearPreview()
}

defineExpose({
  clearPreview,
  openPicker,
  handlePictureCardPreview,
  handleDownload,
  handleRemove,
})

onBeforeUnmount(() => {
  if (objectUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(objectUrl.value)
  }
})
</script>

<template>
  <div :class="['base-image-upload', className]">
    <div v-if="preview" class="base-image-upload__card">
      <slot name="file" :file="uploadFile" :disabled="disabled" :handlePictureCardPreview="handlePictureCardPreview"
        :handleDownload="handleDownload" :handleRemove="handleRemove">
        <div class="base-image-upload__default-card">
          <img :src="preview" alt="Image preview" class="base-image-upload__image" />

        </div>
      </slot>
    </div>

    <button v-else type="button" class="base-image-upload__trigger" :disabled="disabled" @click="openPicker">
      <span class="base-image-upload__placeholder">
        <Plus class="!w-10" />
      </span>
    </button>

    <input ref="fileInputRef" type="file" class="sr-only" :accept="accept" @change="handleFileChange" />

    <el-dialog v-model="previewVisible" width="min(560px, 96vw)" append-to-body>
      <img v-if="previewImageUrl" :src="previewImageUrl" alt="Image preview" class="base-image-upload__dialog-image" />
    </el-dialog>
  </div>
</template>

<style scoped>
.base-image-upload {
  display: inline-flex;
  flex-direction: column;
  gap: 0.75rem;
}

.base-image-upload__trigger {
  width: v-bind(width);
  height: v-bind(height);
  border: 1px dashed var(--el-border-color);
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: var(--el-transition-duration-fast);
  background: #fff;
  padding: 0;
}

.base-image-upload__trigger:hover:not(:disabled) {
  border-color: var(--el-color-primary);
}

.base-image-upload__trigger:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.base-image-upload__image {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.base-image-upload__placeholder {
  display: grid;
  place-items: center;
  width: 100%;
  height: 100%;
  font-size: 28px;
  color: #8c939d;
}

.base-image-upload__card {
  width: v-bind(width);
  min-height: v-bind(height);
}

.base-image-upload__default-card {
  display: grid;
  gap: 0.5rem;
  padding: 0.5rem;
  border: 1px dashed var(--el-border-color);
  border-radius: 12px;
  background: #fff;
}

.base-image-upload__action {
  border: 1px solid var(--el-border-color);
  background: #fff;
  border-radius: 999px;
  padding: 0.45rem 0.75rem;
  cursor: pointer;
  font-size: 0.875rem;
}

.base-image-upload__action:hover {
  border-color: var(--el-color-primary);
  color: var(--el-color-primary);
}

.base-image-upload__dialog-image {
  width: 100%;
  max-height: 70vh;
  object-fit: contain;
  display: block;
}
</style>
