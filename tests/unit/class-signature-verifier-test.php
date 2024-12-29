<?php
/**
 * Signature verifier tests.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP\Tests;

use Farcaster_WP\Signature_Verifier;
use WP_UnitTestCase;

/**
 * These tests prove the signature verifier works.
 */
class Signature_Verifier_Test extends WP_UnitTestCase {

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
	 * Sample invalid signature data for testing.
	 *
	 * @var array
	 */
	private $invalid_signature_data;

	/**
	 * Set up test data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->valid_signature_data   = json_decode( '{"header":"eyJmaWQiOjQ2OCwidHlwZSI6ImFwcF9rZXkiLCJrZXkiOiIweDYwZTZkZDdkNjMxZDAwYTBkOTI4OTlmNDNlYWM4ZDE4M2UzN2IzMzRmNzgwZmM0NWExOTExY2VmMGEyZWU5YTcifQ","payload":"eyJldmVudCI6Im5vdGlmaWNhdGlvbnNfZW5hYmxlZCIsIm5vdGlmaWNhdGlvbkRldGFpbHMiOnsidXJsIjoiaHR0cHM6Ly9hcGkud2FycGNhc3QuY29tL3YxL2ZyYW1lLW5vdGlmaWNhdGlvbnMiLCJ0b2tlbiI6IjAxOTNkYmQzLWEwNjAtMzljOC0wYTkwLTc4MTJlNzU3N2FmNyJ9fQ","signature":"A4mHBGMa-d6KBJ8ZV57Qm83gtUSulaVpjCNcHzsrCjHuei1lC8Tm6g29mp_05qzeDGLOQ_uIS5wXLeX0kCuiCA"}', true );
		$this->another_signature      = json_decode( '{"header":"eyJmaWQiOjkxNzYwMiwidHlwZSI6ImFwcF9rZXkiLCJrZXkiOiIweDA0Zjg5NWQxM2IzYjI4YmM0MjRkOTQ2OTkyMGYyNzYyMjBjYzE2NWU1ZDYxNjFjOWZhZTRkNGFiZGI5OTk1ZDcifQ","payload":"eyJldmVudCI6ImZyYW1lX2FkZGVkIiwibm90aWZpY2F0aW9uRGV0YWlscyI6eyJ1cmwiOiJodHRwczovL2FwaS53YXJwY2FzdC5jb20vdjEvZnJhbWUtbm90aWZpY2F0aW9ucyIsInRva2VuIjoiMDE5M2ZlMGItMDZiMy04OGFiLTI1ZmEtNTg5ZGMxYTZkOTNhIn19","signature":"y5RGn2ScKPWlQo6rlgEJyXmBeadniQqV_LMTIHf4Ra7nlmK5GMks-L0Hfzvi8e_-zTflJo7NwP6_yRj82rEEDg"}', true );
		$this->invalid_signature_data = json_decode( '{"header":"eyJmaWQiOjQ2OCwidHlwZSI6ImFwcF9rZXkiLCJrZXkiOiIweDYwZTZkZDdkNjMxZDAwYTBkOTI4OTlmNDNlYWM4ZDE4M2UzN2IzMzRmNzgwZmM0NWExOTExY2VmMGEyZWU5YTcifQ","payload":"eyJldmVudCI6Im5vdGlmaWNhdGlvbnNfZW5hYmxlZCIsIm5vdGlmaWNhdGlvbkRldGFpbHMiOnsidXJsIjoiaHR0cHM6Ly9hcGkud2FycGNhc3QuY29tL3YxL2ZyYW1lLW5vdGlmaWNhdGlvbnMiLCJ0b2tlbiI6IjAxOTNkYmQzLWEwNjAtMzljOC0wYTkwLTc4MTJlNzU3N2FmNyJ9fQ","signature":"A4mHBGMa-d6KBJ8ZV57Qm83gtUSulaVpjCNcHzsrCjHuei1lC8Tm6g29mp_05qzeDGLOO_uIS5wXLeX0kCuiCA"}', true );
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
	public function test_verify_invalid_signature_length() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid signature length' );

		$invalid_data              = $this->valid_signature_data;
		$invalid_data['signature'] = 'invalid_signature';

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with missing data structure.
	 */
	public function test_verify_invalid_data_structure() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid signature data structure' );

		$invalid_data = array(
			'header' => 'some_header',
			// Missing payload and signature.
		);

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with invalid header JSON.
	 */
	public function test_verify_invalid_header_json() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Error decoding and parsing header' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{invalid json' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with invalid app key format (no 0x prefix).
	 */
	public function test_verify_invalid_app_key_format_no_prefix() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid app key format - must start with 0x' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{"fid":468,"type":"app_key","key":"invalidkey"}' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with invalid hex in app key.
	 */
	public function test_verify_invalid_app_key_format_invalid_hex() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Error checking signature: sodium_crypto_sign_verify_detached(): Argument #3 ($public_key) must be SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES bytes long' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{"fid":468,"type":"app_key","key":"0x1234"}' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with an empty string in app key.
	 */
	public function test_verify_invalid_app_key_format_empty_string() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid app key format' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{"fid":468,"type":"app_key","key":""}' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with odd-length hex string in app key.
	 */
	public function test_verify_invalid_app_key_format_odd_length() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid app key format' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{"fid":468,"type":"app_key","key":"0x123"}' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with invalid hex characters in app key.
	 */
	public function test_verify_invalid_app_key_format_invalid_hex_chars() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid app key format' );

		$invalid_data           = $this->valid_signature_data;
		$invalid_data['header'] = base64_encode( '{"fid":468,"type":"app_key","key":"0xzzzz1234zzzz"}' );

		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification fails with invalid signature.
	 */
	public function test_verify_invalid_signature() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Signature verification failed' );

		$invalid_data = $this->invalid_signature_data;
		Signature_Verifier::verify( $invalid_data );
	}

	/**
	 * Test that signature verification works with valid signature but no RPC URL.
	 */
	public function test_verify_valid_signature_no_rpc_url() {
		$options = get_option( 'farcaster_wp', array() );
		$old_rpc = $options['rpc_url'] ?? '';

		// Temporarily remove RPC URL.
		$options['rpc_url'] = '';
		update_option( 'farcaster_wp', $options );

		try {
			$this->assertTrue(
				Signature_Verifier::verify( $this->valid_signature_data )
			);
		} finally {
			// Restore original RPC URL.
			$options['rpc_url'] = $old_rpc;
			update_option( 'farcaster_wp', $options );
		}
	}
}
