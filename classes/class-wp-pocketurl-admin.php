<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function wp_pocketurl_do_something_after_title( $post ) {
    if( 'wp_pocketurl_link' !== $post->post_type ) 
    {
        return;
    }
    echo '<b>' . esc_html__('The above will be the short link slug on your site', 'wp_pocketurl') . '</b>';
}
add_action( 'edit_form_after_title', 'wp_pocketurl_do_something_after_title' );
class WP_PocketURLs_Admin{
	function __construct(){
		add_filter( 'manage_edit-wp_pocketurl_link_columns', array($this,'wp_pocketurl_edit_link_columns') ) ;
		add_filter( 'manage_edit-wp_pocketurl_link_category_columns', array($this,'wp_pocketurl_edit_link_category_columns') ) ;
		add_filter( 'manage_wp_pocketurl_link_posts_custom_column', array($this,'wp_pocketurl_columns_data') ) ;
		add_filter( 'manage_wp_pocketurl_link_category_custom_column', array($this,'wp_pocketurl_category_columns_data'),10,3 ) ;
		//add setting page menu
		add_action("admin_menu",array($this,'wp_pocketurl_pages_init'));
		//register settings page
		add_action('admin_init', array($this,'register_wp_pocketurl_settings'));
		// pre update 
		add_filter( 'pre_update_option_wp_pocketurl_link_prefix', array($this,'wp_pocketurl_link_slug_change'), 10, 2 );
		add_action( 'update_option_wp_pocketurl_link_prefix', array($this,'wp_pocketurl_action_update_option'),10,2 );
		add_action('wp_dashboard_setup', array($this,'wp_pocketurl_dashboard_widget') );
		// add reset clicks count button
		add_filter( 'post_row_actions', array( $this, 'add_reset_clicks_count_btn' ), 10, 2 );
		add_action( 'admin_action_wp_pocketurl_reset_clicks', array( $this, 'wp_pocketurl_reset_clicks' ) );
	}
	//add clicks column to wp pocketurl link edit page
	public function wp_pocketurl_edit_link_columns($columns){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => esc_html__('Link', 'wp_pocketurl'),
			'category' => esc_html__('Link Categories', 'wp_pocketurl'),
			'clicks' => esc_html__('Clicks', 'wp_pocketurl'),
			'redirect' => esc_html__('Redirect To', 'wp_pocketurl'),
			'link' => esc_html__('Link', 'wp_pocketurl'),
			'date' => esc_html__('Date', 'wp_pocketurl')
		);
		return $columns;
	}
    public function wp_pocketurl_action_update_option($columns){
		return $columns;
	}
	// add click to link category edit page
	public function wp_pocketurl_edit_link_category_columns($columns){
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => esc_html__('Name', 'wp_pocketurl'),
			'description' => esc_html__('Description', 'wp_pocketurl'),
			'slug' => esc_html__('Slug', 'wp_pocketurl'),
			'clicks' => esc_html__('Clicks', 'wp_pocketurl'),
			'posts' => esc_html__('Count', 'wp_pocketurl'),
		);
		return $columns;
	}
	//add content to the custom columns
	public function wp_pocketurl_columns_data($column){
		global $post;
		$post_id = $post->ID;
		switch( $column ){
			case 'category':
				$terms = get_the_terms( $post_id, 'wp_pocketurl_link_category' );
				if ( !empty( $terms ) ) {
					/* Loop through each term, linking to the 'edit posts' page for the specific term. */
					foreach ( $terms as $term ) {
						$out[] = sprintf( '<a href="%s">%s</a>',
							esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'wp_pocketurl_link_category' => $term->slug ), 'edit.php' ) ),
							esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'wp_pocketurl_link_category', 'display' ) )
						);
					}
	
					/* Join the terms, separating them with a comma. */
					echo join( ', ', $out );	
				}else{
					echo esc_html__('Uncategorized', 'wp_pocketurl');	
				}
			break;	
			case 'clicks':
				$clickObj = new WP_PocketURLs_Clicks();
				$clicks = $clickObj->getClickCountByPostID($post_id);
				if($clicks){
					echo $clicks;
				}else{
					echo '0';	
				}
			break;
			case 'link':
                $unid = uniqid();
				echo '<div class="copylink-container"><input class="copythis" id="pocketurls-link' . $unid . '" type="text" readonly="readonly" value="'.get_permalink($post_id).
				'"/> <span class="copy add-new-h2" onclick="pocketURL_myFunction(\'' . $unid . '\');">Copy</span></div>';
			break;
			case 'redirect':
				echo get_post_meta($post_id, 'wp_pocketurl_link', true);
			break;
			default:
			break;
		}
	}
	//add count content to the link category admin table
	public function wp_pocketurl_category_columns_data($out,$column,$tax_id){
		switch( $column ){
			case 'clicks':
				//add clicks count by category id
				$term = get_term( $tax_id, 'wp_pocketurl_link_category' );
				$clickObj = new WP_PocketURLs_Clicks();
				$clicks = $clickObj->getClickCountByCatID($term->term_id);
				$out = (isset($clicks))?$clicks:'0';
			break;
			default:
			break;
		}
		return $out;
	}
	
	//setting page function 
	public function wp_pocketurl_pages_init(){
		//reports page
		add_submenu_page('edit.php?post_type=wp_pocketurl_link', 'WP Pocket URLs Clicks Report', 'Reports', 'manage_options', 'wp_pocketurl_link_reports', array($this,'wp_pocketurl_reports_page_content'));
		
		//setting page
		add_submenu_page('edit.php?post_type=wp_pocketurl_link', 'WP Pocket URLs Settings', 'Settings', 'manage_options', 'wp_pocketurl_link_settings', array($this,'wp_pocketurl_setting_page_content'));
        
        //setting page
		add_submenu_page('edit.php?post_type=wp_pocketurl_link', 'Other Plugins', 'Other Plugins', 'manage_options', 'wp_pocketurl_link_other', array($this,'wp_pocketurl_other_page_content'));
	}
	//setting page content
	public function wp_pocketurl_setting_page_content(){
		require_once(wp_pocketurl_path.'assets/views/settings.php');
	}
    //setting page content
	public function wp_pocketurl_other_page_content(){
		require_once(wp_pocketurl_path.'assets/views/other.php');
	}
	//reports page content
	public function wp_pocketurl_reports_page_content(){
		require_once(wp_pocketurl_path.'assets/views/reports.php');
	}
	//register setting fields
	public function register_wp_pocketurl_settings(){
		add_settings_section( 'default_settings', '', array($this,'default_settings_callback'), 'wp_pocketurl_link_settings' );
		register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_prefix' );
		add_settings_field( 'wp_pocketurl_link_prefix', 'Link Prefix', array($this,'wp_pocketurl_link_prefix_callback'), 'wp_pocketurl_link_settings', 'default_settings' );
		
		register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_redirection' );
		add_settings_field( 'wp_pocketurl_link_redirection', 'Default Link Redirection', array($this,'wp_pocketurl_link_redirection_callback'), 'wp_pocketurl_link_settings', 'default_settings' );

		register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_collect_data' );
		add_settings_field( 'wp_pocketurl_link_collect_data', 'Collect User Data On Redirection', array($this,'wp_pocketurl_link_collect_data_callback'), 'wp_pocketurl_link_settings', 'default_settings' );

		register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_exclude_cat' );
		add_settings_field( 'wp_pocketurl_link_exclude_cat', 'Do Not Add Category To Permalink', array($this,'wp_pocketurl_link_exclude_cat_callback'), 'wp_pocketurl_link_settings', 'default_settings' );

		register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_enable_auto' );
		add_settings_field( 'wp_pocketurl_link_enable_auto', 'Enable Published Posts External Link Auto Shortening', array($this,'wp_pocketurl_auto_callback'), 'wp_pocketurl_link_settings', 'default_settings' );

        register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_exclude_word' );
		add_settings_field( 'wp_pocketurl_link_exclude_word', 'Auto Shortening Banned URL String List', array($this,'wp_pocketurl_link_exclude_word_callback'), 'wp_pocketurl_link_settings', 'default_settings' );
        
        register_setting( 'wp_pocketurl_settings_group', 'wp_pocketurl_link_require_word' );
		add_settings_field( 'wp_pocketurl_link_require_word', 'Auto Shortening Required URL String List', array($this,'wp_pocketurl_link_required_word_callback'), 'wp_pocketurl_link_settings', 'default_settings' );
	}
	// call back function for add settings section
	public function default_settings_callback(){
		//echo 'some text';	
	}
	public function wp_pocketurl_link_prefix_callback() {
		$setting = esc_attr( get_option( 'wp_pocketurl_link_prefix','go' ) );
		echo "<input type='text' name='wp_pocketurl_link_prefix' value='$setting' placeholder=\"Add a prefix to created links\"/>";
	}	
	public function wp_pocketurl_link_redirection_callback(){
		$linkRedirection = esc_attr( get_option( 'wp_pocketurl_link_redirection','301' ) );?>
		<select name="wp_pocketurl_link_redirection">
			<option value="301" <?php echo $linkRedirection == "301" ?  'selected="selected"':''; ?>><?php echo esc_html__('301 Redirect', 'wp_pocketurl');?></option>
			<option value="302" <?php echo $linkRedirection == "302" ?  'selected="selected"':''; ?>><?php echo esc_html__('302 Redirect', 'wp_pocketurl');?></option>
			<option value="303" <?php echo $linkRedirection == "303" ?  'selected="selected"':''; ?>><?php echo esc_html__('303 Redirect', 'wp_pocketurl');?></option>
			<option value="307" <?php echo $linkRedirection == "307" ?  'selected="selected"':''; ?>><?php echo esc_html__('307 Redirect', 'wp_pocketurl');?></option>
			<option value="js" <?php echo $linkRedirection == "js" ?  'selected="selected"':''; ?>><?php echo esc_html__('JavaScript Redirect', 'wp_pocketurl');?></option>
		</select><?php
	}
	public function wp_pocketurl_link_collect_data_callback(){
		$collect = esc_attr( get_option( 'wp_pocketurl_link_collect_data','no' ) );?>
		<select name="wp_pocketurl_link_collect_data">
			<option value="yes" <?php echo $collect == "yes" ?  'selected="selected"':''; ?>><?php echo esc_html__('Yes', 'wp_pocketurl');?></option>
			<option value="no" <?php echo $collect == "no" ?  'selected="selected"':''; ?>><?php echo esc_html__('No', 'wp_pocketurl');?></option>
		</select><?php
	}
    
	public function wp_pocketurl_auto_callback(){
		$collect = esc_attr( get_option( 'wp_pocketurl_link_enable_auto','no' ) );?>
		<select name="wp_pocketurl_link_enable_auto">
			<option value="yes" <?php echo $collect == "yes" ?  'selected="selected"':''; ?>><?php echo esc_html__('Yes', 'wp_pocketurl');?></option>
			<option value="no" <?php echo $collect == "no" ?  'selected="selected"':''; ?>><?php echo esc_html__('No', 'wp_pocketurl');?></option>
		</select><?php
	}	

	public function wp_pocketurl_link_exclude_word_callback(){
		$exclude = esc_attr( get_option( 'wp_pocketurl_link_exclude_word','' ) );
		echo "<input type='text' name='wp_pocketurl_link_exclude_word' value='$exclude' placeholder=\"Exclude this comma separated word list\"/>";
	}
	public function wp_pocketurl_link_required_word_callback(){
		$req = esc_attr( get_option( 'wp_pocketurl_link_require_word','' ) );
		echo "<input type='text' name='wp_pocketurl_link_require_word' value='$req' placeholder=\"Require this comma separated word list\"/>";
	}
	public function wp_pocketurl_link_exclude_cat_callback(){
		$exclude = esc_attr( get_option( 'wp_pocketurl_link_exclude_cat','yes' ) );?>
		<select name="wp_pocketurl_link_exclude_cat">
			<option value="yes" <?php echo $exclude == "yes" ?  'selected="selected"':''; ?>><?php echo esc_html__('Yes', 'wp_pocketurl');?></option>
			<option value="no" <?php echo $exclude == "no" ?  'selected="selected"':''; ?>><?php echo esc_html__('No', 'wp_pocketurl');?></option>
		</select><?php
	}
	
	// change wp pocketurl link post slug if the option changed
	public function wp_pocketurl_link_slug_change($new_value,$old_value ){
		if($new_value !== $old_value )
		{
            $registered = FALSE;
			//check if the old value is empty
			if(empty($old_value)) $old_value = 'go';
			// First, try to load up the rewrite rules. We do this just in case
    		// the default permalink structure is being used.
			if( ($current_rules = get_option('rewrite_rules')) ) {
				// Next, iterate through each custom rule adding a new rule
				// that replaces 'go' with 'option value' and give it a higher
				// priority than the existing rule.
				if(is_array($current_rules))
                {
					foreach($current_rules as $key => $val) {
						if(strpos($key, $old_value ) !== false) {
							$registered = TRUE;
							add_rewrite_rule(str_ireplace($old_value, $new_value, $key), $val, 'top');
						}
					}
				}
			}
			if($registered)
			{
				flush_rewrite_rules();
			}
		}
		return $new_value;
	}

	/*
	* initilize admin widget
	*/
	public function wp_pocketurl_dashboard_widget(){
		wp_add_dashboard_widget('wp_pocketurl_dashboard_widget','WP Pocket URLs - Top 10 Links',array($this,'wp_pocketurl_dashboard_widget_content') );
	}
	/*
	* get link stats
	* get the top 5 links based on clicks & total clicks
	*/
	public function wp_pocketurl_dashboard_widget_content(){
		$wp_pocketurl_report = new WP_PocketURLs_Reports();
		$stats = $wp_pocketurl_report->wp_pocketurl_get_links_stats();?>
		<table class="widefat " cellspacing="0">
	    <thead>
		    <tr>
		    	<th scope="col"><?php echo esc_html__('Link Title', 'wp_pocketurl');?></th>
		    	<th scope="col"><?php echo esc_html__('Clicks/Hits', 'wp_pocketurl');?></th>
		    	<th scope="col"><?php echo esc_html__('Redirect to', 'wp_pocketurl');?></th>
		    	<th scope="col"><?php echo esc_html__('Edit', 'wp_pocketurl');?></th>
		    </tr>
	    </thead>
	    <tbody>
		<?php foreach ($stats as $key => $stat):?>
			<tr <?php echo ($key % 2 )? 'class="alternate"':''; ?> >
			<td class="column" style="border-bottom: 1px solid #eee"><?php echo get_the_title($stat->link_id); ?></td>
			<td class="column" style="border-bottom: 1px solid #eee"><?php echo $stat->clicks ?></td>
			<td class="column" style="border-bottom: 1px solid #eee">
				<a href="<?php echo get_post_meta($stat->link_id,'wp_pocketurl_link',true); ?>"><?php echo esc_html__('View', 'wp_pocketurl');?></a>
			</td>
			<td class="column" style="border-bottom: 1px solid #eee">
				<a href="<?php echo get_edit_post_link($stat->link_id); ?>"><?php echo esc_html__('Edit', 'wp_pocketurl');?></a>
			</td>
			</tr>
		<?php endforeach; ?>
			<tbody>
		</table>
<?php
	}
	/**
 * add reset clicks count button function
 */
	public function add_reset_clicks_count_btn($actions, $id ){
		global $post;
		if( $post->post_type == "wp_pocketurl_link" ){
			$actions['wp_pocketurl_reset_clicks_btn'] = '<a href="post.php?post='.$post->ID.'&action=wp_pocketurl_reset_clicks" class="reset-clicks-count-btn" title="Reset clicks count">Reset</a>';
		}
		
		return $actions;
	}

	/**
	* reset clicks count from the database
	*/
	public function wp_pocketurl_reset_clicks(){
        $id = '';
        if(isset( $_GET[ 'post' ]))
        {
            if(is_numeric( $_GET[ 'post' ]))
            {
                $id = (int) sanitize_text_field($_GET[ 'post' ]);
            }
        }
        if($id == '' && isset( $_REQUEST[ 'post' ]))
        {
            if(is_numeric( $_REQUEST[ 'post' ]))
            {
                $id = (int) sanitize_text_field($_REQUEST[ 'post' ]);
            }
        }
        if($id != '')
        {
            $wp_pocketurl_clicks = new WP_PocketURLs_Clicks();
            $wp_pocketurl_clicks->delete_clicks_count($id);
        }
		wp_redirect( admin_url( 'edit.php?post_type=wp_pocketurl_link' ) );
		exit;
	}
}

