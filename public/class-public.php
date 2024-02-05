<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */

class wpoaipmh_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wpoaipmh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wpoaipmh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		///wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpoaipmh-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wpoaipmh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wpoaipmh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		///wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpoaipmh-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 *
	 *
	 * @since  1.0.0
	 */
	public function flush_rules() {

		global $wp_rewrite;
		$wp_rewrite->flush_rules();

	}

	/**
	 *
	 *
	 * @since  1.0.0
	 */
	public function add_rewrite_rule() {

		add_rewrite_rule(
			'oai',
			'index.php?oai=1',
			'top'
		);

	}

	/**
	 *
	 *
	 * @since  1.0.0
	 */
	public function add_query_vars( $query_vars ) {

		$query_vars[] = 'oai';
		$query_vars[] = 'metadataPrefix';
		$query_vars[] = 'set';
		$query_vars[] = 'from';
		$query_vars[] = 'resumptionToken';
		return $query_vars;

	}

	/**
	 *
	 *
	 * @since  1.0.0
	 */
	public function get_query_vars() {

		global $wp_query; // FIXME: remove ?

		if ( get_query_var( 'oai' ) === '1' ) {
			include_once 'partials/public-display.php';
			exit;
		}

	}

}
