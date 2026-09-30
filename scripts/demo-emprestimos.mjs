/**
 * Vídeo demo: caminhos de Emprestar, Devolver e Cancelar.
 * Uso: node scripts/demo-emprestimos.mjs [baseUrl]
 */
import { chromium } from 'playwright';
import { mkdirSync, renameSync, readdirSync, existsSync, rmSync } from 'fs';
import { join } from 'path';

const baseUrl = process.argv[2] || process.env.DEMO_BASE_URL || 'http://127.0.0.1:8089';
const outDir = join(process.cwd(), 'docs', 'demo');
const videoDir = join(outDir, 'raw-emprestimos');

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
                'max-width:min(780px,84vw)',
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

async function apontarMenu(page, textoLink) {
    await page.evaluate((texto) => {
        document.querySelectorAll('[data-demo-nav]').forEach((n) => n.remove());
        const links = [...document.querySelectorAll('header nav a')];
        const link = links.find((a) => a.textContent.trim() === texto);
        if (!link) {
            return;
        }
        link.scrollIntoView({ block: 'nearest', inline: 'center' });
        const mark = document.createElement('div');
        mark.dataset.demoNav = '1';
        mark.style.cssText = [
            'position:absolute',
            'inset:-4px',
            'border:3px solid #fbbf24',
            'border-radius:10px',
            'box-shadow:0 0 0 4px rgba(251,191,36,.35)',
            'pointer-events:none',
            'z-index:5',
        ].join(';');
        const parent = link;
        if (getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }
        parent.appendChild(mark);
    }, textoLink);
}

async function limparMenu(page) {
    await page.evaluate(() => {
        document.querySelectorAll('[data-demo-nav]').forEach((n) => n.remove());
    });
}

async function clicarMenu(page, textoLink, caption) {
    await highlight(page, caption);
    await apontarMenu(page, textoLink);
    await pause(1800);
    await Promise.all([
        page.waitForLoadState('networkidle'),
        page.locator('header nav a', { hasText: textoLink }).first().click(),
    ]);
    await limparMenu(page);
    await pause(800);
}

