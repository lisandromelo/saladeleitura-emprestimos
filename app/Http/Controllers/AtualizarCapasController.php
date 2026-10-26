<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Services\CapaLivroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AtualizarCapasController extends Controller
{
    public function index(): Response
    {
        $semCapa = Livro::query()->whereNull('capa')->where('situacao', '!=', 'BAIXADO');

        return Inertia::render('Livros/AtualizarCapas', [
            'stats' => [
                'sem_capa' => (clone $semCapa)->count(),
                'pendentes_busca' => (clone $semCapa)->whereNull('capa_busca_falhou_em')->count(),
                'busca_sem_sucesso' => (clone $semCapa)->whereNotNull('capa_busca_falhou_em')->count(),
                'com_capa' => Livro::query()->whereNotNull('capa')->count(),
            ],
            'ultimo_resultado' => session('capas_resultado'),
        ]);
    }

    public function processar(Request $request, CapaLivroService $service): RedirectResponse
    {
        $data = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        set_time_limit(300);

        $resultado = $service->atualizarLote((int) $data['quantidade']);

        $mensagem = sprintf(
            'Processados: %d · Capas encontradas: %d · Sem capa: %d · Erros: %d',
            $resultado['processados'],
            $resultado['encontradas'],
            $resultado['nao_encontradas'],
            $resultado['erros'],
        );

        $redirect = redirect()->route('livros.capas.atualizar')->with('capas_resultado', $resultado);

        if ($resultado['quota_excedida']) {
            return $redirect
                ->with('warning', ($resultado['quota_mensagem'] ?? 'Quota da API excedida.').' '.$mensagem);
        }

        if ($resultado['google_indisponivel']) {
            return $redirect
                ->with('success', $mensagem)
                ->with('warning', 'Google Books está sem cota diária. A busca continuou pela Open Library e por capa de ISBN.');
        }

        return $redirect->with('success', $mensagem);
    }

    public function limparFalhas(): RedirectResponse
    {
        $afetados = Livro::query()
            ->whereNull('capa')
            ->whereNotNull('capa_busca_falhou_em')
            ->update(['capa_busca_falhou_em' => null]);

        return back()->with('success', "Marcação de busca sem sucesso removida de {$afetados} livro(s). Eles voltarão à fila.");
    }

    public function aplicar(Request $request, Livro $livro, CapaLivroService $service): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        if ($livro->isBaixado()) {
            return back()->with('error', 'Livro baixado não recebe capa por esta busca.');
        }

        try {
            $path = $service->importarDeUrl($data['url']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $livro->update([
            'capa' => $path,
            'capa_busca_falhou_em' => null,
        ]);

        return back()->with('success', 'Capa escolhida aplicada a '.$livro->titulo.'.');
    }

    public function remover(Livro $livro): RedirectResponse
    {
        if (! $livro->capa) {
            return back()->with('error', 'Este livro já está sem capa.');
        }

        \Illuminate\Support\Facades\Storage::disk('public')->delete($livro->capa);
        $livro->update([
            'capa' => null,
            'capa_busca_falhou_em' => null,
        ]);

        return back()->with('success', 'Capa removida. Você pode buscar outra ou enviar uma imagem na edição do livro.');
    }

    public function descartar(Livro $livro): RedirectResponse
    {
        if ($livro->capa) {
            return back()->with('error', 'Este livro já tem capa.');
        }

        $livro->update(['capa_busca_falhou_em' => now()]);

        return back()->with('success', 'Nenhuma alternativa serviu. '.$livro->titulo.' saiu da fila.');
    }
}
