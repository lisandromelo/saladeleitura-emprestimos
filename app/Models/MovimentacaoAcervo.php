<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoAcervo extends Model
{
    protected $table = 'movimentacoes_acervo';

    protected $fillable = [
        'livro_id',
        'data_hora',
        'tipo',
        'usuario_id',
        'motivo',
        'observacao',
    ];

    protected function casts(): array
    {
        return ['data_hora' => 'datetime'];
    }

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
