<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import HeaderBreadCrumb from '~/components/admin/HeaderBreadCrumb.vue'
import BaseButton from '~/components/ui/BaseButton.vue'
import BaseCard from '~/components/ui/BaseCard.vue'
import BaseSelect from '~/components/ui/BaseSelect.vue'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

type RoleItem = {
  id: number | string
  name: string
  slug: string
  is_system?: boolean
}

type ModuleItem = {
  id: number | string
  name: string
  slug: string
}

const ACTIONS = ['view', 'create', 'edit', 'delete'] as const

definePageMeta({
  layout: 'admin',
  middleware: ['admin-auth'],
})

const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const adminAuthStore = useAdminAuthStore()
const route = useRoute()
const { accessToken } = storeToRefs(adminAuthStore)

const resolveAuthHeaders = () => (accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined)

const roleOptions = ref<Array<{ id: number | string; label: string }>>([])
const roleRecords = ref<RoleItem[]>([])
const moduleRecords = ref<ModuleItem[]>([])
const selectedRoleId = ref<number | string | null>(null)
const loadingRoles = ref(false)
const loadingModules = ref(false)
const saving = ref(false)

const permissionDraft = reactive<Record<string, string[]>>({})

const selectedRole = computed(() => roleRecords.value.find((item) => item.id === selectedRoleId.value) || null)

const buildPermissionPayload = () => {
  return moduleRecords.value.map((module) => ({
    module_id: module.id,
    permissions: [...(permissionDraft[module.slug] || [])],
  }))
}

const getModuleActions = (moduleSlug: string) => permissionDraft[moduleSlug] || []

const isModuleFullySelected = (moduleSlug: string) => {
  const actions = getModuleActions(moduleSlug)
  return ACTIONS.every((action) => actions.includes(action))
}

const isModuleIndeterminate = (moduleSlug: string) => {
  const actions = getModuleActions(moduleSlug)
  return actions.length > 0 && !isModuleFullySelected(moduleSlug)
}

const setModulePermissions = (moduleSlug: string, checked: boolean) => {
  permissionDraft[moduleSlug] = checked ? [...ACTIONS] : []
}

const hydrateDraft = (permissions: Array<{ module_id: number | string; module?: { slug?: string | null }; permissions?: string[] }>) => {
  moduleRecords.value.forEach((module) => {
    permissionDraft[module.slug] = []
  })

  permissions.forEach((item) => {
    const slug = item.module?.slug
    if (!slug) {
      return
    }

    permissionDraft[slug] = ACTIONS.filter((action) => (item.permissions || []).includes(action))
  })
}

const togglePermission = (moduleSlug: string, action: (typeof ACTIONS)[number], checked: boolean) => {
  const current = permissionDraft[moduleSlug] || []
  permissionDraft[moduleSlug] = checked
    ? Array.from(new Set([...current, action]))
    : current.filter((item) => item !== action)
}

const fetchRoles = async () => {
  loadingRoles.value = true
  try {
    const response: any = await $fetch(`${apiBase}/admin/roles`, {
      method: 'GET',
      headers: resolveAuthHeaders(),
      query: { page: 1, per_page: 200, sort_by: 'name_asc' },
    })

    roleRecords.value = Array.isArray(response?.data) ? response.data : []
    roleOptions.value = roleRecords.value.map((role) => ({
      id: role.id,
      label: `${role.name}${role.slug ? ` (${role.slug})` : ''}`,
    }))

    const queryRoleId = Array.isArray(route.query.role_id)
      ? route.query.role_id[0]
      : route.query.role_id

    if (queryRoleId) {
      const matchedRole = roleRecords.value.find((role) => String(role.id) === String(queryRoleId))
      selectedRoleId.value = matchedRole?.id ?? roleRecords.value[0]?.id ?? null
    } else if (!selectedRoleId.value && roleRecords.value.length) {
      selectedRoleId.value = roleRecords.value[0]?.id ?? null
    }
  } finally {
    loadingRoles.value = false
  }
}

const fetchModules = async () => {
  loadingModules.value = true
  try {
    const response: any = await $fetch(`${apiBase}/admin/modules`, {
      method: 'GET',
      headers: resolveAuthHeaders(),
    })

    moduleRecords.value = Array.isArray(response?.data) ? response.data : []
    moduleRecords.value.forEach((module) => {
      if (!permissionDraft[module.slug]) {
        permissionDraft[module.slug] = []
      }
    })
  } finally {
    loadingModules.value = false
  }
}

