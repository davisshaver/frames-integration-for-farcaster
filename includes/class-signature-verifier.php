<?php
/**
 * Farcaster signature verification.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Exception;
use SodiumException;
use SWeb3\SWeb3;
use SWeb3\SWeb3_Contract;
use StdClass;

/**
 * Class to handle Farcaster signature verification
 *
 * See reference implementation: https://github.com/farcasterxyz/frames/blob/main/packages/frame-node/src/jfs.ts
 */
class Signature_Verifier {

	private const KEY_REGISTRY_ADDRESS = '0x00000000fc1237824fb747abde0ff18990e59b7e';

	/**
	 * Get the Key Registry ABI.
	 *
	 * @return array The Key Registry ABI.
	 */
	private static function get_key_registry_abi() {
		return wp_json_file_decode( FARCASTER_WP_PLUGIN_DIR . '/includes/contracts/abi/key_registry.json', true );
	}

	/**
	 * Get the RPC URL from the options.
	 *
	 * @return string The RPC URL.
	 */
	public static function get_rpc_url() {
		$options = get_option( 'farcaster_wp', array() );
		return $options['rpc_url'] ?? '';
	}

	/**
	 * Verifies a Farcaster signature
	 *
	 * @param array $data The signature data containing header, payload, and signature.
	 * @throws Exception If the signature is invalid or verification fails.
	 * @return bool True if signature is valid.
	 */
	public static function verify( $data ): bool {
		if ( ! isset( $data['header'], $data['payload'], $data['signature'] ) ) {
			throw new Exception( 'Invalid signature data structure' );
		}

		// Decode and parse header.
		try {
			$header_json = base64_decode( $data['header'] );
			$header      = json_decode( $header_json, true );
			
			if ( ! isset( $header['fid'], $header['key'] ) ) {
				throw new Exception( 'Invalid header structure' );
			}
		} catch ( Exception $e ) {
			throw new Exception( 'Error decoding and parsing header: ' . esc_html( $e->getMessage() ) );
		}

		// Verify signature length (base64url decoded).
		$signature = self::base64url_decode( $data['signature'] );
		if ( strlen( $signature ) !== 64 ) {
			throw new Exception( 'Invalid signature length' );
		}

		// Convert hex key to binary.
		if ( strpos( $header['key'], '0x' ) !== 0 ) {
			throw new Exception( 'Invalid app key format - must start with 0x' );
		}

		try {
			$app_key = hex2bin( substr( $header['key'], 2 ) );
			if ( ! $app_key ) {
				throw new Exception( 'Invalid app key format' );
			}
		} catch ( \Exception $e ) {
			throw new Exception( 'Invalid app key format' );
		}

		// Create signed input.
		$signed_input = $data['header'] . '.' . $data['payload'];

		try {
			$verified = sodium_crypto_sign_verify_detached(
				$signature,
				$signed_input,
				$app_key
			);
		} catch ( SodiumException $e ) {
			throw new Exception( 'Error checking signature: ' . esc_html( $e->getMessage() ) );
		}

		if ( ! $verified ) {
			throw new Exception( 'Signature verification failed' );
		}

		$rpc_url = self::get_rpc_url();
		if ( ! $rpc_url ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Farcaster signature verification passed with condition that RPC URL is not set' );
			return true;
		}

		$key_data = new StdClass();

		try {
			$sweb3    = new SWeb3( self::get_rpc_url() );
			$contract = new SWeb3_Contract( $sweb3, self::KEY_REGISTRY_ADDRESS, wp_json_encode( self::get_key_registry_abi() ) );
			$key_data = $contract->call(
				'keyDataOf',
				[
					$header['fid'],
					$header['key'],
				]
			);
		} catch ( Exception $e ) {
			throw new Exception( 'Error fetching key data: ' . esc_html( $e->getMessage() ) );
		}

		if ( ! isset( $key_data->tuple_1 ) || ! isset( $key_data->tuple_1->state ) || ! isset( $key_data->tuple_1->keyType ) ) {
			throw new Exception( 'Contract response is not valid' );
		}

		if ( '1' !== $key_data->tuple_1->state->toString() || '1' !== $key_data->tuple_1->keyType->toString() ) {
			throw new Exception( 'Key not found in signer events' );
		}

		return true;
	}

	/**
	 * Decodes a base64url encoded string
	 *
	 * @param string $data The base64url encoded string.
	 * @return string The decoded data.
	 */
	private static function base64url_decode( string $data ): string {
		$pad = strlen( $data ) % 4;
		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
}
