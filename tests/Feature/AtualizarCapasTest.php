<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Services\CapaLivroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AtualizarCapasTest extends TestCase
{
    use RefreshDatabase;

    public function test_lote_continua_pela_capa_de_isbn_quando_google_books_esta_sem_cota(): void
    {
        Storage::fake('public');

        $livro = Livro::query()->create([
            'codigo' => '1',
            'titulo' => '50 anos depois',
            'autor_medium' => 'Chico Xavier',
            'situacao' => 'DISPONIVEL',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'googleapis.com/books')) {
                return Http::response([
                    'error' => ['code' => 429, 'message' => 'Quota exceeded'],
                ], 429);
            }

            if (str_contains($url, 'openlibrary.org/search.json')) {
                return Http::response([
                    'docs' => [
                        [
                            'title' => '50 Anos Depois',
                            'author_name' => ['Francisco Candido Xavier'],
                            'isbn' => ['8573281421'],
                        ],
                        [
                            'title' => 'Ismael Nery, 50 anos depois',
                            'isbn' => ['0000000000'],
                        ],
                    ],
                ]);
            }

            if (str_contains($url, 'books.google.com/books/content')) {
                return Http::response(str_repeat('B', 2000), 200, ['Content-Type' => 'image/jpeg']);
            }

            return Http::response('gif', 200, ['Content-Type' => 'image/gif']);
        });

        $resultado = app(CapaLivroService::class)->atualizarLote(1);

        $livro->refresh();

        $this->assertFalse($resultado['quota_excedida']);
        $this->assertTrue($resultado['google_indisponivel']);
        $this->assertSame(1, $resultado['encontradas']);
        $this->assertNotNull($livro->capa);
        $this->assertNull($livro->capa_busca_falhou_em);
        Storage::disk('public')->assertExists($livro->capa);
    }

    public function test_lote_nao_marca_falha_quando_as_duas_fontes_estao_indisponiveis(): void
    {
        $livro = Livro::query()->create([
            'codigo' => '2',
            'titulo' => 'Nosso Lar',
            'autor_medium' => 'André Luiz',
            'situacao' => 'DISPONIVEL',
        ]);

        Http::fake([
            'www.googleapis.com/*' => Http::response(['error' => ['message' => 'Quota exceeded']], 429),
            'openlibrary.org/*' => Http::response(['error' => ['message' => 'rate limit']], 429),
            'archive.org/*' => Http::response(['error' => ['message' => 'rate limit']], 429),
        ]);

        $resultado = app(CapaLivroService::class)->atualizarLote(1);

        $livro->refresh();

        $this->assertTrue($resultado['quota_excedida']);
        $this->assertSame(0, $resultado['processados']);
        $this->assertNull($livro->capa);
        $this->assertNull($livro->capa_busca_falhou_em);
    }

    public function test_aplica_automaticamente_correspondencia_exata_e_pula_busca_lenta_no_archive(): void
    {
        Storage::fake('public');

        $livro = Livro::query()->create([
            'codigo' => '4',
            'titulo' => 'Nosso Lar',
            'autor_medium' => 'Chico Xavier',
            'situacao' => 'DISPONIVEL',
        ]);

        Http::fake([
            'www.googleapis.com/books/*' => Http::response([
                'items' => [[
                    'volumeInfo' => [
                        'title' => 'Nosso Lar',
                        'authors' => ['Chico Xavier'],
                        'imageLinks' => ['thumbnail' => 'https://covers.example/nosso-lar.jpg'],
                    ],
                ]],
            ], 200),
            'covers.example/*' => Http::response(str_repeat('I', 2000), 200, ['Content-Type' => 'image/jpeg']),
            'openlibrary.org/*' => Http::response(['docs' => []], 200),
        ]);

        $resultado = app(CapaLivroService::class)->atualizarLote(1);

        $livro->refresh();

        $this->assertSame(1, $resultado['encontradas']);
        $this->assertSame('encontrada', $resultado['detalhes'][0]['status']);
        $this->assertNotNull($livro->capa);
        $this->assertNull($livro->capa_busca_falhou_em);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archive.org/advancedsearch.php'));
        Storage::disk('public')->assertExists($livro->capa);
    }

    public function test_lote_pede_escolha_quando_ha_mais_de_uma_capa(): void
    {
        Livro::query()->create([
            'codigo' => '3',
            'titulo' => '50 anos depois',
            'autor_medium' => 'Chico Xavier',
            'situacao' => 'DISPONIVEL',
        ]);

        Http::fake([
            'www.googleapis.com/*' => Http::response(['items' => []], 200),
            'openlibrary.org/*' => Http::response([
                'docs' => [
                    ['title' => '50 Anos Depois', 'cover_i' => 111, 'author_name' => ['Chico Xavier']],
                    ['title' => '50 anos depois', 'cover_i' => 222, 'author_name' => ['Francisco Xavier']],
                ],
            ], 200),
            'archive.org/*' => Http::response(['response' => ['docs' => []]], 200),
        ]);

        $resultado = app(CapaLivroService::class)->atualizarLote(1);
        $livro = Livro::query()->first();

        $this->assertSame('escolher', $resultado['detalhes'][0]['status']);
        $this->assertCount(2, $resultado['detalhes'][0]['opcoes']);
        $this->assertNull($livro->capa);
        $this->assertNull($livro->capa_busca_falhou_em);
    }

    public function test_busca_inclui_internet_archive_como_terceira_fonte(): void
    {
        Http::fake([
            'www.googleapis.com/books/*' => Http::response(['items' => []], 200),
            'openlibrary.org/*' => Http::response(['docs' => []], 200),
            'archive.org/advancedsearch.php*' => Http::response([
                'response' => [
                    'docs' => [
                        ['identifier' => '50-anos-depois', 'title' => '50 Anos Depois'],
                        ['identifier' => '50-anos-depois-2', 'title' => '50 anos depois'],
                    ],
                ],
            ], 200),
            'archive.org/services/img/*' => Http::response(str_repeat('B', 2000), 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $resultados = app(CapaLivroService::class)->buscar('50 anos depois', 'Chico Xavier');

        $this->assertCount(2, $resultados);
        $this->assertSame('Internet Archive', $resultados[0]['fonte']);
        $this->assertSame('https://archive.org/services/img/50-anos-depois', $resultados[0]['url']);
    }
}
