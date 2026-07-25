<?php
defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for the domains we check typed addresses
 * against. Kept intentionally short: a longer list increases the
 * chance of a false-positive suggestion on an unusual-but-legitimate
 * domain (e.g. a small business's own domain).
 */
class GFETG_Domain_List {

	public static function get() {
		$domains = array(
			'gmail.com', 'googlemail.com',
			'yahoo.com', 'yahoo.co.uk',
			'hotmail.com', 'outlook.com', 'live.com', 'msn.com',
			'icloud.com', 'me.com', 'mac.com',
			'aol.com',
			'protonmail.com', 'proton.me',
			'zoho.com',
			'yandex.com', 'yandex.ru',
			'gmx.com',
			'comcast.net', 'verizon.net', 'att.net',
		);

		/**
		 * Filter the list of domains checked for typos, e.g. to add
		 * regional providers relevant to your users.
		 *
		 * add_filter( 'gfetg_common_domains', function( $domains ) {
		 *     $domains[] = 'example-regional-provider.com';
		 *     return $domains;
		 * } );
		 */
		return apply_filters( 'gfetg_common_domains', $domains );
	}
}
