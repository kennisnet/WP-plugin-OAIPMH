<?php
/**
 * WP Bridge
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */


class wpoaipmh_WP_bridge
{
    // @see filter wpoaipmh/post_types
    // @see filter wpoaipmh/oai_listsets
    private static $post_types = [ 'post' => 'publication', 
	];
    
	protected static $dbtables = [
			'oai' 					=> 'oai',
			'term_relationships'	=> 'oai_term_relationships',
			'terms'					=> 'oai_terms',
			'taxonomy'				=> 'oai_taxonomy',
	];
	
	// @see filter wpoaipmh/core_taxonomies
	private static $core_taxonomies = [
            'sector'                => 'sector',
			'post_competence'		=> 'post_competence', // Bekwaamheidseisen
			'post_tag'				=> 'post_tag', // Tags
	];
	
	protected $taxonomy_ids = array();
	
	public function __construct() {
	    
	    foreach( self::get_core_taxonomies() as $tax_core => $tax_store ) {
	        $this->taxonomy_ids[$tax_core] = self::get_taxonomy_id( $tax_core );
	    }

	}
	
	/**
	 * @since      1.0.2
	 * @return mixed
	 */
	public function get_core_taxonomies() {
	    
	    $core_tax_list = [];
	    
	    foreach( self::$core_taxonomies as $tax_core => $tax_store ) {
	        $core_tax_list[$tax_core] = $tax_store;
	    }
	    
	    return apply_filters( 'wpoaipmh/core_taxonomies', $core_tax_list );
	}
	
	/**
	 * @since      1.0.2 
	 * @return mixed
	 */
	public function get_post_types() {
	    $types_list = [];
	    
	    foreach( self::$post_types as $post_type_internal => $post_type_external ) {
	        $types_list[$post_type_internal] = $post_type_external;
	    }
	    
	    return apply_filters( 'wpoaipmh/post_types', $types_list );
	}
	
	/**
	 * 
	 * @param unknown $type
	 * @return string
	 */
	protected function post_type_convert_to_internal ( $type ) {
	    foreach( self::get_post_types() as $post_type_internal => $post_type_external ) {
			if ( $post_type_external == $type ) {
				return $post_type_internal;
			}
		}
	}
	
	/**
	 * 
	 * @param unknown $type
	 * @return string
	 */
	protected function post_type_convert_to_external ($type ) {
	    foreach(self::get_post_types() as $post_type_internal => $post_type_external ) {
			if ( $post_type_internal == $type ) {
				return $post_type_external;
			}
		}
	}
	
	/**
	 * Called from parent (root) as init
	 */
	public function run() {
	    
		// Normal edit screen
		add_action( 'acf/save_post', array( $this, 'update_table_plugin_acf' ) , 20 );
		add_action( 'acf/save_post', array( $this, 'update_table_core_taxonomies' ) , 20 );
		
		// Normal edit screen
		// Inline edit on list post (CPT) overview
		add_action( 'save_post', array( $this, 'update_table_core_post' ) , 10 , 3 );
	}
	
	/**
	 * Converts mysqldatetime string to DateTime element
	 *
	 * @param unknown $date
	 * @param unknown $type
	 * @return DateTime
	 */
	public static function helper_convertdate( $date, $type = 'DateTime' ) {
		$date = str_replace(' ', 'T', $date).'Z';
		if( $type == 'DateTime' ) {
			return new DateTime( $date );
		}
		if( $type == 'string' ) {
			return $date;
		}
	}
	
	/**
	 * Returns prefixed table
	 * 
	 * @param unknown $table
	 * @return string
	 */
	protected static function get_table( $table ) {
		global $wpdb;
		return $wpdb->prefix .self::$dbtables[$table];
	}

	/**
	 * Helper for update_table_core_post, will fire upon AJAX request to catch inline edits
	 * @param int $post_id
	 */
	protected static function helper_inline_save( $post_id ) {
		self::update_table_plugin_acf( $post_id );
		self::update_table_core_taxonomies( $post_id );
	}
	
	/**
	 * Updates core taxonomies after post save
	 * 
	 * @param int $post_id
	 */
	public static function update_table_core_taxonomies( $post_id ) {

	    foreach( self::get_core_taxonomies() as $core_taxonomy => $store_taxonomy ) {
			
		    $the_terms = wp_get_post_terms( $post_id, $core_taxonomy );
			$terms = [];
			if( is_array( $the_terms ) ) {
				foreach( $the_terms as $the_term ) {
					$terms[] = $the_term->name;
				}
			}

			self::link_all_taxonomy_terms_to_post( $post_id, $store_taxonomy, $terms );
		}
	}
	
