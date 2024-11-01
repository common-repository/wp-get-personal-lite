<?php

/*
Plugin Name: WP Get Personal
Plugin URI: https://www.wpgetpersonal.com
Description: Allows you to personalize your pages simply by adding the name to the end of the URL.
Author: Steven Henty
Version: 1.5.2
Author URI: http://www.stevenhenty.com
------------------------------------------------------------------------
Copyright 2012-2019 Steven Henty
*/

if ( function_exists( 'wpgetpersonal_fs' ) ) {
    wpgetpersonal_fs()->set_basename( false, __FILE__ );
    return;
}


if ( !function_exists( 'wpgetpersonal_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpgetpersonal_fs()
    {
        global  $wpgetpersonal_fs ;
        
        if ( !isset( $wpgetpersonal_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $wpgetpersonal_fs = fs_dynamic_init( array(
                'id'             => '1243',
                'slug'           => 'wp-get-personal-lite',
                'type'           => 'plugin',
                'public_key'     => 'pk_0ee7bb35b6ca75e6698bc65845e42',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'           => 'wpgetpersonal',
                'override_exact' => true,
                'support'        => false,
                'parent'         => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $wpgetpersonal_fs;
    }
    
    // Init Freemius.
    wpgetpersonal_fs();
    // Signal that SDK was initiated.
    do_action( 'wpgetpersonal_fs_loaded' );
    function wpgetpersonal_fs_settings_url()
    {
        return admin_url( 'options-general.php?page=wpgetpersonal' );
    }
    
    wpgetpersonal_fs()->add_filter( 'connect_url', 'wpgetpersonal_fs_settings_url' );
    wpgetpersonal_fs()->add_filter( 'after_skip_url', 'wpgetpersonal_fs_settings_url' );
    wpgetpersonal_fs()->add_filter( 'after_connect_url', 'wpgetpersonal_fs_settings_url' );
    wpgetpersonal_fs()->add_filter( 'after_pending_connect_url', 'wpgetpersonal_fs_settings_url' );
    wpgetpersonal_fs()->add_action( 'after_uninstall', 'wpgetpersonal_fs_uninstall_cleanup' );
}

define( 'WPGETPPERSONAL_DEBUG', false );
define( 'WPGETPPERSONAL_VIRTUAL_URL_FOR_LOGGED_IN_USERS', true );
add_action( 'wp', 'wpgetpersonal_init_method' );
add_action( 'add_meta_boxes', 'wpgetpersonal_add_custom_box' );
add_action( 'save_post', 'wpgetpersonal_save_postdata' );
//register default settings
register_activation_hook( __FILE__, 'wppg_add_defaults' );
function wppg_add_defaults()
{
    $tmp = get_option( 'wpgp_validation_options' );
    
    if ( !is_array( $tmp ) ) {
        $arr = array(
            "activation_email"  => "",
            "plugin_slug"       => "wpgetpersonal",
            "activation_status" => "never",
        );
        update_option( 'wpgp_validation_options', $arr );
    }
    
    $tmp = get_option( 'wpgp_advanced_options' );
    
    if ( !is_array( $tmp ) ) {
        $arr = array(
            "wpgp_cookie_life"       => "30",
            "wpgp_section_separator" => "-",
        );
        update_option( 'wpgp_advanced_options', $arr );
    }

}

register_deactivation_hook( __FILE__, 'wppg_deactivation' );
function wppg_deactivation()
{
    global  $wpdb ;
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'WPGP' OR meta_key='wpgp_admin_notice_register'" );
    delete_option( "wpgp_plugin_options" );
    delete_option( "wpgp_validation_options" );
    delete_option( "wpgp_advanced_options" );
}

function wpgp_get_version()
{
    $plugin_data = get_plugin_data( __FILE__ );
    $plugin_version = $plugin_data['Version'];
    return $plugin_version;
}

if ( is_admin() ) {
    include_once 'settings.php';
}
function wpgetpersonal_init_method()
{
    if ( defined( "DOING_AJAX" ) && DOING_AJAX ) {
        return;
    }
    global  $wp_query ;
    if ( !$wp_query->is_404 ) {
        return;
    }
    $request_url = $_SERVER['REQUEST_URI'];
    $is_ssl = is_ssl();
    $protocol = ( $is_ssl ? 'https://' : 'http://' );
    $complete_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $wpurl = get_bloginfo( 'url' ) . "/";
    // if we're in the admin area exit
    if ( is_admin() ) {
        return;
    }
    $params_strpos = strpos( $request_url, "?" );
    $params = '';
    
    if ( $params_strpos !== false ) {
        $params = substr( $request_url, $params_strpos );
        $request_url = substr( $request_url, 0, $params_strpos );
    }
    
    $wpgp_strpos = strpos( $params, "wpgp=" );
    if ( $wpgp_strpos !== false ) {
        return;
    }
    $url_array = explode( "/", $request_url );
    $totalarr = count( $url_array );
    if ( empty($url_array[$totalarr - 1]) ) {
        $totalarr -= 1;
    }
    $contactinfo = $url_array[$totalarr - 1];
    $pagename = ( isset( $url_array[$totalarr - 2] ) ? $url_array[$totalarr - 2] : '' );
    if ( $contactinfo == "" ) {
        return;
    }
    // test to see if it's the homepage we need to deliver
    $contactinfo_strpos = strpos( $complete_url, $contactinfo );
    $first_part = substr( $complete_url, 0, $contactinfo_strpos );
    
    if ( $first_part == $wpurl ) {
        //looks like we have to take the user to the homepage
        //if it's a page - check the wpgetpersonal setting
        $wp_page_on_front = get_option( 'page_on_front' );
        
        if ( $wp_page_on_front != 0 ) {
            if ( !WPGETPPERSONAL_ACTIVATE_FOR_PAGE_AS_HOMEPAGE ) {
                return;
            }
            $meta_value = get_post_meta( $wp_page_on_front, 'wpgetpersonal', true );
            if ( empty($meta_value) ) {
                return;
            }
        } else {
            if ( !WPGETPPERSONAL_ACTIVATE_FOR_BLOG_AS_HOMEPAGE ) {
                return;
            }
        }
        
        $pagename = '';
        $base_url = $wpurl;
    } else {
        $pagename_strpos = strpos( $complete_url, $pagename );
        $base_url = substr( $complete_url, 0, $pagename_strpos );
        global  $wpdb ;
        $page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE (post_type = 'page' OR post_type = 'post') AND post_name = %s AND post_status = 'publish'", $pagename ) );
        $meta_value = get_post_meta( $page_id, 'wpgetpersonal', true );
        $pagename = $pagename . '/';
        if ( empty($page_id) ) {
            return;
        }
        if ( empty($meta_value) ) {
            return;
        }
    }
    
    if ( is_user_logged_in() ) {
        
        if ( !WPGETPPERSONAL_VIRTUAL_URL_FOR_LOGGED_IN_USERS ) {
            $wpgp_redirect_url = $base_url . "{$pagename}" . $params;
            wp_redirect( $wpgp_redirect_url );
            exit;
        }
    
    }
    // it looks lke a wpgetpersonal page
    status_header( 200 );
    $wp_query->is_404 = false;
    
    if ( $params == "" ) {
        $params = "?";
    } else {
        $params = $params . "&";
    }
    
    $blog_URL = $base_url . $pagename . $params . "wpgp=" . $contactinfo;
    $options = get_option( 'wpgp_advanced_options' );
    $wpgp_cookie_life = 30;
    if ( is_array( $options ) ) {
        $wpgp_cookie_life = $options['wpgp_cookie_life'];
    }
    if ( !isset( $_COOKIE['WPGP'] ) || isset( $_COOKIE['WPGP'] ) && $_COOKIE['WPGP'] != $contactinfo ) {
        setcookie(
            'WPGP',
            $contactinfo,
            time() + 60 * 60 * 24 * $wpgp_cookie_life,
            COOKIEPATH,
            COOKIE_DOMAIN,
            $is_ssl,
            true
        );
    }
    
    if ( defined( 'WPGETPPERSONAL_ACTIVATE_REMEMBER' ) && WPGETPPERSONAL_ACTIVATE_REMEMBER && !empty($_COOKIE) ) {
        $cookies = array_implode( "=", ";", $_COOKIE );
        // this fixes issues with eShop and other plugins that use sessions
        $a = session_id();
        
        if ( !empty($a) ) {
            ob_start();
            session_write_close();
            flush();
        }
        
        $headers = array(
            'cookie' => $cookies,
        );
        add_filter( 'block_local_requests', '__return_false' );
        add_filter( 'https_ssl_verify', '__return_false' );
        $response = wp_safe_remote_get( $blog_URL, array(
            'headers' => $headers,
        ) );
    } else {
        add_filter( 'block_local_requests', '__return_false' );
        add_filter( 'https_ssl_verify', '__return_false' );
        $response = wp_safe_remote_get( $blog_URL );
    }
    
    if ( !is_wp_error( $response ) ) {
        echo  $response['body'] ;
    }
    die;
}

function array_implode( $glue, $separator, $array )
{
    if ( !is_array( $array ) ) {
        return $array;
    }
    $string = array();
    foreach ( $array as $key => $val ) {
        if ( is_array( $val ) ) {
            $val = implode( ',', $val );
        }
        $string[] = "{$key}{$glue}{$val}";
    }
    return implode( $separator, $string );
}

function wpgetpersonal_add_custom_box()
{
    add_meta_box(
        'wpgetpersonal_sectionid',
        __( 'WP Get Personal!', 'wpgetpersonal_textdomain' ),
        'wpgetpersonal_inner_custom_box',
        'page',
        'side'
    );
    add_meta_box(
        'wpgetpersonal_sectionid',
        __( 'WP Get Personal!', 'wpgetpersonal_textdomain' ),
        'wpgetpersonal_inner_custom_box',
        'post',
        'side'
    );
}

function wpgetpersonal_inner_custom_box( $post )
{
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'wpgetpersonal_noncename' );
    $checked = "";
    
    if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
        $postid = $_GET['post'];
        $meta_value = array();
        $meta_value = get_post_meta( $postid, 'wpgetpersonal', true );
        
        if ( !empty($meta_value) == 1 ) {
            if ( $meta_value['wpgetpersonal_activated'] == '1' ) {
                $checked = "checked='yes'";
            }
        } else {
            $checked = "";
        }
    
    }
    
    // The actual fields for data entry
    echo  '<label for="wpgetpersonal_activated">' . __( "Activate virtual urls", 'wpgetpersonal_textdomain' ) . '</label> ' ;
    echo  '<input type="checkbox" id= "wpgetpersonal_activated" name="wpgetpersonal_activated" value="1"  ' . $checked . '/>' ;
    echo  '<br /><br />Shortcode: [wpgetpersonal] &nbsp;<a taret="_blank" href="http://wpgetpersonal.com/instructions/">Full instructions</a>' ;
}

function wpgetpersonal_save_postdata( $post_id )
{
    // verify if this is an auto save routine.
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( false === isset( $_POST['wpgetpersonal_noncename'] ) ) {
        return;
    }
    if ( !wp_verify_nonce( $_POST['wpgetpersonal_noncename'], plugin_basename( __FILE__ ) ) ) {
        return;
    }
    // Check permissions
    
    if ( 'page' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    } else {
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }
    
    if ( !isset( $_POST['wpgetpersonal_activated'] ) or $_POST['wpgetpersonal_activated'] == "" ) {
        $_POST['wpgetpersonal_activated'] = 0;
    }
    $data = array();
    $data['post_id'] = $post_id;
    $data['wpgetpersonal'] = $_POST['wpgetpersonal_activated'];
    $meta_value = get_post_meta( $post_id, 'wpgetpersonal', true );
    
    if ( empty($meta_value) && ($data['wpgetpersonal'] = "1") ) {
        $options = array();
        $options['wpgetpersonal_activated'] = '1';
        add_post_meta(
            $post_id,
            'wpgetpersonal',
            $options,
            true
        );
    } elseif ( '' == $data['wpgetpersonal'] && $meta_value ) {
        delete_post_meta( $post_id, 'wpgetpersonal' );
    }

}

function wpgetpersonal_shortcode( $atts, $content = null )
{
    extract( shortcode_atts( array(
        'fallback'         => 'Friend',
        'section'          => '0',
        'format'           => '',
        'lookup_url'       => '',
        'lookup_separator' => ',',
    ), $atts ) );
    
    if ( isset( $_REQUEST['wpgp'] ) and $_REQUEST['wpgp'] != '' ) {
        $shortcode_val = $_REQUEST['wpgp'];
        $shortcode_val = wpgp_getparam( $shortcode_val, $section );
    } elseif ( isset( $_COOKIE['WPGP'] ) ) {
        $shortcode_val = urldecode( $_COOKIE['WPGP'] );
        //if logged in then set metadata
        $current_user = wp_get_current_user();
        
        if ( 0 == $current_user->ID ) {
            // Not logged in.
        } else {
            // Logged in.
            $wpgp_user_meta_data = get_user_meta( $current_user->ID, 'WPGP', true );
            
            if ( !empty($wpgp_user_meta_data) ) {
                update_user_meta( $current_user->ID, 'WPGP', $shortcode_val );
            } else {
                add_user_meta(
                    $current_user->ID,
                    'WPGP',
                    $shortcode_val,
                    true
                );
            }
        
        }
        
        $shortcode_val = wpgp_getparam( $shortcode_val, $section );
    } elseif ( is_user_logged_in() ) {
        // no request param, no cookie try user meta data
        $current_user = wp_get_current_user();
        $shortcode_val = get_user_meta( $current_user->ID, "WPGP", true );
        
        if ( empty($shortcode_val) ) {
            $shortcode_val = $fallback;
        } else {
            $shortcode_val = wpgp_getparam( $shortcode_val, $section );
        }
    
    } else {
        $shortcode_val = $fallback;
        //finish here
        return $shortcode_val;
    }
    
    
    if ( defined( 'WPGETPPERSONAL_ACTIVATE_CSV_LOOKUP' ) && WPGETPPERSONAL_ACTIVATE_CSV_LOOKUP && !empty($lookup_url) ) {
        if ( !wpgp_http_file_exists( $lookup_url ) ) {
            return "Lookup file not found. Please check the URL";
        }
        $row_data = wpgp_lookup_row( $lookup_url, $lookup_separator, $shortcode_val );
        
        if ( !empty($row_data) ) {
            for ( $i = 1 ;  $i <= count( $row_data ) ;  $i++ ) {
                $column_val = trim( $row_data[$i - 1] );
                $token = "{{$i}}";
                $content = str_replace( $token, $column_val, $content );
                $shortcode_val = $content;
            }
        } else {
            $shortcode_val = $fallback;
        }
    
    } elseif ( !empty($content) ) {
        $num_params = wpgp_count_params( $shortcode_val );
        for ( $i = 1 ;  $i <= $num_params ;  $i++ ) {
            $token = "{{$i}}";
            $section_val = wpgp_getparam( $shortcode_val, $i );
            $content = str_replace( $token, $section_val, $content );
        }
        $shortcode_val = do_shortcode( $content );
    }
    
    return $shortcode_val;
}

function wpgp_lookup_row( $lookup_url, $lookup_separator, $lookup_value )
{
    $lookup_value = strtolower( $lookup_value );
    ini_set( "auto_detect_line_endings", true );
    if ( ($handle = fopen( $lookup_url, "r" )) !== false ) {
        while ( ($data = fgetcsv( $handle, 0, $lookup_separator )) !== false ) {
            if ( $lookup_value == strtolower( trim( $data[0] ) ) ) {
                return $data;
            }
        }
    }
    fclose( $handle );
    return;
}

function wpgp_getparam( $shortcode_val, $section )
{
    
    if ( $section > 0 ) {
        $section -= 1;
        $options = get_option( 'wpgp_advanced_options' );
        $wpgp_section_separator = "-";
        if ( is_array( $options ) ) {
            $wpgp_section_separator = $options['wpgp_section_separator'];
        }
        $shortcode_vals = explode( $wpgp_section_separator, $shortcode_val );
        $shortcode_val = $shortcode_vals[$section];
    }
    
    return $shortcode_val;
}

function wpgp_count_params( $shortcode_val )
{
    $options = get_option( 'wpgp_advanced_options' );
    $wpgp_section_separator = "-";
    if ( is_array( $options ) ) {
        $wpgp_section_separator = $options['wpgp_section_separator'];
    }
    $shortcode_vals = explode( $wpgp_section_separator, $shortcode_val );
    return count( $shortcode_vals );
}

add_shortcode( 'wpgetpersonal', 'wpgetpersonal_shortcode' );
add_filter( 'widget_text', 'do_shortcode' );
add_filter( 'wp_title', 'wpgetpersonal_set_page_title' );
add_filter( 'the_title', 'wpgetpersonal_set_page_title' );
function wpgetpersonal_set_page_title( $orig_title )
{
    // Save registered shortcodes:
    global  $shortcode_tags ;
    $original = $shortcode_tags;
    // Unregister all shortcodes:
    remove_all_shortcodes();
    // Register wpgetpersonal shortcode:
    add_shortcode( 'wpgetpersonal', 'wpgetpersonal_shortcode' );
    // Process title content with the shortcode:
    $title = do_shortcode( $orig_title );
    // Unregister comment shortcodes, restore normal shortcodes
    $shortcode_tags = $original;
    return $title;
}

function wpgp_http_file_exists( $url )
{
    $f = @fopen( $url, "r" );
    
    if ( $f ) {
        fclose( $f );
        return true;
    }
    
    return false;
}

function wpgetpersonal_fs_uninstall_cleanup()
{
    if ( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
    }
    $allposts = get_posts( 'numberposts=-1&post_type=post&post_status=any' );
    foreach ( $allposts as $postinfo ) {
        delete_post_meta( $postinfo->ID, 'wpgetpersonal' );
    }
    $allpages = get_pages( 'post_type=page&meta_key=wpgetpersonal' );
    foreach ( $allpages as $pageinfo ) {
        delete_post_meta( $pageinfo->ID, 'wpgetpersonal' );
    }
    global  $wpdb ;
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'WPGP' OR meta_key='wpgp_admin_notice_register'" ) );
    delete_option( "wpgp_plugin_options" );
    delete_option( "wpgp_advanced_options" );
    delete_option( "wpgp_validation_options" );
}
