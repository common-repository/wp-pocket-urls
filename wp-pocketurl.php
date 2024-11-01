<?php
/*
Plugin Name: WP Pocket URLs
Plugin URI: https://www.coderevolution.ro/wp-pocketurl
Description: WP Pocket URLs gives you the ability to shorten your affiliate links and keep track of clicks for each link.
Version: 1.0.4
Author: CodeRevolution
Author URI: https://www.coderevolution.ro
Text Domain: wp-pocketurl
*/

defined('ABSPATH') or die();
define('wp_pocketurl_url', plugins_url('',__FILE__) );
define('wp_pocketurl_path', dirname(__FILE__) . '/' );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wp_pocketurl_add_action_links');
// Add links in plugins list
function wp_pocketurl_add_action_links($links) {
    $plugin_shortcuts = array(
        '<a href="'.admin_url('edit.php?post_type=wp_pocketurl_link&page=wp_pocketurl_link_settings').'">' . esc_html__('Settings', 'wp-pocketurl') . '</a>',
        '<a href="https://www.buymeacoffee.com/coderevolution" target="_blank" style="color:#3db634;">' . esc_html__('Buy developer a coffee', 'wp-pocketurl') . '</a>'
    );
    return array_merge($links, $plugin_shortcuts);
}

function wp_pocketurl_load_textdomain() {
    load_plugin_textdomain( 'wp-pocketurl', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'wp_pocketurl_load_textdomain' );

require_once( wp_pocketurl_path .'classes/class-wp-pocketurl.php');
require_once( wp_pocketurl_path .'classes/class-wp-pocketurl-clicks.php');
require_once( wp_pocketurl_path .'classes/class-wp-pocketurl-admin.php');
require_once( wp_pocketurl_path .'classes/class-wp-pocketurl-reports.php');
function wp_PocketURLs_Start(){
	$wp_pocketurl = new WP_PocketURLs();
	$wp_pocketurl_clicks = new WP_PocketURLs_Clicks();
	$wp_pocketurl_admin = new WP_PocketURLs_Admin();
	register_activation_hook(__FILE__,array(&$wp_pocketurl_clicks,'wp_pocketurl_create_clicks_table'));
}
wp_PocketURLs_Start();


add_action('wp_poketurlnew_post_cron', 'wp_poketurldo_post', 1, 1);
add_action('transition_post_status', 'wp_poketurlnew_post', 1, 3);
function wp_poketurlnew_post($new_status, $old_status, $post)
{
    if ('publish' !== $new_status or 'publish' === $old_status)
    {
        return;
    }
    else
    {
        if($old_status == 'auto-draft' && $new_status == 'publish' && !has_post_thumbnail($post->ID) && ((function_exists('has_blocks') && has_blocks($post)) || ($post->post_content == '' && function_exists('has_blocks') && !class_exists('Classic_Editor'))))
        {
            $delay_it_is_gutenberg = true;
        }
        else
        {
            $delay_it_is_gutenberg = false;
        }
    }
    if($delay_it_is_gutenberg)
    {
        if(wp_next_scheduled('wp_poketurlnew_post_cron', array($post)) === false)
        {
            wp_schedule_single_event( time() + 2, 'wp_poketurlnew_post_cron', array($post) );
        }
    }
    else
    {
        wp_poketurldo_post($post);
    }
}
function wp_poketurl_giveHost($host_with_subdomain) {
    $array = explode(".", $host_with_subdomain);
    return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "") . "." . $array[count($array) - 1];
}
function wp_poketurl_isExternal($href, $base)
{
    if(empty($href) || empty($base))
    {
        return 1;
    }
    $components = parse_url($href); 
    $comp_base = parse_url($base);
    if(!isset($components['host']) || !isset($comp_base['host']))
    {
        if(stristr($href, $base) !== false)
        {
            return 0;
        }
        return 1;
    }
    return strcasecmp(wp_poketurl_giveHost($components['host']), wp_poketurl_giveHost($comp_base['host']));
}

