<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

defineProps({
    stats: { type: Object, required: true },
});

const branding = computed(() => usePage().props.branding || {});
</script>

<template>
    <AppLayout>
        <section class="mb-8">
            <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-brand">Área do Livro Espírita</p>
            <h1 class="text-3xl font-bold tracking-tight text-brand sm:text-4xl">{{ branding.name }}</h1>
            <p class="mt-2 text-lg text-stone-700">O que você precisa fazer?</p>
        </section>

        <section aria-label="Tarefas principais" class="mb-8 grid gap-4 lg:grid-cols-3">
            <Link
                href="/emprestimos/novo"
                class="group flex min-h-36 items-center gap-5 rounded-xl border border-brand bg-brand px-6 py-6 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-brand-dark hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand/25"
            >
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5ZM4 5.5V22" /></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-bold sm:text-2xl">Novo empréstimo</span>
                    <span class="mt-1 block text-base text-white/90">Registrar a saída de um livro</span>
                </span>
                <span class="text-3xl transition-transform group-hover:translate-x-1" aria-hidden="true">›</span>
            </Link>

            <Link
                href="/emprestimos"
                class="group flex min-h-36 items-center gap-5 rounded-xl border border-emerald-700 bg-emerald-700 px-6 py-6 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-700/25"
            >
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11a8.5 8.5 0 0 1 14.5-6L20 7M20 7V2.5M20 7h-4.5M21 13a8.5 8.5 0 0 1-14.5 6L4 17m0 0v4.5M4 17h4.5" /></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-bold sm:text-2xl">Devolução</span>
                    <span class="mt-1 block text-base text-white/90">Registrar o retorno de um livro</span>
                </span>
                <span class="text-3xl transition-transform group-hover:translate-x-1" aria-hidden="true">›</span>
            </Link>

            <Link
                href="/livros"
                class="group flex min-h-36 items-center gap-5 rounded-xl border-2 border-stone-300 bg-white px-6 py-6 text-stone-900 shadow-sm transition hover:-translate-y-0.5 hover:border-brand hover:shadow-md focus:outline-none focus:ring-4 focus:ring-brand/20"
            >
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-brand" aria-hidden="true">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8" /><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 5 5" /></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-bold sm:text-2xl">Consultar acervo</span>
                    <span class="mt-1 block text-base text-stone-700">Pesquisar livros e disponibilidade</span>
                </span>
                <span class="text-3xl text-brand transition-transform group-hover:translate-x-1" aria-hidden="true">›</span>
            </Link>
        </section>

        <section aria-label="Resumo do acervo">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-stone-900">Resumo do acervo</h2>
                <p class="mt-1 text-base text-stone-700">Consulte a situação atual dos livros cadastrados.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <Link href="/livros" class="rounded-xl border border-stone-300 bg-white p-5 shadow-sm transition hover:border-brand hover:shadow-md" title="Consultar todos os livros ativos">
                    <p class="text-sm font-semibold uppercase tracking-wide text-stone-700">Livros no acervo</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-stone-950">{{ stats.livros_ativos }}</p>
                    <p class="mt-2 text-sm text-stone-700">Total de livros ativos cadastrados</p>
                </Link>
                <Link href="/livros?situacao=DISPONIVEL" class="rounded-xl border border-stone-300 bg-white p-5 shadow-sm transition hover:border-brand hover:shadow-md" title="Consultar livros disponíveis">
                    <p class="text-sm font-semibold uppercase tracking-wide text-stone-700">Disponíveis</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-800">{{ stats.disponiveis }}</p>
                    <p class="mt-2 text-sm text-stone-700">Prontos para empréstimo</p>
                </Link>
                <Link href="/emprestimos" class="rounded-xl border border-stone-300 bg-white p-5 shadow-sm transition hover:border-brand hover:shadow-md" title="Consultar empréstimos em aberto">
                    <p class="text-sm font-semibold uppercase tracking-wide text-stone-700">Emprestados</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-blue-900">{{ stats.emprestados }}</p>
                    <p class="mt-2 text-sm text-stone-700">Com associados neste momento</p>
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
