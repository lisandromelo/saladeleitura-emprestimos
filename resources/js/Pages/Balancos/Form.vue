<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    balanco: { type: Object, default: null },
    responsavel: { type: Object, default: null },
    codigosExistentes: { type: Array, default: () => [] },
});

const editing = computed(() => !!props.balanco);

const form = useForm({
    codigos: '',
    observacao: props.balanco?.observacao || '',
});

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}

function submit() {
    if (editing.value) {
        form.put(`/balancos/${props.balanco.id}`, {
            onSuccess: () => form.reset('codigos'),
        });
    } else {
        form.post('/balancos');
    }
}

function remover(itemId) {
    if (!confirm('Remover este código do balanço?')) return;
    router.delete(`/balancos/${props.balanco.id}/itens/${itemId}`);
}
</script>

<template>
    <AppLayout>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">{{ editing ? 'Continuar balanço' : 'Novo balanço' }}</h1>
            <Link
                v-if="editing"
                :href="`/balancos/${balanco.id}`"
                class="rounded-md border border-stone-200 bg-white px-3 py-2 text-sm hover:bg-stone-50"
            >
                Ver resultado
            </Link>
        </div>

        <p class="mb-2 text-sm text-stone-600">
            Ferramenta de auditoria — não altera a situação dos livros.
            Você pode começar hoje e continuar amanhã.
        </p>
        <p v-if="editing" class="mb-6 text-sm text-muted">
            Iniciado em {{ fmt(balanco.data_hora) }}
            <span v-if="responsavel"> · {{ responsavel.name }}</span>
            · {{ codigosExistentes.length }} código(s) lançado(s)
        </p>
        <p v-else class="mb-6 text-sm text-muted">Códigos são opcionais na criação; dá para lançar depois.</p>

        <form class="mb-8 max-w-xl space-y-3" @submit.prevent="submit">
            <label class="block text-sm">
                {{ editing ? 'Novos códigos encontrados' : 'Códigos encontrados' }}
                <span class="text-muted">(um por linha, ou separados por vírgula/espaço)</span>
                <textarea
                    v-model="form.codigos"
                    class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2 font-mono"
                    rows="8"
                    :placeholder="editing ? 'Informe apenas os códigos ainda não lançados' : 'Opcional — pode lançar depois'"
                />
            </label>
            <label class="block text-sm">Observação
                <textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" />
            </label>
            <button class="rounded-md bg-brand px-4 py-2 text-sm text-white" :disabled="form.processing">
                {{ editing ? 'Salvar continuidade' : 'Iniciar / processar balanço' }}
            </button>
        </form>

        <div v-if="editing && codigosExistentes.length">
            <h2 class="mb-3 text-lg font-semibold">Códigos já lançados</h2>
            <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Código</th>
                            <th class="px-3 py-2">Título</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in codigosExistentes" :key="item.id" class="border-t border-stone-100">
                            <td class="px-3 py-2 font-mono">{{ item.codigo }}</td>
                            <td class="px-3 py-2">
                                <span v-if="item.valido">{{ item.titulo || '—' }}</span>
                                <span v-else class="text-warn">sem cadastro</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" class="text-xs text-warn hover:underline" @click="remover(item.id)">
                                    Remover
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
