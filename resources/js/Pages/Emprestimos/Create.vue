<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    busca: Object,
    areas: { type: Array, default: () => [] },
    associadoSelecionado: { type: Object, default: null },
    livroSelecionado: { type: Object, default: null },
});

const livroQ = ref('');
const associadoQ = ref('');
const livroAtual = ref(null);
const associadoAtual = ref(null);
const mostrarCadastro = ref(false);
const confirmarAberto = ref(false);

const form = useForm({
    livro_id: null,
    associado_id: null,
    observacao: '',
});

const cadastroForm = useForm({
    nome: '',
    telefone: '',
    area_id: '',
    observacao: '',
    retorno: 'emprestimo',
    livro_id: null,
});

const podeRegistrar = computed(() => !!form.livro_id && !!form.associado_id && !form.processing);

function aplicarAssociado(associado) {
    if (!associado) {
        return;
    }
    associadoAtual.value = associado;
    form.associado_id = associado.id;
    associadoQ.value = associado.nome;
}

function aplicarLivro(livro) {
    if (!livro) {
        return;
    }
    livroAtual.value = livro;
    form.livro_id = livro.id;
    // O título é o que o usuário reconhece; o código fica só no painel Selecionados.
    livroQ.value = livro.titulo ?? '';
}

onMounted(() => {
    aplicarAssociado(props.associadoSelecionado);
    aplicarLivro(props.livroSelecionado);
});

watch(() => props.associadoSelecionado, aplicarAssociado);
watch(() => props.livroSelecionado, aplicarLivro);

