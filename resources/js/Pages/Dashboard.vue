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
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-brand">{{ branding.name }}</h1>
            <p class="mt-1 text-muted">Painel operacional do acervo</p>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            <Link
                href="/livros"
                class="rounded-lg border border-stone-200 bg-white/70 p-4 transition hover:border-brand hover:bg-brand/5"
                title="Ver acervo ativo (sem baixados)"
            >
                <p class="text-xs uppercase tracking-wide text-muted">Ativos</p>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ stats.livros_ativos }}</p>
                <p class="mt-1 text-xs text-muted">Clique para ver o acervo</p>
            </Link>
            <Link
                href="/livros?situacao=DISPONIVEL"
                class="rounded-lg border border-stone-200 bg-white/70 p-4 transition hover:border-brand hover:bg-brand/5"
                title="Ver livros disponíveis"
            >
                <p class="text-xs uppercase tracking-wide text-muted">Disponíveis</p>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ stats.disponiveis }}</p>
                <p class="mt-1 text-xs text-muted">Clique para ver disponíveis</p>
            </Link>
            <Link
                href="/emprestimos"
                class="rounded-lg border border-stone-200 bg-white/70 p-4 transition hover:border-brand hover:bg-brand/5"
                title="Ver empréstimos em aberto e registrar devoluções"
            >
                <p class="text-xs uppercase tracking-wide text-muted">Emprestados</p>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ stats.emprestados }}</p>
                <p class="mt-1 text-xs text-muted">Clique para devolver</p>
            </Link>
        </div>

        <div class="flex flex-wrap gap-3">
            <Link href="/emprestimos/novo" class="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-dark">Novo empréstimo</Link>
            <Link href="/livros" class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm hover:bg-stone-50">Pesquisar acervo</Link>
            <Link href="/associados" class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm hover:bg-stone-50">Associados</Link>
            <Link href="/emprestimos" class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm hover:bg-stone-50">Devoluções</Link>
        </div>
    </AppLayout>
</template>
