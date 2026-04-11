<template>
    <BaseModal v-model="model" :title="mode === 'edit' ? 'Edit Category' : 'Add Category'" width="1100px"
        body-class="p-0" footer-class="px-6 pb-6 pt-0">
        <el-form label-position="top">

            <div class="grid gap-6 px-6 pb-6 lg:grid-cols-[360px_minmax(0,1fr)]">
                <section class="p-6 rounded-3xl border border-dashed border-muted bg-surface-2/10 ">
                    <h3 class="category-panel__title">Category Image</h3>

                    <BaseImageUpload v-model="imagePreview" class-name="mx-auto" width="260px" height="260px"
                        @change="handleAvatarChange">
                        <template #file="{ file, handlePictureCardPreview, handleRemove, disabled }">
                            <div class="category-image-card">
                                <img class="el-upload-list__item-thumbnail" :src="file.url" alt="" />
                                <span class="el-upload-list__item-actions">
                                    <span class="el-upload-list__item-preview" @click="handlePictureCardPreview(file)">
                                        <el-icon size="20px">
                                            <ZoomIn />
                                        </el-icon>
                                    </span>
                                    <span v-if="!disabled" class="el-upload-list__item-delete"
                                        @click="handleRemove(file)">
                                        <el-icon>
                                            <Delete />
                                        </el-icon>
                                    </span>
                                </span>
                            </div>
                        </template>
                    </BaseImageUpload>

                    <div class="category-upload-note">
                        <p class="category-upload-note__title">Tip</p>
                        <p class="category-upload-note__text">
                            Recommended size: 1:1 ratio, JPG or PNG, under 2MB.
                        </p>
                    </div>
                </section>
                <section class="p-6 rounded-3xl border border-dashed border-muted bg-surface-2/10 ">
                    <div class="grid gap-5">
                        <el-form-item label="Category Name" prop="name">
                            <BaseInput v-model="form.name" placeholder="Enter category name" />
                        </el-form-item>

                        <el-form-item label="Status" prop="status">
                            <BaseSelect v-model="statusSelectValue" :options="statusOptions" placeholder="Select status"
                                class="w-full" />
                        </el-form-item>

                        <el-form-item label="Description" prop="desc">
                            <BaseInput v-model="form.desc" type="textarea" :rows="9"
                                placeholder="Write a short description for the category" />
                        </el-form-item>
                    </div>
                </section>
            </div>
        </el-form>
        <template #footer>
            <BaseButton @click="closeModal">Cancel</BaseButton>
            <BaseButton type="primary" :loading="loading" @click="submitForm">
                {{ mode === 'edit' ? 'Update Category' : 'Save Category' }}
            </BaseButton>
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
const model = defineModel<boolean>()
const props = withDefaults(
    defineProps<{
        mode?: 'add' | 'create' | 'edit',
        category?: AdminCategoryRecord | null,
        loading?: boolean
    }
    >(),
    {
        mode: 'add',
        category: null,
        loading: false
    }
)

type CategorySubmitPayload = {
    mode: 'create' | 'edit'
    categoryId: string | number | null
    form: {
        name: string
        desc: string
        status: number
    }
    image: File | null
    remove_image: boolean
}

const emit = defineEmits<{
    (e: 'submit', payload: CategorySubmitPayload): void
}>()

// form
const form = reactive({
    name: '',
    desc: '',
    status: 1,
})

const statusOptions = [
    { id: 1, label: 'Active' },
    { id: 0, label: 'Unactive' },
]

const statusSelectValue = computed<number>({
    get: () => (form.status ? 1 : 0),
    set: (value) => {
        form.status = 1
    },
})

const imagePreview = ref('')
const selectedImageFile = ref<File | null>(null)
const initialImagePreview = ref('')

const syncFromCategory = () => {
    form.name = props.category?.name || ''
    form.desc = props.category?.des || ''
    form.status = Number(props.category?.status)
    selectedImageFile.value = null
    initialImagePreview.value = props.category?.image_url || ''
    imagePreview.value = initialImagePreview.value
}

const handleAvatarChange = (file: File | null) => {
    selectedImageFile.value = file
}

const submitForm = async () => {
    emit('submit', {
        mode: props.mode === 'edit' ? 'edit' : 'create',
        categoryId: props.category?.id ?? null,
        form: {
            name: form.name.trim(),
            desc: form.desc.trim(),
            status: Number(form.status),
        },
        image: selectedImageFile.value,
        remove_image: Boolean(initialImagePreview.value && !imagePreview.value && !selectedImageFile.value),
    })
}

const closeModal = () => {
    model.value = false
}

watch(
    () => model.value,
    (open) => {
        if (open) {
            syncFromCategory()
            return
        }

        selectedImageFile.value = null
    },
    { immediate: true },
)

</script>

<style scoped>
.category-hero__title {
    margin: 0;
    font-size: 1.5rem;
    line-height: 1.2;
    font-weight: 800;
    color: rgb(15 23 42);
}

.category-hero__description {
    margin: 0.65rem 0 0;
    max-width: 56ch;
    color: rgb(71 85 105);
}

.category-hero__meta {
    display: grid;
    align-content: start;
    gap: 0.5rem;
    min-width: 240px;
}

.category-hero__hint {
    font-size: 0.875rem;
    color: rgb(100 116 139);
}

.category-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    width: fit-content;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.04);
    padding: 0.45rem 0.8rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgb(15 23 42);
}

.category-pill__dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, rgb(59 130 246), rgb(14 165 233));
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.category-panel {
    border: 1px solid rgba(226, 232, 240, 1);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.92);
    padding: 1.25rem;
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}

.category-panel--accent {
    background:
        radial-gradient(circle at top, rgba(59, 130, 246, 0.08), transparent 45%),
        rgba(255, 255, 255, 0.96);
}

.category-panel__header {
    margin-bottom: 1rem;
}

.category-panel__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: rgb(15 23 42);
}

.category-panel__text {
    margin: 0.35rem 0 0;
    color: rgb(100 116 139);
    font-size: 0.9rem;
}

.category-upload-note {
    margin-top: 1rem;
    border-radius: 18px;
    background: rgba(248, 250, 252, 1);
    padding: 1rem 1rem 0.95rem;
}

.category-upload-note__title {
    margin: 0;
    font-weight: 700;
    color: rgb(15 23 42);
}

.category-upload-note__text {
    margin: 0.35rem 0 0;
    color: rgb(100 116 139);
    font-size: 0.875rem;
}

.category-image-card {
    position: relative;
    width: 260px;
    height: 260px;
    border-radius: 12px;
    overflow: hidden;
}

.category-image-card :deep(.el-upload-list__item-thumbnail) {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}

.category-image-card :deep(.el-upload-list__item-actions) {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.18), rgba(15, 23, 42, 0.46));
}

.category-image-card :deep(.el-upload-list__item-preview),
.category-image-card :deep(.el-upload-list__item-delete) {
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
