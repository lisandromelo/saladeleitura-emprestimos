<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emprestimo extends Model
{
    protected $fillable = [
        'livro_id',
        'associado_id',
        'data_retirada',
        'data_devolucao',
        'usuario_retirada_id',
        'usuario_devolucao_id',
        'observacao',
        'cancelado_em',
        'usuario_cancelamento_id',
        'motivo_cancelamento',
    ];

    protected function casts(): array
    {
        return [
            'data_retirada' => 'datetime',
            'data_devolucao' => 'datetime',
            'cancelado_em' => 'datetime',
        ];
    }

    public function livro(): BelongsTo
    {
        return $this->belongsTo(Livro::class);
    }

    public function associado(): BelongsTo
    {
        return $this->belongsTo(Associado::class);
    }

    public function usuarioRetirada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_retirada_id');
    }

    public function usuarioDevolucao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_devolucao_id');
    }

    public function usuarioCancelamento(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cancelamento_id');
    }

    public function comunicacoes(): HasMany
    {
        return $this->hasMany(Comunicacao::class);
    }

    public function scopeAberto(Builder $query): Builder
    {
        return $query->whereNull('data_devolucao')->whereNull('cancelado_em');
    }

    public function isAberto(): bool
    {
        return $this->data_devolucao === null && $this->cancelado_em === null;
    }

    public function isCancelado(): bool
    {
        return $this->cancelado_em !== null;
    }

    public function diasEmAberto(): int
    {
        return (int) $this->data_retirada->startOfDay()->diffInDays(now()->startOfDay());
    }
}
