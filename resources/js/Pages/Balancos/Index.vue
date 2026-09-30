<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    balancos: Object,
});

function fmt(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex items-center justify-between gap-3">
            <h1 class="text-2xl text-brand">Balanços</h1>
            <Link href="/balancos/novo" class="rounded-md bg-brand px-3 py-2 text-sm text-white">Novo balanço</Link>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white/70">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Data</th>
                        <th class="px-3 py-2">Responsável</th>
                        <th class="px-3 py-2">Códigos</th>
                        <th class="px-3 py-2">Observação</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in balancos.data" :key="b.id" class="border-t border-stone-100">
                        <td class="px-3 py-2">
                            <Link :href="`/balancos/${b.id}`" class="text-brand hover:underline">{{ fmt(b.data_hora) }}</Link>
                        </td>
                        <td class="px-3 py-2">{{ b.usuario?.name }}</td>
                        <td class="px-3 py-2">{{ b.itens_count ?? '—' }}</td>
                        <td class="px-3 py-2">{{ b.observacao || '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <Link :href="`/balancos/${b.id}/editar`" class="text-brand hover:underline">Continuar / editar</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
