<?php
// phpcs:ignoreFile -- Fichier helper du bloc Gutenberg CodeG2rd. Conventions de build bloc, pas classes PHP thème.
/**
 * Aides du bloc code G2RD : langues et correspondance highlight.php.
 * Données partagées avec l'éditeur via languages.json (même dossier que le bloc).
 *
 * @package G2RD
 */

defined('ABSPATH') || exit;

/**
 * Charge l'autoloader Composer du thème pour que la classe Highlight\Highlighter existe.
 * Le paquet scrivo/highlight.php est déclaré à la racine du thème (composer.json).
 */
function g2rd_code_block_ensure_highlight_loaded(): bool
{
    if (class_exists(\Highlight\Highlighter::class)) {
        return true;
    }

    if (! function_exists('get_template_directory')) {
        return false;
    }

    $autoload = \get_template_directory() . '/vendor/autoload.php';
    if (\is_readable($autoload)) {
        require_once $autoload;
    }

    return class_exists(\Highlight\Highlighter::class);
}

/**
 * Charge les définitions de langues depuis languages.json (cache en mémoire).
 *
 * @return array<int, array{label?: string, value?: string, hljs?: string}>
 */
function g2rd_code_block_load_language_definitions(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $path = __DIR__ . '/languages.json';
    if (! is_readable($path)) {
        $cached = [];

        return $cached;
    }

    $raw  = file_get_contents($path);
    $data = json_decode((string) $raw, true);
    $cached = is_array($data) ? $data : [];

    return $cached;
}

/**
 * Liste pour l'affichage du libellé de langue côté PHP (render.php).
 *
 * @return array<int, array{label: string, value: string}>
 */
function g2rd_prettycode_get_languages(): array
{
    $out  = [];
    $rows = g2rd_code_block_load_language_definitions();

    foreach ($rows as $row) {
        if (! isset($row['label'], $row['value'])) {
            continue;
        }
        $out[] = [
            'label' => (string) $row['label'],
            'value' => (string) $row['value'],
        ];
    }

    return $out;
}

/**
 * Convertit la valeur d'attribut du bloc (ex. html) en identifiant highlight.php (ex. xml).
 */
function g2rd_prettycode_lang_to_hljs(string $language): string
{
    $language = strtolower(trim($language));

    foreach (g2rd_code_block_load_language_definitions() as $row) {
        if (! isset($row['value'])) {
            continue;
        }
        if (strtolower((string) $row['value']) === $language) {
            if (! empty($row['hljs'])) {
                return (string) $row['hljs'];
            }

            return $language;
        }
    }

    // Alias courants si la valeur n'est pas dans le JSON (anciens contenus, collage, etc.)
    $aliases = [
        'js'       => 'javascript',
        'ts'       => 'typescript',
        'py'       => 'python',
        'sh'       => 'bash',
        'shell'    => 'bash',
        'yml'      => 'yaml',
        'md'       => 'markdown',
        'txt'      => 'plaintext',
        'text'     => 'plaintext',
        'c++'      => 'cpp',
        'csharp'   => 'csharp',
        'cs'       => 'csharp',
    ];

    if (isset($aliases[$language])) {
        return $aliases[$language];
    }

    return 'plaintext';
}