	/**
	 * Updates ACF taxonomies after post save
	 * 
	 * @param unknown $post_id
	 */
	public static function update_table_plugin_acf( $post_id ) {
		
	    $do_sectors = apply_filters( 'wpoaipmh/acf_do_sectors', true );
	    $do_publication_revision_date = apply_filters( 'wpoaipmh/acf_do_publication_revision_date', true );
	    $do_publication_partner = apply_filters( 'wpoaipmh/acf_do_publication_partner', true );
	    
	    if( $do_sectors ) {
    		$sectors = get_field('publication_sectors', $post_id);
    		$terms = array();
    		if( is_array($sectors) && count($sectors)) {
    			foreach( $sectors as $sector ) {
    				$terms[] = $sector['label'];
    			}
    		}
    		self::link_all_taxonomy_terms_to_post( $post_id, 'sector', $terms );
	    }
		
	    if( $do_publication_revision_date ) {	        
    		$tmp = get_field( 'publication_revision_date', $post_id );
    		$publication_revision_date = null;
    		if( $tmp ) {
    			$publication_revision_date = date("Y-m-d", strtotime( $tmp ) );
    		}
    		
    		self::store_mixed_field( $post_id, $publication_revision_date, 'modified_date_entered' );
	    }
		
	    if( $do_publication_partner ) {
    		$tmp = get_field( 'publication_partner', $post_id );
    		$publication_partner_name = null;
    		if( $tmp ) {
    			$publication_partner_name = get_the_title( $tmp );
    		}
    		self::store_mixed_field( $post_id, $publication_partner_name, 'partner_name' );	        
	    }
	}
	
	/**
	 * Stores string or null to "fieldname" field
	 * 
	 * @param unknown $post_id
	 * @param unknown $value
	 * @param unknown $fieldname
	 */
	private static function store_mixed_field ( $post_id, $value, $fieldname ) {
		global $wpdb;
		
		if( $value ) {
			
			$wpdb->query(
					$wpdb->prepare(
							'UPDATE '.self::get_table('oai') . ' SET
			
						'.$fieldname.' = %s
			
						WHERE ID = %d',
			
							$value,
							$post_id ) );
		} else {
			$wpdb->query(
					$wpdb->prepare(
							'UPDATE '.self::get_table('oai') . ' SET
		
						'.$fieldname.' = null
		
						WHERE ID = %d',
								
							$post_id ) );
		}
	}
	
	/**
	 * Saves post metadata when a post is saved.
	 * Does NOT save taxonomies by default. @see run() & helper_inline_save() methods for more info
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public function update_table_core_post( $post_id, $post, $update ) {

		// If this is just a revision, return
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id);
		// Sanity check for post type
		if ( !in_array($post_type, array_keys( self::get_post_types() ) ) ) {
			return;
		}

		global $wpdb;

		$should_create_record = false;
		$is_deleted = false;
		$is_published = false;
		$was_deleted = 0;
		$was_published = 0;
		
		$post_title = get_the_title( $post_id );
		$post_guid = get_permalink( $post_id );			

		
		
		if(!$update) {
			// New post
			$should_create_record = true;
		} else {
			// Existing post, but not in our index
			$record_count = $wpdb->get_var ( $wpdb->prepare ( 'SELECT COUNT(ID) FROM '.self::get_table('oai') . ' WHERE ID = %d', $post_id ) );
			if( ! $record_count || intval($record_count) < 1 ) {
				$should_create_record = true;
			} else {
				// Was present, now check if published
				$was_published = $wpdb->get_var ( $wpdb->prepare ( 'SELECT is_publicly_published FROM '.self::get_table('oai') . ' WHERE ID = %d', $post_id ) );
				$was_deleted = $wpdb->get_var ( $wpdb->prepare ( 'SELECT is_deleted FROM '.self::get_table('oai') . ' WHERE ID = %d', $post_id ) );
			}
		}

		if( $should_create_record ) {
			$wpdb->query( $wpdb->prepare( 'INSERT INTO '.self::get_table('oai') . ' (ID, created_date) VALUES (%d, %s)', $post_id, $post->post_date ) );
		}
		
		/**
		 * Note to dev:
		 * 
		 * Published = published && NO password
		 * Deleted = !Published OR trashed
		 */
				
