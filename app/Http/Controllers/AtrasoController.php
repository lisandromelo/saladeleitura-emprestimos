<?php

namespace App\Http\Controllers;

use App\Models\Emprestimo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AtrasoController extends Controller
{
    public function index(Request $request): Response
    {
        $dias = max(0, $request->integer('dias', 60));

        $emprestimos = Emprestimo::query()
            ->with(['livro', 'associado', 'comunicacoes' => fn ($q) => $q->latest('data_hora')->limit(1)])
            ->aberto()
            ->where('data_retirada', '<=', now()->subDays($dias))
            ->orderBy('data_retirada')
            ->get()
            ->map(function (Emprestimo $e) {
                $ultima = $e->comunicacoes->first();

                return [
                    'id' => $e->id,
                    'associado' => $e->associado?->only('id', 'nome', 'telefone'),
                    'livro' => $e->livro?->only('id', 'codigo', 'titulo'),
                    'data_retirada' => $e->data_retirada?->toIso8601String(),
                    'dias_em_aberto' => $e->diasEmAberto(),
                    'ultima_comunicacao' => $ultima ? [
                        'data_hora' => $ultima->data_hora?->toIso8601String(),
                        'meio' => $ultima->meio,
                        'assunto' => $ultima->assunto,
                    ] : null,
                ];
            });

        return Inertia::render('Atrasos/Index', [
            'dias' => $dias,
            'emprestimos' => $emprestimos,
        ]);
    }
}
