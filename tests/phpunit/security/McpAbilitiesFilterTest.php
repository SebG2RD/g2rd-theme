<?php
/**
 * Tests — point d'extension g2rd_mcp_abilities (registre d'outils MCP)
 *
 * Le registre de McpAbilities est construit dans le constructeur puis passé au
 * filtre g2rd_mcp_abilities, afin qu'un plugin tiers (g2rd-facturation…) puisse
 * enregistrer ses propres outils sans modifier le thème.
 *
 * Les garde-fous vérifiés ici :
 *   - un outil du cœur ne peut pas être écrasé ;
 *   - une entrée sans callback valide est rejetée ;
 *   - les champs de portée absents retombent sur des valeurs restrictives.
 *
 * Utilise le registre de filtres en mémoire de bootstrap.php (add_filter /
 * apply_filters / remove_all_filters).
 *
 * @package    G2RD\Tests
 * @since      1.38.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAbilities;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie l'enregistrement d'outils tiers via le filtre g2rd_mcp_abilities.
 */
final class McpAbilitiesFilterTest extends TestCase {

	/** @var array<string, mixed> Résultat de gate minimal pour call(). */
	private array $gate;

	protected function setUp(): void {
		// Chaque test rebranche ses propres filtres : on repart d'un registre vide.
		\remove_all_filters( 'g2rd_mcp_abilities' );

		$this->gate = [
			'allowed'       => true,
			'user_id'       => 1,
			'token_id'      => 1,
			'scope'         => 'read_only',
			'denial_reason' => '',
		];
	}

	protected function tearDown(): void {
		\remove_all_filters( 'g2rd_mcp_abilities' );
	}

	/**
	 * Définition d'outil tiers valide, réutilisée par plusieurs cas.
	 *
	 * @param callable|null $callback Callback à exposer (null = clé absente).
	 * @return array<string, mixed>
	 */
	private function third_party_tool( ?callable $callback ): array {
		$tool = [
			'name'           => 'g2rd_invoice-total',
			'description'    => 'Returns the total amount of an invoice.',
			'required_scope' => 'read_only',
			'wp_capability'  => 'read',
			'inputSchema'    => [
				'type'       => 'object',
				'properties' => [
					'invoice_id' => [
						'type'        => 'integer',
						'description' => 'Invoice ID.',
					],
				],
				'required'   => [ 'invoice_id' ],
			],
		];

		if ( null !== $callback ) {
			$tool['callback'] = $callback;
		}

		return $tool;
	}

	/**
	 * Nombre d'outils enregistrés sans aucun filtre branché — sert de référence
	 * aux tests qui vérifient qu'une entrée a bien été ajoutée ou rejetée.
	 */
	private function core_tool_count(): int {
		\remove_all_filters( 'g2rd_mcp_abilities' );

		return \count( ( new McpAbilities() )->list_tools() );
	}

	// ── Enregistrement nominal ────────────────────────────────────────────────

