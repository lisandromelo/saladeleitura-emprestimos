<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    associados: Object,
    filters: Object,
    areas: Array,
});

const q = ref(props.filters.q || '');
const showForm = ref(false);

const form = useForm({
    nome: '',
    telefone: '',
    area_id: '',
    observacao: '',
    ativo: true,
});

function search() {
    router.get('/associados', { q: q.value }, { preserveState: true });
}

function submit() {
    form.post('/associados', {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">Associados</h1>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white" @click="showForm = !showForm">
                {{ showForm ? 'Fechar' : 'Novo associado' }}
            </button>
        </div>

        <form v-if="showForm" class="mb-6 grid max-w-xl gap-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="submit">
            <label class="text-sm">Nome<input v-model="form.nome" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="text-sm">Telefone<input v-model="form.telefone" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2"></label>
            <label class="text-sm">Área
                <select v-model="form.area_id" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    <option value="">—</option>
                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nome }}</option>
                </select>
            </label>
            <label class="text-sm">Observação<textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" /></label>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Salvar</button>
        </form>

        <form class="mb-4 flex gap-2" @submit.prevent="search">
            <input v-model="q" class="w-64 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Nome ou telefone">
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Buscar</button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Nome</th>
                        <th class="px-3 py-2">Telefone</th>
                        <th class="px-3 py-2">Área</th>
                        <th class="px-3 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in associados.data" :key="a.id" class="border-t border-stone-100">
                        <td class="px-3 py-2"><Link :href="`/associados/${a.id}`" class="text-brand hover:underline">{{ a.nome }}</Link></td>
                        <td class="px-3 py-2">{{ a.telefone || '—' }}</td>
                        <td class="px-3 py-2">{{ a.area?.nome || '—' }}</td>
                        <td class="px-3 py-2">{{ a.ativo ? 'Ativo' : 'Inativo' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
