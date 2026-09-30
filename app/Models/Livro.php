<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Livro extends Model
{
    protected $fillable = [
        'codigo',
        'titulo',
        'autor_medium',
        'espirito',
        'classificacao_id',
        'data_entrada',
        'forma_entrada',
        'situacao',
        'data_baixa',
        'motivo_baixa',
        'observacao',
        'capa',
        'capa_busca_falhou_em',
        'usuario_cadastro_id',
    ];

    protected $appends = ['capa_url'];

    protected function casts(): array
    {
        return [
            'data_entrada' => 'date',
            'data_baixa' => 'date',
            'capa_busca_falhou_em' => 'datetime',
        ];
    }

    protected function capaUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->capa) {
                return null;
            }

            return Storage::disk('public')->url($this->capa);
        });
    }

    public function classificacao(): BelongsTo
    {
        return $this->belongsTo(Classificacao::class);
    }

    public function usuarioCadastro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cadastro_id');
    }

    public function emprestimos(): HasMany
    {
        return $this->hasMany(Emprestimo::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoAcervo::class);
    }

    public function isDisponivel(): bool
    {
        return $this->situacao === 'DISPONIVEL';
    }

    public function isBaixado(): bool
    {
        return $this->situacao === 'BAIXADO';
    }
}
