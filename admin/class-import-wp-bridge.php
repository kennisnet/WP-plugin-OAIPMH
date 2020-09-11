<?php
/**
 * Import bridge
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */

class wpoaipmh_Import_bridge extends wpoaipmh_WP_bridge
{
	public static function import_action() {
		if( isset( $_GET['kennisnet_wpoaipmh_import_start'] ) || isset( $_GET['kennisnet_wpoaipmh_import_limit_install'] ) ) {
			$import = new wpoaipmh_Import_bridge();
			$import->import_into_oai();
		}
	}
	
	/**
	 * Populate the oai tables
	 *
	 * @return void
	 */
	private function import_into_oai() {
	    global $wpdb;
	    
		if( isset( $_GET['kennisnet_wpoaipmh_import_limit'] ) ) {
			if(intval( $_GET['kennisnet_wpoaipmh_import_limit'] ) > 0) {
				$this->import_limit = intval( $_GET['kennisnet_wpoaipmh_import_limit'] );
			}
		}
		if( !current_user_can( 'manage_options' )) {
			wp_die( 'Not allowed'.__FILE__ );
		}
		
		if(isset($_GET['kennisnet_wpoaipmh_import_limit_install'])) {
			update_option( 'kennisnet_wpoaipmh_stagger', intval($_GET['kennisnet_wpoaipmh_import_limit_install']), false );
			wp_die( 'Installed, now run start GET' );
		}
		$this->post_type = ( isset( $_GET['kennisnet_wpoaipmh_import_cpt'] ) ) ? esc_attr( $_GET['kennisnet_wpoaipmh_import_cpt'] ) : 'post';
		$this->import_limit = 50;

		global $wpdb;
	
		$start = get_option( 'kennisnet_wpoaipmh_stagger' );
		echo "STARTING AT $start, limit ".$this->import_limit."<br/>\n<br/>\n";
		$args = [
				'posts_per_page' => $this->import_limit,
				'posts_per_archive_page' => $this->import_limit,
				'offset' => $start,
				'post_type' => $this->post_type,
		];
	
		$the_query = new WP_Query( $args );
	
		if ( ! $the_query->have_posts() ) {
		    echo "Starting to find orphaned deleted records. If this times out, you can safely reload this page.<br/>\n";
		    
		    // Note to dev: we _need_ the is_deleted && is_publicly_published otherwhise each run will overwrite the deleted_date
			$sql = 'SELECT ID FROM '.self::get_table('oai') . '  WHERE ID NOT IN ( SELECT ID FROM '.$wpdb->posts.' ) AND ( is_deleted != 1 OR is_publicly_published != 0 )';
			$results = $wpdb->get_results( $sql );
			
			if( $results ) {
                foreach ( $results as $row ) {
                    echo "BUSY with post_id ".$row->ID." which was deleted in the past<br/>\n";
                    $this->update_table_core_post_set_deleted( $row->ID, apply_filters( 'wpoaipmh/import_bridge_deleted_deleted_time', '' ) );
        			parent::remove_taxonomy_links_for_post( $row->ID );
                }
			}
			
			delete_option( 'kennisnet_wpoaipmh_stagger' );
			echo 'done'; 
			die();
		}
	
		// Set flag
		define( 'WP_OAIPMH_FORCE_INLINE_SAVE', true );
		
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
	
			echo "BUSY with post_id ".$the_query->post->ID." ".get_the_title()."<br/>\n";
			$post_id	= $the_query->post->ID;
			$post		= $the_query->post;
			
			$should_create_record = false;
			// Existing post, but not in our index
			$record_count = $wpdb->get_var ( $wpdb->prepare ( 'SELECT COUNT(ID) FROM '.self::get_table('oai') . ' WHERE ID = %d', $post_id ) );
			if( ! $record_count || intval($record_count) < 1 ) {
				$should_create_record = true;
			}
	
			$update		= true;
			if ( $should_create_record ) {
				$update = false;
			}
			// Insert or update oai data
			$this->update_table_core_post( $post_id, $post, $update );
			
		} // while ( $the_query->have_posts() ) {
	
		update_option( 'kennisnet_wpoaipmh_stagger', ($start+$this->import_limit), false );
		$new_url = admin_url( 'plugins.php?kennisnet_wpoaipmh_import_limit='.$this->import_limit.'&kennisnet_wpoaipmh_import_start='.($start+$this->import_limit) );
		$new_url .= '&kennisnet_wpoaipmh_import_cpt='.$this->post_type;
		echo '<meta http-equiv="refresh" content="5;url='.$new_url.'">';
		die();
	}
	
}
