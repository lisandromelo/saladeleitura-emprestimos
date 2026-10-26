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

function formatDate(value) {
    if (!value) return '—';
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) return `${match[3]}/${match[2]}/${match[1]}`;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('pt-BR', { timeZone: 'UTC' });
}
</script>

<template>
    <AppLayout>
        <div class="mb-6">
            <Link href="/livros" class="mb-4 inline-flex min-h-10 items-center gap-2 rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                <span aria-hidden="true">←</span> Voltar ao acervo
            </Link>
            <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="grid gap-6 md:grid-cols-[13rem_minmax(0,1fr)]">
                <div class="flex min-h-64 w-full max-w-52 items-start justify-center overflow-hidden rounded-xl border border-stone-200 bg-stone-50 shadow-sm">
                    <img v-if="livro.capa_url" :src="livro.capa_url" :alt="`Capa de ${livro.titulo}`" class="h-80 w-full object-contain">
                    <span v-else class="flex h-64 w-full items-center justify-center px-3 text-center text-sm text-stone-500">Sem capa disponível</span>
                </div>
                <div class="min-w-0">
                    <h1 class="text-3xl font-bold leading-tight text-brand sm:text-4xl">{{ livro.titulo }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-stone-100 px-3 py-1.5 text-sm font-semibold text-stone-800">Código {{ livro.codigo }}</span>
                        <span class="rounded-md px-3 py-1.5 text-sm font-bold" :class="livro.situacao === 'DISPONIVEL' ? 'bg-emerald-100 text-emerald-900' : livro.situacao === 'EMPRESTADO' ? 'bg-amber-100 text-amber-900' : 'bg-rose-100 text-rose-900'">{{ livro.situacao }}</span>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-stone-200 bg-white p-4">
                            <dt class="text-sm font-semibold text-stone-600">Autor/Médium</dt>
                            <dd class="mt-1 text-lg font-bold text-stone-950">{{ livro.autor_medium || '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-stone-200 bg-white p-4">
                            <dt class="text-sm font-semibold text-stone-600">Espírito</dt>
                            <dd class="mt-1 text-lg font-bold text-stone-950">{{ livro.espirito || '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-stone-200 bg-white p-4">
                            <dt class="text-sm font-semibold text-stone-600">Classificação</dt>
                            <dd class="mt-1 text-base font-semibold text-stone-900">{{ livro.classificacao?.nome || '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-stone-200 bg-white p-4">
                            <dt class="text-sm font-semibold text-stone-600">Entrada no acervo</dt>
                            <dd class="mt-1 text-base font-semibold text-stone-900">{{ formatDate(livro.data_entrada) }}<span v-if="livro.forma_entrada"> · {{ livro.forma_entrada }}</span></dd>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <Link v-if="canManage" :href="`/livros/${livro.id}/editar`" class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm">Editar</Link>
                <Link href="/emprestimos/novo" class="rounded-md bg-brand px-3 py-2 text-sm text-white">Emprestar</Link>
            </div>
            </div>
        </div>

        <div v-if="livro.observacao" class="mb-8 rounded-lg border border-stone-200 bg-stone-50 p-4">
            <h2 class="text-sm font-bold text-stone-700">Observação</h2>
            <p class="mt-1 whitespace-pre-wrap text-base text-stone-900">{{ livro.observacao }}</p>
        </div>

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
