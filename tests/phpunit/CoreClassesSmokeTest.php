<?php
declare(strict_types=1);

namespace G2RD\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests unitaires "smoke" des classes critiques.
 * But: détecter rapidement une régression de chargement/surface API.
 */
final class CoreClassesSmokeTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function hookableClassProvider(): array
    {
        return [
            'LicenseManager' => [\G2RD\LicenseManager::class, 'register_hooks'],
            'ThemeOptions' => [\G2RD\ThemeOptions::class, 'register_hooks'],
            'GeoAnalyzer' => [\G2RD\GeoAnalyzer::class, 'register_hooks'],
            'SeoHelper' => [\G2RD\SEO_Helper::class, 'register_hooks'],
        ];
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function staticInitClassProvider(): array
    {
        return [
            'GoogleReviews' => [\G2RD\GoogleReviews::class],
        ];
    }

    #[DataProvider('hookableClassProvider')]
    public function test_hookable_classes_are_loadable_and_expose_register_hooks(
        string $className,
        string $expectedMethod
    ): void {
        self::assertTrue(class_exists($className), "Classe introuvable: {$className}");

        $reflection = new ReflectionClass($className);
        self::assertTrue(
            $reflection->hasMethod($expectedMethod),
            "Méthode attendue absente sur {$className}: {$expectedMethod}"
        );
    }

    #[DataProvider('staticInitClassProvider')]
    public function test_static_init_classes_are_loadable(string $className): void
    {
        self::assertTrue(class_exists($className), "Classe introuvable: {$className}");
    }

    public function test_github_updater_constructor_signature_stays_compatible(): void
    {
        self::assertTrue(class_exists(\G2RD\GitHubUpdater::class));
        $reflection = new ReflectionClass(\G2RD\GitHubUpdater::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertGreaterThanOrEqual(1, $constructor->getNumberOfParameters());
    }
}
