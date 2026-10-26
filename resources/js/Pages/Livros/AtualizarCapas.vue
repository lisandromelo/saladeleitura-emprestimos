<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { ref } from 'vue';

defineProps({
    stats: Object,
    ultimo_resultado: Object,
});

const form = useForm({
    quantidade: 10,
});

const escolha = useForm({
    url: '',
});
const selecionadas = ref({});
const capaAmpliada = ref(null);

function processar() {
    form.post('/livros/capas/atualizar');
}

function limparFalhas() {
    if (!confirm('Reabrir a fila para livros que já tiveram busca sem sucesso?')) {
        return;
    }
    router.post('/livros/capas/limpar-falhas');
}

function selecionarCapa(livroId, opcao) {
    selecionadas.value[livroId] = opcao;
}

function confirmarCapa(livroId) {
    const opcao = selecionadas.value[livroId];
    if (!opcao) return;
    if (!confirm('Confirmar esta capa para o livro selecionado?')) return;
    escolha.url = opcao.url || opcao.thumbnail;
    escolha.post('/livros/' + livroId + '/capa', {
        preserveScroll: true,
        onSuccess: () => {
            const atualizadas = { ...selecionadas.value };
            delete atualizadas[livroId];
            selecionadas.value = atualizadas;
        },
    });
}

function descartar(livroId) {
    router.post(`/livros/${livroId}/capa/descartar`, {}, { preserveScroll: true });
}

