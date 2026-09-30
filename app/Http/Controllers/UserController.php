<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'perfil', 'ativo', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'perfil' => ['required', Rule::in(['ADMIN', 'USUARIO'])],
        ]);

        User::query()->create([
            ...$data,
            'ativo' => true,
        ]);

        return back()->with('success', 'Usuário criado.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'perfil' => ['required', Rule::in(['ADMIN', 'USUARIO'])],
            'ativo' => ['required', 'boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $usuario->update($data);

        return back()->with('success', 'Usuário atualizado.');
    }
}