function buscarLivro() {
    router.get('/emprestimos/buscar', {
        tipo: 'livro',
        q: livroQ.value,
        associado_id: form.associado_id || undefined,
        livro_id: form.livro_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function buscarAssociado() {
    router.get('/emprestimos/buscar', {
        tipo: 'associado',
        q: associadoQ.value,
        associado_id: form.associado_id || undefined,
        livro_id: form.livro_id || undefined,
    }, { preserveState: true });
}

function escolherLivro(item) {
    aplicarLivro(item);
}

function escolherAssociado(item) {
    aplicarAssociado(item);
}

function limparLivro() {
    livroAtual.value = null;
    form.livro_id = null;
    livroQ.value = '';
}

function limparAssociado() {
    associadoAtual.value = null;
    form.associado_id = null;
    associadoQ.value = '';
}

function abrirCadastro() {
    mostrarCadastro.value = !mostrarCadastro.value;
    if (mostrarCadastro.value && associadoQ.value && !cadastroForm.nome) {
        cadastroForm.nome = associadoQ.value;
    }
}

function cadastrarAssociado() {
    cadastroForm.livro_id = form.livro_id;
    cadastroForm.post('/associados', {
        preserveScroll: true,
        onSuccess: () => {
            mostrarCadastro.value = false;
            cadastroForm.reset('telefone', 'area_id', 'observacao');
        },
    });
}

function pedirConfirmacao() {
    if (!podeRegistrar.value) {
        return;
    }
    confirmarAberto.value = true;
}

function fecharConfirmacao() {
    if (form.processing) {
        return;
    }
    confirmarAberto.value = false;
}

function confirmarRegistro() {
    form.post('/emprestimos', {
        onFinish: () => {
            confirmarAberto.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">Novo empréstimo</h1>

        <section
            class="mb-6 overflow-hidden rounded-xl border-2 border-brand/35 bg-gradient-to-br from-brand/10 via-white to-paper shadow-[0_10px_30px_-18px_rgba(0,109,91,0.55)]"
            aria-live="polite"
        >
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-brand/15 bg-brand px-4 py-2.5 text-white">
                <h2 class="text-sm font-semibold tracking-wide uppercase">Selecionados</h2>
                <p class="text-xs text-white/85">Revise livro e associado antes de registrar</p>
            </div>

            <div class="grid gap-0 md:grid-cols-2">
                <div class="border-b border-brand/10 p-4 md:border-b-0 md:border-r">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand">Livro</p>
                    <template v-if="livroAtual">
                        <p class="mt-1 text-lg font-semibold text-ink">{{ livroAtual.titulo }}</p>
                        <p class="mt-0.5 text-sm text-muted">Código {{ livroAtual.codigo }}</p>
                        <p v-if="livroAtual.autor" class="mt-1 text-xs text-muted">{{ livroAtual.autor }}</p>
                        <button type="button" class="mt-3 text-xs font-medium text-warn hover:underline" @click="limparLivro">
                            Trocar livro
                        </button>
                    </template>
                    <p v-else class="mt-2 text-sm text-muted">Nenhum livro selecionado</p>
                </div>

                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand">Associado</p>
                    <template v-if="associadoAtual">
                        <p class="mt-1 text-lg font-semibold text-ink">{{ associadoAtual.nome }}</p>
                        <p v-if="associadoAtual.telefone" class="mt-0.5 text-sm text-ink/90">{{ associadoAtual.telefone }}</p>
                        <p v-else class="mt-0.5 text-sm text-muted">Sem telefone</p>
                        <button type="button" class="mt-3 text-xs font-medium text-warn hover:underline" @click="limparAssociado">
                            Trocar associado
                        </button>
                    </template>
                    <p v-else class="mt-2 text-sm text-muted">Nenhum associado selecionado</p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border border-stone-200 bg-white/70 p-4" :class="livroAtual ? 'ring-1 ring-brand/20' : ''">
                <h2 class="mb-3 font-semibold">Livro</h2>
                <form class="mb-3 flex gap-2" @submit.prevent="buscarLivro">
                    <input v-model="livroQ" class="flex-1 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Título do livro">
                    <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Buscar</button>
                </form>
                <ul v-if="busca?.tipo === 'livro'" class="space-y-1 text-sm">
                    <li v-for="item in busca.itens" :key="item.id">
                        <button
                            type="button"
                            class="w-full rounded-md px-2 py-1.5 text-left transition hover:bg-brand/8"
                            :class="form.livro_id === item.id ? 'bg-brand/10 font-medium text-brand' : 'text-brand'"
                            @click="escolherLivro(item)"
                        >
                            {{ item.titulo }} ({{ item.codigo }})
                        </button>
                    </li>
                </ul>
            </div>

            <div class="rounded-lg border border-stone-200 bg-white/70 p-4" :class="associadoAtual ? 'ring-1 ring-brand/20' : ''">
                <h2 class="mb-3 font-semibold">Associado</h2>
                <form class="mb-3 flex flex-wrap gap-2" @submit.prevent="buscarAssociado">
                    <input v-model="associadoQ" class="min-w-[12rem] flex-1 rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Nome ou telefone">
                    <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Buscar</button>
                    <button type="button" class="rounded-md border border-brand px-3 py-2 text-sm text-brand hover:bg-brand hover:text-white" @click="abrirCadastro">
                        {{ mostrarCadastro ? 'Fechar' : 'Cadastrar' }}
                    </button>
                </form>

                <form
                    v-if="mostrarCadastro"
                    class="mb-4 space-y-3 rounded-md border border-stone-200 bg-white p-3"
                    @submit.prevent="cadastrarAssociado"
                >
                    <p class="text-sm font-medium text-ink">Novo associado</p>
                    <label class="block text-sm">Nome
                        <input v-model="cadastroForm.nome" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                        <p v-if="cadastroForm.errors.nome" class="mt-1 text-sm text-warn">{{ cadastroForm.errors.nome }}</p>
                    </label>
                    <label class="block text-sm">Telefone
                        <input v-model="cadastroForm.telefone" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    </label>
                    <label class="block text-sm">Área
                        <select v-model="cadastroForm.area_id" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                            <option value="">—</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nome }}</option>
                        </select>
                    </label>
                    <label class="block text-sm">Observação
                        <textarea v-model="cadastroForm.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" />
                    </label>
                    <button class="rounded-md bg-brand px-3 py-2 text-sm text-white" :disabled="cadastroForm.processing">
                        Salvar associado
                    </button>
                </form>

                <ul v-if="busca?.tipo === 'associado'" class="space-y-1 text-sm">
                    <li v-for="item in busca.itens" :key="item.id">
                        <button
                            type="button"
                            class="w-full rounded-md px-2 py-1.5 text-left transition hover:bg-brand/8"
                            :class="form.associado_id === item.id ? 'bg-brand/10 font-medium text-brand' : 'text-brand'"
                            @click="escolherAssociado(item)"
                        >
                            {{ item.nome }} {{ item.telefone ? `(${item.telefone})` : '' }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <form class="mt-6 max-w-xl space-y-3" @submit.prevent="pedirConfirmacao">
            <label class="block text-sm">Observação
                <textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" />
            </label>
            <button class="rounded-md bg-brand px-4 py-2 text-sm text-white" :disabled="!podeRegistrar">
                Registrar empréstimo
            </button>
        </form>

        <div
            v-if="confirmarAberto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/45 p-4 backdrop-blur-[2px]"
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirm-emprestimo-titulo"
            @click.self="fecharConfirmacao"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-xl border border-brand/20 bg-white shadow-2xl">
                <div class="border-b border-brand/15 bg-brand px-5 py-3 text-white">
                    <h3 id="confirm-emprestimo-titulo" class="text-lg font-semibold">Confirmar empréstimo</h3>
                    <p class="mt-0.5 text-sm text-white/85">Verifique os dados antes de concluir</p>
                </div>

                <div class="space-y-4 px-5 py-4 text-sm">
                    <div class="rounded-lg border border-brand/15 bg-brand/5 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand">Livro</p>
                        <p class="mt-1 font-semibold text-ink">{{ livroAtual?.titulo }}</p>
                        <p class="mt-0.5 text-muted">Código {{ livroAtual?.codigo }}</p>
                        <p v-if="livroAtual?.autor" class="mt-0.5 text-muted">{{ livroAtual.autor }}</p>
                    </div>
                    <div class="rounded-lg border border-brand/15 bg-brand/5 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand">Associado</p>
                        <p class="mt-1 font-semibold text-ink">{{ associadoAtual?.nome }}</p>
                        <p class="mt-0.5 text-muted">{{ associadoAtual?.telefone || 'Sem telefone' }}</p>
                    </div>
                    <div v-if="form.observacao" class="rounded-lg border border-stone-200 bg-stone-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Observação</p>
                        <p class="mt-1 whitespace-pre-wrap text-ink">{{ form.observacao }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 bg-stone-50 px-5 py-3">
                    <button
                        type="button"
                        class="rounded-md border border-stone-200 bg-white px-4 py-2 text-sm text-ink hover:bg-stone-100"
                        :disabled="form.processing"
                        @click="fecharConfirmacao"
                    >
                        Voltar
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-brand px-4 py-2 text-sm text-white hover:bg-brand-dark"
                        :disabled="form.processing"
                        @click="confirmarRegistro"
                    >
                        {{ form.processing ? 'Registrando…' : 'Confirmar registro' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
