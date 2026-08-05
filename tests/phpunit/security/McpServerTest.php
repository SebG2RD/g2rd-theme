<?php
/**
 * Tests — McpServer (JSON-RPC 2.0 dispatcher)
 *
 * Tests the full request/response cycle using a mock SecurityGate so no DB
 * is required. McpAbilities is used directly (no WP_Query calls in these tests).
 *
 * @package    G2RD\Tests
 * @since      1.12.0
 */

declare(strict_types=1);

namespace G2RD\Tests;

use G2RD\McpAbilities;
use G2RD\McpServer;
use G2RD\McpSecurityGate;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for McpServer JSON-RPC dispatch and auth integration.
 */
final class McpServerTest extends TestCase {

	// ── Shared allowed gate result ────────────────────────────────────────────

	/** @return array<string, mixed> */
	private function allowed_result( string $scope = 'read_only' ): array {
		return [
			'allowed'       => true,
			'user_id'       => 1,
			'token_id'      => 1,
			'scope'         => $scope,
			'denial_reason' => '',
		];
	}

	/** @return array<string, mixed> */
	private function denied_result( string $reason = 'Token is invalid, expired or revoked' ): array {
		return [
			'allowed'       => false,
			'user_id'       => 0,
			'token_id'      => 0,
			'scope'         => '',
			'denial_reason' => $reason,
		];
	}

	/**
	 * Builds a McpServer with an optional partial mock gate.
	 *
	 * @param array<string, mixed>|null $gate_return Return value for authorize().
	 *                                               Null means use real gate (default = allowed).
	 */
	private function make_server( ?array $gate_return = null ): McpServer {
		$gate = $this->getMockBuilder( McpSecurityGate::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'authorize' ] )
			->getMock();

		$gate->method( 'authorize' )
			->willReturn( $gate_return ?? $this->allowed_result() );