const fetchRoleDetail = async (roleId: number | string) => {
  const response: any = await $fetch(`${apiBase}/admin/roles/${roleId}`, {
    method: 'GET',
    headers: resolveAuthHeaders(),
  })

  const permissions = response?.data?.permissions || []
  hydrateDraft(permissions)
}

const loadPage = async () => {
  await Promise.all([fetchModules(), fetchRoles()])
  if (selectedRoleId.value) {
    await fetchRoleDetail(selectedRoleId.value)
  }
}

const savePermissions = async () => {
  if (!selectedRoleId.value) {
    ElMessage.warning('Select a role first.')
    return
  }

  saving.value = true
  try {
    await $fetch(`${apiBase}/admin/roles/${selectedRoleId.value}`, {
      method: 'PUT',
      headers: resolveAuthHeaders(),
      body: {
        permissions: buildPermissionPayload(),
      },
    })

    ElMessage.success('Role permissions updated.')
    await fetchRoleDetail(selectedRoleId.value)
  } catch (error: any) {
    ElMessage.error(error?.data?.message || 'Failed to update permissions.')
  } finally {
    saving.value = false
  }
}

watch(
  selectedRoleId,
  async (roleId) => {
    if (!roleId) {
      return
    }

    if (!moduleRecords.value.length) {
      return
    }

    await fetchRoleDetail(roleId)
  },
)

watch(
  () => route.query.role_id,
  async (roleId) => {
    if (!roleId || !moduleRecords.value.length || !roleRecords.value.length) {
      return
    }

    const matchedRole = roleRecords.value.find((role) => String(role.id) === String(Array.isArray(roleId) ? roleId[0] : roleId))
    if (matchedRole) {
      selectedRoleId.value = matchedRole.id
      await fetchRoleDetail(matchedRole.id)
    }
  },
)

onMounted(async () => {
  await loadPage()
})
</script>

<template>
  <div>
    <HeaderBreadCrumb title="Admin Roles Permission">
      <el-breadcrumb-item :to="{ path: '/admin/dashboard' }">Dashboard</el-breadcrumb-item>
      <el-breadcrumb-item :to="{ path: '/admin/roles' }">Admin Roles</el-breadcrumb-item>
      <el-breadcrumb-item>Permission Matrix</el-breadcrumb-item>
    </HeaderBreadCrumb>

    <BaseCard>
      <template #header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div class="grid w-full gap-3 lg:grid-cols-[280px_minmax(0,1fr)]">
            <BaseSelect v-model="selectedRoleId" :options="roleOptions" placeholder="Select role" class="w-full" />
          </div>

          <div class="flex gap-3">
            <BaseButton @click="loadPage">Refresh</BaseButton>
            <BaseButton type="primary" :loading="saving" @click="savePermissions">Save Permissions</BaseButton>
          </div>
        </div>
      </template>

      <div class="space-y-4">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
          <p class="m-0 text-sm text-slate-500">
            Role:
            <strong class="text-slate-950">{{ selectedRole?.name || 'None selected' }}</strong>
            <span v-if="selectedRole?.is_system"
              class="ml-2 rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
              System role
            </span>
          </p>
        </div>

        <el-table :data="moduleRecords" border v-loading="loadingRoles || loadingModules">
          <el-table-column prop="name" label="Module" width="220" />
          <el-table-column label="Slug" prop="slug" width="220" />
          <el-table-column label="All" width="90" align="center">
            <template #default="{ row }">
              <el-checkbox :model-value="isModuleFullySelected(row.slug)"
                :indeterminate="isModuleIndeterminate(row.slug)"
                @change="(checked: boolean) => setModulePermissions(row.slug, checked)" />
            </template>
          </el-table-column>

          <el-table-column v-for="action in ACTIONS" :key="action" :label="action.toUpperCase()" width="120">
            <template #default="{ row }">
              <el-checkbox :model-value="permissionDraft[row.slug]?.includes(action)"
                @change="(checked: boolean) => togglePermission(row.slug, action, checked)" />
            </template>
          </el-table-column>
        </el-table>
      </div>
    </BaseCard>
  </div>
</template>
