<template>
    <footer class="border-t border-slate-200 bg-white">
        <section class="px-4 pt-8 sm:px-5 sm:pt-10 desktop:container">
            <div
                class="rounded-3xl border border-slate-200 bg-slate-900 px-5 py-6 text-white shadow-sm sm:px-6 sm:py-8 lg:flex lg:items-center lg:justify-between lg:gap-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                        Newsletter
                    </p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight sm:text-3xl">
                        Stay up to date with our latest offers.
                    </h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-white/70 sm:text-base">
                        Get product drops, updates, and special promotions delivered straight to your inbox.
                    </p>
                </div>

                <form class="mt-6 flex w-full max-w-xl flex-col gap-3 lg:mt-0 lg:min-w-[360px]"
                    @submit.prevent="subscribeNewsLetter">
                    <label class="sr-only" for="footer-newsletter-email">
                        Email address
                    </label>
                    <div class="relative">
                        <Icon name="mdi:email"
                            class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-white/50" />
                        <input id="footer-newsletter-email" ref="email_sub_ref" v-model.trim="email_subscribe"
                            type="email" autocomplete="email" inputmode="email" placeholder="Enter your email"
                            class="w-full rounded-full border border-white/10 bg-white/5 py-3 pl-12 pr-4 text-sm text-white outline-none transition focus:border-white/30 focus:bg-white/10 sm:py-3.5 sm:text-base" />
                    </div>
                    <el-button native-type="submit" :loading="isLoading"
                        class="!h-12 !rounded-full !border-0 !bg-white !px-6 !text-sm !font-semibold !text-slate-900 hover:!bg-slate-100 sm:!h-12 sm:!px-8">
                        Subscribe
                    </el-button>
                </form>
            </div>
        </section>

        <section class="px-4 py-10 sm:px-5 sm:py-12 desktop:container">
            <div class="grid gap-10 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <NuxtLink to="/" class="inline-flex items-center gap-2">
                        <span class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">
                            {{ siteName }}
                        </span>
                    </NuxtLink>
                    <p class="mt-4 max-w-md text-sm leading-6 text-slate-600 sm:text-base">
                        We create clothes that fit your style, feel comfortable, and are easy to wear every day.
                    </p>

                    <ul class="mt-6 flex flex-wrap gap-2">
                        <li v-for="social in socialLinks" :key="social.label">
                            <a :href="social.href" :aria-label="social.label" target="_blank" rel="noreferrer"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-950">
                                <Icon :name="social.icon" class="text-lg" />
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:col-span-8 lg:grid-cols-3">
                    <nav v-for="section in footerSections" :key="section.title" class="space-y-4">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-950">
                            {{ section.title }}
                        </h3>
                        <ul class="space-y-3">
                            <li v-for="item in section.items" :key="item.label">
                                <NuxtLink v-if="item.to" :to="item.to"
                                    class="text-sm text-slate-600 transition hover:text-slate-950">
                                    {{ item.label }}
                                </NuxtLink>
                                <a v-else :href="item.href || '#'"
                                    class="text-sm text-slate-600 transition hover:text-slate-950">
                                    {{ item.label }}
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">
                    {{ siteName }} © {{ currentYear }}. All rights reserved.
                </p>

                <ul class="flex flex-wrap gap-2">
                    <li v-for="payment in paymentMethods" :key="payment.label"
                        class="flex h-10 w-14 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 text-slate-700 shadow-sm">
                        <Icon :name="payment.icon" class="text-xl" />
                    </li>
                </ul>
            </div>
        </section>
    </footer>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { useAppSetting } from '~/composables/useAppSetting'

type FooterItem = {
    label: string
    to?: string
    href?: string
}

type FooterSection = {
    title: string
    items: FooterItem[]
}

const { appSetting } = useAppSetting()
const config = useRuntimeConfig()
const apiBase = (config.public.apiBase || '').replace(/\/$/, '')

const email_subscribe = ref('')
const isLoading = ref(false)
const email_sub_ref = ref<HTMLInputElement | null>(null)

const siteName = computed(() => appSetting.value?.app_name || config.public.appName || 'Shop.co')
const currentYear = ref('')

const footerSections: FooterSection[] = [
    {
        title: 'Shop',
        items: [
            { label: 'Browse Categories', to: '/frontend/categories' },
            { label: 'Favorites', to: '/frontend/favorites' },
            { label: 'Cart', to: '/frontend/cart' },
        ],
    },
    {
        title: 'Account',
        items: [
            { label: 'Login', to: '/auth/login' },
            { label: 'Sign Up', to: '/auth/signup' },
            { label: 'Need help?', href: 'mailto:support@shop.co?subject=Order%20help' },
        ],
    },
    {
        title: 'Support',
        items: [
            { label: 'Customer Support', href: 'mailto:support@shop.co' },
            { label: 'Delivery Details', href: 'mailto:support@shop.co?subject=Delivery%20details' },
            { label: 'Terms & Conditions', href: 'mailto:support@shop.co?subject=Terms%20and%20Conditions' },
            { label: 'Privacy Policy', href: 'mailto:support@shop.co?subject=Privacy%20Policy' },
        ],
    },
]

const socialLinks = [
    { label: 'Twitter', icon: 'mdi:twitter', href: 'https://twitter.com' },
    { label: 'Facebook', icon: 'mdi:facebook', href: 'https://facebook.com' },
    { label: 'Instagram', icon: 'mdi:instagram', href: 'https://instagram.com' },
    { label: 'GitHub', icon: 'mdi:github', href: 'https://github.com' },
]

const paymentMethods = [
    { label: 'Visa', icon: 'logos:visa' },
    { label: 'Mastercard', icon: 'logos:mastercard' },
    { label: 'PayPal', icon: 'logos:paypal' },
    { label: 'Google Pay', icon: 'logos:google-pay' },
]

const subscribeNewsLetter = async () => {
    const email = email_subscribe.value.trim()

    if (!email) {
        ElMessage({ message: 'Please enter your email address.', type: 'warning' })
        email_sub_ref.value?.focus()
        return
    }

    isLoading.value = true
    try {
        await $fetch(`${apiBase}/newsletters/subscribe`, {
            method: 'POST',
            credentials: 'include',
            body: { email },
        })

        ElMessage({ message: 'Subscribed to newsletter!', type: 'success' })
        email_subscribe.value = ''
    } catch (error: any) {
        ElMessage({ message: error?.data?.message || 'Failed to subscribe.', type: 'error' })
        await nextTick()
        email_sub_ref.value?.focus()
    } finally {
        isLoading.value = false
    }
}
onMounted(() => {
    currentYear.value = new Date().getFullYear().toString()
})
</script>
