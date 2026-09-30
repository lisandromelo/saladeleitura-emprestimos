<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('classificacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('livros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->string('autor_medium')->nullable();
            $table->string('espirito')->nullable();
            $table->foreignId('classificacao_id')->nullable()->constrained('classificacoes')->nullOnDelete();
            $table->date('data_entrada')->nullable();
            $table->string('forma_entrada')->nullable();
            $table->string('situacao', 20)->default('DISPONIVEL'); // DISPONIVEL | EMPRESTADO | BAIXADO
            $table->date('data_baixa')->nullable();
            $table->string('motivo_baixa')->nullable();
            $table->text('observacao')->nullable();
            $table->foreignId('usuario_cadastro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('titulo');
            $table->index('situacao');
            $table->index('autor_medium');
            $table->index('espirito');
        });

        Schema::create('associados', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('telefone')->nullable();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->text('observacao')->nullable();
            $table->foreignId('usuario_cadastro_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('nome');
        });

        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained('livros')->restrictOnDelete();
            $table->foreignId('associado_id')->constrained('associados')->restrictOnDelete();
            $table->dateTime('data_retirada');
            $table->dateTime('data_devolucao')->nullable();
            $table->foreignId('usuario_retirada_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('usuario_devolucao_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('data_retirada');
            $table->index('data_devolucao');
        });

        Schema::create('comunicacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('associado_id')->constrained('associados')->restrictOnDelete();
            $table->foreignId('emprestimo_id')->nullable()->constrained('emprestimos')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('data_hora');
            $table->string('meio', 40);
            $table->string('assunto');
            $table->text('mensagem');
            $table->string('resultado')->nullable();
            $table->timestamps();
        });

        Schema::create('balancos', function (Blueprint $table) {
            $table->id();
            $table->dateTime('data_hora');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('balanco_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balanco_id')->constrained('balancos')->cascadeOnDelete();
            $table->foreignId('livro_id')->nullable()->constrained('livros')->nullOnDelete();
            $table->string('codigo_informado');
            $table->boolean('encontrado')->default(true);
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('movimentacoes_acervo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained('livros')->restrictOnDelete();
            $table->dateTime('data_hora');
            $table->string('tipo', 20); // ENTRADA | BAIXA
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['livro_id', 'data_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_acervo');
        Schema::dropIfExists('balanco_itens');
        Schema::dropIfExists('balancos');
        Schema::dropIfExists('comunicacoes');
        Schema::dropIfExists('emprestimos');
        Schema::dropIfExists('associados');
        Schema::dropIfExists('livros');
        Schema::dropIfExists('classificacoes');
        Schema::dropIfExists('areas');
    }
};
