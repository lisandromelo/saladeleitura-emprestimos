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

    private bool $googleVolumesIndisponivel = false;

    private bool $internetArchiveIndisponivel = false;

    private bool $openLibraryIndisponivel = false;

    public function quotaFoiExcedida(): bool
    {
        return $this->quotaExcedida;
    }

    public function mensagemQuota(): ?string
    {
        return $this->quotaMensagem;
    }

    public function googleVolumesIndisponivel(): bool
    {
        return $this->googleVolumesIndisponivel;
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

        $resultados = [];

        if (! $this->googleVolumesIndisponivel) {
            $resultados = array_merge($resultados, $this->buscarGoogleBooks($titulo, $autor));
            if ($resultados === [] && $autor && ! $this->googleVolumesIndisponivel) {
                $resultados = array_merge($resultados, $this->buscarGoogleBooks($titulo, null));
            }
        }

        if (! $this->openLibraryIndisponivel) {
            $resultados = array_merge($resultados, $this->buscarOpenLibrary($titulo, $autor));
        }

        $catalogo = $this->unicos($this->filtrarPorTitulo($resultados, $titulo), 6);

        // Evita uma terceira consulta externa quando já existe uma correspondência forte.
        // Isso reduz bastante a espera nos casos comuns sem aumentar o risco de capa errada.
        if ($this->temCorrespondenciaConfiavel($titulo, $autor, $catalogo)) {
            return [$this->melhorCorrespondencia($titulo, $autor, $catalogo)];
        }

        $arquivo = $this->buscarInternetArchive($titulo, $autor);

        if ($this->googleVolumesIndisponivel && $this->openLibraryIndisponivel && $this->internetArchiveIndisponivel && $catalogo === [] && $arquivo === []) {
            $this->marcarQuota(
                $this->quotaMensagem
                    ?? 'As fontes de capa estão indisponíveis no momento. Tente novamente mais tarde; os livros não processados continuam na fila.'
            );
        }

        return $this->unicos(array_merge($catalogo, $arquivo), 12);
    }

    /**
     * @return array{
     *     processados: int,
     *     encontradas: int,
     *     nao_encontradas: int,
     *     erros: int,
     *     quota_excedida: bool,
     *     quota_mensagem: ?string,
     *     google_indisponivel: bool,
     *     interrompido: bool,
     *     detalhes: list<array{id: int, codigo: string, titulo: string, status: string, detalhe?: string}>
     * }
     */
    public function atualizarLote(int $quantidade): array
    {
        $quantidade = max(1, min(50, $quantidade));
        $this->quotaExcedida = false;
        $this->quotaMensagem = null;
        $this->googleVolumesIndisponivel = false;
        $this->internetArchiveIndisponivel = false;
        $this->openLibraryIndisponivel = false;

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
            'google_indisponivel' => false,
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

                if ($this->quotaExcedida && $candidatos === []) {
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

                if (count($candidatos) > 1) {
                    $resumo['detalhes'][] = [
                        'id' => $livro->id,
                        'codigo' => $livro->codigo,
                        'titulo' => $livro->titulo,
                        'status' => 'escolher',
                        'detalhe' => count($candidatos).' alternativas',
                        'opcoes' => array_map(fn (array $candidato): array => [
                            'titulo' => $candidato['titulo'],
                            'autores' => $candidato['autores'],
                            'thumbnail' => $candidato['thumbnail'],
                            'url' => $candidato['url'] ?? $candidato['thumbnail'],
                            'fonte' => $candidato['fonte'],
                        ], $candidatos),
                    ];

                    continue;
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

                $path = $this->importarDeUrl($primeiro['url'] ?? $primeiro['thumbnail']);
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
        $resumo['google_indisponivel'] = $this->googleVolumesIndisponivel;

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

        if ($this->respostaIndicaQuota($response)) {
            $this->marcarQuota("Quota/limite da API excedido ao baixar a capa (HTTP {$response->status()}). Tente novamente mais tarde; os livros não processados continuam na fila.");
            throw new RuntimeException($this->quotaMensagem ?? 'Quota da API excedida.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível baixar a imagem da capa.');
        }

        $binario = $response->body();
        if (! $this->binarioPareceImagem($binario, (string) $response->header('Content-Type'))) {
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
     * @param  list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>  $resultados
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    private function filtrarPorTitulo(array $resultados, string $titulo): array
    {
        $compativeis = [];
        $alternativas = [];

        foreach ($resultados as $item) {
            if ($this->tituloCompativel($titulo, $item['titulo'])) {
                $compativeis[] = $item;
                continue;
            }

            // Busca ampliada para não perder títulos com subtítulos ou pequenas variações.
            // Estes resultados nunca são aplicados automaticamente: ficam para revisão.
            $tokensConsulta = $this->tokens($titulo);
            $tokensCandidato = $this->tokens($item['titulo']);
            if ($tokensConsulta === [] || $tokensCandidato === []) {
                continue;
            }

            $cobertura = count(array_intersect($tokensConsulta, $tokensCandidato)) / count($tokensConsulta);
            $extras = count(array_diff($tokensCandidato, $tokensConsulta));

            if ($cobertura >= 0.6 && $extras <= 4) {
                $alternativas[] = $item;
            }
        }

        $selecionados = $compativeis !== [] ? $compativeis : $alternativas;

        usort($selecionados, function (array $a, array $b) use ($titulo): int {
            return $this->pontuarTitulo($titulo, $b['titulo']) <=> $this->pontuarTitulo($titulo, $a['titulo']);
        });

        return $selecionados;
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

            if ($this->respostaIndicaQuota($response)) {
                $this->googleVolumesIndisponivel = true;
                Log::warning("Google Books sem cota (HTTP {$response->status()}). A busca segue pela Open Library e por ISBN.");

                return [];
            }

            if (! $response->successful()) {
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
                    'url' => $thumb,
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
     * Terceira fonte: capas no Internet Archive, sem chave de API.
     *
     * @return list<array{titulo: string, autores: string, thumbnail: string, url: string, fonte: string}>
     */
    private function buscarInternetArchive(string $titulo, ?string $autor): array
    {
        if ($this->internetArchiveIndisponivel) {
            return [];
        }

        try {
            $consulta = 'title:("'.$titulo.'") AND mediatype:texts';
            $response = Http::timeout(12)
                ->withHeaders(['User-Agent' => 'SalaDeLeitura/1.0'])
                ->get('https://archive.org/advancedsearch.php', [
                    'q' => $consulta,
                    'fl' => 'identifier,title',
                    'rows' => 6,
                    'output' => 'json',
                ]);

            if ($this->respostaIndicaQuota($response)) {
                $this->internetArchiveIndisponivel = true;
                Log::warning("Internet Archive indisponível (HTTP {$response->status()}).");

                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            $itens = [];
            foreach ($response->json('response.docs') ?? [] as $doc) {
                $tituloDoc = (string) ($doc['title'] ?? '');
                $identificador = (string) ($doc['identifier'] ?? '');
                if ($identificador === '' || ! $this->mencionaTitulo($titulo, $tituloDoc)) {
                    continue;
                }

                $url = 'https://archive.org/services/img/'.$identificador;
                if (! $this->urlTemImagem($url)) {
                    continue;
                }

                $itens[] = [
                    'titulo' => $tituloDoc,
                    'autores' => $autor ?? '',
                    'thumbnail' => $url,
                    'url' => $url,
                    'fonte' => 'Internet Archive',
                ];

                if (count($itens) >= 4) {
                    break;
                }
            }

            return $itens;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar capas no Internet Archive: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Só automatiza quando o título normalizado coincide integralmente e o autor
     * também é compatível, quando conhecido. Casos duvidosos seguem para revisão.
     *
     * @param  list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>  $candidatos
     */
    private function temCorrespondenciaConfiavel(string $titulo, ?string $autor, array $candidatos): bool
    {
        $correspondencias = 0;

        foreach ($candidatos as $candidato) {
            if ($this->normalizar($titulo) !== $this->normalizar($candidato['titulo'])) {
                continue;
            }

            if ($autor && ! $this->autorCompativel($autor, $candidato['autores'] !== '' ? [$candidato['autores']] : [])) {
                continue;
            }

            $correspondencias++;
        }

        // Mais de uma capa igualmente compatível pode representar edições distintas.
        return $correspondencias === 1;
    }

    /**
     * @param  list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>  $candidatos
     * @return array{titulo: string, autores: string, thumbnail: string, fonte: string}
     */
    private function melhorCorrespondencia(string $titulo, ?string $autor, array $candidatos): array
    {
        usort($candidatos, function (array $a, array $b) use ($titulo, $autor): int {
            $pontuacaoA = $this->pontuarTitulo($titulo, $a['titulo']);
            $pontuacaoB = $this->pontuarTitulo($titulo, $b['titulo']);

            if ($autor) {
                $pontuacaoA += $this->autorCompativel($autor, [$a['autores']]) ? 0.2 : 0;
                $pontuacaoB += $this->autorCompativel($autor, [$b['autores']]) ? 0.2 : 0;
            }

            return $pontuacaoB <=> $pontuacaoA;
        });

        return $candidatos[0];
    }

    private function mencionaTitulo(string $consulta, string $texto): bool
    {
        $tokens = array_values(array_filter(
            $this->tokens($consulta),
            fn (string $token): bool => mb_strlen($token) >= 3 || ctype_digit($token),
        ));

        if ($tokens === []) {
            return false;
        }

        $intersecao = array_intersect($tokens, $this->tokens($texto));

        return count($intersecao) / count($tokens) >= 0.6;
    }

    /**
     * @return list<array{titulo: string, autores: string, thumbnail: string, fonte: string}>
     */
    private function buscarOpenLibrary(string $titulo, ?string $autor): array
    {
        try {
            $response = Http::timeout(12)->get('https://openlibrary.org/search.json', [
                'title' => $titulo,
                'limit' => 15,
                'fields' => 'title,author_name,cover_i,isbn',
            ]);

            if ($this->respostaIndicaQuota($response)) {
                $this->openLibraryIndisponivel = true;
                Log::warning("Open Library indisponível (HTTP {$response->status()}).");

                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            $docs = [];
            foreach ($response->json('docs') ?? [] as $doc) {
                $tituloDoc = (string) ($doc['title'] ?? '');
                if (! $this->tituloCompativel($titulo, $tituloDoc)) {
                    continue;
                }
                $docs[] = $doc;
            }

            usort($docs, function (array $a, array $b) use ($titulo, $autor): int {
                return $this->pontuarDocumento($titulo, $autor, $b) <=> $this->pontuarDocumento($titulo, $autor, $a);
            });

            $itens = [];
            foreach (array_slice($docs, 0, 4) as $doc) {
                $thumb = $this->thumbnailOpenLibrary($doc);
                if ($thumb === null) {
                    continue;
                }

                $itens[] = [
                    'titulo' => (string) ($doc['title'] ?? $titulo),
                    'autores' => implode(', ', $doc['author_name'] ?? []),
                    'thumbnail' => $thumb['url'],
                    'url' => $thumb['url'],
                    'fonte' => $thumb['fonte'],
                ];
            }

            return $itens;
        } catch (\Throwable $e) {
            Log::warning('Falha ao buscar capas na Open Library: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array{url: string, fonte: string}|null
     */
    private function thumbnailOpenLibrary(array $doc): ?array
    {
        $coverId = $doc['cover_i'] ?? null;
        if ($coverId) {
            return [
                'url' => 'https://covers.openlibrary.org/b/id/'.$coverId.'-L.jpg',
                'fonte' => 'Open Library',
            ];
        }

        foreach (array_slice($doc['isbn'] ?? [], 0, 2) as $isbn) {
            $url = $this->capaPorIsbn((string) $isbn);
            if ($url === null) {
                continue;
            }

            return [
                'url' => $url,
                'fonte' => str_contains($url, 'books.google.com') ? 'Capa por ISBN (Google)' : 'Capa por ISBN (Open Library)',
            ];
        }

        return null;
    }

    private function capaPorIsbn(string $isbn): ?string
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn) ?? '';
        if (strlen($isbn) < 10) {
            return null;
        }

        $urls = [
            'https://books.google.com/books/content?vid=ISBN'.$isbn.'&printsec=frontcover&img=1&zoom=1',
            'https://covers.openlibrary.org/b/isbn/'.$isbn.'-L.jpg',
        ];

        foreach ($urls as $url) {
            if ($this->urlTemImagem($url)) {
                return $url;
            }
        }

        return null;
    }

    private function urlTemImagem(string $url): bool
    {
        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'SalaDeLeitura/1.0',
                    'Accept' => 'image/*,*/*',
                ])
                ->get($url);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        return $this->binarioPareceImagem($response->body(), (string) $response->header('Content-Type'));
    }

    private function binarioPareceImagem(string $binario, string $mime): bool
    {
        if ($binario === '' || strlen($binario) < 800) {
            return false;
        }

        if (str_contains(mb_strtolower($mime), 'gif')) {
            return false;
        }

        if ($mime !== '' && ! str_contains(mb_strtolower($mime), 'image')) {
            return false;
        }

        return true;
    }

    private function tituloCompativel(string $consulta, string $candidato): bool
    {
        $tokensConsulta = $this->tokens($consulta);
        $tokensCandidato = $this->tokens($candidato);

        if ($tokensConsulta === [] || $tokensCandidato === []) {
            return false;
        }

        $intersecao = array_intersect($tokensConsulta, $tokensCandidato);
        $cobertura = count($intersecao) / count($tokensConsulta);
        $extras = count($tokensCandidato) - count($intersecao);

        return $cobertura >= 0.75 && $extras <= 1;
    }

    private function pontuarTitulo(string $consulta, string $candidato): float
    {
        $tokensConsulta = $this->tokens($consulta);
        $tokensCandidato = $this->tokens($candidato);
        if ($tokensConsulta === []) {
            return 0;
        }

        $intersecao = array_intersect($tokensConsulta, $tokensCandidato);
        $cobertura = count($intersecao) / count($tokensConsulta);
        $extras = max(0, count($tokensCandidato) - count($intersecao));

        return $cobertura - ($extras * 0.08);
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function pontuarDocumento(string $titulo, ?string $autor, array $doc): float
    {
        $pontos = $this->pontuarTitulo($titulo, (string) ($doc['title'] ?? ''));
        if ($autor && $this->autorCompativel($autor, $doc['author_name'] ?? [])) {
            $pontos += 0.2;
        }
        if (! empty($doc['cover_i'])) {
            $pontos += 0.05;
        }

        return $pontos;
    }

    /**
     * @param  list<string>  $autores
     */
    private function autorCompativel(string $autor, array $autores): bool
    {
        $tokensAutor = $this->tokens($autor);
        if ($tokensAutor === []) {
            return false;
        }

        $tokensEncontrados = $this->tokens(implode(' ', $autores));
        $intersecao = array_intersect($tokensAutor, $tokensEncontrados);

        return count($intersecao) / count($tokensAutor) >= 0.5;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $valor): array
    {
        $normalizado = $this->normalizar($valor);
        $partes = preg_split('/\s+/', $normalizado) ?: [];
        $ignorar = ['a', 'o', 'as', 'os', 'de', 'da', 'do', 'das', 'dos', 'e', 'em', 'um', 'uma'];

        $tokens = [];
        foreach ($partes as $parte) {
            if ($parte === '' || in_array($parte, $ignorar, true)) {
                continue;
            }
            $tokens[] = $parte;
        }

        return array_values(array_unique($tokens));
    }

    private function normalizar(string $valor): string
    {
        $valor = mb_strtolower(trim($valor));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        if ($ascii !== false) {
            $valor = $ascii;
        }
        $valor = preg_replace('/[^a-z0-9 ]+/', ' ', $valor) ?? $valor;
        $valor = preg_replace('/\s+/', ' ', $valor) ?? $valor;

        return trim($valor);
    }

    private function respostaIndicaQuota(Response $response): bool
    {
        $status = $response->status();
        $corpo = mb_strtolower((string) $response->body());

        return in_array($status, [429, 403], true)
            || str_contains($corpo, 'quota')
            || str_contains($corpo, 'rate limit')
            || str_contains($corpo, 'ratelimit')
            || str_contains($corpo, 'user rate limit exceeded');
    }

    private function marcarQuota(string $mensagem): void
    {
        $this->quotaExcedida = true;
        $this->quotaMensagem = $mensagem;
        Log::warning($mensagem);
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