		return new McpServer( $gate, new McpAbilities() );
	}

	/** Helper: builds a WP_REST_Request with JSON body and optional bearer token. */
	private function make_request( array $body, string $token = '' ): WP_REST_Request {
		$req = new WP_REST_Request();
		$req->set_json_params( $body );
		if ( '' !== $token ) {
			$req->set_header( 'authorization', "Bearer {$token}" );
		}
		return $req;
	}

	/** Helper: extracts the JSON-RPC response data from WP_REST_Response. */
	private function data( WP_REST_Response $resp ): array {
		return (array) $resp->data;
	}

	// ── Test 1 : invalid JSON-RPC body ────────────────────────────────────────

	/**
	 * A non-array body returns -32600 Invalid Request.
	 */
	public function test_invalid_body_returns_invalid_request(): void {
		$server = $this->make_server();
		$req    = new WP_REST_Request();
		$req->set_json_params( [] ); // set to array then override via reflection

		// Build a request that returns null from get_json_params.
		$bad_req = new class extends WP_REST_Request {
			public function get_json_params(): ?array { return null; }
		};

		$resp = $server->handle_request( $bad_req );
		$data = $this->data( $resp );

		$this->assertSame( 200, $resp->status );
		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( -32600, $data['error']['code'] );
		$this->assertNull( $data['id'] );
	}

	/**
	 * A request without 'jsonrpc' field returns -32600.
	 */
	public function test_missing_jsonrpc_field_returns_invalid_request(): void {
		$server = $this->make_server();
		$req    = $this->make_request( [ 'method' => 'tools/list', 'id' => 1 ] );

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( -32600, $data['error']['code'] );
	}

	// ── Test 2 : notification (no id) ─────────────────────────────────────────

	/**
	 * A notification (no 'id' key) returns HTTP 202 with no response body.
	 */
	public function test_notification_returns_202(): void {
		$server = $this->make_server();
		$req    = $this->make_request( [
			'jsonrpc' => '2.0',
			'method'  => 'notifications/initialized',
		] ); // no 'id' key

		$resp = $server->handle_request( $req );

		$this->assertSame( 202, $resp->status );
		$this->assertNull( $resp->data );
	}

	// ── Test 3 : initialize ───────────────────────────────────────────────────

	/**
	 * initialize returns server capabilities without requiring a token.
	 */
	public function test_initialize_returns_capabilities(): void {
		$server = $this->make_server();
		$req    = $this->make_request( [
			'jsonrpc' => '2.0',
			'method'  => 'initialize',
			'id'      => 1,
		] ); // no Authorization header

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( 200, $resp->status );
		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( 1, $data['id'] );
		$this->assertArrayHasKey( 'result', $data );

		$result = $data['result'];
		$this->assertSame( '2024-11-05', $result['protocolVersion'] );
		$this->assertArrayHasKey( 'capabilities', $result );
		$this->assertArrayHasKey( 'tools', $result['capabilities'] );
		$this->assertArrayHasKey( 'serverInfo', $result );
		$this->assertSame( 'G2RD MCP Server', $result['serverInfo']['name'] );
	}

	// ── Test 4 : missing Authorization header ────────────────────────────────

	/**
	 * tools/list without Authorization header returns -32001.
	 */
	public function test_tools_list_without_token_returns_auth_error(): void {
		$server = $this->make_server();
		$req    = $this->make_request( [
			'jsonrpc' => '2.0',
			'method'  => 'tools/list',
			'id'      => 2,
		] ); // no Authorization header

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( 200, $resp->status );
		$this->assertSame( -32001, $data['error']['code'] );
		$this->assertSame( 2, $data['id'] );
	}

	// ── Test 5 : gate denial ─────────────────────────────────────────────────

	/**
	 * When SecurityGate denies, tools/list returns -32001 with denial reason.
	 */
	public function test_gate_denial_returns_32001(): void {
		$server = $this->make_server( $this->denied_result( 'IP locked out' ) );
		$req    = $this->make_request(
			[ 'jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 3 ],
			'g2rd_sometoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( -32001, $data['error']['code'] );
		$this->assertStringContainsString( 'IP locked out', $data['error']['message'] );
	}

	// ── Test 6 : tools/list success ──────────────────────────────────────────

	/**
	 * Authorized tools/list returns a list of tool objects.
	 */
	public function test_tools_list_returns_tool_array(): void {
		$server = $this->make_server();
		$req    = $this->make_request(
			[ 'jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 4 ],
			'g2rd_validtoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( 200, $resp->status );
		$this->assertSame( '2.0', $data['jsonrpc'] );
		$this->assertSame( 4, $data['id'] );
		$this->assertArrayHasKey( 'result', $data );
		$this->assertArrayHasKey( 'tools', $data['result'] );

		$tools = $data['result']['tools'];
		$this->assertIsArray( $tools );
		$this->assertCount( 58, $tools ); // 28 read-only + 30 write (+ WooCommerce variations)

		// Each tool must expose name, description, inputSchema.
		foreach ( $tools as $tool ) {
			$this->assertArrayHasKey( 'name', $tool );
			$this->assertArrayHasKey( 'description', $tool );
			$this->assertArrayHasKey( 'inputSchema', $tool );
		}
	}

	// ── Test 7 : unknown method ───────────────────────────────────────────────

	/**
	 * An unrecognized method with a valid token returns -32601 Method not found.
	 */
	public function test_unknown_method_returns_32601(): void {
		$server = $this->make_server();
		$req    = $this->make_request(
			[ 'jsonrpc' => '2.0', 'method' => 'resources/list', 'id' => 5 ],
			'g2rd_validtoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( -32601, $data['error']['code'] );
		$this->assertSame( 5, $data['id'] );
	}

	// ── Test 8 : tools/call unknown tool ─────────────────────────────────────

	/**
	 * tools/call with an unknown tool name returns -32601.
	 */
	public function test_tools_call_unknown_tool_returns_32601(): void {
		$server = $this->make_server();
		$req    = $this->make_request(
			[
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'id'      => 6,
				'params'  => [ 'name' => 'g2rd_nonexistent', 'arguments' => [] ],
			],
			'g2rd_validtoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( -32601, $data['error']['code'] );
		$this->assertStringContainsString( 'nonexistent', $data['error']['message'] );
	}

	// ── Test 9 : tools/call missing name ─────────────────────────────────────

	/**
	 * tools/call without 'name' param returns -32602 Invalid params.
	 */
	public function test_tools_call_missing_name_returns_32602(): void {
		$server = $this->make_server();
		$req    = $this->make_request(
			[
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'id'      => 7,
				'params'  => [ 'arguments' => [] ], // no 'name'
			],
			'g2rd_validtoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( -32602, $data['error']['code'] );
	}

	// ── Test 10 : tools/call success (get-site-info) ──────────────────────────

	/**
	 * tools/call g2rd/get-site-info returns a successful text payload.
	 */
	public function test_tools_call_get_site_info_returns_success(): void {
		$server = $this->make_server();
		$req    = $this->make_request(
			[
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'id'      => 8,
				'params'  => [ 'name' => 'g2rd_get-site-info', 'arguments' => [] ],
			],
			'g2rd_validtoken'
		);

		$resp = $server->handle_request( $req );
		$data = $this->data( $resp );

		$this->assertSame( 200, $resp->status );
		$this->assertArrayHasKey( 'result', $data );

		$result  = $data['result'];
		$content = $result['content'][0] ?? [];

		$this->assertSame( 'text', $content['type'] );
		$this->assertFalse( $result['isError'] );

		$site_data = json_decode( $content['text'], true );
		$this->assertSame( 'Test Site', $site_data['name'] );
		$this->assertSame( 'https://example.com', $site_data['url'] );
	}
}
