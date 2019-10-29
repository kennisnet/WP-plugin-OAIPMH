<?php
/**

* Plugin Name:		WP OAIPMH
* Plugin URI:		https://www.kennisnet.nl
* Description:		This plugin connects the WordPress content with OAIPMH
*
* Version:         2.0.6
*
* @link       https://www.kennisnet.nl
* @since      1.0.0
* @package    wpoaipmh
*
* Author:            Kennisnet
* Author URI:        https://www.kennisnet.nl
* License:           GPL-3.0+
* License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
* Text Domain:       wpoaipmh
* Domain Path:       /languages
*/


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Make sure
if( !defined( 'WPOAIPMH_PLUGIN_LOADED') ) {
    define( 'WPOAIPMH_PLUGIN_LOADED', true);
    
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-activator.php
     */
    function activate_WPOAIPMH() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
        wpoaipmh_Activator::activate();
    }
    
    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-deactivator.php
     */
    function deactivate_WPOAIPMH() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
        wpoaipmh_Deactivator::deactivate();
    }
    
    register_activation_hook( __FILE__, 'activate_WPOAIPMH' );
    register_deactivation_hook( __FILE__, 'deactivate_WPOAIPMH' );
    
    require plugin_dir_path( __FILE__ ) . 'includes/class-core.php';
    require plugin_dir_path( __FILE__ ) . 'includes/class-wp-bridge.php';
    require plugin_dir_path( __FILE__ ) . 'includes/class-oai-wp-bridge.php';
    
    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    function run_wpoaipmh() {
        $plugin_core = new wpoaipmh_Core();
        $plugin_core->run();
        
        if( is_admin() && $plugin_core->is_installed() ) {
            $plugin_bridge = new wpoaipmh_WP_bridge();
            $plugin_bridge->run();
            
            require plugin_dir_path( __FILE__ ) . 'admin/class-import-wp-bridge.php';
            add_action ('after_setup_theme', array( 'wpoaipmh_Import_bridge', 'import_action') );
        }
    }
    
    run_wpoaipmh();
}
