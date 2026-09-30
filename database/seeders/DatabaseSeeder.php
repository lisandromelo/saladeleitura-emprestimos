<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Classificacao;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@saladeleitura.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'perfil' => 'ADMIN',
                'ativo' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operador@saladeleitura.test'],
            [
                'name' => 'Operador',
                'password' => 'password',
                'perfil' => 'USUARIO',
                'ativo' => true,
            ],
        );

        foreach (['Infantil', 'Juvenil', 'Adulto', 'Geral'] as $nome) {
            Area::query()->firstOrCreate(['nome' => $nome], ['ativo' => true]);
        }

        foreach (['Doutrina', 'Romance', 'Infantil', 'História', 'Outros'] as $nome) {
            Classificacao::query()->firstOrCreate(['nome' => $nome], ['ativo' => true]);
        }
    }
}
