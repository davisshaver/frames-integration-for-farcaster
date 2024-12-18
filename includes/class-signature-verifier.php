<?php
/**
 * Farcaster signature verification.
 *
 * @package Farcaster_WP
 */

namespace Farcaster_WP;

use Exception;
use SodiumException;

/**
 * Class to handle Farcaster signature verification
 *
 * See reference implementation: https://github.com/farcasterxyz/frames/blob/main/packages/frame-node/src/jfs.ts
 */
class Signature_Verifier {
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
		$app_key = hex2bin( ltrim( $header['key'], '0x' ) );
		if ( ! $app_key ) {
			throw new Exception( 'Invalid app key format' );
		}

		// Create signed input.
		$signed_input = $data['header'] . '.' . $data['payload'];

		$verified = false;
		try {
			$verified = sodium_crypto_sign_verify_detached(
				$signature,
				$signed_input,
				$app_key
			);
		} catch ( SodiumException $e ) {
			throw new Exception( 'Error checking signature: ' . esc_html( $e->getMessage() ) );
		}
		// @TODO We need to verify the key and FID relationship is valid.
		return $verified;
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
