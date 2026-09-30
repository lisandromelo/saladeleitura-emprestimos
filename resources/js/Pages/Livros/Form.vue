<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
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
            body: JSON.stringify({ url: item.thumbnail }),
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
        <h1 class="mb-6 text-2xl text-brand">
            {{ editing ? 'Editar livro' : 'Novo livro' }}
        </h1>

        <form class="max-w-xl space-y-4" enctype="multipart/form-data" @submit.prevent="submit">
            <div class="space-y-3 rounded-lg border border-stone-200 bg-white/70 p-4">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="flex h-36 w-28 items-center justify-center overflow-hidden rounded border border-stone-200 bg-stone-50">
                        <img v-if="preview" :src="preview" alt="Capa" class="h-full w-full object-cover">
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
                            <button
                                type="button"
                                class="rounded-md bg-brand px-3 py-1.5 text-xs text-white disabled:opacity-60"
                                :disabled="buscando || importando || !form.titulo"
                                @click="buscarCapas"
                            >
                                {{ buscando ? 'Buscando…' : 'Buscar capa na internet' }}
                            </button>
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
                            <p class="line-clamp-1 text-[11px] text-muted">{{ item.autores || item.fonte }}</p>
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
    </AppLayout>
</template>
