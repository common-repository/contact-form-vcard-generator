<?php

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('WP_gvccf_plugin_links')) {

    class WP_gvccf_plugin_links {

        private $_wp_gvcnf_slug = WP_GVCCF_SLUG;

        public function __construct() {
            add_filter('plugin_row_meta', array($this, 'wp_gvcnf_add_meta_links'), 10, 2);
            add_filter('plugin_action_links_' . $this->_wp_gvcnf_slug . '/' . $this->_wp_gvcnf_slug . '.php', array($this, 'wp_gvcnf_add_action_links'), 10, 3);
            add_filter('plugin_action_links_' . $this->_wp_gvcnf_slug . '/' . $this->_wp_gvcnf_slug . '.php', array($this, 'wp_gvcnf_details_link'), 10, 3);
        }

        /**
         * Add plugin links
         * 
         * @param type $links
         * @param type $file
         * @return string
         */
        public function wp_gvcnf_add_meta_links($links, $file) {
            if ($file == '' . $this->_wp_gvcnf_slug . '/' . $this->_wp_gvcnf_slug . '.php') {
                $plugin_url = 'http://wordpress.org/plugins/' . $this->_wp_gvcnf_slug . '/';
                $links[] = '<a href="' . $plugin_url . '" target="_blank" title="' . __(
                                'Click here to visit the plugin on WordPress.org', $this->_wp_gvcnf_slug
                        ) . '">' . __('Visit WordPress.org page', $this->_wp_gvcnf_slug) . '</a>';
                $rate_url = 'https://wordpress.org/support/plugin/' . $this->_wp_gvcnf_slug . '/reviews/?rate=5#new-post';
                $links[] = '<a href="' . $rate_url . '" target="_blank" title="' . __(
                                'Click here to rate and review this plugin on WordPress.org', $this->_wp_gvcnf_slug
                        ) . '">' . __('Rate this plugin', $this->_wp_gvcnf_slug) . '</a>';
            }
            return $links;
        }

        /**
         * Add Settings link
         * 
         * @param type $links
         * @return type
         */
        public function wp_gvcnf_add_action_links($links) {
            $mylinks = array(
                '<a href="' . admin_url('admin.php?page=wp_gvccf_generate_vcard_settings') . '">Settings</a>',
            );
            return array_merge($links, $mylinks);
        }

        /**
         * Add Plugin Details link
         * 
         * @param type $links
         * @param type $plugin_file
         * @param type $plugin_data
         * @return type
         */
        public function wp_gvcnf_details_link($links, $plugin_file, $plugin_data) {
            if (isset($plugin_data['PluginURI']) && false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/extend/plugins/')) {
                $slug = basename($plugin_data['PluginURI']);
                $links[] = sprintf('<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url("plugin-install.php?tab=plugin-information&amp;plugin=" . $slug . "&amp;TB_iframe=true&amp;width=600&amp;height=550"), esc_attr(sprintf(__("More information about %s"), $plugin_data['Name'])), __('Details')
                );
            }
            return $links;
        }

    }

}