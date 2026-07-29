<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useOrderRealtimeStore } from '~/stores/orderRealtimeStore'
definePageMeta({
    layout: false,
    middleware: ['admin-auth'],
})
import {
    Bell,
    Box,
    DataLine,
    Goods,
    Grid,
    House,
    Search,
    ShoppingCart,
    Tickets,
    User,
    Setting,
    Close,
    Menu,
} from '@element-plus/icons-vue'
const orderDetailAlert = useOrderRealtimeStore()
import { useAdminAuthStore } from '~/stores/adminAuthStore'
const router = useRouter()
const route = useRoute()
const adminAuthStore = useAdminAuthStore()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')
const { accessToken } = storeToRefs(adminAuthStore)
const isAuthenticated = computed(() => adminAuthStore.isAuthenticated || Boolean(adminAuthStore.accessToken))
const can = adminAuthStore.can
const isSuperAdmin = adminAuthStore.isSuperAdmin
const appName = ref('Clothes Shop')
const sidebarOpen = ref(false)

const resolveAuthHeaders = () => (accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined)

const loadAppSetting = async () => {
    try {
        const response: any = await $fetch(`${apiBase}/admin/setting`, {
            method: 'GET',
            headers: resolveAuthHeaders(),
        })

        appName.value = response?.data?.app_name || appName.value
    } catch {
        // Keep the default brand label if settings are unavailable.
    }
}

const handleLogout = async () => {
    adminAuthStore.resetAuth()
    await router.push('/admin/login')
}
const adminProfile = toRef(adminAuthStore.adminProfile);

type NavItem = {
    index: string
    label: string
    icon: any
    badge?: string
    disabled?: boolean
    moduleKey?: string
    action?: string
}

type NavGroup = {
    title: string
    items: NavItem[]
}

const navigationGroups: NavGroup[] = [
    {
        title: 'Overview',
        items: [{ index: '/admin/dashboard', label: 'Dashboard', icon: House, badge: 'Live', moduleKey: 'dashboard' }],
    },
    {
        title: 'Home',
        items: [
            { index: '/admin/hero-slides', label: 'Hero Slides', icon: Tickets, badge: 'New', moduleKey: 'hero-slides' },
        ],
    },
    {
        title: 'Catalog',
        items: [
            { index: '/admin/products', label: 'Products', icon: Box, badge: 'Live', moduleKey: 'products' },
            { index: '/admin/categories', label: 'Categories', icon: Grid, badge: 'Live', moduleKey: 'categories' },
            { index: '/admin/sub_categories', label: 'Sub Categories', icon: Grid, badge: 'Live', moduleKey: 'categories' },
            { index: '/admin/collections', label: 'Collections', icon: Goods, badge: 'Live', moduleKey: 'collections' },
        ],
    },
    {
        title: 'Inventory',
        items: [
            { index: '/admin/purchases', label: 'Purchases', icon: Box, badge: 'Live', moduleKey: 'purchases' },
        ],
    },
    {
        title: 'Commerce',
        items: [
            { index: '/admin/orders', label: 'Orders', icon: ShoppingCart, badge: 'Live', moduleKey: 'orders' },
            { index: '/admin/customers', label: 'Customers', icon: User, badge: 'Live', moduleKey: 'customers' },
            { index: '/admin/promotions', label: 'Promotions', icon: Tickets, badge: 'Live', moduleKey: 'promotions' },
        ],
    },
    {
        title: 'Reports',
        items: [
            { index: '/admin/analytics', label: 'Analytics', icon: DataLine, badge: 'Live', moduleKey: 'analytics' }
        ],
    },
    {
        title: 'Settings',
        items: [
            { index: '/admin/roles', label: 'Admin Role', icon: User, badge: 'Live', moduleKey: 'roles' },
            { index: '/admin/roles/permission', label: 'Role Permission', icon: User, badge: 'Live', moduleKey: 'roles' },
            { index: '/admin/admins', label: 'Admins', icon: User, badge: 'Live', moduleKey: 'admins' },
            { index: '/admin/setting', label: 'Admin Setting', icon: Setting, badge: 'Live', moduleKey: 'setting' }
        ],
    },
]

const visibleNavigationGroups = computed(() => {
    if (isSuperAdmin) {
        return navigationGroups
    }

    return navigationGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.moduleKey || can(item.moduleKey, item.action || 'view')),
        }))
        .filter((group) => group.items.length > 0)
})

