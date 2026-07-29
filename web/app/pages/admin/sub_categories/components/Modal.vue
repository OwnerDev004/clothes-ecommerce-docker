<template>
  <BaseModal
    v-model="model"
    :title="mode === 'edit' ? 'Edit Sub Category' : 'Add Sub Category'"
    width="min(1100px, 96vw)"
    body-class="p-0"
    footer-class="px-4 pb-4 pt-0 sm:px-6 sm:pb-6"
  >
    <el-form label-position="top">
      <div class="grid gap-4 px-4 pb-4 sm:px-6 sm:pb-6 lg:grid-cols-[320px_minmax(0,1fr)] lg:gap-6">
        <section class="rounded-3xl border border-border bg-surface p-5 sm:p-6">
          <h3 class="sub-category-panel__title">Sub Category Image</h3>
          <BaseImageUpload
            v-model="imagePreview"
            class-name="mx-auto"
            width="260px"
            height="260px"
            @change="handleAvatarChange"
          >
            <template #file="{ file, handlePictureCardPreview, handleRemove, disabled }">
              <div class="sub-category-image-card">
                <img class="el-upload-list__item-thumbnail" :src="file.url" alt="" />
                <span class="el-upload-list__item-actions">
                  <span class="el-upload-list__item-preview" @click="handlePictureCardPreview(file)">
                    <el-icon size="20px">
                      <ZoomIn />
                    </el-icon>
                  </span>
                  <span v-if="!disabled" class="el-upload-list__item-delete" @click="handleRemove(file)">
                    <el-icon>
                      <Delete />
                    </el-icon>
                  </span>
                </span>
              </div>
            </template>
          </BaseImageUpload>

          <div class="sub-category-upload-note">
            <p class="sub-category-upload-note__title">Tip</p>
            <p class="sub-category-upload-note__text">
              Use a square image when possible. JPG, PNG, or WEBP under 5MB works best.
            </p>
          </div>
        </section>

        <section class="rounded-3xl border border-border bg-surface p-5 sm:p-6">
          <div class="grid gap-5">
            <el-form-item label="Sub Category Name" prop="name">
              <BaseInput v-model="form.name" placeholder="Enter sub category name" />
            </el-form-item>

            <div class="grid gap-4 sm:grid-cols-2">
              <el-form-item label="Category" prop="category_id">
                <BaseSelect
                  v-model="form.category_id"
                  :options="categories"
                  placeholder="Select category"
                  class="w-full"
                />
              </el-form-item>

              <el-form-item label="Parent Sub Category" prop="parent_id">
                <BaseSelect
                  v-model="form.parent_id"
                  :options="parentOptions"
                  placeholder="Optional parent"
                  class="w-full"
                />
              </el-form-item>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <el-form-item label="Status" prop="status">
                <BaseSelect v-model="form.status" :options="statusOptions" placeholder="Select status" class="w-full" />
              </el-form-item>

              <el-form-item label="Level">
                <div class="rounded-2xl border border-border bg-slate-50 px-4 py-3 text-sm text-slate-600">
                  {{ form.level === 2 ? 'Child sub category' : 'Top level sub category' }}
                </div>
              </el-form-item>
            </div>

            <el-form-item label="Description" prop="des">
              <BaseInput
                v-model="form.des"
                type="textarea"
                :rows="8"
                placeholder="Write a short description for the sub category"
              />
            </el-form-item>
          </div>
        </section>
      </div>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-3 px-4 pb-4 pt-0 sm:px-6 sm:pb-6">
        <BaseButton @click="closeModal">Cancel</BaseButton>
        <BaseButton type="primary" :loading="loading" @click="submitForm">
          {{ mode === 'edit' ? 'Update Sub Category' : 'Save Sub Category' }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Delete, ZoomIn } from '@element-plus/icons-vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseInput from '~/components/ui/BaseInput.vue'
import BaseImageUpload from '~/components/ui/BaseImageUpload.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import BaseModal from '~/components/ui/BaseModal.vue'
import type { AdminSubCategoryRecord, SubCategorySubmitPayload } from '~/composables/useAdminSubCategory'

type SelectOption = {
  id: string | number
  label: string
}

const model = defineModel<boolean>()

