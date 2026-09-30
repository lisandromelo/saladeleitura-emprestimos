<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comunicacao extends Model
{
    protected $table = 'comunicacoes';

    protected $fillable = [
        'associado_id',
        'emprestimo_id',
        'usuario_id',
        'data_hora',
        'meio',
        'assunto',
        'mensagem',
        'resultado',
    ];

    protected function casts(): array
    {
        return ['data_hora' => 'datetime'];
    }

    public function associado(): BelongsTo
    {
        return $this->belongsTo(Associado::class);
    }

    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
