<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    areas: Array,
});

const form = useForm({ nome: '' });

function store() {
    form.post('/cadastros/areas', { onSuccess: () => form.reset() });
}

function update(area, event) {
    const row = event.target.closest('form');
    const nome = row.querySelector('[name=nome]').value;
    const ativo = row.querySelector('[name=ativo]').checked;
    router.put(`/cadastros/areas/${area.id}`, { nome, ativo });
}

function excluir(area) {
    if (!area.pode_excluir) {
        return;
    }
    if (!confirm(`Excluir a área "${area.nome}"?`)) {
        return;
    }
    router.delete(`/cadastros/areas/${area.id}`);
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">Áreas</h1>

        <form class="mb-6 flex gap-2" @submit.prevent="store">
            <input v-model="form.nome" class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Nova área" required>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Adicionar</button>
        </form>

        <ul class="space-y-3">
            <li v-for="area in areas" :key="area.id">
                <form class="flex flex-wrap items-center gap-2 rounded-lg border border-stone-200 bg-white/70 p-3" @submit.prevent="update(area, $event)">
                    <input name="nome" :value="area.nome" class="rounded-md border border-stone-300 bg-white px-2 py-1 text-sm">
                    <label class="flex items-center gap-1 text-sm">
                        <input name="ativo" type="checkbox" :checked="area.ativo">
                        Ativo
                    </label>
                    <button class="rounded-md border border-stone-200 bg-white px-2 py-1 text-xs">Salvar</button>
                    <button
                        v-if="area.pode_excluir"
                        type="button"
                        class="rounded-md border border-rose-200 bg-white px-2 py-1 text-xs text-warn"
                        @click="excluir(area)"
                    >
                        Excluir
                    </button>
                    <span v-else class="text-xs text-muted">Em uso ({{ area.associados_count }})</span>
                </form>
            </li>
        </ul>
    </AppLayout>
</template>
