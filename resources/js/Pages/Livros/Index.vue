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
const layout = ref('lista');

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
            <h1 class="text-3xl font-bold text-brand">Acervo</h1>
            <div class="flex items-center gap-1 rounded-lg border border-stone-300 bg-white p-1" role="group" aria-label="Modo de visualização">
                <button type="button" class="rounded-md px-3 py-2 text-sm font-semibold" :class="layout === 'lista' ? 'bg-emerald-800 text-white' : 'text-stone-700 hover:bg-stone-100'" :aria-pressed="layout === 'lista'" @click="layout = 'lista'">Lista</button>
                <button type="button" class="rounded-md px-3 py-2 text-sm font-semibold" :class="layout === 'blocos' ? 'bg-emerald-800 text-white' : 'text-stone-700 hover:bg-stone-100'" :aria-pressed="layout === 'blocos'" @click="layout = 'blocos'">Blocos</button>
            </div>
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

        <div v-if="layout === 'lista'" class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left text-stone-800">
                    <tr>
                        <th class="px-3 py-3">Capa</th>
                        <th class="px-3 py-3">Código</th>
                        <th class="px-3 py-3">Título</th>
                        <th class="px-3 py-3">Autor/Médium</th>
                        <th class="px-3 py-3">Espírito</th>
                        <th class="px-3 py-3">Classificação</th>
                        <th class="px-3 py-3">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="livro in livros.data" :key="livro.id" class="border-t border-stone-200 odd:bg-white even:bg-stone-50 hover:bg-emerald-50/70">
                        <td colspan="7" class="p-0">
                            <Link :href="`/livros/${livro.id}`" class="grid grid-cols-[3.5rem_4rem_minmax(12rem,2fr)_minmax(8rem,1fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_7rem] items-center text-stone-800 transition-colors hover:text-emerald-950">
                                <span class="px-3 py-2">
                                    <img v-if="livro.capa_url" :src="livro.capa_url" :alt="`Capa de ${livro.titulo}`" class="h-14 w-10 rounded object-cover shadow-sm">
                                    <span v-else class="text-xs text-stone-500">—</span>
                                </span>
                                <span class="px-3 py-2 text-brand">{{ livro.codigo }}</span>
                                <span class="px-3 py-3 text-base font-semibold leading-snug text-stone-950">{{ livro.titulo }}</span>
                                <span class="px-3 py-2">{{ livro.autor_medium || '—' }}</span>
                                <span class="px-3 py-2">{{ livro.espirito || '—' }}</span>
                                <span class="px-3 py-2">{{ livro.classificacao?.nome || '—' }}</span>
                                <span class="px-3 py-2 text-sm font-medium">{{ livro.situacao }}</span>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link v-for="livro in livros.data" :key="livro.id" :href="`/livros/${livro.id}`" class="group flex min-h-40 gap-4 rounded-xl border border-stone-300 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-600 hover:bg-emerald-50/50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                <span class="flex h-28 w-20 shrink-0 items-center justify-center overflow-hidden rounded-md bg-stone-100">
                    <img v-if="livro.capa_url" :src="livro.capa_url" :alt="`Capa de ${livro.titulo}`" class="h-full w-full object-cover">
                    <span v-else class="text-sm text-stone-500">Sem capa</span>
                </span>
                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="text-xs font-semibold uppercase tracking-wide text-stone-600">Código {{ livro.codigo }}</span>
                    <span class="mt-1 text-lg font-bold leading-snug text-stone-950 group-hover:text-emerald-900">{{ livro.titulo }}</span>
                    <span class="mt-2 text-sm text-stone-700">{{ livro.autor_medium || 'Autor não informado' }}</span>
                    <span v-if="livro.espirito" class="mt-1 text-sm text-stone-700">Espírito: {{ livro.espirito }}</span>
                    <span class="mt-auto pt-3 text-sm font-semibold text-emerald-800">{{ livro.classificacao?.nome || 'Sem classificação' }} · {{ livro.situacao }}</span>
                </span>
            </Link>
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
