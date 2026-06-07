<?php
/**
 * Tests — McpConfirmationQueue: g2rd_create-full-post + g2rd_batch composite tools.
 *
 * Covers:
 *   - Confirmation email recap contains every submitted field (title, slug,
 *     categories, tags, image URL, SEO meta) — the user-required acceptance test.
 *   - Atomic orchestration: post + featured image + terms + structured report.
 *   - Rollback: when wp_insert_post fails, the sideloaded media is deleted.
 *   - Batch: sequential dispatch, per-op report, rejection of nested/unknown tools.
 *
 * Private executors are exercised via reflection (mirrors McpConfirmationQueueTest);
 * media/term/permalink behaviour is driven by the controllable stubs in bootstrap.php.
 *
 * @package    G2RD\Tests
 * @since      1.21.2
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpConfirmationQueue;
use G2RD\McpEncryption;
use G2RD\McpAuditLog;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Verifies the composite write executors and their confirmation email recap.
 */
final class McpCreateFullPostTest extends TestCase {

	private McpConfirmationQueue $queue;
	private ReflectionClass $ref;

	protected function setUp(): void {
		global $wpdb, $g2rd_post_store, $g2rd_post_meta_store, $g2rd_wp_mail_log,
			$g2rd_wpdb_insert_return, $g2rd_user_can, $g2rd_current_user_id,
			$g2rd_wp_insert_post_result, $g2rd_wp_update_post_result,
			$g2rd_download_url_result, $g2rd_media_sideload_result,
			$g2rd_deleted_attachments, $g2rd_post_thumbnails, $g2rd_post_terms,
			$g2rd_filetype_return, $g2rd_option_store;

		$g2rd_post_store            = [];
		$g2rd_post_meta_store       = [];
		$g2rd_wp_mail_log           = [];
		$g2rd_wpdb_insert_return    = true;
		$g2rd_user_can              = true;
		$g2rd_current_user_id       = 0;
		$g2rd_wp_insert_post_result = null;
		$g2rd_wp_update_post_result = null;
		$g2rd_download_url_result   = null;
		$g2rd_media_sideload_result = null;
		$g2rd_deleted_attachments   = [];
		$g2rd_post_thumbnails       = [];
		$g2rd_post_terms            = [];
		$g2rd_filetype_return       = null;
		$g2rd_option_store          = [ 'admin_email' => 'admin@example.com' ];

		$wpdb->inserts   = [];
		$wpdb->updates   = [];
		$wpdb->insert_id = 0;

		$audit_mock = $this->getMockBuilder( McpAuditLog::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'log' ] )
			->getMock();
		$audit_mock->method( 'log' )->willReturn( 1 );

