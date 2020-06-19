<?php
/**
 * Add new CPT
 */
use Picturae\OaiPmh\Implementation\Set;

add_filter( 'wpoaipmh/post_types', 'my_wpoaipmh_post_types' );
add_filter( 'wpoaipmh/oai_listsets', 'my_wpoaipmh_post_types_set' );
function my_wpoaipmh_post_types( $types ) {
    return [ 'article' => 'Publication' ];
}
function my_wpoaipmh_post_types_set( $types ) {
    $items = [];
    $items[] = new Set( 'article', 'Publication' );
    
    return $items;
}
/**
 * /Add new CPT
 */

/**
 * Add tax
 */
add_filter( 'wpoaipmh/core_taxonomies', 'my_wpoaipmh_core_taxonomies' );
function my_wpoaipmh_core_taxonomies( $taxonomies ) {
    return [
        'tax1'     => 'tax1',
        'tax2'     => 'tax2',
    ];
}

add_filter( 'wpoaipmh/oai_record_do_tax/tax1', 'my_wpoaipmh_oai_record_do_tax', 10, 3 );
add_filter( 'wpoaipmh/oai_record_do_tax/tax2', 'my_wpoaipmh_oai_record_do_tax', 10, 3 );
function my_wpoaipmh_oai_record_do_tax( $recordID, $tax_items, $attribs_lang_nl ) {
    
    $general_subs = [];
    $bridge = new wpoaipmh_OAI_WP_bridge();
    foreach( $tax_items as $tax_item ) {
        $keyword_sub = $bridge->helper_meta_create_structure( 'lom:langstring', array(), $attribs_lang_nl, $tax_item->term );
        $general_subs[] = $bridge->helper_meta_create_structure( 'lom:keyword', array( $keyword_sub ) );
    }
    return $general_subs;
}
/**
 * /Add tax
 */