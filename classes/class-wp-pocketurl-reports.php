<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
if( class_exists('WP_PocketURLs_Reports') ) return;

class WP_PocketURLs_Reports{

  function __construct(){

  }
  /*
  * return reports filters used in reports view
  */
  public function wp_pocketurl_reports_filters( $cmonth=null, $cat=null, $ccountry=null, $clink=null ){

    //link title
    $output[] ='<label for="wp_pocketurl_link"><span>Link:</span>';
    $output[]='<select name="wp_pocketurl_link">';
    $output[] = '<option value="">all</option>';
    $links = $this->wp_pocketurl_get_all_links();
    foreach ($links as $key => $link) {
      $lselected = ($clink == $link->ID) ? 'selected' :'';
      $output[] ="<option value='" . esc_attr($link->ID) . "' " . esc_attr($lselected) . ">" . esc_attr($link->post_title) . "</option>";
    }
    $output[]='</select></label>';
    //months list
    $output[] ='<label for="wp_pocketurl_link_click_months"><span>Date:</span>';
    $output[]='<select name="wp_pocketurl_link_click_months">';
    $output[] = '<option value="">all</option>';
    $months = $this->wp_pocketurl_link_clicks_months();
    foreach ($months as $key => $month) {
      $mselected = ($cmonth == $month) ? 'selected' :'';
      $output[] = '<option value="'.esc_attr($month).'" '.esc_attr($mselected).'>'.esc_attr($month).'</option>';
    }
    $output[]='</select></label>';

    //taxonomies list
    $terms = get_terms('wp_pocketurl_link_category');
    $output[] ='<label for="wp_pocketurl_link_category"><span>Link Category:</span>';
    $output[]='<select name="wp_pocketurl_link_category">';
    $output[] = '<option value="">all</option>';
    foreach ($terms as $key => $term) {
      $cselected = ($cat == $term->term_id) ? 'selected' :'';
      $output[] = '<option value="'.esc_attr($term->term_id).'" '.esc_attr($cselected).'>'.esc_attr($term->name).'</option>';
    }
    $output[]='</select></label>';

    //countries list
    $countries = $this->wp_pocketurl_link_countries();
    $output[] ='<label for="wp_pocketurl_link_country"><span>Clicks Country:</span>';
    $output[]='<select name="wp_pocketurl_link_country">';
    $output[] = '<option value="">all</option>';
    foreach ($countries as $key => $country) {
      $coselected = ($ccountry == $country->code) ? 'selected' :'';
      $output[] = '<option value="'.esc_attr($country->code).'" '.esc_attr($coselected).'>'.esc_attr($country->name).'</option>';
    }
    $output[]='</select></label>';
    $output[]='<input type="submit" class="button button-primary" value="'.__('Update').'" />';
    
    echo implode('',$output);
  }
  /*
  * get a list of countries that have a click on a link
  */
  public function wp_pocketurl_link_countries(){
    global $wpdb;
    $sql = "SELECT DISTINCT(click_country_code) as code, click_country as name FROM {$wpdb->wp_pocketurl_clicks_table} WHERE click_country <> 'not available'";
    return $wpdb->get_results($sql);
  }
  /*
  * prepare months filters
  * return array of earliest and latest month/year of clicks, 
  */
  public function wp_pocketurl_link_clicks_months(){
    global $wpdb;
    $sql = "SELECT MIN(click_date) as min_date,MAX(click_date) as max_date FROM {$wpdb->wp_pocketurl_clicks_table}";

    $dates = $wpdb->get_row($sql,'ARRAY_A');

    $interval = new DateInterval('P1M');
    if(isset($dates['max_date']))
    {
      $max_date = new DateTime($dates['max_date']);
    }
    else
    {
      $max_date = new DateTime('now');
    }
    $max_date->add($interval);
    if(!isset($dates['min_date']))
    {
      $dates['min_date'] = 'now';
    }
    $period = new DatePeriod( new DateTime($dates['min_date']), $interval, $max_date );
    foreach($period as $date) { 
        $date_list[] = $date->format('M Y');
    }
    return $date_list;
  }
  /*
  * get all wp pocketurl links
  */
  public function wp_pocketurl_get_all_links($cat=null, $country=null, $url=null){
    $args = array(
      'post_type' => 'wp_pocketurl_link',
      'post_per_page' => -1,
    );

    // set tax_query
    if($cat !== null){
      $args['tax_query'] = array(
        array(
        'taxonomy' => 'wp_pocketurl_link_category',
        'field' => 'term_id',
        'term' => ($cat != null )? $cat : ''
        )
      );
    }

    //set meta_query
    if($url !== null){
      $args['meta_query'] = array(
        array(
          'key' => 'wp_pocketurl_link',
          'value' => $url
        )
      );
    }
    // set post__in query if country value is being set
    if($url !== null){
      $args['post__in'] = $this->wp_pocketurl_links_by_country($country);
    }

    $links = new WP_Query( $args );
    return $links->get_posts();
  }
  /*
  * return array of posts IDs by country code
  */
  public function wp_pocketurl_links_by_country($code){
    global $wpdb;
    $sql = $wpdb->prepare(
        "SELECT DISTINCT(link_id) as ID FROM {$wpdb->wp_pocketurl_clicks_table} WHERE click_country_code = %s",
        $code
    );
    return (array) $wpdb->get_col($sql);
  }
  /*
  * get all links IDs based on category ID
  */
  public function wp_pocketurl_get_links_ids_by_cat_id($cat){
    return get_posts(array(
        'numberposts'   => -1, // get all posts.
        'post_type' => 'wp_pocketurl_link',
        'tax_query'     => array(
            array(
                'taxonomy'  => wp_pocketurl_link_category,
                'field'     => 'id',
                'terms'     => is_array($cat) ? $cat : array($cat),
            ),
        ),
        'fields'        => 'ids', // only get post IDs.
    ));
  }
  /*
  * get clicks count grouped by date
  */
  public function wp_pocketurl_get_clicks_report($cmonth = null, $cat = null, $country = null, $link = null){
    global $wpdb;
    $sql = "SELECT count(1) as clicks, DATE(click_date) as date FROM {$wpdb->wp_pocketurl_clicks_table}";

    $where_clauses = [];

    if ($country) {
        $where_clauses[] = $wpdb->prepare("click_country_code = %s", $country);
    }

    if ($cmonth) {
        $year = date('Y', strtotime($cmonth));
        $month = date('m', strtotime($cmonth));
        $where_clauses[] = $wpdb->prepare("YEAR(click_date) = %d AND MONTH(click_date) = %d", $year, $month);
    }

    if ($cat) {
        $IDs = implode(',', array_map('absint', $this->wp_pocketurl_get_links_ids_by_cat_id($cat)));
        if (!empty($IDs)) {
            $where_clauses[] = "link_id IN ({$IDs})";
        }
    }

    if ($link) {
        $where_clauses[] = $wpdb->prepare("link_id = %d", $link);
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql .= " GROUP BY DATE(click_date) ORDER BY click_date ASC";

    $result = $wpdb->get_results($sql);
    return $result; 
}
  
  /*
  * get top 10 links with the most clicks
  */
  public function wp_pocketurl_get_links_stats(){
    global $wpdb;
    $query = "SELECT link_id, count(1) as clicks FROM {$wpdb->wp_pocketurl_clicks_table} Group BY `link_id` LIMIT 10";
    $result = $wpdb->get_results($query);
    return $result;
  }
}
