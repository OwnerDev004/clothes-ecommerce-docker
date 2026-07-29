<template>
  <BaseModal v-model="model" :title="mode === 'edit' ? 'Edit Hero Slide' : 'Add Hero Slide'" width="min(1100px, 96vw)"
    body-class="p-0" footer-class="px-4 pb-4 pt-0 sm:px-6 sm:pb-6">
    <el-form label-position="top">
      <div class="grid gap-4 px-4 pb-4 sm:px-6 sm:pb-6 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-6">
        <section class="p-5 rounded-3xl border border-border bg-surface sm:p-6">
          <h3 class="panel__title">Slide Image</h3>
          <BaseImageUpload v-model="imagePreview" class-name="mx-auto" width="260px" height="260px"
            @change="handleImageChange">
            <template #file="{ file, handlePictureCardPreview, handleRemove, disabled }">
              <div class="image-card">
                <img class="el-upload-list__item-thumbnail" :src="file.url" alt="" />
                <span class="el-upload-list__item-actions">
                  <span class="el-upload-list__item-preview" @click="handlePictureCardPreview(file)">
                    <el-icon size="20px"><ZoomIn /></el-icon>
                  </span>
                  <span v-if="!disabled" class="el-upload-list__item-delete" @click="handleRemove(file)">
                    <el-icon><Delete /></el-icon>
                  </span>
                </span>
              </div>
            </template>
          </BaseImageUpload>
          <div class="upload-note">
            <p class="upload-note__title">Tip</p>
            <p class="upload-note__text">Recommended size: 16:9 ratio, JPG or PNG, under 2MB.</p>
          </div>
        </section>
        <section class="p-5 rounded-3xl border border-border bg-surface sm:p-6">
          <div class="grid gap-5">
            <el-form-item label="Title">
              <BaseInput v-model="form.title" placeholder="Enter slide title" />
            </el-form-item>

            <el-form-item label="Subtitle (badge)">
              <BaseInput v-model="form.subtitle" placeholder="e.g. New Season, Summer Sale" />
            </el-form-item>

            <el-form-item label="Description">
              <BaseInput v-model="form.description" type="textarea" :rows="3" placeholder="Slide description text" />
            </el-form-item>

            <div class="grid gap-4 sm:grid-cols-2">
              <el-form-item label="Link URL">
                <BaseInput v-model="form.link_url" placeholder="e.g. /frontend/categories" />
              </el-form-item>
              <el-form-item label="Link Text">
                <BaseInput v-model="form.link_text" placeholder="e.g. Shop Now" />
              </el-form-item>
            </div>

            <el-form-item label="Gradient (CSS)">
              <BaseInput v-model="form.gradient" placeholder="linear-gradient(135deg, #f7f7f7 0%, #fff 45%, #f1f5f9 100%)" />
            </el-form-item>

            <div class="grid gap-4 sm:grid-cols-3">
              <el-form-item label="Sort Order">
                <BaseInput v-model.number="form.sort_order" type="number" placeholder="0" />
              </el-form-item>
              <el-form-item label="Status">
                <BaseSelect v-model="form.status" :options="statusOptions" class="w-full" />
              </el-form-item>
            </div>
          </div>
        </section>
      </div>
    </el-form>
    <template #footer>
      <div class="flex justify-end gap-3 px-4 pb-4 pt-0 sm:px-6 sm:pb-6">
        <BaseButton @click="closeModal">Cancel</BaseButton>
        <BaseButton type="primary" :loading="loading" @click="submitForm">
          {{ mode === 'edit' ? 'Update Slide' : 'Save Slide' }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Delete, ZoomIn } from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue';
import BaseInput from '~/components/ui/BaseInput.vue';
import BaseImageUpload from '~/components/ui/BaseImageUpload.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseModal from '~/components/ui/BaseModal.vue';
import type { AdminHeroSlideRecord, HeroSlideSubmitPayload } from '~/composables/useAdminHeroSlide';

const model = defineModel<boolean>()
const props = withDefaults(
  defineProps<{
    mode?: 'add' | 'create' | 'edit',
    slide: AdminHeroSlideRecord | null,
    loading?: boolean
  }>(),
  { mode: 'add', slide: null, loading: false }
)

const emit = defineEmits<{
  (e: 'submit', payload: HeroSlideSubmitPayload): void
}>()

const form = reactive({
  title: '',
  subtitle: '',
  description: '',
  gradient: '',
  link_url: '',
  link_text: 'Shop Now',
  sort_order: 0,
  status: 1,
})

const statusOptions = [
  { id: 1, label: 'Active' },
  { id: 0, label: 'Inactive' },
]

const imagePreview = ref('')
const selectedImageFile = ref<File | null>(null)
const initialImagePreview = ref('')

const syncFromSlide = () => {
  const s = props.slide
  form.title = s?.title || ''
  form.subtitle = s?.subtitle || ''
  form.description = s?.description || ''
  form.gradient = s?.gradient || ''
  form.link_url = s?.link_url || ''
  form.link_text = s?.link_text || 'Shop Now'
  form.sort_order = s?.sort_order ?? 0
  form.status = s ? Number(s?.status) : 1
  selectedImageFile.value = null
  initialImagePreview.value = s?.image_url || ''
  imagePreview.value = initialImagePreview.value
}

const handleImageChange = (file: File | null) => {
  selectedImageFile.value = file
}

const submitForm = async () => {
  emit('submit', {
    mode: props.mode === 'edit' ? 'edit' : 'create',
    slideId: props.slide?.id ?? null,
    form: {
      title: form.title.trim(),
      subtitle: form.subtitle.trim(),
      description: form.description.trim(),
      gradient: form.gradient.trim(),
      link_url: form.link_url.trim(),
      link_text: form.link_text.trim(),
      sort_order: form.sort_order,
      status: form.status,
    },
    image: selectedImageFile.value,
    remove_image: Boolean(initialImagePreview.value && !imagePreview.value && !selectedImageFile.value),
  })
}

const closeModal = () => { model.value = false }

watch(
  () => model.value,
  (open) => {
    if (open) {
      syncFromSlide()
      return
    }
    selectedImageFile.value = null
  },
  { immediate: true },
)
</script>

<style scoped>
.panel__title {
  margin: 0 0 1rem;
  font-size: 1rem;
  font-weight: 700;
  color: rgb(15 23 42);
}

.upload-note {
  margin-top: 1rem;
  border-radius: 18px;
  background: rgba(248, 250, 252, 1);
  padding: 1rem 1rem 0.95rem;
}

.upload-note__title {
  margin: 0;
  font-weight: 700;
  color: rgb(15 23 42);
}

.upload-note__text {
  margin: 0.35rem 0 0;
  color: rgb(100 116 139);
  font-size: 0.875rem;
}

.image-card {
  position: relative;
  width: 260px;
  height: 260px;
  border-radius: 12px;
  overflow: hidden;
}

.image-card :deep(.el-upload-list__item-thumbnail) {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.image-card :deep(.el-upload-list__item-actions) {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: rgba(15, 23, 42, 0.45);
}

.image-card :deep(.el-upload-list__item-preview),
.image-card :deep(.el-upload-list__item-delete) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  color: #fff;
  cursor: pointer;
  background: rgb(255 255 255 / 0.18);
  backdrop-filter: blur(8px);
}
</style>
