import { watchDebounced } from '@vueuse/core'
import { lowerCase } from 'lodash'
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { storeToRefs } from 'pinia'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

// Types
export type AdminCategoryRecord = {
    id: number | string,
    name: string,
    slug: string,
    des?: string | null,
    status?: string | number | null,
    image_url?: string | null,
    created_at?: string
}
export type AdminCategoryRecordTable = AdminCategoryRecord & {
    preview_image : string[],
    status: number
}
export type AdminCategoryListResponse = {
    data: AdminCategoryRecord[],
    meta: MetaPage
}
export type MetaPage = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}
export type CategorySubmitPayload = {
    mode: 'create' | 'edit'
    categoryId: string | number | null
    form: {
        name: string
        desc: string
        status: boolean
    }
    image: File | null
    remove_image: boolean
}
export const useAdminCategory = () => {
    const config = useRuntimeConfig();
    const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
    const pending = ref(false)
    const isFormModal = ref(false)
    const selectedCategory = ref<AdminCategoryRecord | null>(null)
    const modalMode = ref<'create'|'edit'>('create')
    const saving = ref(false)
    const error = ref<Error|null>(null)
    const { accessToken } = storeToRefs(useAdminAuthStore())
    const categoriesResponse = ref<AdminCategoryListResponse>({
      data: [],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
      },
    })
const filters = reactive({
    search_txt: '',
    status: null,
    sort_by: '',
    page: 1,
    per_page: 10
})
// pagination
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
})
const deletingId = ref<number | string | null>(null)
const statusOption = ref([
    { id: 1, label: 'Active' },
    { id: 0, label: 'UnActive' }
])
const sortOptions = [
    { id: 'latest', label: 'Latest' },
    { id: 'oldest', label: 'Oldest' },
    { id: 'name_asc', label: 'Name A-Z' },
    { id: 'name_desc', label: 'Name Z-A' },
]

// resolve Auth Header
const resolveAuthHeaders = () => {
    return accessToken.value ? {Authorization:`Bearer ${accessToken.value}`} : undefined;
}
const buildQuery = () => {
      const query : Record<string, string | number> = {
        'page': filters.page,
        'per_page' : filters.per_page,
        'sort_by' : filters.sort_by
      }
      const add = (key: string, value: string | number | null) => {
          if(value !== null && value !== ''){
             query[key] = value
          }
      }
      add('search_txt', lowerCase(filters.search_txt).trim())
      add('status', filters.status)

      return query;
}
//defaultMetaPage
const defaultMetaPage  = (): MetaPage => {
    return {
    current_page: 1,
    last_page: 1,
    per_page: filters.per_page,
    total: 0
    }
}

//normalizeData
const normalizeAdminCategoryData = (category:AdminCategoryRecord):AdminCategoryRecordTable =>{
     
    const image = category?.image_url ? category?.image_url : '';
    return {
        ...category,
        preview_image: [image],
        status: Number(category?.status)
    }
    
}

//** functions **/


// loadCategory 
const categoryFetching = async() => {
    if(!accessToken.value){
        return;
    }
    pending.value = true
    error.value = null
  try {
    const response:any = await $fetch(`${apiBase}/admin/categories`, {
        method: 'GET',
        headers: resolveAuthHeaders(),
        query: buildQuery()
     })
     return categoriesResponse.value = {
        data: Array.isArray(response?.data) ? response?.data : [],
        meta: response?.meta || defaultMetaPage()
    }
  } catch (err) {
     error.value = err as Error
     categoriesResponse.value = {
        data: [],
        meta: defaultMetaPage()
     }
  } finally{
       pending.value = false
  }
}

//reset Filters
const resetFilters = () => {
filters.search_txt = ''
filters.status = null
filters.sort_by = ''
}
//add Category
const addCategory = () => {
    selectedCategory.value = null
    modalMode.value = 'create'
    isFormModal.value = true
}

