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
     * Un ancrage posé par l'utilisateur fait autorité : des liens internes
     * peuvent déjà pointer dessus, on ne le remplace pas.
     */
    public function testAuthorAnchorIsPreserved(): void
    {
        $html = '<main id="mon-ancre">contenu</main>';
        $out = $this->renderGroup($html, ['tagName' => 'main', 'anchor' => 'mon-ancre']);

        self::assertSame($html, $out);
        // Le landmark est considéré comme traité : aucun groupe suivant ne doit
        // récupérer l'identifiant, sinon le document en compterait deux.
        self::assertStringNotContainsString(
            Accessibility::mainId(),
            $this->renderGroup('<main>suivant</main>', ['tagName' => 'main'])
        );
    }

    public function testExistingIdIsNotDuplicated(): void
    {
        $html = '<main id="deja-la">contenu</main>';
        self::assertSame($html, $this->renderGroup($html, ['tagName' => 'main']));
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
}
