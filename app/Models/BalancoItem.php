<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalancoItem extends Model
{
    protected $table = 'balanco_itens';

    protected $fillable = [
        'balanco_id',
        'livro_id',
        'codigo_informado',
        'encontrado',
        'observacao',
    ];

    protected function casts(): array
    {
        return ['encontrado' => 'boolean'];
    }

    public function balanco(): BelongsTo
    {
        return $this->belongsTo(Balanco::class);
    }

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }
}
