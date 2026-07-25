<?php
/**
 * Plugin Name: GF Email Typo Guard
 * Description: Adds a per-field "domain typo suggestion" option to Gravity Forms Email fields (e.g. suggests "gmail.com" when a user types "gamil.con"). Scripts and styles are only loaded on pages that actually render a form using the feature.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Requires Plugins: gravityforms
 * Author: Hadi Omid
 * Author URI: https://github.com/hadiomid
 * License: GPL-2.0-or-later
 * Text Domain: gf-email-typo-guard
 */

defined( 'ABSPATH' ) || exit;

define( 'GFETG_VERSION', '1.0.0' );
define( 'GFETG_FILE', __FILE__ );
define( 'GFETG_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFETG_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'gfetg_bootstrap' );

function gfetg_bootstrap() {

	// Fail quietly (with an admin notice) if Gravity Forms isn't active,
	// rather than fataling.
	if ( ! class_exists( 'GFForms' ) ) {
		add_action( 'admin_notices', 'gfetg_missing_gf_notice' );
		return;
	}

	require_once GFETG_DIR . 'includes/class-domain-list.php';
	require_once GFETG_DIR . 'includes/class-field-setting.php';
	require_once GFETG_DIR . 'includes/class-assets.php';
	require_once GFETG_DIR . 'includes/class-validation.php';

	GFETG_Field_Setting::init();
	GFETG_Assets::init();
	GFETG_Validation::init();
}

function gfetg_missing_gf_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'GF Email Typo Guard requires Gravity Forms to be installed and active.', 'gf-email-typo-guard' ) .
		'</p></div>';
}
