<?php

namespace App\Http\Controllers;

use App\Models\Associado;
use App\Models\Comunicacao;
use App\Models\Emprestimo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComunicacaoController extends Controller
{
    public function create(Request $request): Response
    {
        $emprestimoId = $request->integer('emprestimo_id') ?: null;
        $emprestimo = $emprestimoId
            ? Emprestimo::query()->with(['associado', 'livro'])->find($emprestimoId)
            : null;

        return Inertia::render('Comunicacoes/Create', [
            'emprestimo' => $emprestimo,
            'associados' => Associado::query()->where('ativo', true)->orderBy('nome')->limit(100)->get(['id', 'nome', 'telefone']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'associado_id' => ['required', 'exists:associados,id'],
            'emprestimo_id' => ['nullable', 'exists:emprestimos,id'],
            'meio' => ['required', 'string', 'max:40'],
            'assunto' => ['required', 'string', 'max:255'],
            'mensagem' => ['required', 'string'],
            'resultado' => ['nullable', 'string', 'max:255'],
        ]);

        Comunicacao::query()->create([
            ...$data,
            'usuario_id' => $request->user()->id,
            'data_hora' => now(),
        ]);

        return redirect()->route('atrasos.index')->with('success', 'Comunicação registrada.');
    }
}
