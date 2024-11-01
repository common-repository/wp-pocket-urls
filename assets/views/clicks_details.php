<?php
require_once(wp_pocketurl_path.'classes/class-wp-pocketurl-clicks.php');
$clickObj = new WP_PocketURLs_Clicks();
$count = $clickObj->getClicksCountBYID(get_the_ID());
if($count)
{
    $current_page = (isset($_GET['click_details_page']))? sanitize_text_field($_GET['click_details_page']) : 1;
    if(isset($_GET['click_details_page'])){
        $current_page = sanitize_text_field($_GET['click_details_page']);
        $page_start = (sanitize_text_field($_GET['click_details_page']) == 1)? 0 : sanitize_text_field($_GET['click_details_page']) * 10 - 9;
    }else{
        $current_page = 1;
        $page_start = 0;
    }
    $clicks = $clickObj->getClicksDetailsByID(get_the_ID(),$page_start,10);
    $total_pages =ceil( $clickObj->getClicksDetailsTotalBYID(get_the_ID()) / 10);
?>
<div class="clicks-details-container">
<span class="clicks-count"><strong><?php echo esc_html__('Clicks Total: ', 'wp_pocketurl');?></strong><?php echo esc_html($count);echo esc_html__(' clicks', 'wp_pocketurl');?></span>
<table class="clicks-details">
	<tr>
    	<th><?php echo esc_html__('No.', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('Date/Time', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('IP', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('Country', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('City', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('Region Code', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('Latitude/Longitude', 'wp_pocketurl');?></th>
        <th><?php echo esc_html__('Timezone', 'wp_pocketurl');?></th> 
    </tr>
<?php
    $ii = (isset($_GET['click_details_page']))? sanitize_text_field($_GET['click_details_page']) : 0;
    $i = ( $ii == 1 || $ii == 0 )? 0 : $page_start - 1;
    foreach($clicks as $click)
    {
        $i++;
	?>
    <tr>
    	<td><?php echo esc_html($i); ?></td>
        <td><?php echo esc_html($click->click_date); ?></td>
        <td><?php echo esc_html($click->click_ip); ?></td>
        <td><?php echo esc_html($click->click_country.' ['.$click->click_country_code.']'); ?></td>
        <td><?php echo esc_html($click->click_city); ?></td>
        <td><?php echo esc_html($click->click_region_code);?></td>
        <td><?php echo esc_html($click->click_latitude.','.$click->click_longitude); ?></td>
        <td><?php echo esc_html($click->click_timezone); ?></td>
    </tr>
<?php
    }
?>
<tr class="footer">
    <td colspan="2">
     <strong><?php echo esc_html__('Page:', 'wp_pocketurl');?></strong> <?php echo esc_html($current_page);?>&nbsp;<?php echo esc_html__('of', 'wp_pocketurl');?>&nbsp;<?php echo esc_html($total_pages);?>
    </td>
    <td colspan="9">
    	<div class="pages">
        	<?php
				for($i=1;$total_pages >= $i;$i++){
					if($current_page == $i){?>
                    <span><?php echo esc_html($i); ?></span>
					<?php }else{?>
					<a href="<?php echo esc_url($_SERVER["REQUEST_URI"] ."&click_details_page=$i");?>"><?php echo esc_html($i); ?></a>
				<?php }}
			?>
        </div>
    </td>
</tr>
</table></div>
<?php
}
else
{
	echo esc_html__('There are no clicks yet.', 'wp_pocketurl');
}
?>