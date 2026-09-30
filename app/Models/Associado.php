<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Associado extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'area_id',
        'ativo',
        'observacao',
        'usuario_cadastro_id',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function usuarioCadastro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cadastro_id');
    }

    public function emprestimos(): HasMany
    {
        return $this->hasMany(Emprestimo::class);
    }

    public function comunicacoes(): HasMany
    {
        return $this->hasMany(Comunicacao::class);
    }
}
