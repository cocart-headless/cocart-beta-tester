/**
 * Handles the version picker form.
 *
 * @package CoCart Beta Tester\JS
 */
jQuery(function( $ ) {
	/**
	 * Version picker
	 */
	var cc_beta_tester_version_picker = {

		/**
		 * Initialize Version Information click
		 */
		init: function() {
			instance = this;
			instance.new_version = undefined;

			$( '#ccbt-modal-version-switch-confirm' ).on( 'click', this.showConfirmVersionSwitchModal );

			$( 'input[type=radio][name=ccbt_switch_to_version]' ).change( function() {
				if ( $( this ).is( ':checked' ) ) {
					instance.new_version = $( this ).val();
				}
			} ).trigger( 'change' );
		},

		/**
		 * Handler for showing/hiding version switch modal
		 */
		showConfirmVersionSwitchModal: function( event ) {
			event.preventDefault();

			if ( ! instance.new_version ) {
				alert( cc_beta_tester_version_picker_params.i18n_pick_version );
			} else {
				$( this ).WCBackboneModal({
					template: 'ccbt-version-switch-confirm',
					variable: {
						new_version: instance.new_version,
					},
				});

				$( '#ccbt-submit-version-switch' ).on( 'click', instance.submitSwitchVersionForm );
			}
		},

		/**
		 * Submit form to switch version of CoCart.
		 */
		submitSwitchVersionForm: function( event ) {
			event.preventDefault();

			$( 'form[name=ccbt-select-version]' ).get( 0 ).submit();
		},
	};

	cc_beta_tester_version_picker.init();
});