class WP_poketurl_keywords{ 
    public static $charset = 'UTF-8';
    public static $banned_words = array('adsbygoogle', 'able', 'about', 'above', 'act', 'add', 'afraid', 'after', 'again', 'against', 'age', 'ago', 'agree', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'amount', 'an', 'and', 'anger', 'angry', 'animal', 'another', 'answer', 'any', 'appear', 'apple', 'are', 'arrive', 'arm', 'arms', 'around', 'arrive', 'as', 'ask', 'at', 'attempt', 'aunt', 'away', 'back', 'bad', 'bag', 'bay', 'be', 'became', 'because', 'become', 'been', 'before', 'began', 'begin', 'behind', 'being', 'bell', 'belong', 'below', 'beside', 'best', 'better', 'between', 'beyond', 'big', 'body', 'bone', 'born', 'borrow', 'both', 'bottom', 'box', 'boy', 'break', 'bring', 'brought', 'bug', 'built', 'busy', 'but', 'buy', 'by', 'call', 'came', 'can', 'cause', 'choose', 'close', 'close', 'consider', 'come', 'consider', 'considerable', 'contain', 'continue', 'could', 'cry', 'cut', 'dare', 'dark', 'deal', 'dear', 'decide', 'deep', 'did', 'die', 'do', 'does', 'dog', 'done', 'doubt', 'down', 'during', 'each', 'ear', 'early', 'eat', 'effort', 'either', 'else', 'end', 'enjoy', 'enough', 'enter', 'even', 'ever', 'every', 'except', 'expect', 'explain', 'fail', 'fall', 'far', 'fat', 'favor', 'fear', 'feel', 'feet', 'fell', 'felt', 'few', 'fill', 'find', 'fit', 'fly', 'follow', 'for', 'forever', 'forget', 'from', 'front', 'gave', 'get', 'gives', 'goes', 'gone', 'good', 'got', 'gray', 'great', 'green', 'grew', 'grow', 'guess', 'had', 'half', 'hang', 'happen', 'has', 'hat', 'have', 'he', 'hear', 'heard', 'held', 'hello', 'help', 'her', 'here', 'hers', 'high', 'hill', 'him', 'his', 'hit', 'hold', 'hot', 'how', 'however', 'I', 'if', 'ill', 'in', 'indeed', 'instead', 'into', 'iron', 'is', 'it', 'its', 'just', 'keep', 'kept', 'knew', 'know', 'known', 'late', 'least', 'led', 'left', 'lend', 'less', 'let', 'like', 'likely', 'likr', 'lone', 'long', 'look', 'lot', 'make', 'many', 'may', 'me', 'mean', 'met', 'might', 'mile', 'mine', 'moon', 'more', 'most', 'move', 'much', 'must', 'my', 'near', 'nearly', 'necessary', 'neither', 'never', 'next', 'no', 'none', 'nor', 'not', 'note', 'nothing', 'now', 'number', 'of', 'off', 'often', 'oh', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'out', 'please', 'prepare', 'probable', 'pull', 'pure', 'push', 'put', 'raise', 'ran', 'rather', 'reach', 'realize', 'reply', 'require', 'rest', 'run', 'said', 'same', 'sat', 'saw', 'say', 'see', 'seem', 'seen', 'self', 'sell', 'sent', 'separate', 'set', 'shall', 'she', 'should', 'side', 'sign', 'since', 'so', 'sold', 'some', 'soon', 'sorry', 'stay', 'step', 'stick', 'still', 'stood', 'such', 'sudden', 'suppose', 'take', 'taken', 'talk', 'tall', 'tell', 'ten', 'than', 'thank', 'that', 'the', 'their', 'them', 'then', 'there', 'therefore', 'these', 'they', 'this', 'those', 'though', 'through', 'till', 'to', 'today', 'told', 'tomorrow', 'too', 'took', 'tore', 'tought', 'toward', 'tried', 'tries', 'trust', 'try', 'turn', 'two', 'under', 'until', 'up', 'upon', 'us', 'use', 'usual', 'various', 'verb', 'very', 'visit', 'want', 'was', 'we', 'well', 'went', 'were', 'what', 'when', 'where', 'whether', 'which', 'while', 'white', 'who', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without', 'would', 'yes', 'yet', 'you', 'young', 'your', 'br', 'img', 'p','lt', 'gt', 'quot', 'copy');
    public static $min_word_length = 4;
    
    public static function text($text, $length = 160)
    {
        return self::limit_chars(self::clean($text), $length,'',TRUE);
    } 

    public static function keywords($text, $max_keys = 3)
    {
        include (dirname(__FILE__) . "/res/diacritics.php");
        $wordcount = array_count_values(str_word_count(self::clean($text), 1, $diacritics));
        foreach ($wordcount as $key => $value) 
        {
            if ( (strlen($key)<= self::$min_word_length) OR in_array($key, self::$banned_words))
                unset($wordcount[$key]);
        }
        uasort($wordcount,array('self','cmp'));
        $wordcount = array_slice($wordcount,0, $max_keys);
        return implode(' ', array_keys($wordcount));
    } 

    private static function clean($text)
    { 
        $text = html_entity_decode($text,ENT_QUOTES,self::$charset);
        $text = strip_tags($text);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = str_replace (array('\r\n', '\n', '+'), ',', $text);
        return trim($text); 
    } 

    private static function cmp($a, $b) 
    {
        if ($a == $b) return 0; 

        return ($a < $b) ? 1 : -1; 
    } 

    private static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    {
        $end_char = ($end_char === NULL) ? '&#8230;' : $end_char;
        $limit = (int) $limit;
        if (trim($str) === '' OR strlen($str) <= $limit)
            return $str;
        if ($limit <= 0)
            return $end_char;
        if ($preserve_words === FALSE)
            return rtrim(substr($str, 0, $limit)).$end_char;
        if ( ! preg_match('/^.{0,'.$limit.'}\s/us', $str, $matches))
            return $end_char;
        return rtrim($matches[0]).((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    }
} 
function wp_poketurldo_post($post)
{
    $enabled = get_option( 'wp_pocketurl_link_enable_auto','no' );
    if($enabled == 'yes')
    {
        $timeout = 3600;
        ini_set('safe_mode', 'Off');
        ini_set('max_execution_time', $timeout);
        ini_set('ignore_user_abort', 1);
        if(function_exists('ignore_user_abort'))
{
    ignore_user_abort(true);
}
        if(function_exists('set_time_limit'))
{
    set_time_limit($timeout);
}
        
        $content = $post->post_content;
        $dom = new DOMDocument('1.0');
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        libxml_use_internal_errors($internalErrors);
        $anchors = $dom->getElementsByTagName('a');
        $url = get_site_url();
        $need_update = false;
        foreach ($anchors as $element) 
        {
            $href = $element->getAttribute('href');
            if(wp_poketurl_isExternal($href, $url) != 0)
            {
                $my_post = array();
                $exclude = get_option( 'wp_pocketurl_link_exclude_word','' );
                if($exclude != '')
                {
                    $exclude = explode(',', $exclude);
                    foreach($exclude as $ex)
                    {
                        if(strstr($href, trim($ex)) !== false)
                        {
                            continue;
                        }
                    }
                }
                $req = get_option( 'wp_pocketurl_link_require_word','' );
                if($req != '')
                {
                    $req = explode(',', $req);
                    $found = false;
                    foreach($req as $ex)
                    {
                        if(strstr($href, trim($ex)) !== false)
                        {
                            $found = true;
                        }
                    }
                    if($found = false)
                    {
                        continue;
                    }
                }
                $url_str = preg_replace('#^(?:https?:\/\/)?(?:www\.)?#i', '', $href);
                $url_str = str_replace(array('/', '-', '_', ':'), ' ', $url_str);
                $keyword_class = new WP_poketurl_keywords();
                $query_words = $keyword_class->keywords($url_str, 1);
                $feed_id = sanitize_title($query_words);
                if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                {
                    $query_words = $keyword_class->keywords($url_str, 2);
                    $feed_id = sanitize_title($query_words);
                    if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                    {
                        $query_words = $keyword_class->keywords($url_str, 3);
                        $feed_id = sanitize_title($query_words);
                        if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                        {
                            $query_words = $keyword_class->keywords($url_str, 4);
                            $feed_id = sanitize_title($query_words);
                            if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                            {
                                $query_words = $keyword_class->keywords($url_str, 5);
                                $feed_id = sanitize_title($query_words);
                                if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                                {
                                    $query_words = $keyword_class->keywords($url_str, 6);
                                    $feed_id = sanitize_title($query_words);
                                    if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                                    {
                                        $query_words = $keyword_class->keywords($url_str, 7);
                                        $feed_id = sanitize_title($query_words);
                                        if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                                        {
                                            $query_words = $keyword_class->keywords($url_str, 8);
                                            $feed_id = sanitize_title($query_words);
                                            if (wp_pocketurl_get_page_by_title(html_entity_decode($feed_id), OBJECT, 'wp_pocketurl_link') !== NULL)
                                            {
                                                $feed_id .= '-' . uniqid();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $my_post['post_title'] = $feed_id;
                $my_post['post_type'] = 'wp_pocketurl_link';
                $my_post['post_status'] = 'publish';
                $post_id = wp_insert_post($my_post, true);
                if (!is_wp_error($post_id)) {
                    if($post_id === 0)
                    {
                        wp_pocketurl_log_to_file('Error occurred while inserting new redirect rule!');
                    }
                    else
                    {
                        $cusOption = '0';
                        $linkRedirection = get_option('wp_pocketurl_link_redirection', '301');
                        update_post_meta($post_id,'wp_pocketurl_link', $href);
                        update_post_meta($post_id,'wp_pocketurl_link_custom_options', $cusOption);
                        update_post_meta($post_id,'wp_pocketurl_link_redirection', $linkRedirection);
                        $new_link = get_permalink($post_id);
                        $content = str_replace($href, $new_link, $content);
                        $need_update = true;
                    }
                }
                else
                {
                    $errors = $post_id->get_error_messages();
                    foreach ($errors as $error) {
                        wp_pocketurl_log_to_file('Error occurred while inserting new redirect rule: "' . print_r($my_post, true) . '": ' . $error);
                    }
                }
            }
        }
        
        if($need_update === true)
        {
            $args = array();
            $args['ID'] = $post->ID;
            $args['post_content'] = $content;
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
            remove_filter('title_save_pre', 'wp_filter_kses');
            $post_updated = wp_update_post($args);
            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
            add_filter('title_save_pre', 'wp_filter_kses');
            if (is_wp_error($post_updated)) {
                $errors = $post_updated->get_error_messages();
                foreach ($errors as $error) {
                    wp_pocketurl_log_to_file('Error occurred while updating post "' . $post->post_title . '": ' . $error);
                }
            }
        }
    }
}
function wp_pocketurl_get_page_by_title($title, $ret_type, $post_type)
{
    $xposts = get_posts(
        array(
            'post_type'              => $post_type,
            'title'                  => $title,
            'post_status'            => 'all',
            'numberposts'            => 1,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,           
            'orderby'                => 'post_date ID',
            'order'                  => 'ASC',
        )
    );
    if ( ! empty( $xposts ) ) {
        $zap = $xposts[0];
    } else {
        $zap = null;
    }
    return $zap;
}
function wp_pocketurl_log_to_file($str)
{
    $d = date("j-M-Y H:i:s e", current_time( 'timestamp' ));
    error_log("[$d] " . $str . "<br/>\r\n", 3, WP_CONTENT_DIR . '/wp_poketurl_info.log');
}