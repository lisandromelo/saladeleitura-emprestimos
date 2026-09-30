<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    emprestimo: Object,
    associados: Array,
});

const form = useForm({
    associado_id: props.emprestimo?.associado_id || props.emprestimo?.associado?.id || '',
    emprestimo_id: props.emprestimo?.id || '',
    meio: 'WhatsApp',
    assunto: 'Lembrete de devolução',
    mensagem: '',
    resultado: '',
});

function submit() {
    form.post('/comunicacoes');
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">Registrar comunicação</h1>

        <p v-if="emprestimo" class="mb-4 text-sm text-stone-600">
            Empréstimo: {{ emprestimo.livro?.codigo }} — {{ emprestimo.livro?.titulo }} · {{ emprestimo.associado?.nome }}
        </p>

        <form class="max-w-xl space-y-3" @submit.prevent="submit">
            <label class="block text-sm">Associado
                <select v-model="form.associado_id" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                    <option value="">—</option>
                    <option v-for="a in associados" :key="a.id" :value="a.id">{{ a.nome }}</option>
                </select>
            </label>
            <label class="block text-sm">Meio
                <select v-model="form.meio" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    <option>WhatsApp</option>
                    <option>Telefone</option>
                    <option>E-mail</option>
                    <option>Outro</option>
                </select>
            </label>
            <label class="block text-sm">Assunto<input v-model="form.assunto" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="block text-sm">Mensagem<textarea v-model="form.mensagem" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" rows="4" required /></label>
            <label class="block text-sm">Resultado<input v-model="form.resultado" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2"></label>
            <button class="rounded-md bg-brand px-4 py-2 text-sm text-white">Salvar</button>
        </form>
    </AppLayout>
</template>
