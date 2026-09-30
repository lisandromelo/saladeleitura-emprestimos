<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function index(): Response
    {
        $areas = Area::query()
            ->withCount('associados')
            ->orderBy('nome')
            ->get()
            ->map(fn (Area $area) => [
                ...$area->only('id', 'nome', 'ativo'),
                'associados_count' => $area->associados_count,
                'pode_excluir' => $area->associados_count === 0,
            ]);

        return Inertia::render('Cadastros/Areas', [
            'areas' => $areas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120', 'unique:areas,nome'],
        ]);

        Area::query()->create(['nome' => $data['nome'], 'ativo' => true]);

        return back()->with('success', 'Área cadastrada.');
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120', Rule::unique('areas', 'nome')->ignore($area->id)],
            'ativo' => ['required', 'boolean'],
        ]);

        $area->update($data);

        return back()->with('success', 'Área atualizada.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        if ($area->associados()->exists()) {
            return back()->with('error', 'Área em uso por associados e não pode ser excluída.');
        }

        $area->delete();

        return back()->with('success', 'Área excluída.');
    }
}
