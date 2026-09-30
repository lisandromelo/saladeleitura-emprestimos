<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Associado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssociadoController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->string('q'));

        $associados = Associado::query()
            ->with('area')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('nome', 'like', "%{$q}%")
                        ->orWhere('telefone', 'like', "%{$q}%");
                });
            })
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Associados/Index', [
            'associados' => $associados,
            'filters' => ['q' => $q],
            'areas' => Area::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'observacao' => ['nullable', 'string'],
            'ativo' => ['sometimes', 'boolean'],
            'retorno' => ['nullable', 'string', 'in:emprestimo'],
            'livro_id' => ['nullable', 'integer', 'exists:livros,id'],
        ]);

        $retorno = $data['retorno'] ?? null;
        $livroId = $data['livro_id'] ?? null;
        unset($data['retorno'], $data['livro_id']);

        $data['ativo'] = $data['ativo'] ?? true;
        $data['usuario_cadastro_id'] = $request->user()->id;

        $associado = Associado::query()->create($data);

        if ($retorno === 'emprestimo') {
            return redirect()
                ->route('emprestimos.create', array_filter([
                    'associado_id' => $associado->id,
                    'livro_id' => $livroId,
                ]))
                ->with('success', 'Associado cadastrado.');
        }

        return back()->with('success', 'Associado cadastrado.');
    }

    public function update(Request $request, Associado $associado): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'observacao' => ['nullable', 'string'],
            'ativo' => ['required', 'boolean'],
        ]);

        $associado->update($data);

        return back()->with('success', 'Associado atualizado.');
    }

    public function show(Request $request, Associado $associado): Response
    {
        $associado->load('area');

        $emprestimos = $associado->emprestimos()
            ->with('livro')
            ->orderByDesc('data_retirada')
            ->get();

        $comunicacoes = null;
        if ($request->user()->isAdmin()) {
            $comunicacoes = $associado->comunicacoes()
                ->with(['usuario', 'emprestimo.livro'])
                ->orderByDesc('data_hora')
                ->get();
        }

        return Inertia::render('Associados/Show', [
            'associado' => $associado,
            'emprestimos' => $emprestimos,
            'comunicacoes' => $comunicacoes,
            'canSeeComunicacoes' => $request->user()->isAdmin(),
        ]);
    }
}
