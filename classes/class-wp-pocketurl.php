<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once(wp_pocketurl_path.'classes/class-add-tax.php');

add_filter('enter_title_here', 'wp_pocketurl_place_holder' , 20 , 2 );
function wp_pocketurl_place_holder($title , $post){
    if( $post->post_type == 'wp_pocketurl_link' ){
        $my_title = "/your-custom-redirect-url";
        return $my_title;
    }
    return $title;
}

register_activation_hook( __FILE__, 'wp_pocketurl_activate' );
/**
 * Add a flag that will allow to flush the rewrite rules when needed.
 */
function wp_pocketurl_activate() {
    if ( ! get_option( 'wp_pocketurl_flush_rewrite_rules_flag' ) ) {
        add_option( 'wp_pocketurl_flush_rewrite_rules_flag', true );
    }
}

add_action( 'init', 'wp_pocketurl_flush_rewrite_rules_maybe', 20 );
/**
 * Flush rewrite rules if the previously added flag exists,
 * and then remove the flag.
 */
function wp_pocketurl_flush_rewrite_rules_maybe() {
    if ( get_option( 'wp_pocketurl_flush_rewrite_rules_flag' ) ) {
        flush_rewrite_rules();
        delete_option( 'wp_pocketurl_flush_rewrite_rules_flag' );
    }
}

register_deactivation_hook( __FILE__, 'wp_pocketurl_flush_rewrite_rules' );
function wp_pocketurl_flush_rewrite_rules()
{
    flush_rewrite_rules();
}

class WP_PocketURLs{
	
