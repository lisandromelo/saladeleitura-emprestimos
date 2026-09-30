<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <GuestLayout>
        <h1 class="mb-6 text-lg font-semibold">Entrar</h1>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm">
                E-mail
                <input v-model="form.email" type="email" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
                <p v-if="form.errors.email" class="mt-1 text-sm text-warn">{{ form.errors.email }}</p>
            </label>
            <label class="block text-sm">
                Senha
                <input v-model="form.password" type="password" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required>
            </label>
            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input v-model="form.remember" type="checkbox">
                Lembrar-me
            </label>
            <button type="submit" class="w-full rounded-md bg-brand px-3 py-2 text-white hover:bg-brand-dark" :disabled="form.processing">
                Entrar
            </button>
        </form>
    </GuestLayout>
</template>
