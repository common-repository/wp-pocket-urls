<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
exit;
}
wp_pocketurl_uninstall();
function wp_pocketurl_uninstall(){
	global $wpdb;
	$wpdb->wp_pocketurl_clicks_table = "{$wpdb->prefix}pocketurl_clicks";
	$wpdb->wp_pocketurl_clicks_count_table = "{$wpdb->prefix}pocketurl_clicks_count";
	//drop plugin tables
	$sql = "DROP TABLE IF EXISTS $wpdb->wp_pocketurl_clicks_table";
	$wpdb->query($sql);
	//delete all posts of wp_pocketurl_link post type
	$posts_table = "{$wpdb->prefix}posts";
	$postmeta_table = "{$wpdb->prefix}postmeta";
	$wpdb->query("DELETE p,pm FROM {$posts_table} p LEFt JOIN {$postmeta_table} pm on pm.post_id = p.id WHERE p.post_type = 'wp_pocketurl_link'");
	//delete all custom taxonomies of wp_pocketurl_link_category type
	// Prepare & excecute SQL
	$tax = 'wp_pocketurl_link_category';
	$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy=%s",$tax ) );
  
  // Delete Terms
	foreach ( $terms as $term ) {
		$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
		$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
		$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
		delete_option( 'prefix_' . $term->slug . '_option_name' );
	}
	// Delete Taxonomy
	$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => 'wp_pocketurl_link_category' ) );
	
	
	// delete plugin options
	delete_option( 'wp_pocketurl_link_prefix' );
	delete_option( 'wp_pocketurl_link_redirection' );
	delete_option( 'wp_pocketurl_link_nofollow' );
	delete_option( 'wp_pocketurl_link_tracking_code' );	
}