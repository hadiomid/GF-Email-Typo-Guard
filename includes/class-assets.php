<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles front-end performance: the JS/CSS files are only registered
 * (no HTTP request yet) on normal page load, and only actually
 * enqueued inside Gravity Forms' gform_enqueue_scripts action - which
 * GF fires per-form, only for forms it has determined are really
 * being output on the current request. That means:
 *
 *  - A page with no Gravity Forms form: nothing loads.
 *  - A page with a GF form that has no typo-guard-enabled Email
 *    field: nothing loads.
 *  - A page with a GF form that DOES have the feature enabled on at
 *    least one Email field: the assets load once, however many forms
 *    or fields are on the page.
 */
class GFETG_Assets {

	const HANDLE = 'gfetg-email-typo-guard';

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
		add_action( 'gform_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ), 10, 2 );
		add_filter( 'gform_field_content', array( __CLASS__, 'mark_field_html' ), 10, 5 );
	}

	/**
	 * Registers (does NOT enqueue) the assets so they're available to
	 * call on later without needing to know the file path again.
	 */
	public static function register() {
		wp_register_script(
			self::HANDLE,
			GFETG_URL . 'assets/js/email-typo-guard.js',
			array(),
			GFETG_VERSION,
			true
		);

		wp_register_style(
			self::HANDLE,
			GFETG_URL . 'assets/css/email-typo-guard.css',
			array(),
			GFETG_VERSION
		);
	}

	/**
	 * Fired by Gravity Forms once per form that is actually rendered
	 * on the current page. Only enqueues our assets if that specific
	 * form has at least one Email field with the feature turned on.
	 */
	public static function maybe_enqueue( $form, $is_ajax ) {
		if ( ! self::form_has_typo_guard_field( $form ) ) {
			return;
		}

		wp_enqueue_script( self::HANDLE );
		wp_enqueue_style( self::HANDLE );

		wp_localize_script( self::HANDLE, 'gfetgData', array(
			'domains'   => GFETG_Domain_List::get(),
			'threshold' => apply_filters( 'gfetg_domain_distance_threshold', 0.3 ),
			'strings'   => array(
				/* translators: %s is replaced with the clickable suggested address */
				'suggestionTemplate' => esc_html__( 'Did you mean %s?', 'gf-email-typo-guard' ),
			),
		) );
	}

	private static function form_has_typo_guard_field( $form ) {
		if ( empty( $form['fields'] ) ) {
			return false;
		}
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'email' && ! empty( $field->enableTypoSuggest ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Flags the actual <input> element for fields with the feature
	 * enabled, so the front-end script only watches those specific
	 * inputs rather than every email field on the page (relevant when
	 * a form has more than one Email field, or Email Confirmation
	 * enabled, and only some of them should be checked).
	 *
	 * Note: with "Email Confirmation" turned on for a field, Gravity
	 * Forms renders two <input> tags in this same $field_content
	 * string (the address, then the confirmation box). The limit of 1
	 * below intentionally marks only the first one.
	 */
	public static function mark_field_html( $field_content, $field, $value, $entry_id, $form_id ) {
		if ( $field->type !== 'email' || empty( $field->enableTypoSuggest ) ) {
			return $field_content;
		}

		return preg_replace(
			'/<input\b/i',
			'<input data-gfetg-domain-check="1"',
			$field_content,
			1
		);
	}
}
