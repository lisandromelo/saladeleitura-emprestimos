<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    livro: Object,
    historico: Array,
    canManage: Boolean,
});

const baixaForm = useForm({
    motivo_baixa: 'Perda',
    observacao: '',
    data_baixa: '',
});

const ausenciaForm = useForm({
    data_balanco: new Date().toISOString().slice(0, 10),
});

function baixar() {
    if (!confirm('Confirmar baixa deste livro?')) return;
    baixaForm.post(`/livros/${props.livro.id}/baixar`);
}

function ausencia() {
    ausenciaForm.post(`/livros/${props.livro.id}/ausencia`);
}

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-wrap items-start gap-4">
                <div class="flex h-40 w-28 shrink-0 items-center justify-center overflow-hidden rounded border border-stone-200 bg-stone-50">
                    <img v-if="livro.capa_url" :src="livro.capa_url" :alt="`Capa de ${livro.titulo}`" class="h-full w-full object-cover">
                    <span v-else class="px-2 text-center text-xs text-muted">Sem capa</span>
                </div>
                <div>
                    <h1 class="text-2xl text-brand">{{ livro.titulo }}</h1>
                    <p class="text-sm text-stone-600">Código {{ livro.codigo }} · {{ livro.situacao }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Link v-if="canManage" :href="`/livros/${livro.id}/editar`" class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm">Editar</Link>
                <Link href="/emprestimos/novo" class="rounded-md bg-brand px-3 py-2 text-sm text-white">Emprestar</Link>
            </div>
        </div>

        <dl class="mb-8 grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-stone-500">Autor/Médium</dt><dd>{{ livro.autor_medium || '—' }}</dd></div>
            <div><dt class="text-stone-500">Espírito</dt><dd>{{ livro.espirito || '—' }}</dd></div>
            <div><dt class="text-stone-500">Classificação</dt><dd>{{ livro.classificacao?.nome || '—' }}</dd></div>
            <div><dt class="text-stone-500">Entrada</dt><dd>{{ livro.data_entrada || '—' }} ({{ livro.forma_entrada || '—' }})</dd></div>
            <div class="sm:col-span-2"><dt class="text-stone-500">Observação</dt><dd class="whitespace-pre-wrap">{{ livro.observacao || '—' }}</dd></div>
        </dl>

        <h2 class="mb-3 text-lg font-semibold">Histórico</h2>
        <div class="mb-8 overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Data</th>
                        <th class="px-3 py-2">Evento</th>
                        <th class="px-3 py-2">Operador</th>
                        <th class="px-3 py-2">Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(ev, i) in historico" :key="i" class="border-t border-stone-100">
                        <td class="px-3 py-2">{{ fmt(ev.data) }}</td>
                        <td class="px-3 py-2">{{ ev.evento }}</td>
                        <td class="px-3 py-2">{{ ev.operador || '—' }}</td>
                        <td class="px-3 py-2">{{ ev.observacao || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="canManage && livro.situacao !== 'BAIXADO'" class="grid gap-6 md:grid-cols-2">
            <form class="space-y-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="baixar">
                <h3 class="font-semibold">Baixa</h3>
                <label class="block text-sm">Motivo
                    <select v-model="baixaForm.motivo_baixa" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                        <option>Perda</option>
                        <option>Repassado por doação</option>
                        <option>Outro</option>
                    </select>
                </label>
                <label class="block text-sm">Observação
                    <textarea v-model="baixaForm.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" />
                </label>
                <button class="rounded-md bg-warn px-3 py-2 text-sm text-white">Registrar baixa</button>
            </form>

            <form class="space-y-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="ausencia">
                <h3 class="font-semibold">Registrar ausência (balanço)</h3>
                <p class="text-xs text-stone-500">Não altera a situação do livro — só adiciona observação.</p>
                <label class="block text-sm">Data do balanço
                    <input v-model="ausenciaForm.data_balanco" type="date" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                </label>
                <button class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm">Registrar ausência</button>
            </form>
        </div>
    </AppLayout>
</template>
