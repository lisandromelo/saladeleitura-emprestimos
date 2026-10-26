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
    if (props.busca?.tipo === 'livro') {
        router.get('/emprestimos/buscar', {
            tipo: 'livro',
            q: '',
            associado_id: form.associado_id || undefined,
            livro_id: item.id,
        }, { preserveState: true, preserveScroll: true, replace: true });
    }
}

function escolherAssociado(item) {
    aplicarAssociado(item);
    if (props.busca?.tipo === 'associado') {
        router.get('/emprestimos/buscar', {
            tipo: 'associado',
            q: '',
            associado_id: item.id,
            livro_id: form.livro_id || undefined,
        }, { preserveState: true, preserveScroll: true, replace: true });
    }
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
        <div class="mb-8"><h1 class="text-3xl font-bold text-brand sm:text-4xl">Novo empréstimo</h1><p class="mt-2 text-base text-stone-700">Siga os passos abaixo para registrar a saída de um livro.</p></div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-xl border border-stone-300 bg-white p-5 shadow-sm" :class="livroAtual ? 'ring-1 ring-brand/20' : ''">
                <h2 class="mb-2 text-xl font-bold text-stone-900">1. Escolha o livro</h2><p class="mb-4 text-base text-stone-700">Pesquise pelo título e selecione o livro que será emprestado.</p><div v-if="livroAtual" class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 p-3" aria-live="polite"><p class="text-base font-semibold text-stone-900">{{ livroAtual.titulo }}</p><p class="mt-1 text-sm text-stone-700">Código {{ livroAtual.codigo }}</p><button type="button" class="mt-2 text-sm font-semibold text-emerald-900 underline" @click="limparLivro">Escolher outro livro</button></div>
                <form class="mb-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="buscarLivro">
                    <input v-model="livroQ" class="min-h-12 flex-1 rounded-md border border-stone-400 bg-white px-4 py-3 text-base text-stone-900 placeholder:text-stone-500 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20" placeholder="Digite o título do livro">
                    <button class="min-h-12 rounded-md bg-brand px-5 py-3 text-base font-semibold text-white hover:bg-brand-dark">Buscar livro</button>
                </form>
                <ul v-if="busca?.tipo === 'livro'" class="space-y-2 text-base">
                    <li v-for="item in busca.itens" :key="item.id">
                        <button
                            type="button"
                            class="w-full rounded-md border border-stone-200 px-3 py-3 text-left text-base text-stone-900 transition hover:border-emerald-500 hover:bg-emerald-50"
                            :class="form.livro_id === item.id ? 'bg-brand/10 font-medium text-brand' : 'text-brand'"
                            @click="escolherLivro(item)"
                        >
                            {{ item.titulo }} ({{ item.codigo }})
                        </button>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-stone-300 bg-white p-5 shadow-sm" :class="associadoAtual ? 'ring-1 ring-brand/20' : ''">
                <h2 class="mb-2 text-xl font-bold text-stone-900">2. Escolha o associado</h2><p class="mb-4 text-base text-stone-700">Pesquise pelo nome ou telefone. Se não estiver cadastrado, use o botão Novo associado.</p><div v-if="associadoAtual" class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 p-3" aria-live="polite"><p class="text-base font-semibold text-stone-900">{{ associadoAtual.nome }}</p><p class="mt-1 text-sm text-stone-700">{{ associadoAtual.telefone || "Sem telefone cadastrado" }}</p><button type="button" class="mt-2 text-sm font-semibold text-emerald-900 underline" @click="limparAssociado">Escolher outro associado</button></div>
                <form class="mb-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap" @submit.prevent="buscarAssociado">
                    <input v-model="associadoQ" class="min-h-12 min-w-[12rem] flex-1 rounded-md border border-stone-400 bg-white px-4 py-3 text-base text-stone-900 placeholder:text-stone-500 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20" placeholder="Digite nome ou telefone">
                    <button class="min-h-12 rounded-md bg-brand px-5 py-3 text-base font-semibold text-white hover:bg-brand-dark">Buscar</button>
                    <button type="button" class="min-h-12 rounded-md border-2 border-emerald-800 bg-emerald-800 px-5 py-3 text-base font-bold text-white shadow-sm hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/25" @click="abrirCadastro">
                        {{ mostrarCadastro ? 'Cancelar cadastro' : '+ Novo associado' }}
                    </button>
                </form>

                <form
                    v-if="mostrarCadastro"
                    class="mb-4 space-y-4 rounded-lg border-2 border-emerald-200 bg-emerald-50/50 p-4"
                    @submit.prevent="cadastrarAssociado"
                >
                    <p class="text-lg font-bold text-stone-900">Cadastrar novo associado</p><p class="text-base text-stone-700">Preencha os dados para continuar com o empréstimo.</p>
                    <label class="block text-base font-semibold text-stone-900">Nome
                        <input v-model="cadastroForm.nome" class="mt-1 min-h-11 w-full rounded-md border border-stone-400 bg-white px-3 py-2.5 text-base text-stone-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20" required>
                        <p v-if="cadastroForm.errors.nome" class="mt-1 text-sm text-warn">{{ cadastroForm.errors.nome }}</p>
                    </label>
                    <label class="block text-base font-semibold text-stone-900">Telefone
                        <input v-model="cadastroForm.telefone" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    </label>
                    <label class="block text-base font-semibold text-stone-900">Área
                        <select v-model="cadastroForm.area_id" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                            <option value="">—</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nome }}</option>
                        </select>
                    </label>
                    <label class="block text-base font-semibold text-stone-900">Observação
                        <textarea v-model="cadastroForm.observacao" class="mt-1 w-full rounded-md border border-stone-400 bg-white px-3 py-3 text-base text-stone-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20" rows="2" />
                    </label>
                    <button class="min-h-12 rounded-md bg-emerald-800 px-5 py-3 text-base font-bold text-white hover:bg-emerald-900 disabled:cursor-not-allowed disabled:opacity-60" :disabled="cadastroForm.processing">
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

        <form class="mt-8 max-w-2xl space-y-4 rounded-xl border border-stone-300 bg-white p-5 shadow-sm" @submit.prevent="pedirConfirmacao">
            <h2 class="text-xl font-bold text-stone-900">3. Confirmar empréstimo</h2>
            <p class="text-base text-stone-700">Confira os dados e registre o empréstimo.</p>
            <label class="block text-base font-semibold text-stone-900">Observação
                <textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="2" />
            </label>
            <button class="min-h-12 rounded-md bg-emerald-800 px-6 py-3 text-base font-bold text-white shadow-sm hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!podeRegistrar">
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
