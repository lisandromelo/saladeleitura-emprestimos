<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    associado: Object,
    emprestimos: Array,
    comunicacoes: Array,
    canSeeComunicacoes: Boolean,
});

const form = useForm({
    nome: props.associado.nome,
    telefone: props.associado.telefone || '',
    area_id: props.associado.area_id || '',
    observacao: props.associado.observacao || '',
    ativo: props.associado.ativo,
});

function save() {
    form.put(`/associados/${props.associado.id}`);
}

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">{{ associado.nome }}</h1>

        <form class="mb-8 grid max-w-xl gap-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="save">
            <label class="text-sm">Nome<input v-model="form.nome" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="text-sm">Telefone<input v-model="form.telefone" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2"></label>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.ativo" type="checkbox"> Ativo</label>
            <label class="text-sm">Observação<textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" /></label>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Salvar</button>
        </form>

        <h2 class="mb-3 text-lg font-semibold">Histórico de empréstimos</h2>
        <div class="mb-8 overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Livro</th>
                        <th class="px-3 py-2">Retirada</th>
                        <th class="px-3 py-2">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in emprestimos" :key="e.id" class="border-t border-stone-100">
                        <td class="px-3 py-2">{{ e.livro?.titulo }} ({{ e.livro?.codigo }})</td>
                        <td class="px-3 py-2">{{ fmt(e.data_retirada) }}</td>
                        <td class="px-3 py-2">
                            <template v-if="e.cancelado_em">
                                Cancelado em {{ fmt(e.cancelado_em) }}
                                <span v-if="e.motivo_cancelamento" class="block text-xs text-muted">{{ e.motivo_cancelamento }}</span>
                            </template>
                            <template v-else-if="e.data_devolucao">{{ fmt(e.data_devolucao) }}</template>
                            <template v-else>Em aberto</template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="canSeeComunicacoes">
            <h2 class="mb-3 text-lg font-semibold">Comunicações</h2>
            <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Data</th>
                            <th class="px-3 py-2">Meio</th>
                            <th class="px-3 py-2">Assunto</th>
                            <th class="px-3 py-2">Operador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in comunicacoes" :key="c.id" class="border-t border-stone-100">
                            <td class="px-3 py-2">{{ fmt(c.data_hora) }}</td>
                            <td class="px-3 py-2">{{ c.meio }}</td>
                            <td class="px-3 py-2">{{ c.assunto }}</td>
                            <td class="px-3 py-2">{{ c.usuario?.name || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
