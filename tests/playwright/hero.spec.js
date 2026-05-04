import { test, expect } from '@playwright/test';

const PAGE = process.env.PLAYWRIGHT_HERO_PAGE || '/';

test.describe('g2rd/hero — rendu frontend', () => {
    test.beforeEach(async ({ page }) => {
        const errors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') errors.push(msg.text());
        });
        page.errors = errors;

        await page.goto(PAGE);
    });

    test('le bloc hero est visible', async ({ page }) => {
        const hero = page.locator('.wp-block-g2rd-hero');
        await expect(hero).toBeVisible();
    });

    test('un titre est présent dans le hero', async ({ page }) => {
        const heading = page.locator('.wp-block-g2rd-hero').getByRole('heading');
        await expect(heading).toBeVisible();
        const text = await heading.textContent();
        expect(text.trim().length).toBeGreaterThan(0);
    });

    test('le bouton CTA est cliquable', async ({ page }) => {
        const cta = page.locator('.wp-block-g2rd-hero').getByRole('link').first();
        const ctaCount = await cta.count();

        if (ctaCount > 0) {
            await expect(cta).toBeVisible();
            await expect(cta).toHaveAttribute('href');
        }
    });

    test('aucune erreur JavaScript critique', async ({ page }) => {
        // Attendre que les scripts se chargent
        await page.waitForLoadState('networkidle');
        const critiques = (page.errors || []).filter(
            (e) => !e.includes('favicon') && !e.includes('404')
        );
        expect(critiques).toHaveLength(0);
    });

    test('le bloc est lisible en mode sombre', async ({ page }) => {
        // Activer le dark mode via localStorage
        await page.evaluate(() => {
            localStorage.setItem('g2rd-theme', 'dark');
        });
        await page.reload();

        const body = page.locator('body, [data-theme]');
        const hero = page.locator('.wp-block-g2rd-hero');
        await expect(hero).toBeVisible();

        // Vérifier qu'une couleur de texte est définie (pas de texte invisible)
        const color = await hero.evaluate((el) =>
            window.getComputedStyle(el).color
        );
        expect(color).not.toBe('rgba(0, 0, 0, 0)');
    });
});
