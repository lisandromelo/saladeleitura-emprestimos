<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classificacao extends Model
{
    protected $table = 'classificacoes';

    protected $fillable = ['nome', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function livros(): HasMany
    {
        return $this->hasMany(Livro::class);
    }
}
