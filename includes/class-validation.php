<?php
defined( 'ABSPATH' ) || exit;

/**
 * Server-side mirror of the front-end suggestion logic. Off by
 * default: this feature is a *suggestion*, and hard-blocking
 * submission on a guessed correction would create false-positive
 * friction for people who genuinely use an uncommon domain (the
 * front-end hint already lets a user accept or ignore it freely).
 *
 * If you want a no-JS fallback that blocks submission until the user
 * either fixes the address or resubmits to confirm it, opt in with:
 *
 *   add_filter( 'gfetg_enable_server_side_check', '__return_true' );
 */
class GFETG_Validation {

	public static function init() {
		if ( apply_filters( 'gfetg_enable_server_side_check', false ) ) {
			add_filter( 'gform_field_validation', array( __CLASS__, 'validate' ), 10, 4 );
		}
	}

	public static function validate( $result, $value, $form, $field ) {
		if ( $field->type !== 'email' || empty( $field->enableTypoSuggest ) || empty( $value ) ) {
			return $result;
		}

		$suggestion = self::suggest_domain( $value );

		if ( $suggestion !== null ) {
			$result['is_valid'] = false;
			$result['message']  = sprintf(
				/* translators: %s: suggested email address */
				esc_html__( 'Did you mean %s? Please correct your email, or resubmit to confirm it is correct.', 'gf-email-typo-guard' ),
				esc_html( $suggestion )
			);
		}

		return $result;
	}

	/**
	 * Returns a corrected "local@suggested-domain" address if the
	 * domain looks like a close typo of a common provider, or null if
	 * there's no confident suggestion. Mirrors the JS in
	 * assets/js/email-typo-guard.js so both sides agree on what
	 * counts as "close enough".
	 */
	public static function suggest_domain( $email ) {
		$at_pos = strrpos( $email, '@' );
		if ( $at_pos === false ) {
			return null;
		}

		$local  = substr( $email, 0, $at_pos );
		$domain = strtolower( substr( $email, $at_pos + 1 ) );

		if ( $domain === '' ) {
			return null;
		}

		$candidates = GFETG_Domain_List::get();

		if ( in_array( $domain, $candidates, true ) ) {
			return null; // Exact match: nothing to suggest.
		}

		$best_distance = null;
		$best_domain   = null;

		foreach ( $candidates as $candidate ) {
			$distance = levenshtein( $domain, $candidate );
			if ( $best_distance === null || $distance < $best_distance ) {
				$best_distance = $distance;
				$best_domain   = $candidate;
			}
		}

		if ( $best_domain === null || $best_distance === 0 ) {
			return null;
		}

		$threshold = apply_filters( 'gfetg_domain_distance_threshold', 0.3 );
		$ratio     = $best_distance / max( strlen( $best_domain ), 1 );

		if ( $ratio > $threshold || $best_distance > 3 ) {
			return null; // Too different to be a confident typo guess.
		}

		return $local . '@' . $best_domain;
	}
}
