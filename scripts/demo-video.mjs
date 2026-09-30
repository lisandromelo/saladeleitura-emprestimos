/**
 * Gera um vídeo de demonstração navegando pelas telas principais.
 * Uso: node scripts/demo-video.mjs [baseUrl]
 */
import { chromium } from 'playwright';
import { mkdirSync, renameSync, readdirSync, existsSync } from 'fs';
import { join } from 'path';

const baseUrl = process.argv[2] || process.env.DEMO_BASE_URL || 'http://localhost:8089';
const outDir = join(process.cwd(), 'docs', 'demo');
const videoDir = join(outDir, 'raw');

mkdirSync(videoDir, { recursive: true });

const pause = (ms) => new Promise((r) => setTimeout(r, ms));

async function highlight(page, title) {
    await page.evaluate((text) => {
        let el = document.getElementById('demo-caption');
        if (!el) {
            el = document.createElement('div');
            el.id = 'demo-caption';
            el.style.cssText = [
                'position:fixed',
                'left:24px',
                'bottom:24px',
                'z-index:99999',
                'max-width:min(720px,80vw)',
                'padding:12px 16px',
                'border-radius:10px',
                'background:rgba(0,109,91,0.92)',
                'color:#fff',
                'font:600 18px/1.35 "Source Sans 3",Segoe UI,sans-serif',
                'box-shadow:0 10px 30px rgba(0,0,0,.25)',
                'pointer-events:none',
            ].join(';');
            document.body.appendChild(el);
        }
        el.textContent = text;
    }, title);
}

async function go(page, path, caption, waitMs = 2200) {
    await page.goto(`${baseUrl}${path}`, { waitUntil: 'networkidle' });
    await highlight(page, caption);
    await pause(waitMs);
}

async function main() {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'pt-BR',
        recordVideo: {
            dir: videoDir,
            size: { width: 1440, height: 900 },
        },
    });
    const page = await context.newPage();

    await go(page, '/login', 'Sala de Leitura CEEBN — demonstração', 2500);

    await page.fill('input[type="email"], input[name="email"]', 'admin@saladeleitura.test');
    await page.fill('input[type="password"], input[name="password"]', 'password');
    await highlight(page, 'Acesso do administrador');
    await pause(1200);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Painel inicial — visão geral do acervo');
    await pause(2800);

    await go(page, '/livros', 'Acervo — livros, capas e situação');
    await page.mouse.wheel(0, 420);
    await pause(1800);

    const firstBook = page.locator('a[href^="/livros/"]').first();
    if (await firstBook.count()) {
        await firstBook.click();
        await page.waitForLoadState('networkidle');
        await highlight(page, 'Ficha do livro — detalhes e histórico');
        await pause(2600);
    }

    await go(page, '/associados', 'Associados cadastrados');
    await go(page, '/emprestimos', 'Empréstimos em andamento e histórico');
    await go(page, '/emprestimos/novo', 'Novo empréstimo — seleção de livro e associado');
    await go(page, '/atrasos', 'Atrasos — acompanhamento de devoluções');
    await go(page, '/balancos', 'Balanço do acervo');
    await go(page, '/livros/capas/atualizar', 'Atualizar capas — busca automática na internet');
    await go(page, '/cadastros/areas', 'Cadastro de áreas');
    await go(page, '/cadastros/classificacoes', 'Cadastro de classificações');
    await go(page, '/', 'Sala de Leitura CEEBN — fim da demonstração', 3000);

    await context.close();
    await browser.close();

    const videos = readdirSync(videoDir).filter((f) => f.endsWith('.webm'));
    if (!videos.length) {
        throw new Error('Nenhum vídeo gerado pelo Playwright.');
    }

    const finalPath = join(outDir, 'saladeleitura-demonstracao.webm');
    renameSync(join(videoDir, videos[0]), finalPath);

    for (const extra of videos.slice(1)) {
        try {
            renameSync(join(videoDir, extra), join(outDir, extra));
        } catch {
            /* ignore */
        }
    }

    console.log(JSON.stringify({
        ok: true,
        baseUrl,
        video: finalPath,
        exists: existsSync(finalPath),
    }, null, 2));
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
