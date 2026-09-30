<?php

namespace App\Console\Commands;

use App\Models\Classificacao;
use App\Models\Livro;
use App\Models\MovimentacaoAcervo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarCatalogoCommand extends Command
{
    protected $signature = 'acervo:importar-catalogo
                            {arquivo? : Caminho do .ods/.xlsx (padrão: storage/app/catalogo.ods)}
                            {--dry-run : Apenas valida e mostra o resumo, sem gravar}';

    protected $description = 'Importa o catálogo de livros a partir da planilha da Sala de Leitura';

    public function handle(): int
    {
        $path = $this->argument('arquivo') ?: storage_path('app/catalogo.ods');

        if (! is_file($path)) {
            $this->error("Arquivo não encontrado: {$path}");

            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Catálogo') ?? $spreadsheet->getSheet(0);

        $admin = User::query()->where('perfil', 'ADMIN')->orderBy('id')->first();
        $hoje = now()->toDateString();
        $dryRun = (bool) $this->option('dry-run');

        $criados = 0;
        $atualizados = 0;
        $ignorados = 0;
        $classificacoesUsadas = [];

        DB::beginTransaction();

        try {
            for ($row = 2; $row <= (int) $sheet->getHighestDataRow(); $row++) {
                $codigo = trim((string) $sheet->getCell([1, $row])->getFormattedValue());
                $titulo = trim((string) $sheet->getCell([2, $row])->getFormattedValue());
                $autor = $this->nullable(trim((string) $sheet->getCell([3, $row])->getFormattedValue()));
                $espirito = $this->nullable(trim((string) $sheet->getCell([4, $row])->getFormattedValue()));
                $classificacaoNome = trim((string) $sheet->getCell([5, $row])->getFormattedValue());

                if ($codigo === '' && $titulo === '') {
                    continue;
                }

                if ($codigo === '' || $titulo === '') {
                    $this->warn("Linha {$row}: código ou título vazio — ignorada.");
                    $ignorados++;

                    continue;
                }

                $classificacaoId = null;
                if ($classificacaoNome !== '') {
                    $nomeNormalizado = $this->normalizarClassificacao($classificacaoNome);
                    $classificacoesUsadas[$nomeNormalizado] = true;

                    if (! $dryRun) {
                        $classificacao = Classificacao::query()->firstOrCreate(
                            ['nome' => $nomeNormalizado],
                            ['ativo' => true],
                        );
                        $classificacaoId = $classificacao->id;
                    }
                }

                if ($dryRun) {
                    $existe = Livro::query()->where('codigo', $codigo)->exists();
                    $existe ? $atualizados++ : $criados++;

                    continue;
                }

                $livro = Livro::query()->where('codigo', $codigo)->first();

                $payload = [
                    'titulo' => $titulo,
                    'autor_medium' => $autor,
                    'espirito' => $espirito,
                    'classificacao_id' => $classificacaoId,
                ];

                if ($livro) {
                    $livro->update($payload);
                    $atualizados++;
                } else {
                    $livro = Livro::query()->create([
                        ...$payload,
                        'codigo' => $codigo,
                        'data_entrada' => $hoje,
                        'forma_entrada' => 'ACERVO_ANTERIOR',
                        'situacao' => 'DISPONIVEL',
                        'observacao' => 'Importado do catálogo (planilha) na implantação.',
                        'usuario_cadastro_id' => $admin?->id,
                    ]);

                    MovimentacaoAcervo::query()->create([
                        'livro_id' => $livro->id,
                        'data_hora' => now(),
                        'tipo' => 'ENTRADA',
                        'usuario_id' => $admin?->id,
                        'observacao' => 'Importação do catálogo — acervo anterior à implantação',
                    ]);

                    $criados++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Criados: {$criados} | Atualizados: {$atualizados} | Ignorados: {$ignorados}");
        $this->line('Classificações: '.implode(', ', array_keys($classificacoesUsadas)));

        if (! $dryRun) {
            $this->info('Total de livros no acervo: '.Livro::query()->count());
        }

        return self::SUCCESS;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function normalizarClassificacao(string $nome): string
    {
        $map = [
            'obras básicas' => 'Obras básicas',
            'obras basicas' => 'Obras básicas',
            'mediúnico' => 'Mediúnico',
            'mediunico' => 'Mediúnico',
            'doutrinário' => 'Doutrinário',
            'doutrinario' => 'Doutrinário',
            'romance' => 'Romance',
            'pesquisa' => 'Pesquisa',
        ];

        $chave = mb_strtolower(trim($nome));

        return $map[$chave] ?? trim($nome);
    }
}
