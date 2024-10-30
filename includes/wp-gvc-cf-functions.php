<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check for the contact form 7 availability
 */
if (!function_exists('wp_gvccf_cf7_notice')) {

    function wp_gvccf_cf7_notice() {
        global $pagenow;
        if ($pagenow == 'plugins.php' || ($pagenow == 'admin.php' && $_REQUEST['page'] == WP_GVCCF_SAVE_CF7_ADMIN_MENU)) {
            $url = network_admin_url('plugin-install.php?tab=search&type=term&s=Contact+Form+7&plugin-search-input=Search+Plugins');
            echo '<div class="wrap"><div class="error">
        <p>To use Contact Form vCard Generator plugin <a href="' . $url . '">Contact Form 7</a> plugin is required.</p>
    </div></div>';
        }
    }

}

/**
 * Check for the used contact form plugin is the latest version or not.
 */
if (!function_exists('wp_gvccf_cf7_notice_version')) {

    function wp_gvccf_cf7_notice_version() {
        global $pagenow;
        if ($pagenow == 'plugins.php' || ($pagenow == 'admin.php' && $_REQUEST['page'] == WP_GVCCF_SAVE_CF7_ADMIN_MENU)) {
            echo '<div class="wrap"><div class="error"><p>Please update the version of the Contact Form 7 plugin to the latest version to use WP generate vcard plugin.</p></div></div>';
        }
    }

}

/**
 * 
 * @global type $wpdb
 */
if (!function_exists('wp_gvccf_check_required')) {

    function wp_gvccf_check_required() {
        global $wpdb;
        $error = false;
        if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            if (!wp_gvccf_check_cf7_version()) {
                add_action('admin_notices', 'wp_gvccf_cf7_notice_version');
                $error = true;
            }
        } else {
            add_action('admin_notices', 'wp_gvccf_cf7_notice');
            $error = true;
        }
        if (!$error) {
            $sql_create = $wpdb->prepare("CREATE TABLE IF NOT EXISTS  " . WP_GVCCF_CF_DETAILS_TABLE . " (id int(8) NOT NULL PRIMARY KEY AUTO_INCREMENT,cf7_table_prefix VARCHAR (255) NULL, cf7_title VARCHAR(100) NOT NULL, cf7_version varchar(10) NOT NULL, cf7_form_id VARCHAR(100) NOT NULL, cf7_source VARCHAR(100) NOT NULL , cf7_status ENUM('YES','NO') DEFAULT 'NO' NOT NULL,cf7_vcard_settings text NOT NULL,cf7_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ");
            $wpdb->query($sql_create);
        }
    }

}

/**
 * Deactivation of the plugin
 */
if (!function_exists('wp_gvccf_deactivation')) {

    function wp_gvccf_deactivation() {
        /* Remove settings */
        delete_option('wp_gvccf_delete_msg');
        delete_option('wp_gvccf_cf_shortcode');
        delete_option('wp_gvccf_cf');
        delete_option('wp_gvccf_cf_shortcode_error');
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '" . WP_GVCCF_CF_DETAILS_TABLE . "'") == WP_GVCCF_CF_DETAILS_TABLE) {
            $result = $wpdb->get_results("select  cf7_table_prefix from " . WP_GVCCF_CF_DETAILS_TABLE . " ");
            foreach ($result as $value) {
                $wpdb->query("DROP TABLE IF EXISTS " . $value->cf7_table_prefix);
            }
            $wpdb->query("DROP TABLE IF EXISTS " . WP_GVCCF_CF_DETAILS_TABLE . " ");
        }
    }

}

/**
 * For Checking the installed contact form 7 version
 * 
 * @return boolean
 */
if (!function_exists('wp_gvccf_check_cf7_version')) {

    function wp_gvccf_check_cf7_version() {
        global $wp_gvccf_plugin_data;
        if ($wp_gvccf_plugin_data['contact-form-7/wp-contact-form-7.php']['Version'] >= WP_GVCCF_REQUIRED_CF_VERSION) {
            return true;
        } else {
            return false;
        }
    }

}

if (!function_exists('wp_gvccf_get_cf7_version')) {

    function wp_gvccf_get_cf7_version() {
        global $wp_gvccf_plugin_data;
        if ($wp_gvccf_plugin_data['contact-form-7/wp-contact-form-7.php']['Version'] >= WP_GVCCF_REQUIRED_CF_VERSION) {
            return $wp_gvccf_plugin_data['contact-form-7/wp-contact-form-7.php']['Version'];
        } else {
            return false;
        }
    }

}

if (!function_exists('wp_gvccf_get_cf7_default_settings')) {

    function wp_gvccf_get_cf7_default_settings() {
        global $wp_gvccf_vcard_object;
        return (object) array(
                    'is_send_vcard_enable' => '0',
					'is_save_vcard_enable' => '1',
                    'selected_vcard_fields' => (object) $wp_gvccf_vcard_object->wp_gvccf_get_vcard_blank_fields(),
        );
    }

}

if (!function_exists('wp_gvccf_get_cf7_settings')) {

    function wp_gvccf_get_cf7_settings($table = '') {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT cf7_vcard_settings FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE cf7_table_prefix = '" . $table . "'  ");
        $data = $wpdb->get_row($sql);
        if (!empty($data->cf7_vcard_settings)) {
            $existing_fields = json_decode($data->cf7_vcard_settings);
            if(!isset($existing_fields->selected_vcard_fields->vcard_photo)){
                $existing_fields->selected_vcard_fields->vcard_photo = '';
            }
            return json_decode(json_encode($existing_fields));
        } else {
            return wp_gvccf_get_cf7_default_settings();
        }
    }

}

if (!function_exists('wp_gvccf_cf_settings_available')) {

    function wp_gvccf_cf_settings_available($table = '') {
        $cf7_vcard_settings = wp_gvccf_get_cf7_settings($table);
        if (isset($cf7_vcard_settings->selected_vcard_fields) && is_object($cf7_vcard_settings->selected_vcard_fields)) {
            foreach ($cf7_vcard_settings->selected_vcard_fields as $key => $value) {
                if ($value != '') {
                    return true;
                }
            }
        }
        return false;
    }

}

if (!function_exists('wp_gvccf_cf_settings_available_and_downloadable')) {
    function wp_gvccf_cf_settings_available_and_downloadable($table = '') {
        $cf7_vcard_settings = wp_gvccf_get_cf7_settings($table);

        if (isset($cf7_vcard_settings->is_save_vcard_enable) && $cf7_vcard_settings->is_save_vcard_enable == 1) {
            return true;
        }
        return false;
    }

}

if (!function_exists('wp_gvccf_cf_is_contact_form_available')) {

    function wp_gvccf_cf_is_contact_form_available() {
        if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            if (!wp_gvccf_check_cf7_version()) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

}