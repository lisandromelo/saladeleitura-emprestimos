<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livros', function (Blueprint $table) {
            $table->timestamp('capa_busca_falhou_em')->nullable()->after('capa');
            $table->index('capa_busca_falhou_em');
        });
    }

    public function down(): void
    {
        Schema::table('livros', function (Blueprint $table) {
            $table->dropIndex(['capa_busca_falhou_em']);
            $table->dropColumn('capa_busca_falhou_em');
        });
    }
};
