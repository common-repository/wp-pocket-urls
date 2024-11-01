<?php
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

?>
<style>
  .leftcol{
    width:75%;
    float: left;
  }
  .rightcol{
    float: right;
    width:25% 
  }
  .rightcol h3{
    line-height:30px; 
  }
  .rightcol img{
    max-width:100%;
  }
  .full{
    display: block;
    width:100%;
    text-align: center;
  }
  .warning{
    border-left:5px solid #e6db55;
  }
  .wp-core-ui .notice.is-dismissible{
    width:68%;
  }
</style>
<div class="wrap">
	<h2><?php echo esc_html__('WP Pocket URLs Settings', 'wp_pocketurl');?></h2>
    <div class="leftcol">
    <?php settings_errors(); ?>
    <div class="notice warning is-dismissible">
        <p><?php _e( 'You may need to ', 'wp-pocketurl' ); ?>
            <a href="<?php echo admin_url('options-permalink.php'); ?>"><?php echo esc_html__('save site permalink settings page', 'wp_pocketurl');?></a>,&nbsp;<?php echo esc_html__('after updating link prefix setting!', 'wp_pocketurl');?>
        </p>
    </div>
    <form method="POST" action="options.php" enctype="multipart/form-data">
    	<?php settings_fields( 'wp_pocketurl_settings_group' ); ?>
        <?php do_settings_sections( 'wp_pocketurl_link_settings' ); ?>
        <?php submit_button(); ?>
    </form></div>
</div>