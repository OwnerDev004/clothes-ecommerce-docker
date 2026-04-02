<script setup lang="ts">
import { computed } from 'vue'
import {
    ArrowRight,
    Bell,
    Box,
    Coin,
    DataLine,
    Goods,
    Grid,
    House,
    Search,
    ShoppingCart,
    Star,
    Tickets,
    User,
} from '@element-plus/icons-vue'
import { useAdminAuthStore } from '~/stores/adminAuthStore'

const router = useRouter()
const adminAuthStore = useAdminAuthStore()
const isAuthenticated = computed(() => adminAuthStore.isAuthenticated || Boolean(adminAuthStore.accessToken))

const handleLogout = async () => {
    adminAuthStore.resetAuth()
    await router.push('/admin/login')
}

type NavItem = {
    index: string
    label: string
    icon: any
    badge?: string
    disabled?: boolean
}

type NavGroup = {
    title: string
    items: NavItem[]
}

const navigationGroups: NavGroup[] = [
    {
        title: 'Overview',
        items: [{ index: '/admin/dashboard', label: 'Dashboard', icon: House, badge: 'Live' }],
    },
    {
        title: 'Catalog',
        items: [
            { index: '/admin/products', label: 'Products', icon: Box },
            { index: '/admin/categories', label: 'Categories', icon: Grid, badge: 'Soon', disabled: true },
            { index: '/admin/collections', label: 'Collections', icon: Goods, badge: 'Soon', disabled: true },
        ],
    },
    {
        title: 'Commerce',
        items: [
            { index: '/admin/orders', label: 'Orders', icon: ShoppingCart, badge: 'Soon', disabled: true },
            { index: '/admin/customers', label: 'Customers', icon: User, badge: 'Soon', disabled: true },
            { index: '/admin/promotions', label: 'Promotions', icon: Tickets, badge: 'Soon', disabled: true },
        ],
    },
    {
        title: 'Reports',
        items: [
            { index: '/admin/analytics', label: 'Analytics', icon: DataLine, badge: 'Soon', disabled: true },
            { index: '/admin/finance', label: 'Finance', icon: Coin, badge: 'Soon', disabled: true },
            { index: '/admin/quality', label: 'Quality', icon: Star, badge: 'Soon', disabled: true },
        ],
    },
]
</script>

<template>
    <div class="h-[100dvh] overflow-hidden bg-slate-50">
        <div class="flex h-full min-h-0 flex-col xl:flex-row">
            <aside
                class="scroll-shell scroll-smooth flex h-72 w-full flex-none flex-col overflow-y-auto border-b border-white/10 bg-[linear-gradient(180deg,rgba(7,14,28,0.98),rgba(10,17,34,0.92)),linear-gradient(160deg,#18243f,#0c1221_64%)] px-4 py-5 text-white shadow-[0_30px_80px_rgba(2,6,23,0.25)] xl:h-full xl:w-[286px] xl:border-b-0 xl:border-r xl:border-white/10 xl:px-[18px] xl:py-6">
                <div class="flex items-center gap-3 rounded-2xl px-3 pb-4 pt-2">
                    <div
                        class="grid h-11 w-11 place-items-center rounded-[16px] bg-[linear-gradient(145deg,#f8fafc,#c7d2fe)] font-extrabold tracking-[0.08em] text-slate-950 shadow-[0_14px_30px_rgba(96,165,250,0.22)]">
                        CS
                    </div>
                    <div>
                        <p class="m-0 text-[1rem] font-bold">Clothes Shop</p>
                        <p class="m-0 mt-1 text-sm text-white/55">Admin console</p>
                    </div>
                </div>

                <div class="mx-1 mb-4 rounded-[20px] border border-white/10 bg-white/5 p-4 backdrop-blur-md">
                    <p class="m-0 text-[0.72rem] uppercase tracking-[0.12em] text-white/50">Store status</p>
                    <div class="mt-2 flex items-center gap-2 font-semibold">
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_0_6px_rgba(52,211,153,0.12)]"></span>
                        <span>Operational</span>
                    </div>
                    <p class="m-0 mt-2 text-sm leading-6 text-white/65">
                        Inventory, orders, and promotions are ready for the day.
                    </p>
                </div>

                <div class="flex-1 space-y-4 pr-1">
                    <div v-for="group in navigationGroups" :key="group.title" class="space-y-2">
                        <p class="m-0 px-3 text-[0.72rem] uppercase tracking-[0.12em] text-white/50">
                            {{ group.title }}
                        </p>

                        <nav class="space-y-1">
                            <NuxtLink v-for="item in group.items" :key="item.index" :to="item.index"
                                class="flex h-12 w-full items-center gap-3 rounded-2xl px-3 text-left text-sm text-white/75 transition hover:bg-white/10 hover:text-white">
                                <el-icon class="text-base">
                                    <component :is="item.icon" />
                                </el-icon>
                                <span class="flex-1">{{ item.label }}</span>
                                <span v-if="item.badge"
                                    class="rounded-full bg-white/10 px-2.5 py-1 text-[0.7rem] font-semibold tracking-wide text-white">
                                    {{ item.badge }}
                                </span>
                            </NuxtLink>
                        </nav>
                    </div>
                </div>

                <div class="pt-4">
                    <div class="mx-1 rounded-[20px] border border-white/10 bg-white/[0.08] p-4">
                        <p class="m-0 text-[0.72rem] uppercase tracking-[0.12em] text-white/50">This week</p>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <strong class="block text-[1.1rem]">128</strong>
                                <span class="text-sm text-white/55">orders</span>
                            </div>
                            <div>
                                <strong class="block text-[1.1rem]">24</strong>
                                <span class="text-sm text-white/55">low stock</span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-20 flex flex-col gap-5 border-b border-slate-200/70 bg-[rgba(244,246,251,0.92)] px-2 py-5 backdrop-blur-md sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-7">
                    <div class="flex items-center gap-3">
                        <el-input class="admin-search-input !w-full sm:!w-[360px]"
                            placeholder="Search products, orders, customers">
                            <template #prefix>
                                <el-icon>
                                    <Search />
                                </el-icon>
                            </template>
                        </el-input>
                    </div>

                    <section class="flex items-center gap-3">
                        <el-button circle
                            class="!h-11 !w-11 !rounded-[14px] !border !border-slate-200 !bg-white !shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
                            <el-icon>
                                <Bell />
                            </el-icon>
                        </el-button>

                        <div
                            class="flex items-center gap-3 rounded-[18px] border border-slate-200 bg-white px-3 py-2 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
                            <el-avatar :size="34">AD</el-avatar>
                            <div>
                                <strong class="block text-sm text-slate-950">Admin</strong>
                                <!-- <span class="block text-xs text-slate-500">Super user</span> -->
                            </div>
                        </div>

                        <button v-if="isAuthenticated" type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-[0_10px_30px_rgba(15,23,42,0.06)] transition hover:border-slate-300 hover:text-slate-950"
                            @click="handleLogout">
                            Logout
                        </button>
                    </section>
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

.admin-search-input {
    overflow: hidden;
}

.admin-search-input :deep(.el-input__wrapper) {
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    padding: 12px;
}

.admin-search-input :deep(.el-input__wrapper.is-focus) {
    box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.5), 0 10px 30px rgba(15, 23, 42, 0.08);
}
</style>
