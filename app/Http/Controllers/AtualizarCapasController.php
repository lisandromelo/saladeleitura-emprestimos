<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use App\Services\CapaLivroService;
use Illuminate\Http\JsonResponse;
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
}