const openSidebar = () => {
    sidebarOpen.value = true
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

watch(
    () => route.fullPath,
    () => {
        sidebarOpen.value = false
    },
)

onMounted(() => {
    if (accessToken.value) {
        void loadAppSetting()
    }
})
</script>

<template>
    <div class="admin-theme h-[100dvh] overflow-hidden bg-surface-2">
        <div class="flex h-full min-h-0 xl:flex-row">
            <Transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-x-4" enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 -translate-x-4">
                <div v-if="sidebarOpen" class="fixed inset-0 z-50 xl:hidden">
                    <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-[2px]" @click="closeSidebar" />
                    <aside
                        class="scroll-shell absolute left-0 top-0 flex h-full w-[min(88vw,320px)] flex-col overflow-y-auto border-r border-white/10 bg-[linear-gradient(180deg,rgba(7,14,28,0.98),rgba(10,17,34,0.92)),linear-gradient(160deg,#18243f,#0c1221_64%)] px-4 py-5 text-white shadow-[0_30px_80px_rgba(2,6,23,0.25)]">
                        <div class="mb-3 flex items-center justify-between gap-3 px-1">
                            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-white/50">Navigation</p>
                            <button
                                class="grid h-10 w-10 place-items-center rounded-2xl border border-white/10 bg-white/10 text-white"
                                @click="closeSidebar">
                                <el-icon>
                                    <Close />
                                </el-icon>
                            </button>
                        </div>

                        <AdminSidebar :app-name="appName" :groups="visibleNavigationGroups" @close="closeSidebar" />
                    </aside>
                </div>
            </Transition>

            <aside
                class="scroll-shell hidden h-full w-[286px] flex-none flex-col overflow-y-auto border-r border-white/10 bg-[linear-gradient(180deg,rgba(7,14,28,0.98),rgba(10,17,34,0.92)),linear-gradient(160deg,#18243f,#0c1221_64%)] px-[18px] py-6 text-white shadow-[0_30px_80px_rgba(2,6,23,0.25)] xl:flex">
                <AdminSidebar :app-name="appName" :groups="visibleNavigationGroups" />
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-20 border-b border-slate-200/70 bg-surface/90 px-4 py-4 backdrop-blur-md sm:px-6 lg:px-7">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <button
                                    class="grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-px hover:shadow-md xl:hidden"
                                    @click="openSidebar">
                                    <el-icon>
                                        <Menu />
                                    </el-icon>
                                </button>

                                <div>
                                    <p class="m-0 text-xs uppercase tracking-[0.14em] text-slate-400">Admin console</p>
                                    <h1 class="m-0 truncate text-lg font-semibold text-slate-950 sm:text-xl">
                                        {{ appName }}
                                    </h1>
                                </div>
                            </div>

                            <section class="flex items-center gap-3">
                                <el-badge :value="orderDetailAlert.adminAlertTick" class="item">
                                    <el-button circle
                                        class="!h-11 !w-11 !rounded-[14px] !border !border-surface-2 !bg-muted/10">
                                        <el-icon>
                                            <Bell />
                                        </el-icon>
                                    </el-button>
                                </el-badge>

                                <div class="hidden items-center gap-3 px-3 py-2 sm:flex">
                                    <el-avatar :size="34">{{ userInitialsHelper(adminProfile?.user_name) }}</el-avatar>
                                    <div>
                                        <strong class="block text-sm text-slate-950">{{ adminProfile?.user_name
                                        }}</strong>
                                    </div>
                                </div>

                                <button v-if="isAuthenticated" type="button"
                                    class="rounded-2xl border border-surface-2 bg-muted/10 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-950"
                                    @click="handleLogout">
                                    Logout
                                </button>
                            </section>
                        </div>

                        <div class="flex items-center gap-3 xl:hidden">
                            <el-input class="!w-full" placeholder="Search products, orders, customers">
                                <template #prefix>
                                    <el-icon>
                                        <Search />
                                    </el-icon>
                                </template>
                            </el-input>
                        </div>

                        <div class="hidden items-center gap-3 xl:flex">
                            <el-input class="!w-full sm:!w-[360px]" placeholder="Search products, orders, customers">
                                <template #prefix>
                                    <el-icon>
                                        <Search />
                                    </el-icon>
                                </template>
                            </el-input>
                        </div>
                    </div>
                </header>

                <main class="scroll-shell min-h-0 min-w-0 flex-1 overflow-y-auto p-4 sm:p-6 lg:p-7">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scroll-shell {
    scrollbar-gutter: stable;
    scrollbar-width: thin;
    scrollbar-color: transparent transparent;
    transition: scrollbar-color 0.2s ease;
}

.scroll-shell::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.scroll-shell::-webkit-scrollbar-track {
    background: transparent;
}

.scroll-shell::-webkit-scrollbar-thumb {
    border: 2px solid transparent;
    border-radius: 9999px;
    background-clip: padding-box;
    background-color: rgba(148, 163, 184, 0);
    transition: background-color 0.2s ease;
}

.scroll-shell:hover {
    scrollbar-color: rgba(148, 163, 184, 0.7) transparent;
}

.scroll-shell:hover::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.7);
}

.scroll-shell:hover::-webkit-scrollbar-thumb:hover {
    background-color: rgba(100, 116, 139, 0.9);
}
</style>
