<?php
declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\Accessibility;
use PHPUnit\Framework\TestCase;

/**
 * Socle d'accessibilité RGAA.
 *
 * Couvre le critère 12.5 (lien d'accès rapide au contenu) et la pose de sa
 * cible sur le landmark principal, sans laquelle le lien ne mène nulle part.
 */
final class AccessibilityTest extends TestCase
{
    private Accessibility $a11y;

    protected function setUp(): void
    {
        $GLOBALS['g2rd_test_is_admin'] = false;
        $this->a11y = new Accessibility();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['g2rd_test_is_admin']);
    }

    /**
     * Rend un bloc groupe et renvoie le HTML produit par le filtre.
     *
     * @param array<string, mixed> $attrs
     */
    private function renderGroup(string $html, array $attrs): string
    {
        return $this->a11y->identifyMainLandmark($html, ['attrs' => $attrs]);
    }

    public function testSkipLinkPointsAtTheMainLandmark(): void
    {
        ob_start();
        $this->a11y->renderSkipLink();
        $link = (string) ob_get_clean();

        self::assertStringContainsString('href="#' . Accessibility::mainId() . '"', $link);
        self::assertStringContainsString('Aller au contenu principal', $link);
    }

    /**
     * Le lien doit rester dans l'ordre de tabulation : masqué par une technique
     * qui le retire du flux, il serait inatteignable au clavier — l'inverse du
     * but recherché.
     */
    public function testSkipLinkIsNotRemovedFromTabOrder(): void
    {
        ob_start();
        $this->a11y->outputSkipLinkStyles();
        $css = (string) ob_get_clean();

        self::assertStringNotContainsString('display:none', $css);
        self::assertStringNotContainsString('visibility:hidden', $css);
        self::assertStringContainsString('clip-path', $css);
    }

    public function testMainLandmarkReceivesIdAndProgrammaticFocus(): void
    {
        $out = $this->renderGroup(
            '<main class="wp-block-group">contenu</main>',
            ['tagName' => 'main']
        );

        self::assertStringContainsString('id="' . Accessibility::mainId() . '"', $out);
        // Sans tabindex, le navigateur défile mais ne déplace pas le focus :
        // la tabulation suivante repartirait du haut de la page.
        self::assertStringContainsString('tabindex="-1"', $out);
        // L'ancre se place à l'intérieur du landmark, pas avant lui.
        self::assertMatchesRegularExpression('/<main[^>]*><span id="/', $out);
    }

    public function testNonMainGroupsAreLeftAlone(): void
    {
        $html = '<div class="wp-block-group">contenu</div>';
        self::assertSame($html, $this->renderGroup($html, ['tagName' => 'div']));
        self::assertSame($html, $this->renderGroup($html, []));
    }

    /**
     * Un identifiant doit rester unique dans le document. Les gabarits imbriquent
     * parfois un groupe `main` dans un autre : seul le premier doit être marqué.
     */
    public function testOnlyTheFirstMainLandmarkIsMarked(): void
    {
        $first = $this->renderGroup('<main>un</main>', ['tagName' => 'main']);
        $second = $this->renderGroup('<main>deux</main>', ['tagName' => 'main']);

        self::assertStringContainsString(Accessibility::mainId(), $first);
        self::assertStringNotContainsString(Accessibility::mainId(), $second);
    }

    /**
     * Un ancrage défini par l'auteur ne doit plus faire perdre la cible.
     *
     * L'identifiant était auparavant posé sur <main> lui-même, et on renonçait
     * à marquer dès qu'un ancrage existait : le lien d'évitement pointait alors
     * vers une cible absente, précisément sur les pages les plus travaillées.
     */
    public function testAuthorAnchorStillGetsASkipTarget(): void
    {
        $out = $this->renderGroup(
            '<main id="mon-ancre">contenu</main>',
            ['tagName' => 'main', 'anchor' => 'mon-ancre']
        );

        self::assertStringContainsString('id="mon-ancre"', $out);
        self::assertStringContainsString('id="' . Accessibility::mainId() . '"', $out);
    }

    public function testExistingIdOnMainIsPreserved(): void
    {
        $out = $this->renderGroup('<main id="deja-la">contenu</main>', ['tagName' => 'main']);

        self::assertStringContainsString('id="deja-la"', $out);
        self::assertStringContainsString('id="' . Accessibility::mainId() . '"', $out);
    }

    public function testFlagResetsBetweenPageRenders(): void
    {
        $this->renderGroup('<main>un</main>', ['tagName' => 'main']);
        $this->a11y->resetMainFlag();

        self::assertStringContainsString(
            Accessibility::mainId(),
            $this->renderGroup('<main>page suivante</main>', ['tagName' => 'main'])
        );
    }

    /**
     * Le calcul de contraste doit coller aux valeurs de référence WCAG,
     * sans quoi l'alerte de l'administration désignerait les mauvais couples.
     */
    public function testContrastRatioMatchesReferenceValues(): void
    {
        // Noir sur blanc : borne haute de l'échelle.
        self::assertEqualsWithDelta(21.0, Accessibility::contrastRatio('#000000', '#ffffff'), 0.01);
        // Une couleur avec elle-même ne contraste pas.
        self::assertEqualsWithDelta(1.0, Accessibility::contrastRatio('#3a3a3a', '#3a3a3a'), 0.01);
        // Le rapport est symétrique.
        self::assertSame(
            Accessibility::contrastRatio('#0f172a', '#a3e635'),
            Accessibility::contrastRatio('#a3e635', '#0f172a')
        );
    }

    public function testUnparseableColoursAreIgnoredRatherThanGuessed(): void
    {
        self::assertNull(Accessibility::contrastRatio('transparent', '#ffffff'));
        self::assertNull(Accessibility::hexToRgb('linear-gradient(...)'));
        // La notation courte reste valide.
        self::assertSame([255, 255, 255], Accessibility::hexToRgb('#fff'));
    }

    /**
     * Le lime de la charte est un aplat : illisible en texte sur blanc, il doit
     * être signale ; sur le navy il passe et ne doit pas polluer l'alerte.
     */
    public function testFailingPairsFlagsOnlyWhatIsBelowThreshold(): void
    {
        $palette = [
            ['slug' => 'primary',   'name' => 'Navy',  'color' => '#0f172a'],
            ['slug' => 'secondary', 'name' => 'Lime',  'color' => '#a3e635'],
            ['slug' => 'white',     'name' => 'Blanc', 'color' => '#ffffff'],
            ['slug' => 'grad',      'name' => 'Degrade', 'color' => 'linear-gradient(90deg,#000,#fff)'],
        ];

        $pairs = Accessibility::failingPairs($palette);
        $labels = array_map(static fn($p) => $p['a'] . '/' . $p['b'], $pairs);

        self::assertContains('Lime/Blanc', $labels);
        self::assertNotContains('Navy/Blanc', $labels);
        self::assertNotContains('Navy/Lime', $labels);
        // Une valeur non hexadécimale est écartée, pas devinée.
        foreach ($pairs as $p) {
            self::assertNotSame('Degrade', $p['a']);
            self::assertNotSame('Degrade', $p['b']);
        }
        // Trié du pire au moins mauvais.
        $ratios = array_column($pairs, 'ratio');
        $sorted = $ratios;
        sort($sorted);
        self::assertSame($sorted, $ratios);
    }

    /**
     * Le suffixe « -background-color » contient « -color » : sans précaution,
     * le fond serait relevé comme couleur de texte et comparé à lui-même,
     * produisant un contraste de 1:1 sur une association qui n'existe pas.
     */
    public function testBackgroundClassIsNotMistakenForATextColour(): void
    {
        $pairs = Accessibility::usedColorPairs(
            '<p class="has-white-color has-primary-background-color">x</p>'
        );

        self::assertSame([['white', 'primary']], $pairs);
    }

    public function testOnlyElementsCarryingBothColoursAreCollected(): void
    {
        $markup = '<div class="has-primary-background-color">fond seul</div>'
            . '<p class="has-white-color">texte seul</p>'
            . '<span class="has-muted-color has-cream-background-color">les deux</span>';

        self::assertSame([['muted', 'cream']], Accessibility::usedColorPairs($markup));
    }

    /**
     * Restreindre aux couples employés est ce qui rend l'alerte lisible : la
     * palette compte des couleurs qu'il ne faut pas associer, mais qui ne le
     * sont jamais dans le balisage livré.
     */
    public function testRestrictingToUsedPairsSilencesUnusedCombinations(): void
    {
        $palette = [
            ['slug' => 'primary', 'name' => 'Navy',  'color' => '#0f172a'],
            ['slug' => 'lime',    'name' => 'Lime',  'color' => '#a3e635'],
            ['slug' => 'white',   'name' => 'Blanc', 'color' => '#ffffff'],
        ];

        // Lime sur blanc échoue, mais si personne ne l'emploie, rien à signaler.
        self::assertSame([], Accessibility::failingPairs($palette, [['white', 'primary']]));
        // Dès que le couple est réellement posé, il ressort.
        self::assertCount(1, Accessibility::failingPairs($palette, [['lime', 'white']]));
    }
}
