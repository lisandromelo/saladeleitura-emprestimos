<?php

namespace App\Http\Controllers;

use App\Models\Classificacao;
use App\Models\Livro;
use App\Models\MovimentacaoAcervo;
use App\Services\CapaLivroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LivroController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->string('q'));
        $situacao = $request->string('situacao')->toString();
        $incluirBaixados = $request->boolean('incluir_baixados');

        $livros = Livro::query()
            ->with('classificacao')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('codigo', 'like', "%{$q}%")
                        ->orWhere('titulo', 'like', "%{$q}%")
                        ->orWhere('autor_medium', 'like', "%{$q}%")
                        ->orWhere('espirito', 'like', "%{$q}%")
                        ->orWhereHas('classificacao', fn ($c) => $c->where('nome', 'like', "%{$q}%"));
                });
            })
            ->when($situacao !== '', fn ($query) => $query->where('situacao', $situacao))
            ->when(! $incluirBaixados && $situacao === '', fn ($query) => $query->where('situacao', '!=', 'BAIXADO'))
            ->orderBy('titulo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Livros/Index', [
            'livros' => $livros,
            'filters' => [
                'q' => $q,
                'situacao' => $situacao,
                'incluir_baixados' => $incluirBaixados,
            ],
            'classificacoes' => Classificacao::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'canManage' => $request->user()->isAdmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Livros/Form', [
            'livro' => null,
            'classificacoes' => Classificacao::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request);
        unset($data['capa'], $data['remover_capa'], $data['confirmar_duplicado'], $data['capa_importada']);

        $data['situacao'] = 'DISPONIVEL';
        $data['usuario_cadastro_id'] = $request->user()->id;
        $data['data_entrada'] = $data['data_entrada'] ?? now()->toDateString();
        $data['forma_entrada'] = $data['forma_entrada'] ?? 'DOAÇÃO';

        $tituloExiste = Livro::query()
            ->whereRaw('LOWER(TRIM(titulo)) = ?', [mb_strtolower(trim($data['titulo']))])
            ->exists();

        if ($tituloExiste && ! $request->boolean('confirmar_duplicado')) {
            return back()
                ->withInput()
                ->with('warning', 'Já existe livro com este título. Confirme para cadastrar mesmo assim.')
                ->withErrors(['titulo' => 'Título já cadastrado. Marque a confirmação para prosseguir.']);
        }

        DB::transaction(function () use ($data, $request) {
            if ($request->hasFile('capa')) {
                $data['capa'] = $request->file('capa')->store('capas', 'public');
                $data['capa_busca_falhou_em'] = null;
            } elseif ($path = $this->capaImportadaValida($request->input('capa_importada'))) {
                $data['capa'] = $path;
                $data['capa_busca_falhou_em'] = null;
            }

            $livro = Livro::query()->create($data);

            MovimentacaoAcervo::query()->create([
                'livro_id' => $livro->id,
                'data_hora' => now(),
                'tipo' => 'ENTRADA',
                'usuario_id' => $request->user()->id,
                'observacao' => $data['forma_entrada'] ?? null,
            ]);
        });

        return redirect()->route('livros.index')->with('success', 'Livro cadastrado.');
    }

    public function buscarCapas(Request $request, CapaLivroService $service): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'autor' => ['nullable', 'string', 'max:255'],
        ]);

        $resultados = $service->buscar($data['titulo'], $data['autor'] ?? null);

        if ($service->quotaFoiExcedida()) {
            return response()->json([
                'resultados' => $resultados,
                'total' => count($resultados),
                'quota_excedida' => true,
                'message' => $service->mensagemQuota(),
            ], 429);
        }

        return response()->json([
            'resultados' => $resultados,
            'total' => count($resultados),
            'quota_excedida' => false,
        ]);
    }

    public function importarCapa(Request $request, CapaLivroService $service): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $path = $service->importarDeUrl($data['url']);
        } catch (\Throwable $e) {
            $status = $service->quotaFoiExcedida() ? 429 : 422;

            return response()->json([
                'message' => $e->getMessage(),
                'quota_excedida' => $service->quotaFoiExcedida(),
            ], $status);
        }

        return response()->json([
            'capa' => $path,
            'capa_url' => Storage::disk('public')->url($path),
        ]);
    }

    public function show(Livro $livro): Response
    {
        $livro->load(['classificacao', 'usuarioCadastro']);

        $historico = $this->historico($livro);

        return Inertia::render('Livros/Show', [
            'livro' => $livro,
            'historico' => $historico,
            'canManage' => request()->user()->isAdmin(),
        ]);
    }

    public function edit(Request $request, Livro $livro): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        return Inertia::render('Livros/Form', [
            'livro' => $livro->load('classificacao'),
            'classificacoes' => Classificacao::query()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function update(Request $request, Livro $livro): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validated($request, $livro->id);
        unset($data['situacao'], $data['data_baixa'], $data['motivo_baixa'], $data['capa'], $data['remover_capa'], $data['confirmar_duplicado'], $data['capa_importada']);

        if ($request->boolean('remover_capa') && $livro->capa) {
            Storage::disk('public')->delete($livro->capa);
            $data['capa'] = null;
        }

        if ($request->hasFile('capa')) {
            if ($livro->capa) {
                Storage::disk('public')->delete($livro->capa);
            }
            $data['capa'] = $request->file('capa')->store('capas', 'public');
            $data['capa_busca_falhou_em'] = null;
        } elseif ($path = $this->capaImportadaValida($request->input('capa_importada'))) {
            if ($livro->capa && $livro->capa !== $path) {
                Storage::disk('public')->delete($livro->capa);
            }
            $data['capa'] = $path;
            $data['capa_busca_falhou_em'] = null;
        }

        $livro->update($data);

        return redirect()->route('livros.show', $livro)->with('success', 'Livro atualizado.');
    }

    public function baixar(Request $request, Livro $livro): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($livro->situacao === 'BAIXADO') {
            return back()->with('error', 'Livro já está baixado.');
        }

        if ($livro->situacao === 'EMPRESTADO') {
            return back()->with('error', 'Devolva o livro antes de baixá-lo.');
        }

        $data = $request->validate([
            'motivo_baixa' => ['required', 'string', 'max:120'],
            'observacao' => ['nullable', 'string'],
            'data_baixa' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($livro, $data, $request) {
            $livro->update([
                'situacao' => 'BAIXADO',
                'data_baixa' => $data['data_baixa'] ?? now()->toDateString(),
                'motivo_baixa' => $data['motivo_baixa'],
                'observacao' => trim(($livro->observacao ? $livro->observacao."\n" : '').($data['observacao'] ?? '')),
            ]);

            MovimentacaoAcervo::query()->create([
                'livro_id' => $livro->id,
                'data_hora' => now(),
                'tipo' => 'BAIXA',
                'usuario_id' => $request->user()->id,
                'motivo' => $data['motivo_baixa'],
                'observacao' => $data['observacao'] ?? null,
            ]);
        });

        return redirect()->route('livros.show', $livro)->with('success', 'Baixa registrada.');
    }

    public function registrarAusencia(Request $request, Livro $livro): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'data_balanco' => ['required', 'date'],
        ]);

        $texto = 'Não localizado no balanço de '.date('d/m/Y', strtotime($data['data_balanco']));
        $livro->update([
            'observacao' => trim(($livro->observacao ? $livro->observacao."\n" : '').$texto),
        ]);

        return back()->with('success', 'Ausência registrada na observação do livro.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:50', Rule::unique('livros', 'codigo')->ignore($ignoreId)],
            'titulo' => ['required', 'string', 'max:255'],
            'autor_medium' => ['nullable', 'string', 'max:255'],
            'espirito' => ['nullable', 'string', 'max:255'],
            'classificacao_id' => ['nullable', 'exists:classificacoes,id'],
            'data_entrada' => ['nullable', 'date'],
            'forma_entrada' => ['nullable', 'string', 'max:80'],
            'observacao' => ['nullable', 'string'],
            'capa' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'capa_importada' => ['nullable', 'string', 'max:255'],
            'remover_capa' => ['sometimes', 'boolean'],
            'confirmar_duplicado' => ['sometimes', 'boolean'],
        ], [
            'codigo.unique' => 'Já existe um livro com este código.',
            'capa.image' => 'A capa deve ser uma imagem.',
            'capa.max' => 'A capa deve ter no máximo 2 MB.',
        ]);
    }

    private function capaImportadaValida(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! str_starts_with($path, 'capas/') || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    private function historico(Livro $livro): array
    {
        $eventos = [];

        foreach ($livro->movimentacoes()->with('usuario')->orderBy('data_hora')->get() as $mov) {
            $eventos[] = [
                'data' => $mov->data_hora?->toIso8601String(),
                'evento' => $mov->tipo === 'ENTRADA' ? 'Entrada' : 'Baixa',
                'operador' => $mov->usuario?->name,
                'observacao' => trim(($mov->motivo ? $mov->motivo.' — ' : '').($mov->observacao ?? '')),
            ];
        }

        foreach ($livro->emprestimos()->with(['associado', 'usuarioRetirada', 'usuarioDevolucao', 'usuarioCancelamento'])->orderBy('data_retirada')->get() as $emp) {
            $eventos[] = [
                'data' => $emp->data_retirada?->toIso8601String(),
                'evento' => 'Empréstimo',
                'operador' => $emp->usuarioRetirada?->name,
                'observacao' => 'Associado: '.($emp->associado?->nome ?? '—'),
            ];

            if ($emp->isCancelado()) {
                $eventos[] = [
                    'data' => $emp->cancelado_em?->toIso8601String(),
                    'evento' => 'Cancelamento',
                    'operador' => $emp->usuarioCancelamento?->name,
                    'observacao' => $emp->motivo_cancelamento ?? 'Erro de lançamento',
                ];
            } elseif ($emp->data_devolucao) {
                $eventos[] = [
                    'data' => $emp->data_devolucao->toIso8601String(),
                    'evento' => 'Devolução',
                    'operador' => $emp->usuarioDevolucao?->name,
                    'observacao' => '',
                ];
            }
        }

        usort($eventos, fn ($a, $b) => strcmp($a['data'] ?? '', $b['data'] ?? ''));

        return $eventos;
    }
}