		/**
		 * Handle published
		 */
		if( $post->post_status == 'publish' && $post->post_password == '' ) {

			$is_published = true;
			if( !$was_published ) {
				$wpdb->query(
						$wpdb->prepare(
								'UPDATE '.self::get_table('oai') . ' SET
		
					is_publicly_published = 1,
					is_ever_publicly_published = 1,
					published_date = %s
		
					WHERE ID = %d',
		
								$post->post_modified,
								$post_id ) );
			}
				
		} else {
			// Undelete if nessecary
			if( $was_published ) {
				$wpdb->query(
						$wpdb->prepare(
								'UPDATE '.self::get_table('oai') . ' SET
		
					is_publicly_published = 0,
					published_date = NULL
		
					WHERE ID = %d',
		
								$post_id ) );
			}
		}
		/**
		 * /Handle published
		 */
		
		
		/**
		 * Handle deleted
		 */
		if( $post->post_status == 'trash' ) {
			$is_deleted = true;
			if( $was_deleted == 0 ) {
				$wpdb->query(
						$wpdb->prepare(
								'UPDATE '.self::get_table('oai') . ' SET

					is_deleted = 1,
					is_publicly_published = 0,
					deleted_date = %s

					WHERE ID = %d',

								current_time( 'mysql' ),
								$post_id ) );
			}
		} else {
			// Undelete if nessecary
			if( $was_deleted ) {
				$wpdb->query(
						$wpdb->prepare(
								'UPDATE '.self::get_table('oai') . ' SET

					is_deleted = 0,
					deleted_date = NULL

					WHERE ID = %d',

								$post_id ) );
			}
		}
		/**
		 * /Handle deleted
		 */

		// Update table
		$wpdb->query(
				$wpdb->prepare(
						'UPDATE '.self::get_table('oai') . ' SET

			title = %s,
			permalink = %s,
			modified_date = %s,
			post_type = %s,
			post_excerpt = %s

			WHERE ID = %d',

						$post_title,
						$post_guid,
						$post->post_modified,
						$post_type,
				        apply_filters( 'wpoaipmh/post_excerpt', $post->post_excerpt, $post_id ),

						$post_id ) );

		if( $is_deleted ) {
			// Unlink any taxonomy terms
			self::remove_taxonomy_links_for_post( $post_id );
		}

