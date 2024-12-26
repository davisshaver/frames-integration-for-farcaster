<?php
/**
 * Signature verifier tests.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests;

use PHPUnit\Framework\TestCase;
use Farcaster_WP\Signature_Verifier;

/**
 * These tests prove the signature verifier works.
 */
class Signature_Verifier_Test extends TestCase {

	/**
	 * Sample valid signature data for testing.
	 *
	 * @var array
	 */
	private $valid_signature_data;

	/**
	 * Sample valid signature data for testing.
	 *
	 * @var array
	 */
	private $another_signature;

	/**
	 * Set up test data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->valid_signature_data = json_decode( '{"header":"eyJmaWQiOjQ2OCwidHlwZSI6ImFwcF9rZXkiLCJrZXkiOiIweDYwZTZkZDdkNjMxZDAwYTBkOTI4OTlmNDNlYWM4ZDE4M2UzN2IzMzRmNzgwZmM0NWExOTExY2VmMGEyZWU5YTcifQ","payload":"eyJldmVudCI6Im5vdGlmaWNhdGlvbnNfZW5hYmxlZCIsIm5vdGlmaWNhdGlvbkRldGFpbHMiOnsidXJsIjoiaHR0cHM6Ly9hcGkud2FycGNhc3QuY29tL3YxL2ZyYW1lLW5vdGlmaWNhdGlvbnMiLCJ0b2tlbiI6IjAxOTNkYmQzLWEwNjAtMzljOC0wYTkwLTc4MTJlNzU3N2FmNyJ9fQ","signature":"A4mHBGMa-d6KBJ8ZV57Qm83gtUSulaVpjCNcHzsrCjHuei1lC8Tm6g29mp_05qzeDGLOQ_uIS5wXLeX0kCuiCA"}', true );
		$this->another_signature    = json_decode( '{"header":"eyJmaWQiOjkxNzYwMiwidHlwZSI6ImFwcF9rZXkiLCJrZXkiOiIweDA0Zjg5NWQxM2IzYjI4YmM0MjRkOTQ2OTkyMGYyNzYyMjBjYzE2NWU1ZDYxNjFjOWZhZTRkNGFiZGI5OTk1ZDcifQ","payload":"eyJldmVudCI6ImZyYW1lX2FkZGVkIiwibm90aWZpY2F0aW9uRGV0YWlscyI6eyJ1cmwiOiJodHRwczovL2FwaS53YXJwY2FzdC5jb20vdjEvZnJhbWUtbm90aWZpY2F0aW9ucyIsInRva2VuIjoiMDE5M2ZlMGItMDZiMy04OGFiLTI1ZmEtNTg5ZGMxYTZkOTNhIn19","signature":"y5RGn2ScKPWlQo6rlgEJyXmBeadniQqV_LMTIHf4Ra7nlmK5GMks-L0Hfzvi8e_-zTflJo7NwP6_yRj82rEEDg"}', true );
	}

	/**
	 * Test that signature verification works with valid data.
	 */
	public function test_verify_valid_signature() {
		$this->assertTrue(
			Signature_Verifier::verify( $this->valid_signature_data )
		);
	}

	/**
	 * Test that signature verification works with another valid data.
	 */
	public function test_verify_another_valid_signature() {
		$this->assertTrue(
			Signature_Verifier::verify( $this->another_signature )
		);
	}

	/**
	 * Test that signature verification fails with invalid signature.
	 */
	public function test_verify_invalid_signature() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid signature length' );

		$invalid_data              = $this->valid_signature_data;
		$invalid_data['signature'] = 'invalid_signature';

		Signature_Verifier::verify( $invalid_data );
	}
}