	function __construct() {
		//register custom post
		add_action( 'init', array($this, 'wp_pocketurl_link_init'),0 );
		//register custom taxonomy
		add_action( 'init', array($this, 'wp_pocketurl_link_category_init'), 0 );
		//register link options box
		add_action( 'add_meta_boxes', array($this, 'wp_pocketurl_link_options_init'), 0 );
		// register link clicks details box
		add_action( 'add_meta_boxes', array($this, 'wp_pocketurl_link_clicks_details_init'), 0 );
		// save link options data
		add_action( 'save_post',array($this, 'wp_pocketurl_link_options_save'));
		// delete click data when post deleted
		add_action( 'before_delete_post', array($this, 'wp_pocketurl_delete_click_data') );
		//enqueue scripts and styles
		add_action('admin_enqueue_scripts',array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts',array($this, 'enqueue_scripts'));
		//hook the single post template
		add_filter('single_template', array($this, 'wp_pocketurl_link_template'));
	}

	//enqueue css file to the admin
	public function enqueue_styles(){
		$page = get_current_screen();
		if('wp_pocketurl_link'!=$page->post_type){
			return;	
		}
		wp_enqueue_style('wp-pocketurl',wp_pocketurl_url.'/assets/css/wp-pocketurl.css',array(),'1.0.0');
	}
	//enqueue javascript file to the admin
	public function enqueue_scripts(){
		$page = get_current_screen();

		if('wp_pocketurl_link'!=$page->post_type ){
			return;	
		}
		if( 'wp_pocketurl_link_page_wp_pocketurl_link_reports' == $page->id ){
			wp_enqueue_script('gchart1','https://www.gstatic.com/charts/loader.js',array('jquery'),'1.0.0');
			wp_enqueue_script('wp-pocketurl-reports',wp_pocketurl_url.'/assets/js/reports.js',array('jquery'),'1.0.0');
		}
		$wp_pocketurl_vars = array(
			'url'=> wp_pocketurl_url
		);	
		
		wp_enqueue_script('wp-pocketurl',wp_pocketurl_url.'/assets/js/wp-pocketurl.js',array('jquery'),'1.0.0');
		wp_enqueue_script('copyclipboard',wp_pocketurl_url.'/assets/js/copy.js',array('jquery'),'1.0.0');
		wp_localize_script( 'copyclipboard', 'wpPocketURL', $wp_pocketurl_vars );
	}
	// register custom post type
	public function wp_pocketurl_link_init(){
		$labels = array(
			'name'                => esc_html_x( 'Links', 'Link Type General Name', 'wp_pocketurl' ),
			'singular_name'       => esc_html_x( 'Link', 'Link Type Singular Name', 'wp_pocketurl' ),
			'menu_name'           => esc_html__( 'WP Pocket URLs', 'wp_pocketurl' ),
			'name_admin_bar'      => esc_html__( 'Link Type', 'wp_pocketurl' ),
			'parent_item_colon'   => esc_html__( 'Parent Item:', 'wp_pocketurl' ),
			'all_items'           => esc_html__( 'All Links', 'wp_pocketurl' ),
			'add_new_item'        => esc_html__( 'Add New Link', 'wp_pocketurl' ),
			'add_new'             => esc_html__( 'Add New', 'wp_pocketurl' ),
			'new_item'            => esc_html__( 'New Link', 'wp_pocketurl' ),
			'edit_item'           => esc_html__( 'Edit Link', 'wp_pocketurl' ),
			'update_item'         => esc_html__( 'Update Link', 'wp_pocketurl' ),
			'view_item'           => esc_html__( 'View Link', 'wp_pocketurl' ),
			'search_items'        => esc_html__( 'Search Item', 'wp_pocketurl' ),
			'not_found'           => esc_html__( 'Not found', 'wp_pocketurl' ),
			'not_found_in_trash'  => esc_html__( 'Not found in Trash', 'wp_pocketurl' ),
		);
        $rewrite = array(
            'with_front'          => true,
            'pages'               => true,
            'feeds'               => false,
        );
		// rewrite permalink structure
		$custom_slug = urlencode(get_option('wp_pocketurl_link_prefix','go'));
		$exclude_cat = get_option('wp_pocketurl_link_exclude_cat','no');
        
        if($custom_slug != '')
        {
            if($exclude_cat == 'no'){
                $rewrite['slug'] = "$custom_slug/%link-cat%";
            }else{
                $rewrite['slug'] = "$custom_slug";
            }
		}
        else
        {
            if($exclude_cat == 'no'){
                $rewrite['slug'] = "%link-cat%";
            }
            else
            {
                $rewrite['slug'] = '/';
				$rewrite['with_front'] = false;
            }
        }
		$args = array(
			'label'               => esc_html__( 'wp_pocketurl_link', 'wp_pocketurl' ),
			'description'         => esc_html__( 'Redirect Rule', 'wp_pocketurl' ),
			'labels'              => $labels,
			'supports'            => array( 'title'),
			'taxonomies'          => array( 'wp_pocketurl_link_category' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon' 		  => 'dashicons-admin-links',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		);
        $args['rewrite'] = $rewrite;
		register_post_type( 'wp_pocketurl_link', $args );
		flush_rewrite_rules();
	}
	// register taxonomy
	public function wp_pocketurl_link_category_init() {
		$labels = array(
			'name'                       => esc_html_x( 'Link Categories', 'Taxonomy General Name', 'wp_pocketurl' ),
			'singular_name'              => esc_html_x( 'Link Category', 'Taxonomy Singular Name', 'wp_pocketurl' ),
			'menu_name'                  => esc_html__( 'Link Categories', 'wp_pocketurl' ),
			'all_items'                  => esc_html__( 'All Categories', 'wp_pocketurl' ),
			'parent_item'                => esc_html__( 'Parent Category', 'wp_pocketurl' ),
			'parent_item_colon'          => esc_html__( 'Parent Category:', 'wp_pocketurl' ),
			'new_item_name'              => esc_html__( 'New Category', 'wp_pocketurl' ),
			'add_new_item'               => esc_html__( 'Add New Category', 'wp_pocketurl' ),
			'edit_item'                  => esc_html__( 'Edit Category', 'wp_pocketurl' ),
			'update_item'                => esc_html__( 'Update Category', 'wp_pocketurl' ),
			'view_item'                  => esc_html__( 'View Link Category', 'wp_pocketurl' ),
			'separate_items_with_commas' => esc_html__( 'Separate Link Categories with commas', 'wp_pocketurl' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Link Categories', 'wp_pocketurl' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'wp_pocketurl' ),
			'popular_items'              => esc_html__( 'Popular Categories', 'wp_pocketurl' ),
			'search_items'               => esc_html__( 'Search Items', 'wp_pocketurl' ),
			'not_found'                  => esc_html__( 'Not Found', 'wp_pocketurl' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( 'wp_pocketurl_link_category', array( 'wp_pocketurl_link' ), $args );
		$taxonomy_permalinks = new Add_Taxonomy_To_Post_Permalinks( 'wp_pocketurl_link_category',array('tagname'=>'link-cat') );
	}
	//register link options metabox
	public function wp_pocketurl_link_options_init(){
		add_meta_box("wp_pocketurl_link_options","Link Options",array($this,"wp_pocketurl_link_options_display"),
		"wp_pocketurl_link","normal","high");
		
	}
	//call link options view
	public function wp_pocketurl_link_options_display(){
		require_once(wp_pocketurl_path.'assets/views/link-options.php');	
	}
	//save link options data
	public function wp_pocketurl_link_options_save($post_id){
		if(! $this->user_can_save($post_id)){
			return;	
		}
		//get variables from the POST
		$linkURL = stripslashes(strip_tags($_POST['wp_pocketurl_link']));
		if(strpos($linkURL,'http://') === false && strpos($linkURL,'https://') === false){
			$linkURL = 'http://'.$linkURL;
		}
		$cusOption = (isset($_POST['wp_pocketurl_link_custom_options']))?sanitize_text_field($_POST['wp_pocketurl_link_custom_options']):'0';
		$linkRedirection = (isset($_POST['wp_pocketurl_link_redirection']))?sanitize_text_field($_POST['wp_pocketurl_link_redirection']):'301';
		
		//update post meta
		update_post_meta($post_id,'wp_pocketurl_link',$linkURL);
		update_post_meta($post_id,'wp_pocketurl_link_custom_options',$cusOption);
		update_post_meta($post_id,'wp_pocketurl_link_redirection',$linkRedirection);
	}

	//check if the user can save
	public function user_can_save($post_id){
		$is_valid_nonce =
		(isset($_POST['wp_pocketurl_link_options_nonce'])) &&
		wp_verify_nonce($_POST['wp_pocketurl_link_options_nonce'],
			'wp_pocketurl_link_options_save');
		
		$is_autosave = wp_is_post_autosave($post_id);
		$is_revision = wp_is_post_revision($post_id);
		
		return !($is_revision || $is_autosave) && $is_valid_nonce;
	}
	
	function wp_pocketurl_link_template($single){
		global $post;
		/* Checks for single template by post type */
		if ($post->post_type == "wp_pocketurl_link"){
			global $wp_filesystem;
			if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
				include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
				wp_filesystem($creds);
			}
			if($wp_filesystem->exists(wp_pocketurl_path . 'assets/views/single-wp_pocketurl_link.php'))
            {
				return wp_pocketurl_path . 'assets/views/single-wp_pocketurl_link.php';
            }
		}
		return $single;
	}
	//register link clicks details metabox
	public function wp_pocketurl_link_clicks_details_init(){
		add_meta_box("wp_pocketurl_link_clicks_details","Clicks details",array($this,"wp_pocketurl_link_clicks_details"),
		"wp_pocketurl_link","normal","low");
		
	}
	
	public function wp_pocketurl_link_clicks_details(){
		require_once(wp_pocketurl_path.'assets/views/clicks_details.php');
	}
	
	public function wp_pocketurl_delete_click_data($postID){
		global $wpdb;
		global $post_type;   
		if ($post_type != 'wp_pocketurl_link') return;
		$sql = $wpdb->prepare(
			"DELETE FROM {$wpdb->wp_pocketurl_clicks_table} WHERE link_id = %d",
			$postID
		);
		$rows = $wpdb->query($sql);
	}
	
}