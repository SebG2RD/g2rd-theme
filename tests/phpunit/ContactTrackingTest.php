<?php
declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\ContactTracking;
use PHPUnit\Framework\TestCase;

/**
 * Compteurs du suivi des clics de contact.
 *
 * Seules les fonctions pures sont testées ici : elles portent toute la logique
 * de comptage, l'endpoint REST ne faisant que les enchaîner.
 */
final class ContactTrackingTest extends TestCase
{
    public function test_normalize_returns_empty_skeleton_for_non_array(): void
    {
        $expected = [
            'tel'  => ['total' => 0, 'months' => []],
            'mail' => ['total' => 0, 'months' => []],
        ];

        self::assertSame($expected, ContactTracking::normalize('corrompu'));
        self::assertSame($expected, ContactTracking::normalize(null));
        self::assertSame($expected, ContactTracking::normalize([]));
    }

    public function test_normalize_drops_invalid_months_and_values(): void
    {
        $stats = ContactTracking::normalize([
            'tel'    => [
                'total'  => '7',
                'months' => [
                    '2026-10' => 4,
                    'oops'    => 99,
                    '2026-9'  => 3,
                    '2026-09' => 'x',
                    '2026-08' => '2',
                ],
            ],
            'mail'   => 'pas un tableau',
            'inconnu' => ['total' => 50],
        ]);

        self::assertSame(['total' => 7, 'months' => ['2026-10' => 4, '2026-08' => 2]], $stats['tel']);
        self::assertSame(['total' => 0, 'months' => []], $stats['mail']);
        self::assertArrayNotHasKey('inconnu', $stats);
    }

    public function test_increment_counts_types_and_months_separately(): void
    {
        $stats = ContactTracking::normalize([]);
        $stats = ContactTracking::increment($stats, 'tel', '2026-10');
        $stats = ContactTracking::increment($stats, 'tel', '2026-10');
        $stats = ContactTracking::increment($stats, 'mail', '2026-10');
        $stats = ContactTracking::increment($stats, 'tel', '2026-11');

        self::assertSame(3, $stats['tel']['total']);
        self::assertSame(['2026-10' => 2, '2026-11' => 1], $stats['tel']['months']);
        self::assertSame(1, $stats['mail']['total']);
        self::assertSame(['2026-10' => 1], $stats['mail']['months']);
    }

    public function test_increment_ignores_unknown_type_and_invalid_month(): void
    {
        $stats = ContactTracking::normalize([]);

        self::assertSame($stats, ContactTracking::increment($stats, 'fax', '2026-10'));
        self::assertSame($stats, ContactTracking::increment($stats, 'tel', '2026-10-29'));
    }

    public function test_increment_keeps_only_the_most_recent_months(): void
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[sprintf('2025-%02d', $m)] = 1;
        }
        $stats = ContactTracking::normalize(['tel' => ['total' => 12, 'months' => $months]]);

        $stats = ContactTracking::increment($stats, 'tel', '2026-01');

        self::assertCount(ContactTracking::MONTHS, $stats['tel']['months']);
        self::assertArrayNotHasKey('2025-01', $stats['tel']['months']);
        self::assertSame(1, $stats['tel']['months']['2026-01']);
        self::assertSame(13, $stats['tel']['total']);
    }

    public function test_sum_for_month_adds_every_type(): void
    {
        $stats = ContactTracking::normalize([
            'tel'  => ['total' => 9, 'months' => ['2026-10' => 5, '2026-09' => 4]],
            'mail' => ['total' => 2, 'months' => ['2026-10' => 2]],
        ]);

        self::assertSame(7, ContactTracking::sumForMonth($stats, '2026-10'));
        self::assertSame(4, ContactTracking::sumForMonth($stats, '2026-09'));
        self::assertSame(0, ContactTracking::sumForMonth($stats, '2026-01'));
    }
}
