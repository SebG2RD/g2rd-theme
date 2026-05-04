import { test, expect } from '@playwright/test';

const PAGE = process.env.PLAYWRIGHT_CAROUSEL_PAGE || '/demo-carousel';

test.describe('g2rd/carousel — rendu et interactions', () => {
    test.beforeEach(async ({ page }) => {
        const errors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') errors.push(msg.text());
        });
        page.errors = errors;

        await page.goto(PAGE);
        // Attendre que Swiper soit initialisé
        await page.waitForSelector('.wp-block-g2rd-carousel .swiper', { timeout: 10_000 });
    });

    test('le bloc carousel est visible', async ({ page }) => {
        const carousel = page.locator('.wp-block-g2rd-carousel');
        await expect(carousel).toBeVisible();
    });

    test('au moins un slide est visible', async ({ page }) => {
        const activeSlide = page.locator('.wp-block-g2rd-carousel .swiper-slide-active');
        await expect(activeSlide).toBeVisible();
    });

    test('navigation vers le slide suivant', async ({ page }) => {
        const nextBtn = page.locator('.wp-block-g2rd-carousel .swiper-button-next');
        const slideCount = await page.locator('.wp-block-g2rd-carousel .swiper-slide:not(.swiper-slide-duplicate)').count();

        if (slideCount > 1 && await nextBtn.count() > 0) {
            // Récupérer l'index du slide actif avant clic
            const before = await page.locator('.wp-block-g2rd-carousel .swiper-slide-active').getAttribute('data-swiper-slide-index');

            await nextBtn.click();
            // Attendre la transition
            await page.waitForTimeout(600);

            const after = await page.locator('.wp-block-g2rd-carousel .swiper-slide-active').getAttribute('data-swiper-slide-index');
            expect(after).not.toBe(before);
        } else {
            test.skip();
        }
    });

    test('navigation vers le slide précédent', async ({ page }) => {
        const prevBtn = page.locator('.wp-block-g2rd-carousel .swiper-button-prev');
        const nextBtn = page.locator('.wp-block-g2rd-carousel .swiper-button-next');
        const slideCount = await page.locator('.wp-block-g2rd-carousel .swiper-slide:not(.swiper-slide-duplicate)').count();

        if (slideCount > 1 && await prevBtn.count() > 0) {
            // Aller au slide 2 d'abord
            await nextBtn.click();
            await page.waitForTimeout(600);

            const before = await page.locator('.wp-block-g2rd-carousel .swiper-slide-active').getAttribute('data-swiper-slide-index');
            await prevBtn.click();
            await page.waitForTimeout(600);

            const after = await page.locator('.wp-block-g2rd-carousel .swiper-slide-active').getAttribute('data-swiper-slide-index');
            expect(after).not.toBe(before);
        } else {
            test.skip();
        }
    });

    test('aucune erreur Swiper dans la console', async ({ page }) => {
        await page.waitForLoadState('networkidle');
        const swiperErrors = (page.errors || []).filter((e) =>
            e.toLowerCase().includes('swiper') || e.toLowerCase().includes('carousel')
        );
        expect(swiperErrors).toHaveLength(0);
    });
});
