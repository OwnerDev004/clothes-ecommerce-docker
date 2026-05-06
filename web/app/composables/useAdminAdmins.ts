import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

export type adminRecord = {
  id: number | string | null
  first_name: string
  last_name: string
  full_name: string
  gender: 'male' | 'female'
  dob?: string | null
  user_name: string
  phone?: string | null
  email: string
  role: string
  created_at: string
  updated_at: string
}

export type adminForm = {
  first_name: string
  last_name: string
  gender: 'male' | 'female'
  dob: string
  user_name: string
  phone: string
  email: string
  password: string
  role: string
}

export type adminSubmitPayload = {
  mode: 'create' | 'edit'
  id: string | number | null
  form: adminForm
}

type rolesMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

type adminListResponse = {
  data: adminRecord[]
  meta?: rolesMeta
}

export const useAdminAdmins = () => {
  const config = useRuntimeConfig()
  const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
  const adminAuthStore = useAdminAuthStore()
  const { accessToken } = storeToRefs(adminAuthStore)

  const filters = reactive({
    search_txt: '',
    sort_by: 'latest',
    page: 1,
    per_page: 10,
  })

  const listResponse = ref<adminListResponse>({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: filters.per_page,
      total: 0,
    },
  })

  const tableData = computed(() => listResponse.value.data || [])
  const pending = ref(false)
  const loading = ref(false)
  const deletingId = ref<number | string | null>(null)
  const isModalAdmin = ref(false)
  const modalMode = ref<'create' | 'edit'>('create')
  const selectedAdmin = ref<adminRecord | null>(null)

  const pagination = computed(() => ({
    current_page: listResponse.value.meta?.current_page || 1,
    last_page: listResponse.value.meta?.last_page || 1,
    per_page: listResponse.value.meta?.per_page || filters.per_page,
    total: listResponse.value.meta?.total || 0,
  }))

  const resolveAuthHeaders = () => {
    return accessToken.value
      ? { Authorization: `Bearer ${accessToken.value}` }
      : undefined
  }

  const buildQuery = () => ({
    page: filters.page,
    per_page: filters.per_page,
    sort_by: filters.sort_by,
    search_txt: filters.search_txt.trim(),
  })

  const fetchAdmins = async () => {
    const response: any = await $fetch(`${apiBase}/admin/admins`, {
      method: 'GET',
      headers: resolveAuthHeaders(),
      query: buildQuery(),
    })

    return {
      data: Array.isArray(response?.data) ? response.data : [],
      meta: response?.meta || {
        current_page: 1,
        last_page: 1,
        per_page: filters.per_page,
        total: 0,
      },
    } as adminListResponse
  }

  const loadAdmins = async () => {
    if (!accessToken.value) {
      return
    }

    pending.value = true
    try {
      listResponse.value = await fetchAdmins()
    } catch (error) {
      console.error('Failed to load admins', error)
      listResponse.value = {
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: filters.per_page,
          total: 0,
        },
      }
    } finally {
      pending.value = false
    }
  }

  const openCreate = () => {
    selectedAdmin.value = null
    modalMode.value = 'create'
    isModalAdmin.value = true
  }

  const openEdit = (admin: adminRecord) => {
    selectedAdmin.value = admin
    modalMode.value = 'edit'
    isModalAdmin.value = true
  }

  const deleteAdmin = async (admin: adminRecord) => {
    if (!admin.id) {
      return
    }

    try {
      await ElMessageBox.confirm(`Delete admin "${admin.full_name || admin.user_name}"?`, 'Confirm delete', {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      })

      deletingId.value = admin.id
      await $fetch(`${apiBase}/admin/admins/${admin.id}`, {
        method: 'DELETE',
        headers: resolveAuthHeaders(),
      })
      ElMessage.success('Admin deleted successfully.')
      await loadAdmins()
    } catch (error: any) {
      if (error !== 'cancel' && error !== 'close') {
        ElMessage.error(error?.data?.message || 'Failed to delete admin.')
      }
    } finally {
      deletingId.value = null
    }
  }

  const handleSubmit = async (payload: adminSubmitPayload) => {
    loading.value = true
    try {
      const body: Record<string, unknown> = {
        first_name: payload.form.first_name,
        last_name: payload.form.last_name,
        gender: payload.form.gender,
        dob: payload.form.dob || null,
        user_name: payload.form.user_name,
        phone: payload.form.phone || null,
        email: payload.form.email,
        role: payload.form.role,
      }

      if (payload.form.password.trim()) {
        body.password = payload.form.password
      }

      if (payload.mode === 'create') {
        await $fetch(`${apiBase}/admin/admins`, {
          method: 'POST',
          headers: resolveAuthHeaders(),
          body,
        })
        ElMessage.success('Admin created successfully.')
      } else if (payload.id) {
        await $fetch(`${apiBase}/admin/admins/${payload.id}`, {
          method: 'PUT',
          headers: resolveAuthHeaders(),
          body,
        })
        ElMessage.success('Admin updated successfully.')
      }

      isModalAdmin.value = false
      await loadAdmins()
    } catch (error: any) {
      ElMessage.error(error?.data?.message || 'Failed to save admin.')
    } finally {
      loading.value = false
    }
  }

  const resetFilters = () => {
    filters.search_txt = ''
    filters.sort_by = 'latest'
    filters.page = 1
  }

  const setPage = (page: number) => {
    filters.page = page
  }

  watch(
    () => [filters.page, filters.per_page, filters.sort_by, filters.search_txt],
    () => {
      void loadAdmins()
    },
  )

  return {
    tableData,
    filters,
    pending,
    loading,
    deletingId,
    isModalAdmin,
    modalMode,
    selectedAdmin,
    pagination,
    handleSubmit,
    openCreate,
    openEdit,
    deleteAdmin,
    resetFilters,
    setPage,
    loadAdmins,
  }
}