		$this->queue = new McpConfirmationQueue( new McpEncryption(), $audit_mock );
		$this->ref   = new ReflectionClass( McpConfirmationQueue::class );
	}

	/** Invokes a private method on the queue via reflection. */
	private function call_private( string $method, array $args = [] ) {
		$m = $this->ref->getMethod( $method );
		$m->setAccessible( true );
		return $m->invokeArgs( $this->queue, $args );
	}

	/** Sample create-full-post arguments. */
	private function sample_args(): array {
		return [
			'title'                  => 'Mon article complet',
			'content'                => '<!-- wp:paragraph --><p>Bonjour</p><!-- /wp:paragraph -->',
			'excerpt'                => 'Un extrait',
			'status'                 => 'publish',
			'post_type'              => 'post',
			'slug'                   => 'mon-article-complet',
			'categories'             => [ 12, 34 ],
			'tags'                   => [ 'wordpress', 'mcp' ],
			'featured_image_url'     => 'https://example.com/photo.png',
			'featured_image_title'   => 'Photo',
			'featured_image_alt'     => 'Une photo de test',
			'featured_image_caption' => 'Légende',
			'seo'                    => [
				'meta_title'       => 'Titre SEO optimisé',
				'meta_description' => 'Description SEO complète',
				'focus_keyword'    => 'mot-clé-cible',
				'og_title'         => 'OG titre',
				'og_description'   => 'OG description',
				'canonical'        => 'https://example.com/canonical',
				'noindex'          => false,
			],
		];
	}

	/**
	 * The confirmation email recap must contain every submitted field.
	 *
	 * This is the user-required acceptance test: one tool call, one email, and that
	 * email summarises the whole article.
	 */
	public function test_confirmation_email_contains_full_recap(): void {
		global $g2rd_wp_mail_log;

		$result = $this->queue->enqueue( 7, 3, '203.0.113.5', 'g2rd_create-full-post', $this->sample_args() );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $g2rd_wp_mail_log );
		$this->assertCount( 1, $g2rd_wp_mail_log, 'A single confirmation email must be sent.' );

		$last    = end( $g2rd_wp_mail_log );
		$message = (string) $last['message'];

		// Core fields.
		$this->assertStringContainsString( 'Mon article complet', $message );
		$this->assertStringContainsString( 'mon-article-complet', $message );
		$this->assertStringContainsString( 'publish', $message );

		// Categories + tags.
		$this->assertStringContainsString( '12', $message );
		$this->assertStringContainsString( '34', $message );
		$this->assertStringContainsString( 'wordpress', $message );
		$this->assertStringContainsString( 'mcp', $message );

		// Featured image.
		$this->assertStringContainsString( 'https://example.com/photo.png', $message );
		$this->assertStringContainsString( 'Une photo de test', $message );

		// SEO metas.
		$this->assertStringContainsString( 'Titre SEO optimisé', $message );
		$this->assertStringContainsString( 'Description SEO complète', $message );
		$this->assertStringContainsString( 'mot-clé-cible', $message );
		$this->assertStringContainsString( 'OG titre', $message );
		$this->assertStringContainsString( 'OG description', $message );
		$this->assertStringContainsString( 'https://example.com/canonical', $message );
	}

	/** Nominal orchestration: post created, featured image set, terms assigned, report built. */
	public function test_create_full_post_nominal(): void {
		global $g2rd_post_store, $g2rd_post_thumbnails, $g2rd_post_terms;

		$ok = $this->call_private( 'exec_create_full_post', [ $this->sample_args() ] );

		$this->assertTrue( $ok );

		$post_ids = array_keys(
			array_filter( $g2rd_post_store, static fn( $p ) => 'post' === $p->post_type )
		);
		$this->assertCount( 1, $post_ids );
		$post_id = $post_ids[0];

		// Featured image set.
		$this->assertArrayHasKey( $post_id, $g2rd_post_thumbnails );
		$this->assertGreaterThan( 0, $g2rd_post_thumbnails[ $post_id ] );

		// Terms assigned.
		$this->assertSame( [ 12, 34 ], $g2rd_post_terms[ $post_id ]['category'] );
		$this->assertSame( [ 'wordpress', 'mcp' ], $g2rd_post_terms[ $post_id ]['post_tag'] );

		// Structured report.
		$report = $this->queue->get_last_report();
		$this->assertTrue( $report['success'] );
		$this->assertSame( 'g2rd_create-full-post', $report['tool'] );
		$this->assertSame( $post_id, $report['post_id'] );
		$this->assertGreaterThan( 0, $report['attachment_id'] );
		$this->assertSame( 'ok', $report['steps']['image'] );
		$this->assertSame( 'ok', $report['steps']['post'] );
		$this->assertSame( 'ok', $report['steps']['featured'] );
		$this->assertArrayHasKey( 'seo', $report['steps'] );
	}

	/** Image failure is non-blocking: the post is still created. */
	public function test_create_full_post_image_failure_is_non_blocking(): void {
		global $g2rd_media_sideload_result;

		$g2rd_media_sideload_result = new \WP_Error( 'sideload_failed', 'boom' );

		$ok = $this->call_private( 'exec_create_full_post', [ $this->sample_args() ] );

		$this->assertTrue( $ok );

		$report = $this->queue->get_last_report();
		$this->assertTrue( $report['success'] );
		$this->assertSame( 0, $report['attachment_id'] );
		$this->assertStringStartsWith( 'failed', $report['steps']['image'] );
		$this->assertSame( 'ok', $report['steps']['post'] );
	}

	/** When wp_insert_post fails, the sideloaded media is rolled back. */
	public function test_create_full_post_rolls_back_media_on_insert_failure(): void {
		global $g2rd_wp_insert_post_result, $g2rd_deleted_attachments, $g2rd_post_store;

		$g2rd_wp_insert_post_result = new \WP_Error( 'insert_failed', 'nope' );

		$ok = $this->call_private( 'exec_create_full_post', [ $this->sample_args() ] );

		$this->assertFalse( $ok );

		$report = $this->queue->get_last_report();
		$this->assertFalse( $report['success'] );
		$this->assertSame( 'post_insert_failed', $report['error'] );
		$this->assertSame( 'rolled_back', $report['steps']['image'] );

		// The imported attachment was deleted (no dangling media).
		$this->assertNotEmpty( $g2rd_deleted_attachments );
		$this->assertArrayNotHasKey( $g2rd_deleted_attachments[0], $g2rd_post_store );
	}

	/** Missing title is refused before any insert. */
	public function test_create_full_post_requires_title(): void {
		$args          = $this->sample_args();
		$args['title'] = '';

		$ok = $this->call_private( 'exec_create_full_post', [ $args ] );

		$this->assertFalse( $ok );
		$this->assertSame( 'missing_title', $this->queue->get_last_report()['error'] );
	}

	/** Batch: sequential dispatch, per-op report, rejection of nested/unknown tools. */
	public function test_batch_dispatch_and_guards(): void {
		$ok = $this->call_private(
			'exec_batch',
			[
				[
					'operations' => [
						[ 'tool' => 'g2rd_create-category', 'arguments' => [ 'name' => 'Actualités' ] ],
						[ 'tool' => 'g2rd_batch', 'arguments' => [] ],   // nested → rejected
						[ 'tool' => 'g2rd_unknown', 'arguments' => [] ], // unknown → rejected
					],
				],
			]
		);

		$this->assertTrue( $ok );

		$report = $this->queue->get_last_report();
		$this->assertTrue( $report['batch'] );
		$this->assertCount( 3, $report['operations'] );
		$this->assertTrue( $report['operations'][0]['success'] );
		$this->assertFalse( $report['operations'][1]['success'] );
		$this->assertSame( 'tool_not_allowed', $report['operations'][1]['error'] );
		$this->assertFalse( $report['operations'][2]['success'] );
		$this->assertSame( 'tool_not_allowed', $report['operations'][2]['error'] );
	}

	/** Batch with no operations fails cleanly. */
	public function test_batch_empty_fails(): void {
		$ok = $this->call_private( 'exec_batch', [ [ 'operations' => [] ] ] );

		$this->assertFalse( $ok );
		$this->assertSame( 'no_operations', $this->queue->get_last_report()['error'] );
	}
}
