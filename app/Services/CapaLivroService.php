<?php

namespace App\Services;

use App\Models\Livro;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CapaLivroService
{
    private bool $quotaExcedida = false;

    private ?string $quotaMensagem = null;

    public function quotaFoiExcedida(): bool
    {
        return $this->quotaExcedida;
    }

    public function mensagemQuota(): ?string
    {
        return $this->quotaMensagem;
    }

    /**
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    public function buscar(string $titulo, ?string $autor = null): array
    {
        $titulo = trim($titulo);
        if ($titulo === '') {
            return [];
        }

        $autor = $autor !== null ? trim($autor) : null;
        if ($autor === '') {
            $autor = null;
        }

        $resultados = array_merge(
            $this->buscarGoogleBooks($titulo, $autor),
            $this->buscarOpenLibrary($titulo, $autor),
        );

        if ($resultados === [] && $autor && ! $this->quotaExcedida) {
            $resultados = array_merge(
                $this->buscarGoogleBooks($titulo, null),
                $this->buscarOpenLibrary($titulo, null),
            );
        }

        return $this->unicos($resultados, 12);
    }

    /**
     * @return array{
     *     processados: int,
     *     encontradas: int,
     *     nao_encontradas: int,
     *     erros: int,
     *     quota_excedida: bool,
     *     quota_mensagem: ?string,
     *     interrompido: bool,
     *     detalhes: list<array{id: int, codigo: string, titulo: string, status: string, detalhe?: string}>
     * }
     */
    public function atualizarLote(int $quantidade): array
    {
        $quantidade = max(1, min(50, $quantidade));
        $this->quotaExcedida = false;
        $this->quotaMensagem = null;

        $livros = Livro::query()
            ->whereNull('capa')
            ->whereNull('capa_busca_falhou_em')
            ->where('situacao', '!=', 'BAIXADO')
            ->orderBy('titulo')
            ->orderBy('codigo')
            ->limit($quantidade)
            ->get();

        $resumo = [
            'processados' => 0,
            'encontradas' => 0,
            'nao_encontradas' => 0,
            'erros' => 0,
            'quota_excedida' => false,
            'quota_mensagem' => null,
            'interrompido' => false,
            'detalhes' => [],
        ];

        foreach ($livros as $livro) {
            if ($this->quotaExcedida) {
                $resumo['interrompido'] = true;
                break;
            }

            $resumo['processados']++;

            try {
                $candidatos = $this->buscar($livro->titulo, $livro->autor_medium);

                if ($this->quotaExcedida) {
                    // Não marca falha permanente: o livro continua pendente na fila.
                    $resumo['interrompido'] = true;
                    $resumo['processados']--;
                    $resumo['detalhes'][] = [
                        'id' => $livro->id,
                        'codigo' => $livro->codigo,
                        'titulo' => $livro->titulo,
                        'status' => 'quota',
                        'detalhe' => $this->quotaMensagem,
                    ];
                    break;
                }

                $primeiro = $candidatos[0] ?? null;

                if (! $primeiro) {
                    $livro->update(['capa_busca_falhou_em' => now()]);
                    $resumo['nao_encontradas']++;
                    $resumo['detalhes'][] = [
                        'id' => $livro->id,
                        'codigo' => $livro->codigo,
                        'titulo' => $livro->titulo,
                        'status' => 'nao_encontrada',
                    ];

                    continue;
                }

                $path = $this->importarDeUrl($primeiro['thumbnail']);
                $livro->update([
                    'capa' => $path,
                    'capa_busca_falhou_em' => null,
                ]);

                $resumo['encontradas']++;
                $resumo['detalhes'][] = [
                    'id' => $livro->id,
                    'codigo' => $livro->codigo,
                    'titulo' => $livro->titulo,
                    'status' => 'encontrada',
                    'detalhe' => $primeiro['fonte'],
                ];
            } catch (RuntimeException $e) {
                if ($this->ehErroQuota($e->getMessage()) || $this->quotaExcedida) {
                    $resumo['interrompido'] = true;
                    $resumo['quota_excedida'] = true;
                    $resumo['quota_mensagem'] = $this->quotaMensagem ?: $e->getMessage();
                    $resumo['detalhes'][] = [
                        'id' => $livro->id,
                        'codigo' => $livro->codigo,
                        'titulo' => $livro->titulo,
                        'status' => 'quota',
                        'detalhe' => $resumo['quota_mensagem'],
                    ];
                    break;
                }

                $livro->update(['capa_busca_falhou_em' => now()]);
                $resumo['erros']++;
                $resumo['detalhes'][] = [
                    'id' => $livro->id,
                    'codigo' => $livro->codigo,
                    'titulo' => $livro->titulo,
                    'status' => 'erro',
                    'detalhe' => $e->getMessage(),
                ];
                Log::warning('Atualizar capas: falha no livro '.$livro->id.': '.$e->getMessage());
            } catch (\Throwable $e) {
                $livro->update(['capa_busca_falhou_em' => now()]);
                $resumo['erros']++;
                $resumo['detalhes'][] = [
                    'id' => $livro->id,
                    'codigo' => $livro->codigo,
                    'titulo' => $livro->titulo,
                    'status' => 'erro',
                    'detalhe' => $e->getMessage(),
                ];
                Log::warning('Atualizar capas: falha no livro '.$livro->id.': '.$e->getMessage());
            }
        }

        $resumo['quota_excedida'] = $this->quotaExcedida || $resumo['quota_excedida'];
        $resumo['quota_mensagem'] = $this->quotaMensagem ?: $resumo['quota_mensagem'];

        return $resumo;
    }

    public function importarDeUrl(string $url): string
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'SalaDeLeitura/1.0',
                'Accept' => 'image/*,*/*',
            ])
            ->get($url);

        $this->registrarSeQuota($response, 'download da capa');

        if ($this->quotaExcedida) {
            throw new RuntimeException($this->quotaMensagem ?? 'Quota da API excedida.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível baixar a imagem da capa.');
        }

        $binario = $response->body();
        if ($binario === '' || strlen($binario) < 100) {
            throw new RuntimeException('Arquivo de capa inválido ou vazio.');
        }

        $mime = $response->header('Content-Type') ?: (new \finfo(FILEINFO_MIME_TYPE))->buffer($binario);
        $extensao = match (true) {
            str_contains((string) $mime, 'png') => 'png',
            str_contains((string) $mime, 'webp') => 'webp',
            str_contains((string) $mime, 'gif') => 'gif',
            default => 'jpg',
        };

        if (strlen($binario) > 2 * 1024 * 1024) {
            throw new RuntimeException('A imagem da capa excede 2 MB.');
        }

        $path = 'capas/'.Str::uuid().'.'.$extensao;
        Storage::disk('public')->put($path, $binario);

        return $path;
    }

    /**
     * @param  list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>  $resultados
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    private function unicos(array $resultados, int $limite): array
    {
        $vistos = [];
        $unicos = [];

        foreach ($resultados as $item) {
            $chave = md5(mb_strtolower($item['thumbnail']));
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;
            $unicos[] = $item;
        }

        return array_slice($unicos, 0, $limite);
    }

    /**
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    private function buscarGoogleBooks(string $titulo, ?string $autor): array
    {
        try {
            $q = 'intitle:'.self::quote($titulo);
            if ($autor) {
                $q .= '+inauthor:'.self::quote($autor);
            }

            $response = Http::timeout(12)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => $q,
                'maxResults' => 8,
                'printType' => 'books',
            ]);

            $this->registrarSeQuota($response, 'Google Books');

            if ($this->quotaExcedida || ! $response->successful()) {
                return [];
            }

            $itens = [];
            foreach ($response->json('items') ?? [] as $item) {
                $info = $item['volumeInfo'] ?? [];
                $links = $info['imageLinks'] ?? [];
                $thumb = $links['thumbnail'] ?? $links['smallThumbnail'] ?? null;
                if (! $thumb) {
                    continue;
                }

                $thumb = preg_replace('/^http:/', 'https:', $thumb);
                $thumb = preg_replace('/zoom=\d+/', 'zoom=1', $thumb);

                $itens[] = [
                    'titulo' => (string) ($info['title'] ?? $titulo),
                    'autores' => implode(', ', $info['authors'] ?? []),
                    'thumbnail' => $thumb,
                    'fonte' => 'Google Books',
                ];
            }

            return $itens;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar capas no Google Books: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    private function buscarOpenLibrary(string $titulo, ?string $autor): array
    {
        try {
            $params = [
                'title' => $titulo,
                'limit' => 8,
                'fields' => 'title,author_name,cover_i,key',
            ];
            if ($autor) {
                $params['author'] = $autor;
            }

            $response = Http::timeout(12)
                ->get('https://openlibrary.org/search.json', $params);

            $this->registrarSeQuota($response, 'Open Library');

            if ($this->quotaExcedida || ! $response->successful()) {
                return [];
            }

            $itens = [];
            foreach ($response->json('docs') ?? [] as $doc) {
                $coverId = $doc['cover_i'] ?? null;
                if (! $coverId) {
                    continue;
                }

                $itens[] = [
                    'titulo' => (string) ($doc['title'] ?? $titulo),
                    'autores' => implode(', ', $doc['author_name'] ?? []),
                    'thumbnail' => 'https://covers.openlibrary.org/b/id/'.$coverId.'-M.jpg',
                    'fonte' => 'Open Library',
                ];
            }

            return $itens;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar capas na Open Library: '.$e->getMessage());

            return [];
        }
    }

    private function registrarSeQuota(Response $response, string $origem): void
    {
        $status = $response->status();
        $corpo = mb_strtolower((string) $response->body());

        $pareceQuota = in_array($status, [429, 403], true)
            || str_contains($corpo, 'quota')
            || str_contains($corpo, 'rate limit')
            || str_contains($corpo, 'ratelimit')
            || str_contains($corpo, 'user rate limit exceeded');

        if (! $pareceQuota) {
            return;
        }

        $this->quotaExcedida = true;
        $this->quotaMensagem = "Quota/limite da API excedido em {$origem} (HTTP {$status}). Tente novamente mais tarde; os livros não processados continuam na fila.";
        Log::warning($this->quotaMensagem);
    }

    private function ehErroQuota(string $mensagem): bool
    {
        $mensagem = mb_strtolower($mensagem);

        return str_contains($mensagem, 'quota')
            || str_contains($mensagem, 'rate limit')
            || str_contains($mensagem, 'limite da api');
    }

    private static function quote(string $value): string
    {
        $value = trim($value);

        return '"'.str_replace('"', '', $value).'"';
    }
}
