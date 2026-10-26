<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    livros: Object,
    filters: Object,
    canManage: Boolean,
});

const q = ref(props.filters.q || '');
const situacao = ref(props.filters.situacao || '');
const incluirBaixados = ref(!!props.filters.incluir_baixados);

function search() {
    router.get('/livros', {
        q: q.value,
        situacao: situacao.value || undefined,
        incluir_baixados: incluirBaixados.value ? 1 : undefined,
    }, { preserveState: true });
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">Acervo</h1>
            <Link v-if="canManage" href="/livros/criar" class="rounded-md bg-brand px-3 py-2 text-sm text-white">Novo livro</Link>
        </div>

        <form class="mb-6 flex flex-wrap items-end gap-3" @submit.prevent="search">
            <label class="text-sm">
                Pesquisa
                <input v-model="q" class="mt-1 block w-64 rounded-md border border-stone-300 bg-white px-3 py-2" placeholder="Código, título, autor…">
            </label>
            <label class="text-sm">
                Situação
                <select v-model="situacao" class="mt-1 block rounded-md border border-stone-300 bg-white px-3 py-2">
                    <option value="">Todas (ativos)</option>
                    <option value="DISPONIVEL">Disponível</option>
                    <option value="EMPRESTADO">Emprestado</option>
                    <option value="BAIXADO">Baixado</option>
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="incluirBaixados" type="checkbox">
                Incluir baixados
            </label>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Buscar</button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Capa</th>
                        <th class="px-3 py-2">Código</th>
                        <th class="px-3 py-2">Título</th>
                        <th class="px-3 py-2">Autor/Médium</th>
                        <th class="px-3 py-2">Espírito</th>
                        <th class="px-3 py-2">Classificação</th>
                        <th class="px-3 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="livro in livros.data" :key="livro.id" class="border-t border-stone-200 odd:bg-white even:bg-stone-50 hover:bg-emerald-50/60">
                        <td class="px-3 py-2">
                            <img
                                v-if="livro.capa_url"
                                :src="livro.capa_url"
                                :alt="livro.titulo"
                                class="h-12 w-9 rounded object-cover"
                            >
                            <span v-else class="text-xs text-muted">—</span>
                        </td>
                        <td class="px-3 py-2">
                            <Link :href="`/livros/${livro.id}`" class="text-brand hover:underline">{{ livro.codigo }}</Link>
                        </td>
                        <td class="px-3 py-2">{{ livro.titulo }}</td>
                        <td class="px-3 py-2">{{ livro.autor_medium || '—' }}</td>
                        <td class="px-3 py-2">{{ livro.espirito || '—' }}</td>
                        <td class="px-3 py-2">{{ livro.classificacao?.nome || '—' }}</td>
                        <td class="px-3 py-2">{{ livro.situacao }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="livros.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
            <Link
                v-for="link in livros.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded border border-stone-200 px-2 py-1 text-xs"
                :class="{ 'bg-brand text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
                v-html="link.label"
            />
        </div>
    </AppLayout>
</template>
