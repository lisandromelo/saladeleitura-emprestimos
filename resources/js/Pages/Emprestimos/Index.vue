<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    emprestimos: Object,
});

const emprestimoDevolucao = ref(null);
const dataDevolucaoAgora = ref(null);
const devolverForm = useForm({});

const emprestimoCancelamento = ref(null);
const motivoCancelamento = ref('Erro de lançamento');
const cancelForm = useForm({
    motivo_cancelamento: '',
});

const livroDevolucao = computed(() => {
    const livro = emprestimoDevolucao.value?.livro;
    if (!livro) {
        return '—';
    }
    return `${livro.titulo} (${livro.codigo})`;
});

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}

function abrirDevolver(emprestimo) {
    emprestimoDevolucao.value = emprestimo;
    dataDevolucaoAgora.value = new Date().toISOString();
}

function fecharDevolver() {
    if (devolverForm.processing) {
        return;
    }
    emprestimoDevolucao.value = null;
    dataDevolucaoAgora.value = null;
}

function confirmarDevolver() {
    if (!emprestimoDevolucao.value) {
        return;
    }
    devolverForm.post(`/emprestimos/${emprestimoDevolucao.value.id}/devolver`, {
        onSuccess: () => {
            emprestimoDevolucao.value = null;
            dataDevolucaoAgora.value = null;
        },
    });
}

function abrirCancelar(emprestimo) {
    emprestimoCancelamento.value = emprestimo;
    motivoCancelamento.value = 'Erro de lançamento';
}

function fecharCancelar() {
    if (cancelForm.processing) {
        return;
    }
    emprestimoCancelamento.value = null;
}

function confirmarCancelar() {
    if (!emprestimoCancelamento.value) {
        return;
    }
    cancelForm.motivo_cancelamento = motivoCancelamento.value;
    cancelForm.post(`/emprestimos/${emprestimoCancelamento.value.id}/cancelar`, {
        onSuccess: () => {
            emprestimoCancelamento.value = null;
            cancelForm.reset();
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">Empréstimos em aberto</h1>
            <Link href="/emprestimos/novo" class="rounded-md bg-brand px-3 py-2 text-sm text-white">Novo empréstimo</Link>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Livro</th>
                        <th class="px-3 py-2">Associado</th>
                        <th class="px-3 py-2">Retirada</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in emprestimos.data" :key="e.id" class="border-t border-stone-100">
                        <td class="px-3 py-2">{{ e.livro?.titulo }} ({{ e.livro?.codigo }})</td>
                        <td class="px-3 py-2">{{ e.associado?.nome }}</td>
                        <td class="px-3 py-2">{{ fmt(e.data_retirada) }}</td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <button class="rounded-md border border-stone-200 bg-white px-2 py-1 text-xs hover:bg-stone-50" @click="abrirDevolver(e)">
                                    Devolver
                                </button>
                                <button class="rounded-md border border-warn/30 bg-white px-2 py-1 text-xs text-warn hover:bg-rose-50" @click="abrirCancelar(e)">
                                    Cancelar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="emprestimoDevolucao"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/45 p-4 backdrop-blur-[2px]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="devolver-emprestimo-titulo"
            @click.self="fecharDevolver"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-xl border border-brand/20 bg-white shadow-2xl">
                <div class="border-b border-brand/15 bg-brand px-5 py-3 text-white">
                    <h3 id="devolver-emprestimo-titulo" class="text-lg font-semibold">Confirmação de devolução</h3>
                    <p class="mt-0.5 text-sm text-white/85">Revise os dados antes de concluir</p>
                </div>

                <div class="space-y-3 px-5 py-4 text-sm">
                    <div class="rounded-lg border border-brand/15 bg-brand/5 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand">Livro</p>
                        <p class="mt-1 font-semibold text-ink">{{ livroDevolucao }}</p>
                    </div>
                    <div class="rounded-lg border border-brand/15 bg-brand/5 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand">Associado</p>
                        <p class="mt-1 font-semibold text-ink">{{ emprestimoDevolucao.associado?.nome || '—' }}</p>
                        <p v-if="emprestimoDevolucao.associado?.telefone" class="mt-0.5 text-muted">{{ emprestimoDevolucao.associado.telefone }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Emprestado em</p>
                            <p class="mt-1 font-medium text-ink">{{ fmt(emprestimoDevolucao.data_retirada) }}</p>
                        </div>
                        <div class="rounded-lg border border-stone-200 bg-stone-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Devolvendo em</p>
                            <p class="mt-1 font-medium text-ink">{{ fmt(dataDevolucaoAgora) }}</p>
                        </div>
                    </div>
                    <p class="pt-1 text-base font-semibold text-ink">Confirma a devolução?</p>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 bg-stone-50 px-5 py-3">
                    <button
                        type="button"
                        class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm text-ink hover:bg-stone-100"
                        :disabled="devolverForm.processing"
                        @click="fecharDevolver"
                    >
                        Voltar
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-dark"
                        :disabled="devolverForm.processing"
                        @click="confirmarDevolver"
                    >
                        {{ devolverForm.processing ? 'Registrando…' : 'Confirmar devolução' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="emprestimoCancelamento"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/45 p-4 backdrop-blur-[2px]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cancelar-emprestimo-titulo"
            @click.self="fecharCancelar"
        >
            <div class="w-full max-w-md overflow-hidden rounded-xl border border-warn/20 bg-white shadow-2xl">
                <div class="border-b border-warn/15 bg-warn px-5 py-3 text-white">
                    <h3 id="cancelar-emprestimo-titulo" class="text-lg font-semibold">Cancelar empréstimo</h3>
                    <p class="mt-0.5 text-sm text-white/90">Para erro de lançamento — não registra devolução</p>
                </div>
                <div class="space-y-3 px-5 py-4 text-sm">
                    <p class="text-ink/90">
                        <span class="font-medium">{{ emprestimoCancelamento.livro?.titulo }}</span>
                        · {{ emprestimoCancelamento.associado?.nome }}
                    </p>
                    <p class="text-ink/90">
                        O livro volta a ficar disponível. O lançamento permanece no histórico como cancelado.
                    </p>
                    <label class="block text-sm">Motivo
                        <input
                            v-model="motivoCancelamento"
                            class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2"
                            maxlength="255"
                            required
                        >
                    </label>
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 bg-stone-50 px-5 py-3">
                    <button
                        type="button"
                        class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm text-ink hover:bg-stone-100"
                        :disabled="cancelForm.processing"
                        @click="fecharCancelar"
                    >
                        Voltar
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-warn px-4 py-2 text-sm text-white hover:opacity-90"
                        :disabled="cancelForm.processing || !motivoCancelamento.trim()"
                        @click="confirmarCancelar"
                    >
                        {{ cancelForm.processing ? 'Cancelando…' : 'Confirmar cancelamento' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
