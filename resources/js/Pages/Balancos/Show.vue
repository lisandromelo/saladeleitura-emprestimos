<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    balanco: Object,
    resultado: Object,
});

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}

function registrarAusencia(livroId) {
    const data = props.balanco.data_hora?.slice?.(0, 10) || new Date().toISOString().slice(0, 10);
    router.post(`/livros/${livroId}/ausencia`, { data_balanco: data });
}
</script>

<template>
    <AppLayout>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">Resultado do balanço</h1>
            <Link
                :href="`/balancos/${balanco.id}/editar`"
                class="rounded-md bg-brand px-3 py-2 text-sm text-white"
            >
                Continuar / editar
            </Link>
        </div>
        <p class="mb-6 text-sm text-stone-600">{{ fmt(balanco.data_hora) }} · {{ balanco.usuario?.name }}</p>

        <section class="mb-6">
            <h2 class="mb-2 font-semibold">Não localizados (disponíveis não lançados)</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="l in resultado.nao_localizados" :key="l.id" class="flex flex-wrap items-center gap-3">
                    <Link :href="`/livros/${l.id}`" class="text-brand hover:underline">{{ l.codigo }} — {{ l.titulo }}</Link>
                    <button type="button" class="rounded border border-stone-200 bg-white px-2 py-0.5 text-xs" @click="registrarAusencia(l.id)">
                        Registrar ausência
                    </button>
                </li>
                <li v-if="!resultado.nao_localizados?.length" class="text-stone-500">Nenhum</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="mb-2 font-semibold">Emprestados (não tratados como desaparecidos)</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="l in resultado.emprestados" :key="l.id">{{ l.codigo }} — {{ l.titulo }}</li>
                <li v-if="!resultado.emprestados?.length" class="text-stone-500">Nenhum</li>
            </ul>
        </section>

        <section class="mb-6">
            <h2 class="mb-2 font-semibold">Encontrados na estante</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="l in resultado.encontrados" :key="l.id">{{ l.codigo }} — {{ l.titulo }}</li>
                <li v-if="!resultado.encontrados?.length" class="text-stone-500">Nenhum</li>
            </ul>
        </section>

        <section v-if="resultado.codigos_invalidos?.length">
            <h2 class="mb-2 font-semibold">Códigos sem cadastro</h2>
            <ul class="space-y-1 text-sm">
                <li v-for="i in resultado.codigos_invalidos" :key="i.id">{{ i.codigo_informado }}</li>
            </ul>
        </section>
    </AppLayout>
</template>
