<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    usuarios: Array,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    perfil: 'USUARIO',
});

function store() {
    form.post('/usuarios', { onSuccess: () => form.reset('name', 'email', 'password') });
}

function update(u, event) {
    const row = event.target.closest('form');
    router.put(`/usuarios/${u.id}`, {
        name: row.querySelector('[name=name]').value,
        email: row.querySelector('[name=email]').value,
        password: row.querySelector('[name=password]').value || null,
        perfil: row.querySelector('[name=perfil]').value,
        ativo: row.querySelector('[name=ativo]').checked,
    });
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-2xl text-brand">Usuários / Operadores</h1>

        <form class="mb-8 grid max-w-xl gap-3 rounded-lg border border-stone-200 bg-white/70 p-4" @submit.prevent="store">
            <h2 class="font-semibold">Novo usuário</h2>
            <label class="text-sm">Nome<input v-model="form.name" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="text-sm">E-mail<input v-model="form.email" type="email" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="text-sm">Senha<input v-model="form.password" type="password" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2" required></label>
            <label class="text-sm">Perfil
                <select v-model="form.perfil" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-3 py-2">
                    <option value="USUARIO">USUARIO</option>
                    <option value="ADMIN">ADMIN</option>
                </select>
            </label>
            <button class="rounded-md bg-brand px-3 py-2 text-sm text-white">Criar</button>
        </form>

        <ul class="space-y-4">
            <li v-for="u in usuarios" :key="u.id">
                <form class="grid gap-2 rounded-lg border border-stone-200 bg-white/70 p-4 md:grid-cols-2" @submit.prevent="update(u, $event)">
                    <label class="text-sm">Nome<input name="name" :value="u.name" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-2 py-1"></label>
                    <label class="text-sm">E-mail<input name="email" :value="u.email" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-2 py-1"></label>
                    <label class="text-sm">Nova senha (opcional)<input name="password" type="password" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-2 py-1"></label>
                    <label class="text-sm">Perfil
                        <select name="perfil" :value="u.perfil" class="mt-1 w-full rounded-md border border-stone-300 bg-white px-2 py-1">
                            <option value="USUARIO">USUARIO</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input name="ativo" type="checkbox" :checked="u.ativo">
                        Ativo
                    </label>
                    <div>
                        <button class="rounded-md border border-stone-200 bg-white px-3 py-1 text-sm">Salvar</button>
                    </div>
                </form>
            </li>
        </ul>
    </AppLayout>
</template>
