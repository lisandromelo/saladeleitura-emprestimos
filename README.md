# Sala de Leitura CEEBN

Sistema de controle de empréstimos da Sala de Leitura (Área do Livro Espírita — CEEBN).

## Desenvolvimento

Único requisito no computador: **Docker Engine + Compose**.

```bash
cp .env.example .env   # se ainda não houver .env
./scripts/levantar.sh
```

Na primeira vez, ou depois de mudar `docker/php`, use `docker compose up --build -d` e depois o script no dia a dia.

- App: http://localhost:8089
- Vite: http://localhost:5174
- Mailpit: http://localhost:8026
- MySQL (host): localhost:3308 (no container: `db:3306`)

Após subir, rode o seed (se ainda não rodou):

```bash
docker compose exec app php artisan db:seed
```

Usuários iniciais:

- Admin: `admin@saladeleitura.test` / `password`
- Operador: `operador@saladeleitura.test` / `password`

## Identidade visual (configurável)

Nome, logo e cores vêm de `config/branding.php` / variáveis `BRAND_*` no `.env`.

| Variável | Padrão CEEBN |
|----------|----------------|
| `BRAND_NAME` | Sala de Leitura CEEBN |
| `BRAND_TAGLINE` | Área do Livro Espírita |
| `BRAND_LOGO` | `/images/logo.png` |
| `BRAND_PRIMARY` | `#006D5B` |
| `BRAND_PRIMARY_DARK` | `#005546` |
| `BRAND_SECONDARY` | `#929497` |
| `BRAND_PAPER` | `#F5FAF8` |
| `BRAND_INK` | `#1F2933` |

Para outra instituição: altere as variáveis (cores hex entre aspas no `.env`) e substitua `public/images/logo.png` (ou aponte `BRAND_LOGO` para outro arquivo em `public/`).

A especificação funcional está em `Projeto_Area_do_Livro_Espirita_V1.md`.