// delete Category
const deleteCategory = async(id: string | number) => {
    if(id == ''){
       throw new Error('Category Id not founded')
    }
   try {
     await ElMessageBox.confirm(
            `Delete "${id}"? This also removes its images from Cloudinary.`,
            'Confirm delete',
            {
              confirmButtonText: 'Delete',
              cancelButtonText: 'Cancel',
              type: 'warning',
            },
          )

    deletingId.value = id
     await $fetch(`${apiBase}/admin/categories/${id}`, {
        headers: resolveAuthHeaders(),
        method: 'DELETE',
        credentials: 'include'
    });
    ElMessage.success('Category deleted successfully.')
    void categoryFetching()

    } catch (error) {
        console.error('Failed to delete category', error)
        ElMessage.error('Failed to delete category.')
    } finally {
        deletingId.value = null
    }

}

//edit Category
const editCategory = (category: AdminCategoryRecord) => {
    console.log(category);
    
    selectedCategory.value = category
    modalMode.value = 'edit'
    isFormModal.value = true
}

//action api
const buildCategoryFormData = (payload: CategorySubmitPayload) => {
    const formData = new FormData()
    formData.append('name', payload.form.name)
    formData.append('des', payload.form.desc)
    formData.append('status', payload.form.status ? '1' : '0')

    if (payload.remove_image) {
        formData.append('remove_image', '1')
    }

    if (payload.image) {
        formData.append('image', payload.image)
    }

    return formData
}

const updateCategory = async (payload: CategorySubmitPayload) => {
    if (!payload.categoryId) {
        throw new Error('Missing category id.')
    }

    const formData = buildCategoryFormData(payload)
    formData.append('_method', 'PUT')

    await $fetch(`${apiBase}/admin/categories/${payload.categoryId}`, {
        headers: resolveAuthHeaders(),
        method: 'POST',
        body: formData,
    })
}

const createCategory = async (payload: CategorySubmitPayload) => {
    const formData = buildCategoryFormData(payload)

    await $fetch(`${apiBase}/admin/categories`, {
        headers: resolveAuthHeaders(),
        method: 'POST',
        body: formData,
    })
}

//submit form 
const submitForm = async (payload: CategorySubmitPayload) => {
    try {
        saving.value = true

        if (payload.mode === 'edit' && payload.categoryId) {
            await updateCategory(payload)
            ElMessage.success('Category updated successfully.')
        } else {
            await createCategory(payload)
            ElMessage.success('Category created successfully.')
        }

        isFormModal.value = false
        selectedCategory.value = null
        modalMode.value = 'create'
        void categoryFetching()
    } catch (error) {
        console.error('Failed to save category', error)
        ElMessage.error('Failed to save category.')
    } finally {
        saving.value = false
    }
}

//**Computed */
const dataTable = computed<AdminCategoryListResponse>(()=>{
    return {
        data: categoriesResponse.value?.data ? categoriesResponse.value?.data.map(normalizeAdminCategoryData) : [],
        meta:categoriesResponse.value?.meta
    }

})
const categoryStatus = (status: number) => {
      return status == 1 ? 'Ative' : 'Unactive';
}

//watchDebounded
watchDebounced(
    [()=> filters.status, 
     ()=> filters.sort_by,
     ()=> filters.search_txt,
     ()=> filters.page,
     ()=> filters.per_page   
    ],
    ()=>{
        filters.page = 1
        void categoryFetching()
    },
   { debounce: 300, maxWait: 600 },
)
// watch
  watch(
    () => accessToken.value,
    () => {
      void categoryFetching()
    },
    { immediate: true },
  )
return{
    filters,
    pagination,
    deletingId,
    statusOption,
    sortOptions,
    dataTable,
    isFormModal,
    modalMode,
    saving,
    selectedCategory,
    categoryStatus,
    pending,
    error,
    submitForm,
    resetFilters,
    addCategory,
    deleteCategory,
    editCategory
}
}
