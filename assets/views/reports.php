<?php
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}
require_once(wp_pocketurl_path .'classes/class-wp-pocketurl-reports.php');

$wp_pocketurl_reports = new WP_PocketURLs_Reports();
$wp_pocketurl_clicks = new WP_PocketURLs_Clicks();
$month = $cat = $country = $link = null;
if(!empty( $_GET['wp_pocketurl_link_click_months'] ) ){
  $month = sanitize_text_field($_GET['wp_pocketurl_link_click_months']);
}
if(!empty( $_GET['wp_pocketurl_link_category'] ) ){
  $cat = sanitize_text_field($_GET['wp_pocketurl_link_category']);
}
if(!empty( $_GET['wp_pocketurl_link_country'] ) ){
  $country = sanitize_text_field($_GET['wp_pocketurl_link_country']);
}
if(!empty( $_GET['wp_pocketurl_link'] ) ){
  $link = sanitize_text_field($_GET['wp_pocketurl_link']);
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
  label{
    width:50%;
    display: inline-block;
    margin-bottom: 15px;
  }
  label span{
    display: inline-block;
    width: 30%;
    font-weight: bold;
  }
  label select{
    width: 50%;
  }
  .button.button-primary{
    float: right;
    margin-right: 10%;
  }
</style>
<div class="wrap">
  <h2><?php echo esc_html__('WP Pocket URLs Reports', 'wp_pocketurl');?></h2>
  <div class="leftcol">
    <form method="GET" action="#" enctype="multipart/form-data">
      <input type="hidden" name="post_type" value="wp_pocketurl_link" />
      <input type="hidden" name="page" value="wp_pocketurl_link_reports" />
      <?php $wp_pocketurl_reports->wp_pocketurl_reports_filters($month,$cat,$country,$link); ?>
    </form>
    <!-- gchart-->
    <script type="text/javascript">
      google.charts.load('current', {packages: ['corechart', 'line']});
google.charts.setOnLoadCallback(wp_pocketurl_drawBackgroundColor);

function wp_pocketurl_drawBackgroundColor() {
      var data = new google.visualization.DataTable();
      data.addColumn('date', 'X');
      data.addColumn('number', 'Clicks');

      data.addRows([
        <?php 
          $total_clicks = 0;
          $clicks_obj = $wp_pocketurl_reports->wp_pocketurl_get_clicks_report( $month, $cat, $country, $link );
          
          foreach ($clicks_obj as $key => $obj) {
            echo '[new Date("'.esc_html($obj->date).'"),'.esc_html($obj->clicks).'],';
            $total_clicks += (int)$obj->clicks; 
          }
        ?>
        
      ]);

      var options = {
        backgroundColor: 'transparent',
        interpolateNulls: false,
        height: 400,
        hAxis: {
          title: 'Date'
        },
        vAxis: {
          title: 'Clicks'
        },
        chartArea: 
        {
            left: 'auto',
            top: 'auto'
        },
        
      };

      var chart = new google.visualization.LineChart(document.getElementById('chart_container'));
      chart.draw(data, options);
    }
    </script>
    <!--end gchart-->
    <div class="total-clicks">
      <h4><?php echo esc_html__('Total:', 'wp_pocketurl');?> <?php echo esc_html($total_clicks); ?> <?php echo esc_html__('clicks', 'wp_pocketurl');?></h4>
    </div>
    <div id="chart_container" style="width:800px;"></div>
    </div>
</div>

