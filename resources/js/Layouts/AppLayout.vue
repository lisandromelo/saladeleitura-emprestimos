<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandTheme from '../Components/BrandTheme.vue';
import Logo from '../Components/Logo.vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});
const user = computed(() => page.props.auth?.user);
const branding = computed(() => page.props.branding || {});
const isAdmin = computed(() => !!user.value?.is_admin);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <BrandTheme>
        <div class="min-h-screen">
            <header class="border-b border-brand/15 bg-white/95 shadow-[0_1px_0_0_color-mix(in_srgb,var(--brand-primary)_18%,transparent),0_8px_24px_-16px_rgba(0,0,0,0.18)] backdrop-blur">
                <div class="border-b border-brand/10 bg-gradient-to-r from-paper via-white to-paper">
                    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                        <Link href="/" class="flex min-w-0 items-center gap-4" :aria-label="branding.name">
                            <Logo class-name="h-20 w-auto shrink-0 drop-shadow-sm sm:h-24" />
                            <span class="min-w-0">
                                <span class="block truncate text-2xl font-semibold tracking-wide text-brand sm:text-3xl">{{ branding.name }}</span>
                                <span v-if="branding.tagline" class="mt-1 block truncate text-sm text-muted">{{ branding.tagline }}</span>
                            </span>
                        </Link>
                        <div class="flex shrink-0 items-center gap-3 text-sm">
                            <span class="hidden rounded-md bg-brand/8 px-2.5 py-1 font-medium text-brand sm:inline">{{ user?.name }}</span>
                            <button
                                type="button"
                                class="rounded-md border border-stone-200 bg-white px-3 py-1.5 text-stone-600 transition hover:border-brand/30 hover:text-brand"
                                @click="logout"
                            >
                                Sair
                            </button>
                        </div>
                    </div>
                </div>
                <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-1 gap-y-1 px-4 py-2 text-sm sm:px-5">
                    <Link href="/" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Início</Link>
                    <Link href="/livros" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Acervo</Link>
                    <Link href="/associados" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Associados</Link>
                    <Link href="/emprestimos" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Empréstimos</Link>
                    <Link href="/emprestimos/novo" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Novo empréstimo</Link>
                    <template v-if="isAdmin">
                        <Link href="/atrasos" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Atrasos</Link>
                        <Link href="/balancos" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Balanço</Link>
                        <Link href="/livros/capas/atualizar" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Atualizar capas</Link>
                        <Link href="/cadastros/areas" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Áreas</Link>
                        <Link href="/cadastros/classificacoes" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Classificações</Link>
                        <Link href="/usuarios" class="rounded-md px-2.5 py-1.5 text-ink/80 transition hover:bg-brand/8 hover:text-brand">Usuários</Link>
                    </template>
                </nav>
                <div class="h-0.5 bg-gradient-to-r from-brand via-brand/70 to-brand/20" aria-hidden="true" />
            </header>

            <div v-if="flash.success" class="mx-auto mt-4 max-w-6xl px-6">
                <p class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-ok">{{ flash.success }}</p>
            </div>
            <div v-if="flash.error" class="mx-auto mt-4 max-w-6xl px-6">
                <p class="rounded-md bg-rose-50 px-3 py-2 text-sm text-warn">{{ flash.error }}</p>
            </div>
            <div v-if="flash.warning" class="mx-auto mt-4 max-w-6xl px-6">
                <p class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ flash.warning }}</p>
            </div>

            <main class="mx-auto max-w-6xl px-6 py-8">
                <slot />
            </main>
        </div>
    </BrandTheme>
</template>
