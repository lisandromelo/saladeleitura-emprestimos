<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    dias: Number,
    emprestimos: Array,
});

const dias = ref(props.dias);

function filtrar() {
    router.get('/atrasos', { dias: dias.value }, { preserveState: true });
}

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">Empréstimos em aberto / atrasados</h1>

        <form class="mb-6 flex flex-wrap items-end gap-3" @submit.prevent="filtrar">
            <label class="text-sm">Mostrar empréstimos em aberto há mais de
                <input v-model.number="dias" type="number" min="0" class="ml-2 w-20 rounded-md border border-stone-300 bg-white px-2 py-1"> dias
            </label>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Filtrar</button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Associado</th>
                        <th class="px-3 py-2">Telefone</th>
                        <th class="px-3 py-2">Livro</th>
                        <th class="px-3 py-2">Retirada</th>
                        <th class="px-3 py-2">Dias</th>
                        <th class="px-3 py-2">Última comunicação</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in emprestimos" :key="e.id" class="border-t border-stone-200 odd:bg-white even:bg-stone-50 hover:bg-emerald-50/60">
                        <td class="px-3 py-2">{{ e.associado?.nome }}</td>
                        <td class="px-3 py-2">{{ e.associado?.telefone || '—' }}</td>
                        <td class="px-3 py-2">{{ e.livro?.codigo }} — {{ e.livro?.titulo }}</td>
                        <td class="px-3 py-2">{{ fmt(e.data_retirada) }}</td>
                        <td class="px-3 py-2">{{ e.dias_em_aberto }}</td>
                        <td class="px-3 py-2">
                            <span v-if="e.ultima_comunicacao">{{ fmt(e.ultima_comunicacao.data_hora) }} ({{ e.ultima_comunicacao.meio }})</span>
                            <span v-else>—</span>
                        </td>
                        <td class="px-3 py-2">
                            <Link :href="`/comunicacoes/nova?emprestimo_id=${e.id}`" class="text-brand hover:underline">Registrar comunicação</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
