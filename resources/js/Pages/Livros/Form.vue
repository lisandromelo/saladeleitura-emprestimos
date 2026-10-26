<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    livro: Object,
    classificacoes: Array,
});

const editing = computed(() => !!props.livro);
const preview = ref(props.livro?.capa_url || null);
const buscando = ref(false);
const importando = ref(false);
const buscaErro = ref('');
const resultados = ref([]);
const capaAmpliada = ref(false);

const form = useForm({
    codigo: props.livro?.codigo || '',
    titulo: props.livro?.titulo || '',
    autor_medium: props.livro?.autor_medium || '',
    espirito: props.livro?.espirito || '',
    classificacao_id: props.livro?.classificacao_id || '',
    data_entrada: props.livro?.data_entrada?.slice?.(0, 10) || '',
    forma_entrada: props.livro?.forma_entrada || 'DOAÇÃO',
    observacao: props.livro?.observacao || '',
    capa: null,
    capa_importada: '',
    remover_capa: false,
    confirmar_duplicado: false,
});

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || usePage().props.csrf_token
        || '';
}

function xsrfCookie() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function buscarCapas() {
    buscaErro.value = '';
    resultados.value = [];

    if (!form.titulo.trim()) {
        buscaErro.value = 'Informe o título antes de buscar a capa.';
        return;
    }

    buscando.value = true;
    try {
        const response = await fetch('/livros/capas/buscar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfCookie(),
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                titulo: form.titulo,
                autor: form.autor_medium || null,
            }),
        });

        const data = await response.json();
        if (!response.ok) {
            if (data.quota_excedida || response.status === 429) {
                throw new Error(data.message || 'Quota da API excedida. Tente novamente mais tarde.');
            }
            throw new Error(data.message || 'Falha na busca de capas.');
        }

        resultados.value = data.resultados || [];
        if (!resultados.value.length) {
            buscaErro.value = 'Nenhuma capa encontrada para este título.';
        }
    } catch (e) {
        buscaErro.value = e.message || 'Não foi possível buscar capas.';
    } finally {
        buscando.value = false;
    }
}

async function escolherCapa(item) {
    importando.value = true;
    buscaErro.value = '';
    try {
        const response = await fetch('/livros/capas/importar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfCookie(),
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ url: item.url || item.thumbnail }),
        });

        const data = await response.json();
        if (!response.ok) {
            if (data.quota_excedida || response.status === 429) {
                throw new Error(data.message || 'Quota da API excedida. Tente novamente mais tarde.');
            }
            throw new Error(data.message || 'Falha ao importar a capa.');
        }

        form.capa = null;
        form.capa_importada = data.capa;
        form.remover_capa = false;
        preview.value = data.capa_url;
        resultados.value = [];
    } catch (e) {
        buscaErro.value = e.message || 'Não foi possível importar a capa.';
    } finally {
        importando.value = false;
    }
}

async function colarCapa() {
    buscaErro.value = '';
    try {
        const clipboardItems = await navigator.clipboard.read();
        for (const item of clipboardItems) {
            const mime = item.types.find((type) => ['image/png', 'image/jpeg', 'image/webp'].includes(type));
            if (!mime) continue;
            const blob = await item.getType(mime);
            const extensao = mime === 'image/jpeg' ? 'jpg' : mime === 'image/webp' ? 'webp' : 'png';
            const arquivo = new File([blob], 'capa-colada.' + extensao, { type: mime });
            form.capa = arquivo;
            form.capa_importada = '';
            form.remover_capa = false;
            preview.value = URL.createObjectURL(arquivo);
            resultados.value = [];
            return;
        }
        buscaErro.value = 'A área de transferência não contém uma imagem. Copie ou recorte a imagem novamente e tente colar.';
    } catch (erro) {
        buscaErro.value = 'O navegador não permitiu ler a área de transferência. Clique no campo abaixo e pressione Ctrl+V, ou selecione a imagem do computador.';
    }
}

function colarEvento(event) {
    const item = Array.from(event.clipboardData?.items || []).find((entrada) => entrada.type.startsWith('image/'));
    if (!item) return;
    const arquivo = item.getAsFile();
    if (!arquivo) return;
    const extensao = arquivo.type.split('/')[1] || 'png';
    form.capa = new File([arquivo], 'capa-colada.' + extensao, { type: arquivo.type });
    form.capa_importada = '';
    form.remover_capa = false;
    preview.value = URL.createObjectURL(arquivo);
    resultados.value = [];
    buscaErro.value = '';
}
function onCapaChange(event) {
    const file = event.target.files?.[0] || null;
    form.capa = file;
    form.capa_importada = '';
    form.remover_capa = false;
    if (file) {
        preview.value = URL.createObjectURL(file);
    }
}

function limparCapa() {
    form.capa = null;
    form.capa_importada = '';
    form.remover_capa = true;
    preview.value = null;
    resultados.value = [];
}

