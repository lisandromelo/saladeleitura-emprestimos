<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BrandTheme from '../Components/BrandTheme.vue';
import Logo from '../Components/Logo.vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
const user = computed(() => page.props.auth?.user);
const branding = computed(() => page.props.branding || {});
const isAdmin = computed(() => !!user.value?.is_admin);
const sidebarOpen = ref(true);
const mobileMenuOpen = ref(false);
const currentUrl = computed(() => page.url.split('?')[0]);

const mainLinks = [
    { href: '/', label: 'Início', exact: true, icon: '⌂' },
    { href: '/livros', label: 'Acervo', icon: '▤' },
    { href: '/associados', label: 'Associados', icon: '♙' },
    { href: '/emprestimos', label: 'Empréstimos', icon: '↔' },
    { href: '/emprestimos/novo', label: 'Novo empréstimo', exact: true, icon: '+' },
];
const adminLinks = [
    { href: '/atrasos', label: 'Atrasos' },
    { href: '/balancos', label: 'Balanço' },
    { href: '/livros/capas/atualizar', label: 'Atualizar capas' },
    { href: '/cadastros/areas', label: 'Áreas' },
    { href: '/cadastros/classificacoes', label: 'Classificações' },
    { href: '/usuarios', label: 'Usuários' },
];
function isActive(link) {
    if (link.exact) return currentUrl.value === link.href;
    return currentUrl.value === link.href || currentUrl.value.startsWith(link.href + '/');
}
function logout() {
    router.post('/logout');
}
</script>

<template>
    <BrandTheme>
        <div class="min-h-screen bg-stone-50 text-stone-900">
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 bg-stone-950/40 lg:hidden" aria-hidden="true" @click="mobileMenuOpen = false" />
            <aside
                class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-brand/15 bg-white shadow-xl transition-all duration-200 lg:translate-x-0"
                :class="[mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', sidebarOpen ? 'w-64' : 'lg:w-[4.5rem] w-64']"
            >
                <div class="flex min-h-20 items-center gap-3 border-b border-brand/10 px-4 py-4">
                    <Link href="/" class="flex min-w-0 flex-1 items-center gap-3" :aria-label="branding.name" @click="mobileMenuOpen = false">
                        <Logo class-name="h-10 w-auto shrink-0" />
                        <span v-if="sidebarOpen" class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-brand">{{ branding.name }}</span>
                            <span v-if="branding.tagline" class="mt-0.5 block truncate text-xs text-muted">{{ branding.tagline }}</span>
                        </span>
                    </Link>
                    <button type="button" class="rounded-md p-1.5 text-muted hover:bg-brand/8 hover:text-brand" :aria-label="sidebarOpen ? 'Recolher menu' : 'Expandir menu'" @click="sidebarOpen = !sidebarOpen">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-base" aria-label="Navegação principal">
                    <p v-if="sidebarOpen" class="px-3 pb-2 text-sm font-bold uppercase tracking-wider text-stone-700">Menu principal</p>
                    <Link v-for="link in mainLinks" :key="link.href" :href="link.href" :title="!sidebarOpen ? link.label : undefined"
                        class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 transition"
                        :class="isActive(link) ? 'bg-brand/10 font-semibold text-brand ring-1 ring-inset ring-brand/15' : 'text-stone-800 hover:bg-emerald-50 hover:text-emerald-900'"
                        :aria-current="isActive(link) ? 'page' : undefined" @click="mobileMenuOpen = false">
                        <span class="w-5 shrink-0 text-center" aria-hidden="true">{{ link.icon }}</span>
                        <span v-if="sidebarOpen" class="truncate">{{ link.label }}</span>
                    </Link>
                    <template v-if="isAdmin">
                        <p v-if="sidebarOpen" class="px-3 pb-2 pt-6 text-sm font-bold uppercase tracking-wider text-stone-700">Administração</p>
                        <Link v-for="link in adminLinks" :key="link.href" :href="link.href" :title="!sidebarOpen ? link.label : undefined"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition"
                            :class="isActive(link) ? 'bg-brand/10 font-semibold text-brand ring-1 ring-inset ring-brand/15' : 'text-ink/80 hover:bg-brand/5 hover:text-brand'"
                            :aria-current="isActive(link) ? 'page' : undefined" @click="mobileMenuOpen = false">
                            <span class="w-5 shrink-0 text-center" aria-hidden="true">⚙</span>
                            <span v-if="sidebarOpen" class="truncate">{{ link.label }}</span>
                        </Link>
                    </template>
                </nav>
                <div class="border-t border-brand/10 p-3">
                    <div v-if="sidebarOpen" class="truncate px-3 pb-2 text-sm text-stone-700">{{ user?.name }}</div>
                    <button type="button" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-stone-600 transition hover:bg-rose-50 hover:text-rose-700" :title="!sidebarOpen ? 'Sair' : undefined" @click="logout">
                        <span class="w-5 shrink-0 text-center" aria-hidden="true">↪</span><span v-if="sidebarOpen">Sair</span>
                    </button>
                </div>
            </aside>
            <div class="min-h-screen transition-[padding] duration-200" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-[4.5rem]'">
                <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-brand/15 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6">
                    <button type="button" class="rounded-md p-2 text-brand hover:bg-brand/8 lg:hidden" aria-label="Abrir menu" @click="mobileMenuOpen = true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-brand">{{ branding.name }}</p>
                        <p class="hidden truncate text-sm text-stone-700 sm:block">{{ mainLinks.find(link => isActive(link))?.label || adminLinks.find(link => isActive(link))?.label || '' }}</p>
                    </div>
                    <span class="hidden max-w-48 truncate rounded-md bg-brand/8 px-3 py-1.5 text-sm font-medium text-brand sm:block">{{ user?.name }}</span>
                    <button type="button" class="rounded-md border border-stone-200 bg-white px-3 py-1.5 text-sm text-stone-600 transition hover:border-brand/30 hover:text-brand sm:hidden" @click="logout">Sair</button>
                </header>
                <div class="h-0.5 bg-gradient-to-r from-brand via-brand/70 to-brand/20" aria-hidden="true" />
                <div v-if="flash.success" class="mx-auto mt-4 max-w-6xl px-4 sm:px-6"><p class="rounded-md bg-emerald-50 px-3 py-3 text-base font-medium text-emerald-900">{{ flash.success }}</p></div>
                <div v-if="flash.error" class="mx-auto mt-4 max-w-6xl px-4 sm:px-6"><p class="rounded-md bg-rose-50 px-3 py-3 text-base font-medium text-rose-900">{{ flash.error }}</p></div>
                <div v-if="flash.warning" class="mx-auto mt-4 max-w-6xl px-4 sm:px-6"><p class="rounded-md bg-amber-50 px-3 py-3 text-base font-medium text-amber-900">{{ flash.warning }}</p></div>
                <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8"><slot /></main>
            </div>
        </div>
    </BrandTheme>
</template>
