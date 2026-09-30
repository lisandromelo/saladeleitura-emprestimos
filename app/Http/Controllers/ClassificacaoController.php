<?php

namespace App\Http\Controllers;

use App\Models\Classificacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClassificacaoController extends Controller
{
    public function index(): Response
    {
        $classificacoes = Classificacao::query()
            ->withCount('livros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Classificacao $item) => [
                ...$item->only('id', 'nome', 'ativo'),
                'livros_count' => $item->livros_count,
                'pode_excluir' => $item->livros_count === 0,
            ]);

        return Inertia::render('Cadastros/Classificacoes', [
            'classificacoes' => $classificacoes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120', 'unique:classificacoes,nome'],
        ]);

        Classificacao::query()->create(['nome' => $data['nome'], 'ativo' => true]);

        return back()->with('success', 'Classificação cadastrada.');
    }

    public function update(Request $request, Classificacao $classificacao): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120', Rule::unique('classificacoes', 'nome')->ignore($classificacao->id)],
            'ativo' => ['required', 'boolean'],
        ]);

        $classificacao->update($data);

        return back()->with('success', 'Classificação atualizada.');
    }

    public function destroy(Classificacao $classificacao): RedirectResponse
    {
        if ($classificacao->livros()->exists()) {
            return back()->with('error', 'Classificação em uso por livros e não pode ser excluída.');
        }

        $classificacao->delete();

        return back()->with('success', 'Classificação excluída.');
    }
}
