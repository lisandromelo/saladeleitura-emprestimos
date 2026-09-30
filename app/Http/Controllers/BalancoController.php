<?php

namespace App\Http\Controllers;

use App\Models\Balanco;
use App\Models\BalancoItem;
use App\Models\Livro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BalancoController extends Controller
{
    public function index(): Response
    {
        $balancos = Balanco::query()
            ->with('usuario')
            ->withCount('itens')
            ->orderByDesc('data_hora')
            ->paginate(20);

        return Inertia::render('Balancos/Index', [
            'balancos' => $balancos,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Balancos/Form', [
            'balanco' => null,
            'codigosExistentes' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigos' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
        ]);

        $codigos = $this->parseCodigos($data['codigos'] ?? '');

        $balanco = DB::transaction(function () use ($codigos, $data, $request) {
            $balanco = Balanco::query()->create([
                'data_hora' => now(),
                'usuario_id' => $request->user()->id,
                'observacao' => $data['observacao'] ?? null,
            ]);

            $this->adicionarItens($balanco, $codigos);

            return $balanco;
        });

        return redirect()
            ->route('balancos.edit', $balanco)
            ->with('success', $codigos->isEmpty()
                ? 'Balanço iniciado. Você pode continuar lançando códigos depois.'
                : 'Balanço registrado. Você pode continuar lançando códigos.');
    }

    public function edit(Balanco $balanco): Response
    {
        $balanco->load(['usuario', 'itens.livro']);

        $codigosExistentes = $balanco->itens
            ->sortBy('codigo_informado')
            ->values()
            ->map(fn (BalancoItem $item) => [
                'id' => $item->id,
                'codigo' => $item->codigo_informado,
                'titulo' => $item->livro?->titulo,
                'valido' => $item->livro_id !== null,
            ]);

        return Inertia::render('Balancos/Form', [
            'balanco' => $balanco->only('id', 'data_hora', 'observacao', 'usuario_id'),
            'responsavel' => $balanco->usuario?->only('id', 'name'),
            'codigosExistentes' => $codigosExistentes,
        ]);
    }

    public function update(Request $request, Balanco $balanco): RedirectResponse
    {
        $data = $request->validate([
            'codigos' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
        ]);

        $codigos = $this->parseCodigos($data['codigos'] ?? '');

        $jaLancados = $balanco->itens()->pluck('codigo_informado')
            ->map(fn ($c) => (string) $c)
            ->all();

        $novos = $codigos->reject(fn ($c) => in_array($c, $jaLancados, true))->values();

        DB::transaction(function () use ($balanco, $data, $novos) {
            $balanco->update([
                'observacao' => $data['observacao'] ?? null,
            ]);

            $this->adicionarItens($balanco, $novos);
        });

        $msg = $novos->isEmpty()
            ? 'Balanço atualizado.'
            : 'Balanço atualizado: '.$novos->count().' código(s) adicionado(s).';

        return redirect()
            ->route('balancos.edit', $balanco)
            ->with('success', $msg);
    }

    public function destroyItem(Balanco $balanco, BalancoItem $item): RedirectResponse
    {
        abort_unless($item->balanco_id === $balanco->id, 404);

        $item->delete();

        return back()->with('success', 'Código removido do balanço.');
    }

    public function show(Balanco $balanco): Response
    {
        $balanco->load(['usuario', 'itens.livro']);

        $codigosEncontrados = $balanco->itens->pluck('codigo_informado')->filter()->all();

        $acervoEsperado = Livro::query()
            ->where('situacao', '!=', 'BAIXADO')
            ->get(['id', 'codigo', 'titulo', 'situacao']);

        $encontradosIds = $balanco->itens->pluck('livro_id')->filter()->all();

        $resultado = [
            'encontrados' => $acervoEsperado->whereIn('id', $encontradosIds)->where('situacao', 'DISPONIVEL')->values(),
            'emprestados' => $acervoEsperado->where('situacao', 'EMPRESTADO')->values(),
            'nao_localizados' => $acervoEsperado
                ->where('situacao', 'DISPONIVEL')
                ->whereNotIn('id', $encontradosIds)
                ->values(),
            'baixados_no_lancamento' => $balanco->itens->filter(fn ($i) => $i->livro?->situacao === 'BAIXADO')->values(),
            'codigos_invalidos' => $balanco->itens->whereNull('livro_id')->values(),
            'codigos_informados' => $codigosEncontrados,
        ];

        return Inertia::render('Balancos/Show', [
            'balanco' => $balanco,
            'resultado' => $resultado,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function parseCodigos(?string $texto)
    {
        return collect(preg_split('/[\s,;]+/', (string) $texto) ?: [])
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $codigos
     */
    private function adicionarItens(Balanco $balanco, $codigos): void
    {
        foreach ($codigos as $codigo) {
            $livro = Livro::query()->where('codigo', $codigo)->first();

            BalancoItem::query()->create([
                'balanco_id' => $balanco->id,
                'livro_id' => $livro?->id,
                'codigo_informado' => $codigo,
                'encontrado' => true,
            ]);
        }
    }
}
