<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $stats = [
            'livros_ativos' => Livro::query()->where('situacao', '!=', 'BAIXADO')->count(),
            'disponiveis' => Livro::query()->where('situacao', 'DISPONIVEL')->count(),
            'emprestados' => Livro::query()->where('situacao', 'EMPRESTADO')->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
