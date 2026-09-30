<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Balanco extends Model
{
    protected $fillable = [
        'data_hora',
        'usuario_id',
        'observacao',
    ];

    protected function casts(): array
    {
        return ['data_hora' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(BalancoItem::class);
    }
}
