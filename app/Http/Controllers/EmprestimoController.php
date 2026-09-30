<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Associado;
use App\Models\Emprestimo;
use App\Models\Livro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmprestimoController extends Controller
{
    public function index(Request $request): Response
    {
        $abertos = Emprestimo::query()
            ->with(['livro', 'associado'])
            ->aberto()
            ->orderByDesc('data_retirada')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Emprestimos/Index', [
            'emprestimos' => $abertos,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Emprestimos/Create', $this->createProps($request));
    }

    public function buscar(Request $request): Response
    {
        $tipo = $request->string('tipo')->toString();
        $q = trim((string) $request->string('q'));

        if ($tipo === 'livro') {
            $itens = Livro::query()
                ->with('classificacao')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('codigo', 'like', "%{$q}%")
                            ->orWhere('titulo', 'like', "%{$q}%");
                    });
                })
                ->where('situacao', 'DISPONIVEL')
                ->orderBy('titulo')
                ->limit(20)
                ->get();
        } else {
            $itens = Associado::query()
                ->with('area')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('nome', 'like', "%{$q}%")
                            ->orWhere('telefone', 'like', "%{$q}%");
                    });
                })
                ->where('ativo', true)
                ->orderBy('nome')
                ->limit(20)
                ->get();
        }

        return Inertia::render('Emprestimos/Create', [
            ...$this->createProps($request),
            'busca' => [
                'tipo' => $tipo,
                'q' => $q,
                'itens' => $itens,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createProps(Request $request): array
    {
        $associadoId = $request->integer('associado_id') ?: null;
        $livroId = $request->integer('livro_id') ?: null;

        $associadoSelecionado = $associadoId
            ? Associado::query()->where('ativo', true)->find($associadoId)
            : null;

        $livroSelecionado = $livroId
            ? Livro::query()->where('situacao', 'DISPONIVEL')->find($livroId)
            : null;

        return [
            'areas' => Area::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'associadoSelecionado' => $associadoSelecionado,
            'livroSelecionado' => $livroSelecionado,
            'livroCodigo' => $request->string('codigo')->toString(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'livro_id' => ['required', 'exists:livros,id'],
            'associado_id' => ['required', 'exists:associados,id'],
            'observacao' => ['nullable', 'string'],
        ]);

        $associado = Associado::query()->findOrFail($data['associado_id']);

        if (! $associado->ativo) {
            return back()->with('error', 'Associado inativo não pode receber empréstimo.');
        }

        try {
            DB::transaction(function () use ($data, $request) {
                $livro = Livro::query()->lockForUpdate()->findOrFail($data['livro_id']);

                if (! $livro->isDisponivel()) {
                    throw new \RuntimeException('Livro não está disponível.');
                }

                $aberto = Emprestimo::query()
                    ->where('livro_id', $livro->id)
                    ->aberto()
                    ->exists();

                if ($aberto) {
                    throw new \RuntimeException('Este livro já possui empréstimo em aberto.');
                }

                Emprestimo::query()->create([
                    'livro_id' => $livro->id,
                    'associado_id' => $data['associado_id'],
                    'data_retirada' => now(),
                    'usuario_retirada_id' => $request->user()->id,
                    'observacao' => $data['observacao'] ?? null,
                ]);

                $livro->update(['situacao' => 'EMPRESTADO']);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('emprestimos.index')->with('success', 'Empréstimo registrado.');
    }

    public function devolver(Request $request, Emprestimo $emprestimo): RedirectResponse
    {
        if (! $emprestimo->isAberto()) {
            return back()->with('error', 'Este empréstimo não está em aberto.');
        }

        DB::transaction(function () use ($emprestimo, $request) {
            $emprestimo->update([
                'data_devolucao' => now(),
                'usuario_devolucao_id' => $request->user()->id,
            ]);

            $emprestimo->livro->update(['situacao' => 'DISPONIVEL']);
        });

        return back()->with('success', 'Devolução registrada.');
    }

    public function cancelar(Request $request, Emprestimo $emprestimo): RedirectResponse
    {
        $data = $request->validate([
            'motivo_cancelamento' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $emprestimo->isAberto()) {
            return back()->with('error', 'Só é possível cancelar empréstimos em aberto.');
        }

        DB::transaction(function () use ($emprestimo, $request, $data) {
            $emprestimo->update([
                'cancelado_em' => now(),
                'usuario_cancelamento_id' => $request->user()->id,
                'motivo_cancelamento' => $data['motivo_cancelamento'] ?: 'Erro de lançamento',
            ]);

            $emprestimo->livro->update(['situacao' => 'DISPONIVEL']);
        });

        return back()->with('success', 'Empréstimo cancelado. O livro voltou a ficar disponível.');
    }
}
