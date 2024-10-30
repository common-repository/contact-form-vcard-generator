<?php
/*
  Plugin Name: Contact Form vCard Generator
  Plugin URI: https://wordpress.org/plugins/contact-form-vcard-generator/
  Description: WordPress plugin to generate vCard from Contact Form 7 inquiries. 
  Author: Ashish Ajani
  Version: 2.2
  Author URI: http://freelancer-coder.com/
  License: GPLv2 or later
 */

// Security: Considered blocking direct access to PHP files by adding the following line.
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
define('WP_GVCCF_DIR', plugin_dir_path(__FILE__));
define('WP_GVCCF_SLUG', 'contact-form-vcard-generator');
define('WP_GVCCF_URL', WP_PLUGIN_URL . '/' . WP_GVCCF_SLUG . '/');
define('WP_GVCCF_SAVE_CF7_ADMIN_MENU', 'wp_gvccf_generate_vcard_settings');
define('WP_GVCCF_CF_DETAILS_TABLE', $wpdb->prefix . 'gvccf_contact_form_details');
define('WP_GVCCF_CF_TABLE_PREFIX', $wpdb->prefix . 'gvccf_contact_form_1');
define('WP_GVCCF_REQUIRED_CF_VERSION', '5.0.2');
define('WP_GVCCF_RECORD_PER_PAGE', 10);

if (!function_exists('get_plugins') || !function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$wp_gvccf_plugin_data = get_plugins();
require_once 'includes/wp-gvc-cf-functions.php';
require_once 'includes/wp-gvc-cf-links.php';
$wp_gvccf_plugin_links = new WP_gvccf_plugin_links();
//Plugin Activation
register_activation_hook(__FILE__, 'wp_gvccf_check_required');
add_action('plugins_loaded', 'wp_gvccf_check_required');

//Plugin deactivation hook
register_deactivation_hook(__FILE__, 'wp_gvccf_deactivation');

if (wp_gvccf_cf_is_contact_form_available()) {
    include 'includes/wp-gvc-cf-vcard.class.php';
    $wp_gvccf_vcard_object = new WP_gvc_vcard();
    include 'includes/wp-gvc-cf-action.class.php';
    $wp_gvccf_cf_object = new WP_gvc_cf_action();
} else {
    
}
require_once 'includes/wp-gvc-cf-settings.php';

//Add Media
function wp_gvccf_enqueue_media() {
    wp_enqueue_script('wp-gvc-cf-settings', WP_GVCCF_URL . 'assets/js/wp-gvc-cf.js');
    wp_enqueue_style('wp-gvc-cf-settings', WP_GVCCF_URL . 'assets/css/wp-gvc-cf.css');
}

add_action('admin_init', 'wp_gvccf_enqueue_media');

//Add Menu Option
function wp_gvccf_menu() {
    $page_title = 'Contact Form vCard Generator';
    $menu_title = 'Contact Form vCard Generator';
    $capability = 'manage_options';
    $menu_slug = WP_GVCCF_SAVE_CF7_ADMIN_MENU;
    $function = WP_GVCCF_SAVE_CF7_ADMIN_MENU;
    $icon_url = WP_GVCCF_URL . 'assets/images/vcard-icon.png';
    $position = 99;
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
}

add_action('admin_menu', 'wp_gvccf_menu');
