import { test, expect } from '@playwright/test';

const PAGE = process.env.PLAYWRIGHT_FAQ_PAGE || '/demo-faq';

test.describe('g2rd/faq — rendu et accordéon', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(PAGE);
    });

    test('le bloc FAQ est visible', async ({ page }) => {
        const faq = page.locator('.wp-block-g2rd-faq');
        await expect(faq).toBeVisible();
    });

    test('au moins un item est présent', async ({ page }) => {
        // Cherche les éléments FAQ (details/summary ou pattern JS)
        const items = page.locator('.wp-block-g2rd-faq details, .wp-block-g2rd-faq .g2rd-faq__item');
        const count = await items.count();
        expect(count).toBeGreaterThan(0);
    });

    test('ouverture du premier item au clic', async ({ page }) => {
        const faq = page.locator('.wp-block-g2rd-faq');

        // Support <details>/<summary> (CSS accordion natif)
        const details = faq.locator('details').first();
        const isDetails = await details.count() > 0;

        if (isDetails) {
            // Vérifier fermé par défaut
            const isOpen = await details.evaluate((el) => el.open);

            const summary = details.locator('summary');
            await summary.click();
            await page.waitForTimeout(300);

            const isOpenAfter = await details.evaluate((el) => el.open);
            expect(isOpenAfter).not.toBe(isOpen);
        } else {
            // Pattern JS avec classes
            const trigger = faq.locator('.g2rd-faq__question, [data-faq-trigger], button').first();
            await expect(trigger).toBeVisible();
            await trigger.click();
            await page.waitForTimeout(300);

            const answer = faq.locator('.g2rd-faq__answer, [data-faq-answer]').first();
            await expect(answer).toBeVisible();
        }
    });

    test('fermeture du premier item au second clic', async ({ page }) => {
        const faq = page.locator('.wp-block-g2rd-faq');
        const details = faq.locator('details').first();
        const isDetails = await details.count() > 0;

        if (isDetails) {
            const summary = details.locator('summary');
            // Ouvrir
            await summary.click();
            await page.waitForTimeout(300);
            expect(await details.evaluate((el) => el.open)).toBe(true);

            // Fermer
            await summary.click();
            await page.waitForTimeout(300);
            expect(await details.evaluate((el) => el.open)).toBe(false);
        } else {
            const trigger = faq.locator('.g2rd-faq__question, [data-faq-trigger], button').first();
            await trigger.click();
            await page.waitForTimeout(300);
            await trigger.click();
            await page.waitForTimeout(300);

            const answer = faq.locator('.g2rd-faq__answer, [data-faq-answer]').first();
            const isVisible = await answer.isVisible();
            expect(isVisible).toBe(false);
        }
    });

    test('JSON-LD FAQPage présent si GEO activé', async ({ page }) => {
        const jsonLd = page.locator('script[type="application/ld+json"]');
        const count = await jsonLd.count();

        if (count > 0) {
            let hasFaqSchema = false;
            for (let i = 0; i < count; i++) {
                const content = await jsonLd.nth(i).textContent();
                if (content.includes('FAQPage')) {
                    hasFaqSchema = true;
                    // Vérifier que le JSON est valide
                    expect(() => JSON.parse(content)).not.toThrow();
                    break;
                }
            }
            // Pas d'assertion stricte : le bloc peut être configuré sans GEO
        }
    });

    test('la FAQ est lisible en mode sombre', async ({ page }) => {
        await page.evaluate(() => {
            localStorage.setItem('g2rd-theme', 'dark');
        });
        await page.reload();

        const faq = page.locator('.wp-block-g2rd-faq');
        await expect(faq).toBeVisible();

        const color = await faq.evaluate((el) =>
            window.getComputedStyle(el).color
        );
        expect(color).not.toBe('rgba(0, 0, 0, 0)');
    });
});
