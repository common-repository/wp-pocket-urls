<div class="link-options">
<label><?php echo esc_html__('Link Redirect URL:', 'wp_pocketurl');?> </label>
<input type="text" name="wp_pocketurl_link" placeholder="https://yourawesome.url/goto/link?ref=1" value="<?php echo esc_attr(get_post_meta(get_the_ID(),'wp_pocketurl_link' ,true)); ?>"/>
<div class="wp_pocketurl_error"><?php echo esc_html__('This field can not be empty, and it must contain a valid URL!', 'wp_pocketurl');?> </div>
<div class="clearfix"></div>
<label class="custom-options">
	<?php $cusOPtions =  esc_attr(get_post_meta(get_the_ID(),'wp_pocketurl_link_custom_options' ,true)); ?>
	<input type="checkbox" name="wp_pocketurl_link_custom_options" id="enable-custom-link-options" value="1"
    <?php if($cusOPtions) echo 'checked="checked"'  ?>/>
    Activate custom link options
</label><br>
<fieldset id="custom-options" disabled>
<legend><?php echo esc_html__('Redirect Advanced Options:', 'wp_pocketurl');?></legend>
<label><?php echo esc_html__('Redirect Type:', 'wp_pocketurl');?>
<select name="wp_pocketurl_link_redirection">
<?php $linkRedirection =  esc_attr(get_post_meta(get_the_ID(),'wp_pocketurl_link_redirection' ,true)); ?>
    <option value="301" <?php echo $linkRedirection == "301" ?  'selected="selected"':''; ?>><?php echo esc_html__('301 Redirect', 'wp_pocketurl');?></option>
    <option value="302" <?php echo $linkRedirection == "302" ?  'selected="selected"':''; ?>><?php echo esc_html__('302 Redirect', 'wp_pocketurl');?></option>
    <option value="303" <?php echo $linkRedirection == "303" ?  'selected="selected"':''; ?>><?php echo esc_html__('303 Redirect', 'wp_pocketurl');?></option>
    <option value="307" <?php echo $linkRedirection == "307" ?  'selected="selected"':''; ?>><?php echo esc_html__('307 Redirect', 'wp_pocketurl');?></option>
    <option value="js" <?php echo $linkRedirection == "js" ?  'selected="selected"':''; ?>><?php echo esc_html__('JavaScript Redirect', 'wp_pocketurl');?></option>
</select></label>
</div>
<?php
	wp_nonce_field('wp_pocketurl_link_options_save','wp_pocketurl_link_options_nonce');
?>
</fieldset>