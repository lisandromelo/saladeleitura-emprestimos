<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    classificacoes: Array,
});

const form = useForm({ nome: '' });

function store() {
    form.post('/cadastros/classificacoes', { onSuccess: () => form.reset() });
}

function update(item, event) {
    const row = event.target.closest('form');
    const nome = row.querySelector('[name=nome]').value;
    const ativo = row.querySelector('[name=ativo]').checked;
    router.put(`/cadastros/classificacoes/${item.id}`, { nome, ativo });
}

function excluir(item) {
    if (!item.pode_excluir) {
        return;
    }
    if (!confirm(`Excluir a classificação "${item.nome}"?`)) {
        return;
    }
    router.delete(`/cadastros/classificacoes/${item.id}`);
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl text-brand">Classificações</h1><Link href="/" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50"><span aria-hidden="true">←</span> Voltar</Link></div>

        <form class="mb-6 flex gap-2" @submit.prevent="store">
            <input v-model="form.nome" class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Nova classificação" required>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Adicionar</button>
        </form>

        <ul class="space-y-3">
            <li v-for="item in classificacoes" :key="item.id">
                <form class="flex flex-wrap items-center gap-2 rounded-lg border border-stone-200 bg-white/70 p-3" @submit.prevent="update(item, $event)">
                    <input name="nome" :value="item.nome" class="rounded-md border border-stone-300 bg-white px-2 py-1 text-sm">
                    <label class="flex items-center gap-1 text-sm">
                        <input name="ativo" type="checkbox" :checked="item.ativo">
                        Ativo
                    </label>
                    <button class="rounded-md border border-stone-200 bg-white px-2 py-1 text-xs">Salvar</button>
                    <button
                        v-if="item.pode_excluir"
                        type="button"
                        class="rounded-md border border-rose-200 bg-white px-2 py-1 text-xs text-warn"
                        @click="excluir(item)"
                    >
                        Excluir
                    </button>
                    <span v-else class="text-xs text-muted">Em uso ({{ item.livros_count }})</span>
                </form>
            </li>
        </ul>
    </AppLayout>
</template>