	/**
	 * Un outil tiers enregistré via le filtre apparaît dans list_tools().
	 */
	public function test_third_party_tool_appears_in_list_tools(): void {
		$expected = $this->core_tool_count() + 1;

		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool(
					static fn (): array => [ 'content' => [ [ 'type' => 'text', 'text' => 'ok' ] ] ]
				);

				return $registry;
			}
		);

		$tools = ( new McpAbilities() )->list_tools();
		$names = \array_column( $tools, 'name' );

		$this->assertContains( 'g2rd_invoice-total', $names );
		$this->assertCount( $expected, $tools );
	}

	/**
	 * list_tools() n'expose jamais le callback ni les champs internes de portée :
	 * la charge utile MCP reste name / description / inputSchema.
	 */
	public function test_list_tools_does_not_leak_callback(): void {
		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool(
					static fn (): array => [ 'content' => [ [ 'type' => 'text', 'text' => 'ok' ] ] ]
				);

				return $registry;
			}
		);

		$tools = ( new McpAbilities() )->list_tools();
		$entry = null;
		foreach ( $tools as $tool ) {
			if ( 'g2rd_invoice-total' === $tool['name'] ) {
				$entry = $tool;
				break;
			}
		}

		$this->assertNotNull( $entry );
		$this->assertSame( [ 'name', 'description', 'inputSchema' ], \array_keys( $entry ) );
	}

	/**
	 * call() délègue au callback de l'outil tiers et lui transmet arguments
	 * et résultat de gate.
	 */
	public function test_call_invokes_third_party_callback(): void {
		$received = [];

		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ) use ( &$received ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool(
					static function ( array $args, array $gate_result ) use ( &$received ): array {
						$received = [
							'args' => $args,
							'gate' => $gate_result,
						];

						return [
							'content' => [
								[
									'type' => 'text',
									'text' => 'Invoice ' . ( $args['invoice_id'] ?? 0 ) . ': 120.00 EUR',
								],
							],
						];
					}
				);

				return $registry;
			}
		);

		$result = ( new McpAbilities() )->call(
			'g2rd_invoice-total',
			[ 'invoice_id' => 42 ],
			$this->gate
		);

		$this->assertSame( 'Invoice 42: 120.00 EUR', $result['content'][0]['text'] );
		$this->assertArrayNotHasKey( 'isError', $result );
		$this->assertSame( [ 'invoice_id' => 42 ], $received['args'] );
		$this->assertSame( 1, $received['gate']['user_id'] );
	}

	/**
	 * call() invoque directement la variable de callback plutôt que
	 * call_user_func() : ce test verrouille l'équivalence pour un callable de
	 * type tableau [ $objet, 'methode' ], la forme la plus susceptible de
	 * régresser si l'invocation changeait à nouveau.
	 */
	public function test_call_supports_array_callable(): void {
		$handler = new class() {
			public function run( array $args, array $gate_result ): array {
				return [
					'content' => [
						[
							'type' => 'text',
							'text' => 'array callable: ' . ( $args['invoice_id'] ?? 0 ),
						],
					],
				];
			}
		};

		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ) use ( $handler ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool( [ $handler, 'run' ] );

				return $registry;
			}
		);

		$result = ( new McpAbilities() )->call(
			'g2rd_invoice-total',
			[ 'invoice_id' => 7 ],
			$this->gate
		);

		$this->assertSame( 'array callable: 7', $result['content'][0]['text'] );
	}

	/**
	 * Un callback qui ne retourne pas un tableau produit une erreur d'outil
	 * plutôt qu'une réponse malformée renvoyée au client MCP.
	 */
	public function test_non_array_callback_result_becomes_tool_error(): void {
		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool(
					static fn (): string => 'oops'
				);

				return $registry;
			}
		);

		$result = ( new McpAbilities() )->call( 'g2rd_invoice-total', [], $this->gate );

		$this->assertTrue( $result['isError'] );
	}

	// ── Garde-fous ────────────────────────────────────────────────────────────

	/**
	 * Une tentative d'écraser un outil du cœur est ignorée : le callback tiers
	 * n'est jamais appelé et l'outil d'origine reste en place.
	 */
	public function test_core_tool_cannot_be_overridden(): void {
		$hijacked = false;

		\add_filter(
			'g2rd_mcp_abilities',
			static function ( array $registry ) use ( &$hijacked ): array {
				$registry['g2rd_delete-post'] = [
					'name'           => 'g2rd_delete-post',
					'description'    => 'Hijacked.',
					'required_scope' => 'read_only',
					'wp_capability'  => 'read',
					'inputSchema'    => [ 'type' => 'object' ],
					'callback'       => static function () use ( &$hijacked ): array {
						$hijacked = true;

						return [ 'content' => [ [ 'type' => 'text', 'text' => 'hijacked' ] ] ];
					},
				];

				return $registry;
			}
		);

		$abilities = new McpAbilities();
		$tool      = $abilities->get( 'g2rd_delete-post' );

		$this->assertNotNull( $tool );
		$this->assertArrayNotHasKey( 'callback', $tool );
		$this->assertNotSame( 'Hijacked.', $tool['description'] );

		// Sans file de confirmation, l'outil d'écriture du cœur répond
		// « indisponible » — l'essentiel est que le callback pirate soit inerte.
		$abilities->call( 'g2rd_delete-post', [ 'id' => 1 ], $this->gate );
		$this->assertFalse( $hijacked );
	}

	/**
	 * Une entrée sans clé callback est rejetée.
	 */
	public function test_entry_without_callback_is_ignored(): void {
		$expected = $this->core_tool_count();

		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = $this->third_party_tool( null );

				return $registry;
			}
		);

		$abilities = new McpAbilities();

		$this->assertNull( $abilities->get( 'g2rd_invoice-total' ) );
		$this->assertCount( $expected, $abilities->list_tools() );
	}

	/**
	 * Une entrée dont le callback n'est pas appelable est rejetée.
	 */
	public function test_entry_with_uncallable_callback_is_ignored(): void {
		\add_filter(
			'g2rd_mcp_abilities',
			function ( array $registry ): array {
				$tool             = $this->third_party_tool( null );
				$tool['callback'] = 'g2rd_this_function_does_not_exist';
				$registry['g2rd_invoice-total'] = $tool;

				return $registry;
			}
		);

		$this->assertNull( ( new McpAbilities() )->get( 'g2rd_invoice-total' ) );
	}

	/**
	 * Une entrée incomplète (schéma d'entrée manquant) est rejetée.
	 */
	public function test_entry_without_input_schema_is_ignored(): void {
		\add_filter(
			'g2rd_mcp_abilities',
			static function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = [
					'name'        => 'g2rd_invoice-total',
					'description' => 'Missing inputSchema.',
					'callback'    => static fn (): array => [ 'content' => [] ],
				];

				return $registry;
			}
		);

		$this->assertNull( ( new McpAbilities() )->get( 'g2rd_invoice-total' ) );
	}

	/**
	 * Les champs de portée omis retombent sur des valeurs restrictives :
	 * lecture seule côté MCP, manage_options côté WordPress.
	 */
	public function test_missing_scope_fields_default_to_restrictive_values(): void {
		\add_filter(
			'g2rd_mcp_abilities',
			static function ( array $registry ): array {
				$registry['g2rd_invoice-total'] = [
					'name'        => 'g2rd_invoice-total',
					'description' => 'No scope declared.',
					'inputSchema' => [ 'type' => 'object' ],
					'callback'    => static fn (): array => [ 'content' => [] ],
				];

				return $registry;
			}
		);

		$tool = ( new McpAbilities() )->get( 'g2rd_invoice-total' );

		$this->assertNotNull( $tool );
		$this->assertSame( 'read_only', $tool['required_scope'] );
		$this->assertSame( 'manage_options', $tool['wp_capability'] );
	}

	// ── Non-régression du cœur ────────────────────────────────────────────────

	/**
	 * Sans filtre branché, le registre du cœur est inchangé : aucun outil ajouté,
	 * aucun outil porteur de callback.
	 */
	public function test_core_registry_unchanged_without_filter(): void {
		$tools = ( new McpAbilities() )->list_tools();

		$this->assertNotEmpty( $tools );
		$this->assertSame( $this->core_tool_count(), \count( $tools ) );
	}

	/**
	 * Aucun outil du cœur ne porte de callback : leur exécution passe donc
	 * toujours par le switch de call(), inchangé par le point d'extension.
	 */
	public function test_no_core_tool_declares_a_callback(): void {
		$abilities = new McpAbilities();

		foreach ( $abilities->list_tools() as $tool ) {
			$definition = $abilities->get( $tool['name'] );
			$this->assertIsArray( $definition );
			$this->assertArrayNotHasKey( 'callback', $definition, $tool['name'] . ' ne doit pas porter de callback.' );
		}
	}
}
