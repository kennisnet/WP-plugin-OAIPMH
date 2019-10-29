<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */
class wpoaipmh_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		
		
        /**
        * Check if ACF is active
        **/
        if ( !in_array( 'advanced-custom-fields/acf.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) &&
		    !in_array( 'advanced-custom-fields-pro/acf.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		        
                // Deactivate the plugin
                deactivate_plugins(__FILE__);
		        
                // Throw an error in the wordpress admin console
                $error_message = __('This plugin requires <a href="https://wordpress.org/plugins/advanced-custom-fields/">Advanced Custom Fields</a> or <a href="https://www.advancedcustomfields.com/">Advanced Custom Fields pro</a> plugins to be active!', 'wpoaipmh');
                die($error_message);
        }

        // Pre-check OK, now perform queries
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		include( 'sql/sql.php' );
		update_option( 'plugin_wpoaipmh', array( 'installed' => true ), false );
	}
}