async function registrarEmprestimoViaMenu(page, { livroQ, associadoQ }) {
    await clicarMenu(
        page,
        'Novo empréstimo',
        'Caminho: menu → Novo empréstimo',
    );
    await highlight(page, 'Tela de novo empréstimo');
    await pause(1600);

    const livroInput = page.locator('input[placeholder="Título do livro"]');
    await livroInput.click();
    await livroInput.fill('');
    await highlight(page, 'Busca pelo título (como o usuário pesquisa no dia a dia)');
    await page.keyboard.type(livroQ, { delay: 60 });
    await pause(1000);
    await page.locator('form').filter({ has: livroInput }).locator('button', { hasText: 'Buscar' }).click();
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Resultados: título (código) — o título vem primeiro');
    await pause(1800);

    const livroResult = page.locator('div.rounded-lg').filter({ has: page.locator('h2', { hasText: 'Livro' }) }).locator('ul li button').first();
    await livroResult.click();
    await highlight(page, 'Selecionado: o campo permanece com o título');
    await pause(2000);

    const assocInput = page.locator('input[placeholder="Nome ou telefone"]');
    await assocInput.click();
    await assocInput.fill('');
    await page.keyboard.type(associadoQ, { delay: 45 });
    await page.locator('form').filter({ has: assocInput }).locator('button', { hasText: 'Buscar' }).click();
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Selecionando o associado');
    await pause(1000);

    const assocResult = page.locator('div.rounded-lg').filter({ has: page.locator('h2', { hasText: 'Associado' }) }).locator('ul li button').first();
    await assocResult.click();
    await highlight(page, 'Painel Selecionados — revise antes de registrar');
    await pause(2000);

    await page.locator('button', { hasText: 'Registrar empréstimo' }).click();
    await highlight(page, 'Confirmação do empréstimo');
    await pause(2800);
    await page.locator('button', { hasText: 'Confirmar registro' }).click();
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Empréstimo registrado — lista de empréstimos em aberto');
    await pause(2000);
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

    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await highlight(page, 'Demonstração — Emprestar, Devolver e Cancelar');
    await pause(2200);
    await page.fill('input[type="email"], input[name="email"]', 'admin@saladeleitura.test');
    await page.fill('input[type="password"], input[name="password"]', 'password');
    await Promise.all([
        page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 15000 }),
        page.click('button[type="submit"]'),
    ]);
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Início — use o menu superior para as ações');
    await pause(2200);

    // Mostra também o atalho da página inicial
    const botaoInicio = page.locator('a', { hasText: 'Novo empréstimo' }).first();
    if (await botaoInicio.count()) {
        await highlight(page, 'Atalho no Início: Novo empréstimo');
        await botaoInicio.evaluate((el) => {
            el.style.outline = '3px solid #fbbf24';
            el.style.outlineOffset = '4px';
        });
        await pause(2000);
        await botaoInicio.evaluate((el) => {
            el.style.outline = '';
            el.style.outlineOffset = '';
        });
    }

    // 1) Emprestar pelo menu
    await highlight(page, '1) Emprestar');
    await pause(1200);
    await registrarEmprestimoViaMenu(page, {
        livroQ: '50 anos depois',
        associadoQ: 'Lisandro',
    });

    // 2) Devolver pelo menu Empréstimos
    await clicarMenu(
        page,
        'Empréstimos',
        'Caminho: menu → Empréstimos (devolução e cancelamento)',
    );
    await highlight(page, '2) Devolver — empréstimos em aberto');
    await pause(2000);

    const linhaDevolver = page.locator('tr').filter({ hasText: '50 anos depois' }).first();
    if (await linhaDevolver.count()) {
        await linhaDevolver.locator('button', { hasText: 'Devolver' }).click();
    } else {
        await page.locator('button', { hasText: 'Devolver' }).first().click();
    }
    await highlight(page, 'Confirmação de devolução — livro, associado e datas');
    await pause(3500);
    await page.locator('button', { hasText: 'Confirmar devolução' }).click();
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Devolução registrada — livro disponível novamente');
    await pause(2200);

    // 3) Emprestar de novo para cancelar
    await highlight(page, '3) Novo empréstimo para demonstrar o cancelamento');
    await pause(1200);
    await registrarEmprestimoViaMenu(page, {
        livroQ: 'Animais nossos irmãos',
        associadoQ: 'Lisandro',
    });

    // 4) Cancelar
    await clicarMenu(
        page,
        'Empréstimos',
        'Caminho: menu → Empréstimos → Cancelar',
    );
    await highlight(page, '4) Cancelar — corrige erro de lançamento (não é devolução)');
    await pause(2000);

    const linhaCancelar = page.locator('tr').filter({ hasText: 'Animais nossos irmãos' }).first();
    if (await linhaCancelar.count()) {
        await linhaCancelar.locator('button', { hasText: 'Cancelar' }).click();
    } else {
        await page.locator('button', { hasText: 'Cancelar' }).first().click();
    }
    await highlight(page, 'Confirmação do cancelamento');
    await pause(3000);
    await page.locator('button', { hasText: 'Confirmar cancelamento' }).click();
    await page.waitForLoadState('networkidle');
    await highlight(page, 'Empréstimo cancelado — livro liberado sem devolução');
    await pause(2500);

    await clicarMenu(page, 'Início', 'Volta ao Início');
    await highlight(page, 'Sala de Leitura CEEBN — fim da demonstração');
    await pause(2800);

    await context.close();
    await browser.close();

    const videos = readdirSync(videoDir).filter((f) => f.endsWith('.webm'));
    if (!videos.length) {
        throw new Error('Nenhum vídeo gerado.');
    }

    const finalWebm = join(outDir, 'saladeleitura-emprestimos.webm');
    renameSync(join(videoDir, videos[0]), finalWebm);
    for (const extra of videos.slice(1)) {
        try {
            rmSync(join(videoDir, extra), { force: true });
        } catch {
            /* ignore */
        }
    }
    try {
        rmSync(videoDir, { recursive: true, force: true });
    } catch {
        /* ignore */
    }

    console.log(JSON.stringify({
        ok: true,
        baseUrl,
        video: finalWebm,
        exists: existsSync(finalWebm),
    }, null, 2));
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
