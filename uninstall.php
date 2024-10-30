<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}
global $wpdb;
define('WP_GVCCF_CF_DETAILS_TABLE', $wpdb->prefix . 'wp_gvccf_contact_form_details'); // define menu slug name
define('WP_GVCCF_CF_TABLE_PREFIX', $wpdb->prefix . 'wp_gvccf_contact_form_'); // define menu slug name

/**
 * 
 * @global type $wpdb
 */
function wp_gvccf_delete_plugin() {
    global $wpdb;
    if ($wpdb->get_var("SHOW TABLES LIKE '" . WP_GVCCF_CF_DETAILS_TABLE . "'") == WP_GVCCF_CF_DETAILS_TABLE) {
        $result = $wpdb->get_results("select  cf7_table_prefix from " . WP_GVCCF_CF_DETAILS_TABLE . " ");

        foreach ($result as $value) {
            $wpdb->query("DROP TABLE IF EXISTS " . $value->cf7_table_prefix);
        }
        $wpdb->query("DROP TABLE IF EXISTS " . WP_GVCCF_CF_DETAILS_TABLE . " ");
    }
}

wp_gvccf_delete_plugin();
?>