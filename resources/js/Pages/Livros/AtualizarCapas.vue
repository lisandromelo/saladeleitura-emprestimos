<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    stats: Object,
    ultimo_resultado: Object,
});

const form = useForm({
    quantidade: 10,
});

function processar() {
    form.post('/livros/capas/atualizar');
}

function limparFalhas() {
    if (!confirm('Reabrir a fila para livros que já tiveram busca sem sucesso?')) {
        return;
    }
    router.post('/livros/capas/limpar-falhas');
}

function rotuloStatus(status) {
    return {
        encontrada: 'Capa encontrada',
        nao_encontrada: 'Sem capa',
        erro: 'Erro',
        quota: 'Quota da API',
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
                Processa apenas livros sem capa e sem marcação de busca malsucedida (máx. 50 por vez).
                Pode demorar alguns minutos.
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
    </AppLayout>
</template>
