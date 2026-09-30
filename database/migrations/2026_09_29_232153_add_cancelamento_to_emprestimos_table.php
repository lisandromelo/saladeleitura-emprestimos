<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dateTime('cancelado_em')->nullable()->after('observacao');
            $table->foreignId('usuario_cancelamento_id')->nullable()->after('cancelado_em')->constrained('users')->nullOnDelete();
            $table->string('motivo_cancelamento')->nullable()->after('usuario_cancelamento_id');
            $table->index('cancelado_em');
        });
    }

    public function down(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_cancelamento_id');
            $table->dropIndex(['cancelado_em']);
            $table->dropColumn(['cancelado_em', 'motivo_cancelamento']);
        });
    }
};