function submit() {
    if (editing.value) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
            }))
            .post(`/livros/${props.livro.id}`, {
                forceFormData: true,
            });
    } else {
        form.post('/livros', { forceFormData: true });
    }
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">
                {{ editing ? 'Editar livro' : 'Novo livro' }}
            </h1>
            <Link :href="editing ? '/livros/' + livro.id : '/livros'" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 shadow-sm transition hover:border-emerald-700 hover:bg-emerald-50">
                <span aria-hidden="true">←</span> Voltar
            </Link>
        </div>

        <form class="max-w-xl space-y-4" enctype="multipart/form-data" @submit.prevent="submit">
            <div id="capa" class="space-y-3 rounded-lg border border-stone-200 bg-white/70 p-4">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="flex h-36 w-28 items-center justify-center overflow-hidden rounded border border-stone-200 bg-stone-50">
                        <button v-if="preview" type="button" class="h-full w-full cursor-zoom-in focus:outline-none focus:ring-2 focus:ring-emerald-700" aria-label="Ampliar capa" @click="capaAmpliada = true"><img :src="preview" alt="Capa" class="h-full w-full object-contain"></button>
                        <span v-else class="px-2 text-center text-xs text-muted">Sem capa</span>
                    </div>
                    <div class="min-w-[12rem] flex-1 space-y-2 text-sm">
                        <label class="block">
                            Importar capa do computador
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="mt-1 block w-full text-sm"
                                @change="onCapaChange"
                            >
                        </label>
                        <p class="text-xs text-muted">JPG, PNG, WEBP ou GIF · máx. 2 MB</p>
                        <p v-if="form.errors.capa" class="text-sm text-warn">{{ form.errors.capa }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-md border border-emerald-800 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-900 hover:bg-emerald-100" @click="colarCapa">Colar imagem</button>
                            <button
                                type="button"
                                class="rounded-md bg-brand px-3 py-1.5 text-xs text-white disabled:opacity-60"
                                :disabled="buscando || importando || !form.titulo"
                                @click="buscarCapas"
                            >
                                {{ buscando ? 'Buscando…' : 'Buscar capa na internet' }}
                            </button>                            <a
                                :href="'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent([form.titulo, form.autor_medium].filter(Boolean).join(' '))"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center rounded-md border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-800 hover:bg-stone-50"
                            >Buscar imagens no Google</a>
                            <button
                                v-if="preview"
                                type="button"
                                class="rounded-md border border-stone-200 bg-white px-2 py-1 text-xs text-warn"
                                @click="limparCapa"
                            >
                                Remover capa
                            </button>
                        </div>
                        <p v-if="buscaErro" class="text-sm text-warn">{{ buscaErro }}</p>
                        <p v-if="importando" class="text-xs text-muted">Importando capa selecionada…</p>
                    </div>
                </div>

                <label class="mt-2 block text-xs text-stone-600">
                    Ou clique neste campo e pressione Ctrl+V para colar uma imagem copiada.
                    <input type="text" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm" placeholder="Clique aqui e pressione Ctrl+V" @paste="colarEvento">
                </label>

                <div v-if="resultados.length" class="border-t border-stone-100 pt-3">
                    <p class="mb-2 text-sm font-medium">Selecione uma capa encontrada</p>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                        <button
                            v-for="(item, idx) in resultados"
                            :key="idx"
                            type="button"
                            class="rounded border border-stone-200 bg-white p-2 text-left hover:border-brand"
                            :disabled="importando"
                            @click="escolherCapa(item)"
                        >
                            <img :src="item.thumbnail" :alt="item.titulo" class="mb-2 h-28 w-full object-contain">
                            <p class="line-clamp-2 text-xs font-medium">{{ item.titulo }}</p>
                            <p class="line-clamp-1 text-[11px] text-muted">{{ item.fonte }}</p>
                        </button>
                    </div>
                </div>
            </div>

            <label class="block text-sm">Código
                <input v-model="form.codigo" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                <p v-if="form.errors.codigo" class="text-sm text-warn">{{ form.errors.codigo }}</p>
            </label>
            <label class="block text-sm">Título
                <input v-model="form.titulo" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                <p v-if="form.errors.titulo" class="text-sm text-warn">{{ form.errors.titulo }}</p>
            </label>
            <label class="block text-sm">Autor / Médium
                <input v-model="form.autor_medium" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
            </label>
            <label class="block text-sm">Espírito
                <input v-model="form.espirito" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
            </label>
            <label class="block text-sm">Classificação
                <select v-model="form.classificacao_id" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    <option value="">—</option>
                    <option v-for="c in classificacoes" :key="c.id" :value="c.id">{{ c.nome }}</option>
                </select>
            </label>
            <label v-if="!editing" class="block text-sm">Data de entrada
                <input v-model="form.data_entrada" type="date" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
            </label>
            <label v-if="!editing" class="block text-sm">Forma de entrada
                <input v-model="form.forma_entrada" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
            </label>
            <label class="block text-sm">Observação
                <textarea v-model="form.observacao" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="3" />
            </label>
            <label v-if="!editing" class="flex items-center gap-2 text-sm">
                <input v-model="form.confirmar_duplicado" type="checkbox">
                Confirmar cadastro mesmo se o título já existir
            </label>
            <button class="rounded-md bg-brand px-4 py-2 text-sm text-white" :disabled="form.processing">Salvar</button>
        </form>

        <div v-if="capaAmpliada && preview" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="Capa ampliada" @click.self="capaAmpliada = false" @keydown.esc="capaAmpliada = false">
            <div class="relative flex max-h-[96vh] max-w-[95vw] flex-col items-center rounded-xl bg-white p-4 shadow-2xl">
                <button type="button" class="absolute right-2 top-2 z-10 rounded-full bg-stone-900 px-3 py-1 text-lg font-bold text-white hover:bg-stone-700" aria-label="Fechar imagem ampliada" @click="capaAmpliada = false">×</button>
                <p class="mb-3 pr-8 text-center text-base font-semibold text-stone-900">{{ form.titulo || "Capa do livro" }}</p>
                <img :src="preview" alt="Capa ampliada" class="max-h-[85vh] max-w-full object-contain">
            </div>
        </div>
    </AppLayout>
</template>
