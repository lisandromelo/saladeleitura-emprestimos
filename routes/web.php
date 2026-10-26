<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AssociadoController;
use App\Http\Controllers\AtrasoController;
use App\Http\Controllers\AtualizarCapasController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BalancoController;
use App\Http\Controllers\ClassificacaoController;
use App\Http\Controllers\ComunicacaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmprestimoController;
use App\Http\Controllers\LivroController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/livros', [LivroController::class, 'index'])->name('livros.index');
    Route::get('/livros/criar', [LivroController::class, 'create'])->middleware('admin')->name('livros.create');
    Route::post('/livros', [LivroController::class, 'store'])->middleware('admin')->name('livros.store');
    Route::post('/livros/capas/buscar', [LivroController::class, 'buscarCapas'])->middleware('admin')->name('livros.capas.buscar');
    Route::post('/livros/capas/importar', [LivroController::class, 'importarCapa'])->middleware('admin')->name('livros.capas.importar');
    Route::get('/livros/capas/atualizar', [AtualizarCapasController::class, 'index'])->middleware('admin')->name('livros.capas.atualizar');
    Route::post('/livros/capas/atualizar', [AtualizarCapasController::class, 'processar'])->middleware('admin')->name('livros.capas.processar');
    Route::post('/livros/capas/limpar-falhas', [AtualizarCapasController::class, 'limparFalhas'])->middleware('admin')->name('livros.capas.limpar-falhas');
    Route::post('/livros/{livro}/capa', [AtualizarCapasController::class, 'aplicar'])->middleware('admin')->name('livros.capa.aplicar');
    Route::post('/livros/{livro}/capa/remover', [AtualizarCapasController::class, 'remover'])->middleware('admin')->name('livros.capa.remover');
    Route::post('/livros/{livro}/capa/descartar', [AtualizarCapasController::class, 'descartar'])->middleware('admin')->name('livros.capa.descartar');
    Route::get('/livros/{livro}', [LivroController::class, 'show'])->name('livros.show');
    Route::get('/livros/{livro}/editar', [LivroController::class, 'edit'])->middleware('admin')->name('livros.edit');
    Route::put('/livros/{livro}', [LivroController::class, 'update'])->middleware('admin')->name('livros.update');
    Route::post('/livros/{livro}/baixar', [LivroController::class, 'baixar'])->middleware('admin')->name('livros.baixar');
    Route::post('/livros/{livro}/ausencia', [LivroController::class, 'registrarAusencia'])->middleware('admin')->name('livros.ausencia');

    Route::get('/associados', [AssociadoController::class, 'index'])->name('associados.index');
    Route::post('/associados', [AssociadoController::class, 'store'])->name('associados.store');
    Route::get('/associados/{associado}', [AssociadoController::class, 'show'])->name('associados.show');
    Route::put('/associados/{associado}', [AssociadoController::class, 'update'])->name('associados.update');

    Route::get('/emprestimos', [EmprestimoController::class, 'index'])->name('emprestimos.index');
    Route::get('/emprestimos/novo', [EmprestimoController::class, 'create'])->name('emprestimos.create');
    Route::get('/emprestimos/buscar', [EmprestimoController::class, 'buscar'])->name('emprestimos.buscar');
    Route::post('/emprestimos', [EmprestimoController::class, 'store'])->name('emprestimos.store');
    Route::post('/emprestimos/{emprestimo}/devolver', [EmprestimoController::class, 'devolver'])->name('emprestimos.devolver');
    Route::post('/emprestimos/{emprestimo}/cancelar', [EmprestimoController::class, 'cancelar'])->name('emprestimos.cancelar');

    Route::middleware('admin')->group(function () {
        Route::get('/atrasos', [AtrasoController::class, 'index'])->name('atrasos.index');
        Route::get('/comunicacoes/nova', [ComunicacaoController::class, 'create'])->name('comunicacoes.create');
        Route::post('/comunicacoes', [ComunicacaoController::class, 'store'])->name('comunicacoes.store');

        Route::get('/balancos', [BalancoController::class, 'index'])->name('balancos.index');
        Route::get('/balancos/novo', [BalancoController::class, 'create'])->name('balancos.create');
        Route::post('/balancos', [BalancoController::class, 'store'])->name('balancos.store');
        Route::get('/balancos/{balanco}', [BalancoController::class, 'show'])->name('balancos.show');
        Route::get('/balancos/{balanco}/editar', [BalancoController::class, 'edit'])->name('balancos.edit');
        Route::put('/balancos/{balanco}', [BalancoController::class, 'update'])->name('balancos.update');
        Route::delete('/balancos/{balanco}/itens/{item}', [BalancoController::class, 'destroyItem'])->name('balancos.itens.destroy');

        Route::get('/cadastros/areas', [AreaController::class, 'index'])->name('areas.index');
        Route::post('/cadastros/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::put('/cadastros/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::delete('/cadastros/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

        Route::get('/cadastros/classificacoes', [ClassificacaoController::class, 'index'])->name('classificacoes.index');
        Route::post('/cadastros/classificacoes', [ClassificacaoController::class, 'store'])->name('classificacoes.store');
        Route::put('/cadastros/classificacoes/{classificacao}', [ClassificacaoController::class, 'update'])->name('classificacoes.update');
        Route::delete('/cadastros/classificacoes/{classificacao}', [ClassificacaoController::class, 'destroy'])->name('classificacoes.destroy');

        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
    });
});