		// Catch inline edits here
		if( defined('DOING_AJAX') || defined('WP_OAIPMH_FORCE_INLINE_SAVE') ) {
			self::helper_inline_save( $post_id );
		}
		
	}
	
	/**
	 * Query DB for taxonomy id
	 * 
	 * @param unknown $taxonomy
	 * @return unknown
	 */
	protected static function get_taxonomy_id( $taxonomy, $is_recursive = false ) {
		global $wpdb;
		
		$sql = 'SELECT tax_id FROM '.self::get_table('taxonomy') . ' WHERE taxonomy = %s';
		$id = $wpdb->get_var( $wpdb->prepare( $sql, $taxonomy ) );
		
		if( $id ) { return $id; }
		
		// Create
		$sql = $wpdb->prepare( 'INSERT INTO '.self::get_table('taxonomy') . ' ( `taxonomy`) VALUES ( %s )', $taxonomy );
		$wpdb->query( $sql );
		return $wpdb->insert_id;
	}
	
	/**
	 * Query DB for term id
	 * 
	 * @param unknown $term
	 * @param unknown $taxonomy_id
	 * @return unknown
	 */
	private static function get_term_id ( $term, $taxonomy_id ) {
		global $wpdb;
		
		$sql = 'SELECT term_id FROM '.self::get_table('terms') . ' WHERE tax_id = %d AND term = %s';
		return $wpdb->get_var( $wpdb->prepare( $sql, $taxonomy_id, $term) );
	}
	
	/**
	 * Remove an item from array when key index is unknown
	 * 
	 * @param unknown $array
	 * @param unknown $item
	 * @return unknown
	 */
	private static function helper_remove_item_from_array( $array, $item ) {
		$index = array_search($item, $array);
		
		if ( $index !== false ) {
			unset( $array[$index] );
		}
	
		return $array;
	}

	/**
	 * Queries DB for all linked terms based on taxomomy id
	 * 
	 * @param unknown $post_id
	 * @param unknown $taxonomy_id
	 * @return unknown
	 */
	protected static function get_linked_taxonomy_terms ( $post_id, $taxonomy_id ) {
		global $wpdb;
		
		// If not present or suppressed
		if( !$taxonomy_id ) {
		    return false;
		}
		
		$sql = 'SELECT * FROM '.self::get_table('terms') .
		' WHERE term_id IN (
					SELECT term_id FROM '.self::get_table('term_relationships') . ' WHERE oai_id = %d
				)
				AND tax_id = %d';
		return $wpdb->get_results( $wpdb->prepare( $sql, $post_id, $taxonomy_id ) );
	}
	
	/**
	 * Links all terms to post, cleans up all old links, inserts new tags
	 * 
	 * @param unknown $post_id
	 * @param unknown $taxonomy
	 * @param unknown $terms
	 */
	protected static function link_all_taxonomy_terms_to_post( $post_id, $taxonomy, $terms = [] ) {
		global $wpdb;
		
		$taxonomy_id = self::get_taxonomy_id( $taxonomy );
		
		if(!$taxonomy_id || intval($taxonomy_id) == 0 ) {
			_doing_it_wrong( __FUNCTION__, __('No taxomony id found for '). $taxonomy );
			return;
		}
		
		// Convert based on vdex
		if( $taxonomy == 'sector' || $taxonomy == 'post_sector' ) {
			foreach( $terms as $key => $term ) {
				$terms[$key] = str_replace( 'MBO', 'BVE', $term );
			}
		}
		
		$terms = apply_filters( 'wpoaipmh/tax_terms/'.$taxonomy, $terms, $post_id );
		
		// First, get all linked terms
		$term_links_to_add = $terms;
		$term_links_to_delete_ids = array();
		
		$linked_terms = self::get_linked_taxonomy_terms( $post_id, $taxonomy_id );
		if( $linked_terms ) {
			
			// Need to delete?
			foreach( $linked_terms as $linked_term ) {
				if( !in_array( $linked_term->term, $terms) ) {
					$term_links_to_delete_ids[] = $linked_term->term_id;
				} else {
					// Clear this item from the "add" array
					$term_links_to_add = self::helper_remove_item_from_array( $term_links_to_add, $linked_term->term );
				}
			}
		
		} else {
			// No linked terms at all, we need to link them all
			$term_links_to_add = $terms;
		}
		
		// Now check if we need to delete a term
		if(is_array($term_links_to_delete_ids) && count($term_links_to_delete_ids) > 0) {
			foreach($term_links_to_delete_ids as $term_id) {
				self::remove_taxonomy_link_for_post( $post_id, $term_id );
			}
		}
		
		// Now check if we need to add a term
		if(is_array($term_links_to_add) && count($term_links_to_add) > 0) {
			foreach ( $term_links_to_add as $term ) {				
				self::add_taxonomy_term_to_post( $post_id, $taxonomy_id, $term );
			}
		}
	}

	/**
	 * Deletes specific term/post link for post
	 * 
	 * @param unknown $post_id
	 * @param unknown $term_id
	 */
	private static function remove_taxonomy_link_for_post( $post_id, $term_id ) {
		global $wpdb;
		
		$wpdb->query(
				$wpdb->prepare(
						'DELETE FROM '.self::get_table('term_relationships') . ' WHERE oai_id = %d AND term_id = %d', $post_id, $term_id ) );
	}
	
	/**
	 * Deletes all term/post links for post
	 * 
	 * @param unknown $post_id
	 */
	private static function remove_taxonomy_links_for_post( $post_id ) {
		global $wpdb;
		
		$wpdb->query(
				$wpdb->prepare(
						'DELETE FROM '.self::get_table('term_relationships') . ' WHERE oai_id = %d', $post_id ) );
	}

	/**
	 * Inserts term (if needed) and links term to post
	 * 
	 * @param unknown $post_id
	 * @param unknown $taxonomy_id
	 * @param unknown $term
	 */
	private static function add_taxonomy_term_to_post ( $post_id, $taxonomy_id, $term ) {
		global $wpdb;
		
		$term_id = self::get_term_id ( $term, $taxonomy_id );
		if(!$term_id || intval($term_id) == 0) {
			
			// Insert here
			$wpdb->query( $wpdb->prepare(  'INSERT INTO '.self::get_table('terms') . ' (term, tax_id) VALUES (%s, %d)', $term, $taxonomy_id ) );
			// Make sure
			$term_id = self::get_term_id ( $term, $taxonomy_id );
		}
		
		// Add the link
		$wpdb->query( $wpdb->prepare(  'INSERT INTO '.self::get_table('term_relationships') . ' (oai_id, term_id) VALUES (%s, %d)', $post_id, $term_id ) );
	}
}