const props = withDefaults(
  defineProps<{
    mode?: 'add' | 'create' | 'edit'
    subCategory: AdminSubCategoryRecord | null
    categories?: SelectOption[]
    parentSubCategories?: AdminSubCategoryRecord[]
    loading?: boolean
  }>(),
  {
    mode: 'add',
    subCategory: null,
    categories: () => [],
    parentSubCategories: () => [],
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'submit', payload: SubCategorySubmitPayload): void
}>()

const form = reactive({
  name: '',
  category_id: null as string | number | null,
  parent_id: null as string | number | null,
  status: 1,
  des: '',
  level: 1,
})

const statusOptions = [
  { id: 1, label: 'Active' },
  { id: 0, label: 'Inactive' },
]

const imagePreview = ref('')
const selectedImageFile = ref<File | null>(null)
const initialImagePreview = ref('')

const currentSubCategoryId = computed(() => props.subCategory?.id ?? null)

const parentOptions = computed<SelectOption[]>(() => {
  return props.parentSubCategories
    .filter((item) => Number(item.id) !== Number(currentSubCategoryId.value))
    .filter((item) => Number(item.level || 1) === 1)
    .filter((item) => {
      if (!form.category_id) {
        return true
      }

      return String(item.category_id) === String(form.category_id)
    })
    .map((item) => ({
      id: item.id,
      label: `${item.name}${item.category?.name ? ` · ${item.category.name}` : ''}`,
    }))
})

const syncParentLevel = () => {
  const matchedParent = props.parentSubCategories.find((item) => String(item.id) === String(form.parent_id))

  if (!matchedParent) {
    form.level = 1
    return
  }

  form.level = 2
  form.category_id = matchedParent.category_id
}

const syncFromSubCategory = () => {
  form.name = props.subCategory?.name || ''
  form.category_id = props.subCategory?.category_id ?? null
  form.parent_id = props.subCategory?.parent_id ?? null
  form.status = props.subCategory ? Number(props.subCategory.status ?? 1) : 1
  form.des = props.subCategory?.des || ''
  form.level = Number(props.subCategory?.level || (props.subCategory?.parent_id ? 2 : 1)) === 2 ? 2 : 1
  selectedImageFile.value = null
  initialImagePreview.value = props.subCategory?.image_url || ''
  imagePreview.value = initialImagePreview.value
  syncParentLevel()
}

const handleAvatarChange = (file: File | null) => {
  selectedImageFile.value = file
}

const submitForm = async () => {
  syncParentLevel()

  emit('submit', {
    mode: props.mode === 'edit' ? 'edit' : 'create',
    subCategoryId: props.subCategory?.id ?? null,
    form: {
      name: form.name.trim(),
      category_id: form.category_id,
      parent_id: form.parent_id,
      status: Number(form.status || 0),
      des: form.des.trim(),
      level: form.level,
    },
    image: selectedImageFile.value,
    remove_image: Boolean(initialImagePreview.value && !imagePreview.value && !selectedImageFile.value),
  })
}

const closeModal = () => {
  model.value = false
}

watch(
  () => form.parent_id,
  () => {
    syncParentLevel()
  },
)

watch(
  () => form.category_id,
  () => {
    if (!form.parent_id) {
      return
    }

    const matchedParent = props.parentSubCategories.find((item) => String(item.id) === String(form.parent_id))
    if (matchedParent && String(matchedParent.category_id) !== String(form.category_id)) {
      form.parent_id = null
      form.level = 1
    }
  },
)

watch(
  () => model.value,
  (open) => {
    if (open) {
      syncFromSubCategory()
      return
    }

    selectedImageFile.value = null
  },
  { immediate: true },
)
</script>

<style scoped>
.sub-category-panel__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: rgb(15 23 42);
}

.sub-category-upload-note {
  margin-top: 1rem;
  border-radius: 18px;
  background: rgba(248, 250, 252, 1);
  padding: 1rem 1rem 0.95rem;
}

.sub-category-upload-note__title {
  margin: 0;
  font-weight: 700;
  color: rgb(15 23 42);
}

.sub-category-upload-note__text {
  margin: 0.35rem 0 0;
  font-size: 0.88rem;
  color: rgb(100 116 139);
  line-height: 1.5;
}

.sub-category-image-card {
  position: relative;
  width: 100%;
  height: 100%;
}
</style>