function rotuloStatus(status) {
    return {
        encontrada: 'Capa encontrada',
        nao_encontrada: 'Sem capa',
        erro: 'Erro',
        quota: 'Quota da API',
        escolher: 'Escolher capa',
    }[status] || status;
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl text-brand">Atualizar capas</h1>
                <p class="mt-1 text-sm text-muted">
                    Busca automática na internet para livros sem capa. Somente administrador.
                </p>
            </div>
            <Link href="/livros" class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm">Voltar ao acervo</Link>
        </div>

        <div
            v-if="ultimo_resultado?.quota_excedida"
            class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
        >
            {{ ultimo_resultado.quota_mensagem || 'Quota/limite da API excedido. A fila foi interrompida e os livros restantes continuam pendentes.' }}
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-stone-200 bg-white/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted">Com capa</p>
                <p class="mt-1 text-2xl font-semibold">{{ stats.com_capa }}</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted">Sem capa</p>
                <p class="mt-1 text-2xl font-semibold">{{ stats.sem_capa }}</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted">Pendentes de busca</p>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ stats.pendentes_busca }}</p>
            </div>
            <div class="rounded-lg border border-stone-200 bg-white/70 p-4">
                <p class="text-xs uppercase tracking-wide text-muted">Busca sem sucesso</p>
                <p class="mt-1 text-2xl font-semibold">{{ stats.busca_sem_sucesso }}</p>
            </div>
        </div>

        <form class="mb-8 max-w-md space-y-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="processar">
            <label class="block text-sm">
                Quantidade de livros a processar nesta execução
                <input
                    v-model.number="form.quantidade"
                    type="number"
                    min="1"
                    max="50"
                    class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2"
                    required
                >
            </label>
            <p class="text-xs text-muted">
                Consulta Google Books, Open Library e, como terceira fonte, o Internet Archive.
                Com uma só capa, ela é aplicada. Com mais de uma, o operador escolhe.
            </p>
            <div class="flex flex-wrap gap-2">
                <button class="rounded-md bg-brand px-4 py-2 text-sm text-white" :disabled="form.processing || stats.pendentes_busca === 0">
                    {{ form.processing ? 'Atualizando…' : 'Atualizar capas' }}
                </button>
                <button
                    type="button"
                    class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm"
                    :disabled="stats.busca_sem_sucesso === 0"
                    @click="limparFalhas"
                >
                    Reabrir fila sem sucesso
                </button>
            </div>
        </form>

        <div v-if="ultimo_resultado?.detalhes?.some((item) => item.status === 'escolher')" class="mb-8 space-y-4">
            <h2 class="text-lg font-semibold">Escolha a capa</h2>
            <article
                v-for="item in ultimo_resultado.detalhes.filter((entrada) => entrada.status === 'escolher')"
                :key="item.id"
                class="rounded-lg border border-stone-200 bg-white/70 p-4"
            >
                <p class="font-medium text-ink">{{ item.titulo }} <span class="text-sm text-muted">({{ item.codigo }})</span></p>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="(opcao, idx) in item.opcoes"
                        :key="`${item.id}-${idx}`"
                        class="rounded-lg border bg-white p-2 text-left transition"
                        :class="selecionadas[item.id]?.url === opcao.url ? 'border-emerald-700 ring-2 ring-emerald-700/30' : 'border-stone-200'"
                    >
                        <button type="button" class="mb-2 block w-full cursor-zoom-in rounded bg-stone-50 p-1" aria-label="Ampliar capa" @click="capaAmpliada = opcao">
                            <img :src="opcao.thumbnail" :alt="opcao.titulo" class="h-40 w-full object-contain">
                            <span class="mt-1 block text-xs font-semibold text-emerald-800">Ampliar capa</span>
                        </button>
                        <p class="line-clamp-2 text-sm font-semibold text-stone-900">{{ opcao.titulo }}</p>
                        <p class="line-clamp-1 text-xs text-muted">{{ opcao.fonte }}</p>
                        <button type="button" class="mt-2 w-full rounded-md px-3 py-2 text-sm font-semibold" :class="selecionadas[item.id]?.url === opcao.url ? 'bg-emerald-800 text-white' : 'border border-stone-300 bg-white text-stone-800 hover:bg-emerald-50'" :disabled="escolha.processing" @click="selecionarCapa(item.id, opcao)">
                            {{ selecionadas[item.id]?.url === opcao.url ? '✓ Escolhida' : 'Escolher esta' }}
                        </button>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 pt-3">
                    <button type="button" class="text-sm font-medium text-rose-700 hover:underline" @click="descartar(item.id)">Nenhuma destas</button>
                    <button type="button" class="rounded-md bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!selecionadas[item.id] || escolha.processing" @click="confirmarCapa(item.id)">
                        {{ escolha.processing ? 'Aplicando…' : 'Confirmar escolha' }}
                    </button>
                </div>
            </article>
        </div>

        <div v-if="ultimo_resultado?.detalhes?.length">
            <h2 class="mb-3 text-lg font-semibold">Última execução</h2>
            <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Código</th>
                            <th class="px-3 py-2">Título</th>
                            <th class="px-3 py-2">Resultado</th>
                            <th class="px-3 py-2">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in ultimo_resultado.detalhes" :key="item.id" class="border-t border-stone-100">
                            <td class="px-3 py-2">
                                <Link :href="`/livros/${item.id}`" class="text-brand hover:underline">{{ item.codigo }}</Link>
                            </td>
                            <td class="px-3 py-2">{{ item.titulo }}</td>
                            <td class="px-3 py-2">{{ rotuloStatus(item.status) }}</td>
                            <td class="px-3 py-2 text-muted">{{ item.detalhe || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="capaAmpliada" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="Capa ampliada" @click.self="capaAmpliada = null" @keydown.esc="capaAmpliada = null">
            <div class="relative flex max-h-[96vh] max-w-[95vw] flex-col items-center rounded-xl bg-white p-4 shadow-2xl">
                <button type="button" class="absolute right-2 top-2 z-10 rounded-full bg-stone-900 px-3 py-1 text-lg font-bold text-white hover:bg-stone-700" aria-label="Fechar imagem ampliada" @click="capaAmpliada = null">×</button>
                <p class="mb-3 max-w-[80vw] pr-8 text-center text-base font-semibold text-stone-900">{{ capaAmpliada.titulo }}</p>
                <img :src="capaAmpliada.thumbnail" :alt="capaAmpliada.titulo" class="max-h-[85vh] max-w-full object-contain">
                <p class="mt-2 text-sm text-stone-600">{{ capaAmpliada.autores }} · {{ capaAmpliada.fonte }}</p>
            </div>
        </div>
    </AppLayout>
</template>
