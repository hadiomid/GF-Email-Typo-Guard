<?php
defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Suggest correction for likely domain typos" checkbox to the
 * Email field's Advanced settings tab in the form editor, using
 * Gravity Forms' standard field-settings extension hooks.
 */
class GFETG_Field_Setting {

	public static function init() {
		add_action( 'gform_field_advanced_settings', array( __CLASS__, 'render_setting' ), 10, 2 );
		add_action( 'gform_editor_js', array( __CLASS__, 'editor_js' ) );
	}

	/**
	 * Renders the checkbox markup on the Advanced tab.
	 *
	 * The $position value below (450) targets the slot after Gravity
	 * Forms' own settings on the Email field's Advanced tab in recent
	 * GF versions. If the checkbox doesn't appear when editing an
	 * Email field, open the form editor, inspect where other Advanced
	 * settings sit, and adjust this number - it only controls display
	 * order/location, nothing else depends on it.
	 */
	public static function render_setting( $position, $form_id ) {
		if ( $position != 450 ) {
			return;
		}
		?>
		<li class="email_typo_suggest_setting field_setting">
			<input type="checkbox" id="field_email_typo_suggest"
				onclick="SetFieldProperty('enableTypoSuggest', this.checked);" />
			<label for="field_email_typo_suggest" class="inline">
				<?php esc_html_e( 'Suggest correction for likely domain typos (e.g. "gmail.con" becomes "gmail.com")', 'gf-email-typo-guard' ); ?>
			</label>
		</li>
		<?php
	}

	/**
	 * Runs only inside wp-admin's form editor. Registers our setting
	 * against the "email" field type and keeps the checkbox in sync
	 * with the currently selected field's stored value.
	 */
	public static function editor_js() {
		?>
		<script type="text/javascript">
			if ( typeof fieldSettings !== 'undefined' && fieldSettings.email ) {
				fieldSettings.email += ', .email_typo_suggest_setting';
			}

			jQuery( document ).on( 'gform_load_field_settings', function( event, field ) {
				jQuery( '#field_email_typo_suggest' ).prop( 'checked', field.enableTypoSuggest === true );
			} );
		</script>
		<?php
	}
}
