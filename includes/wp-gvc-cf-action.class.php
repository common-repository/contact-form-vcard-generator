<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('WP_gvc_cf_action')) {

    class WP_gvc_cf_action {

        private $_wpdb;

        /**
         * Initialize the plugin.
         */
        public function __construct() {
            global $wpdb;
            $this->_wpdb = $wpdb;
            $this->init_hooks();
        }

        /**
         * Initialises the WP actions.
         */
        private function init_hooks() {
            add_action('admin_init', array($this, 'wp_gvccf_after_cf7_create'));
            add_action('wpcf7_before_send_mail', array($this, 'wp_gvccf_save_cf7'));
            add_action('wpcf7_after_update', array($this, 'wp_gvccf_update_cf7'));
        }

        /**
         * 
         * @global type $this->_wpdb
         * @param type $wpcf7
         */
        public function wp_gvccf_after_cf7_create($wpcf7) {
            if (wp_gvccf_check_cf7_version()) {
                $wp_gvccf_cf_objs = WPCF7_ContactForm::find(array('orderby' => 'ID'));
                foreach ($wp_gvccf_cf_objs as $wp_gvccf_cf_obj) {
                    $cf7_form_id = $wp_gvccf_cf_obj->id();
                    $cf7_form_title = $wp_gvccf_cf_obj->title();
                    $form_shortcode = $wp_gvccf_cf_obj->prop('form');
                    $shortcode = WPCF7_FormTagsManager::get_instance();
                    $form = $shortcode->scan($form_shortcode);
                }
                $cf7_version = wp_gvccf_get_cf7_version();
                $cf7_form = $this->wp_gvccf_get_cf7_from();
                $table = $this->wp_gvccf_lookup_entry($cf7_form_id, $cf7_form_title, $cf7_version, $cf7_form, $form);
                if (!empty($form)) {
                    foreach ($form as $key => $fields) {
                        if ($fields['name'] == '')
                            continue;
                        if (strstr($fields['name'], '-') != FALSE) {
                            $fields['name'] = str_replace('-', '_', $fields['name']);
                        }
                        if ($fields['type'] == 'file' || $fields['type'] == 'file*') {
                            $db_table_fields[] = "" . $fields["name"] . " text COMMENT 'file_field' default NULL";
                        } else {
                            $db_table_fields[] = "" . $fields["name"] . " text ";
                        }
                    }
                }
                $db_table_field = implode(',', $db_table_fields);
                if ($table != '') {
                    $sql_create = "CREATE TABLE IF NOT EXISTS " . $table . "(id int(8) NOT NULL PRIMARY KEY AUTO_INCREMENT, " . $db_table_field . " ,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
                    $this->_wpdb->query($sql_create);
                }
            }
        }

        /**
         * function to submit contact from7 data to db
         * 
         * @global type $this->_wpdb
         * @param type $wpcf7
         */
        public function wp_gvccf_save_cf7($wpcf7) {
            if (wp_gvccf_check_cf7_version()) {
                $wp_gvccf_cf_obj = WPCF7_ContactForm::get_current();
                $submission = WPCF7_Submission::get_instance();
                $shortcode = WPCF7_FormTagsManager::get_instance();
                $form = $shortcode->scan($wp_gvccf_cf_obj->prop('form'));
                if ($submission) {
                    $submited = array();
                    $cf7_version = wp_gvccf_get_cf7_version();
                    $cf7_form = $this->wp_gvccf_get_cf7_from();
                    $submited['title'] = $wpcf7->title();
                    $submited['posted_data'] = $submission->get_posted_data();
                    $submited['uploaded_files'] = $submission->uploaded_files();
                }
                $table = $this->wp_gvccf_get_tbl_from_lookup($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form);
                if ($table == '') {
                    $table = $this->wp_gvccf_lookup_entry($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form, $form);
                }
                $fields = array();
                $values = array();
                $posted_data = $submited['posted_data'];
                $allformdata = $form;
                $wp_gvccf_compare_array = array();
                foreach ($allformdata as $wp_gvccf_data_single_value) {
                    if ($wp_gvccf_data_single_value['type'] != 'submit' && $wp_gvccf_data_single_value['name'] != '') {
                        $wp_gvccf_compare_array[] = $wp_gvccf_data_single_value['name'];
                    }
                }

                $submission = WPCF7_Submission::get_instance();
                $submited = array();
                if ($submission) {
                    $submited['uploaded_files'] = $submission->uploaded_files();
                }
                $available_table = get_option('wp_gvccf_cf');
                $wp_gvccf_selected_fields = [];
                if ($available_table != '') {
                    $sql = "SELECT cf7_form_id FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id = '" . $available_table . "' ";
                    $result = $this->_wpdb->get_row($sql);
                    if (!empty($result)) {
                        $shortcode = get_option('wp_gvccf_cf_shortcode');
                        $form_id = wp_gvccf_get_id_from_shortcode($shortcode);
                        if (isset($result->cf7_form_id) && $result->cf7_form_id == $form_id) {
                            $result = wp_gvccf_get_cf7_settings($table);
                           
                            if ($result->is_send_vcard_enable == '1') {                
                                $wp_gvccf_selected_fields = $result->selected_vcard_fields;
                            }
                        }
                    }
                }

                foreach ($posted_data as $key => $sdata) {
                    if ((in_array($key, $wp_gvccf_compare_array))) {
                        if (strstr($key, '-') != FALSE) {
                            $key = str_replace('-', '_', $key);
                        }                        
                        $fields[] = '' . $key . '';
                        
                        if($key == $wp_gvccf_selected_fields->{'vcard_photo'} && isset($submited['uploaded_files'][$key])){
                            $file_name = basename($submited['uploaded_files'][$key][0]);
                            $sdata == "" ? $values[] = 'NULL' : $values[] = "'" . esc_sql(content_url().'/uploads/vcard-uploads/'.$file_name) . "'";
                            continue;
                        }

                        if (is_array($sdata)) {
                            $sdata == "" ? $values[] = 'NULL' : $values[] = "'" . esc_sql(implode(",", $sdata)) . "'";
                        } else {
                            $sdata == "" ? $values[] = 'NULL' : $values[] = "'" . esc_sql($sdata) . "'";
                        }
                    }
                }
                $field = implode(',', $fields);
                $value = implode(',', $values);
                $sql_insert = "insert into $table ($field) values ($value)";

                if ($this->_wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                    if (wp_gvccf_check_cf7_version()) {
                        $wp_gvccf_cf_obj = WPCF7_ContactForm::get_current();
                        $shortcode = WPCF7_FormTagsManager::get_instance();
                        $form = $shortcode->scan($wp_gvccf_cf_obj->prop('form'));
                        $cf7_version = wp_gvccf_get_cf7_version();
                        $cf7_form = $this->wp_gvccf_get_cf7_from();
                        $table = $this->wp_gvccf_get_tbl_from_lookup($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form);
                        if ($table == '') {
                            $table = $this->wp_gvccf_lookup_entry($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form, $form);
                        }
                    }
                    foreach ($form as $key => $fields) {
                        if ($fields['name'] == '')
                            continue;
                        if (strstr($fields['name'], '-') != FALSE) {
                            $fields['name'] = str_replace('-', '_', $fields['name']);
                        }
                        if ($fields['type'] == 'file' || $fields['type'] == 'file*') {
                            $db_table_fields[] = "" . $fields["name"] . " text COMMENT 'file_field' default NULL ";
                        } else {
                            $db_table_fields[] = "" . $fields["name"] . " text ";
                        }
                    }
                    $db_table_fields = array_unique($db_table_fields);
                    $db_table_field = implode(',', $db_table_fields);
                    $sql_create = "CREATE TABLE IF NOT EXISTS " . $table . " (id int(8) NOT NULL PRIMARY KEY AUTO_INCREMENT, " . $db_table_field . " ,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
                    $this->_wpdb->query($sql_create);
                } else { }
                $query_status = FALSE;
                $sql = "SELECT cf7_form_id FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE cf7_table_prefix = '" . $table . "' ";
                $result = $this->_wpdb->get_row($sql);
                if (!empty($result)) {
                    $shortcode = get_option('wp_gvccf_cf_shortcode');
                    $form_id = wp_gvccf_get_id_from_shortcode($shortcode);
                    if (isset($result->cf7_form_id)) {
                        $query_status = $this->_wpdb->query($sql_insert);
                    }
                }
                if ($query_status == TRUE) {
                    $id = $this->_wpdb->insert_id;
                    $submitted_data = $submission->get_posted_data();
                    $available_table = get_option('wp_gvccf_cf');
                    if ($available_table != '') {
                        $sql = "SELECT cf7_form_id FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id = '" . $available_table . "' ";
                        $result = $this->_wpdb->get_row($sql);
                        if (!empty($result)) {
                            $shortcode = get_option('wp_gvccf_cf_shortcode');
                            $form_id = wp_gvccf_get_id_from_shortcode($shortcode);
                            if (isset($result->cf7_form_id) && $result->cf7_form_id == $form_id) {
                                $result = wp_gvccf_get_cf7_settings($table);
                                if ($result->is_send_vcard_enable == '1') {
                                    $submission = WPCF7_Submission::get_instance();
                                    $submited = array();
                                    if ($submission) {
                                        $submited['uploaded_files'] = $submission->uploaded_files();
                                    }
                                    $wp_gvccf_selected_fields = $result->selected_vcard_fields;
                                    $wp_gvccf_selected_fields_with_details = array();
                                    foreach ($wp_gvccf_selected_fields as $key => $value) {
                                        $value = str_replace('_', '-', $value);
                                        if (in_array($value, array_keys($submitted_data))) {
                                            $wp_gvccf_selected_fields_with_details[$key] = (is_array($submitted_data[$value]))?join(',', $submitted_data[$value]):$submitted_data[$value];
                                        }
                                        if($key == 'vcard_photo' && isset($submited['uploaded_files'][$value])){
                                            @$wp_gvccf_selected_fields_with_details[$key] = $submited['uploaded_files'][$value][0];
                                        }
                                    }
                                    foreach ($wp_gvccf_selected_fields as $key => $value) {

                                        if (in_array($value, array_keys($submitted_data))) {
                                            $wp_gvccf_selected_fields_with_details[$key] = (is_array($submitted_data[$value]))?join(',', $submitted_data[$value]):$submitted_data[$value];
                                        }
                                        if($key == 'vcard_photo' && isset($submited['uploaded_files'][$value])){
                                            @$wp_gvccf_selected_fields_with_details[$key] = $submited['uploaded_files'][$value][0];
                                        }
                                    }

                                    // Move photo to our upload dir
                                    if(!is_dir(WP_CONTENT_DIR.'/uploads/vcard-uploads')){
                                        wp_mkdir_p(WP_CONTENT_DIR.'/uploads/vcard-uploads');
                                    }

                                    if(isset($wp_gvccf_selected_fields_with_details['vcard_photo'])){
                                        $file_name = basename($wp_gvccf_selected_fields_with_details['vcard_photo']);
                                        copy($wp_gvccf_selected_fields_with_details['vcard_photo'], WP_CONTENT_DIR.'/uploads/vcard-uploads/'.$file_name);
                                        $wp_gvccf_selected_fields_with_details['vcard_photo'] = content_url().'/uploads/vcard-uploads/'.$file_name;
                                    }

                                    if (count($wp_gvccf_selected_fields_with_details) > 0) {
                                        $wp_gvccf_selected_fields_with_details = array($wp_gvccf_selected_fields_with_details);
                                        global $wp_gvccf_vcard_object;
                                        $wp_gvccf_vcard_object->create_vcard($wp_gvccf_selected_fields_with_details);
                                        $wp_gvccf_vcard_object->save_vcard($this->_wpdb->insert_id);
                                        $file_name = $wp_gvccf_vcard_object->get_vcard_file_name();
                                        $wp_gvccf_vcard_object->wp_gvccf_delete_vcard_files($file_name);
                                        $file_url = WP_GVCCF_DIR . 'vcard/' . $file_name;
                                        
                                        /* Add Attachment to the email content */
                                        $mail = $wpcf7->prop('mail');
                                        
                                        if (array_key_exists("attachments",$mail))
                                        {	
											$mail['attachments'] = $mail['attachments'].'
											'.$file_url;
										}
										else
										{										
											$mail['attachments'] = $file_url;												
										}
                                        $wpcf7->set_properties(array(
                                            'mail' => $mail
                                        ));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * function to update table structure on contact form 7 edit
         * 
         * 
         * @global type $this->_wpdb
         * @param type $wpcf7
         */
        public function wp_gvccf_update_cf7($wpcf7) {
            if (wp_gvccf_check_cf7_version()) {
                $wp_gvccf_cf_obj = WPCF7_ContactForm::get_current();
                if (!empty($wp_gvccf_cf_obj)) {
                    $cf7_form_title = $wp_gvccf_cf_obj->title();
                    $cf7_version = wp_gvccf_get_cf7_version();
                    $cf7_form = $this->wp_gvccf_get_cf7_from();
                    $shortcode = WPCF7_FormTagsManager::get_instance();
                    $form = $shortcode->scan($wp_gvccf_cf_obj->prop('form'));
                    $table = $this->wp_gvccf_get_tbl_from_lookup($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form);
                    if ($table == '') {
                        $table = $this->wp_gvccf_lookup_entry($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form, $form);
                    }
                    if ($this->_wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                        if (wp_gvccf_check_cf7_version()) {
                            $wp_gvccf_cf_obj = WPCF7_ContactForm::get_current();
                            $shortcode = WPCF7_FormTagsManager::get_instance();
                            $form = $shortcode->scan($wp_gvccf_cf_obj->prop('form'));
                            $cf7_version = wp_gvccf_get_cf7_version();
                            $cf7_form = $this->wp_gvccf_get_cf7_from();
                            $table = $this->wp_gvccf_get_tbl_from_lookup($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form);
                            if ($table == '') {
                                $table = $this->wp_gvccf_lookup_entry($wp_gvccf_cf_obj->id(), $wp_gvccf_cf_obj->title(), $cf7_version, $cf7_form, $form);
                            }
                        }
                        foreach ($form as $key => $fields) {
                            if ($fields['name'] == '')
                                continue;
                            if (strstr($fields['name'], '-') != FALSE) {
                                $fields['name'] = str_replace('-', '_', $fields['name']);
                            }
                            if ($fields['type'] == 'file' || $fields['type'] == 'file*') {
                                $db_table_fields[] = '' . $fields['name'] . " text COMMENT 'file_field' default NULL ";
                            } else {
                                $db_table_fields[] = '' . $fields['name'] . " text ";
                            }
                        }
                        $db_table_fields = array_unique($db_table_fields);
                        $db_table_field = implode(',', $db_table_fields);
                        $sql_create = "CREATE TABLE IF NOT EXISTS " . $table . " (id int(8) NOT NULL PRIMARY KEY AUTO_INCREMENT, " . $db_table_field . " ,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ";
                        $this->_wpdb->query($sql_create);
                    }
                    $db_table_fields = $this->wp_gvccf_get_table_columns($table);
                    $new_cf7_fields = array();
                    foreach ($form as $key => $fields) {
                        if ($fields['name'] == '') {
                            continue;
                        }
                        if (strstr($fields['name'], '-') != FALSE) {
                            $fields['name'] = str_replace('-', '_', $fields['name']);
                        }
                        if (!in_array($fields['name'], $db_table_fields)) {
                            if ($fields['type'] == 'file' || $fields['type'] == 'file*') {
                                $new_field = "" . $fields["name"] . " text COMMENT 'file_field' default NULL ";
                            } else {
                                $new_field = $fields["name"] . " text ";
                            }
                            $sql_alter = "ALTER TABLE $table ADD(" . $new_field . ")";
                            $this->_wpdb->query($sql_alter);
                        }
                        $new_cf7_fields[] = $fields['name'];
                    }
                    if (!empty($new_cf7_fields)) {
                        $db_table_fields = $this->wp_gvccf_get_table_columns($table);
                        $exclude_columns = array("id", "created_at");
                        $removed_columns = array();
                        foreach ($db_table_fields as $value) {
                            if (in_array($value, $exclude_columns)) {
                                
                            } else {
                                if (!in_array($value, $new_cf7_fields)) {
                                    $sql = "ALTER TABLE $table DROP $value ";
                                    $removed_columns[] = $value;
                                    $this->_wpdb->query($sql);
                                }
                            }
                        }
                        if (!empty($removed_columns)) {
                            $arguments = array(
                                'table' => $table,
                                'removed_columns' => $removed_columns
                            );
                            $this->wp_gvccf_update_cf7_table_settings($arguments);
                        }
                    }
                    if ($cf7_form_title == '') {
                        $cf7_form_title = 'Untitled';
                    }
                    $sql_alter_lookup = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_title = %s WHERE cf7_table_prefix = %s";
                    $this->_wpdb->query($this->_wpdb->prepare($sql_alter_lookup, $cf7_form_title, $table));
                }
            }
        }

        /**
         * 
         * @global type $this->_wpdb
         * @param type $cf7_form_id
         * @param type $cf7_form_title
         * @param type $cf7_version
         * @param type $cf7_form
         * @param type $form
         * @return string
         */
        public function wp_gvccf_lookup_entry($cf7_form_id, $cf7_form_title, $cf7_version, $cf7_form, $form) {
            $sql = "SELECT * FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE cf7_form_id = '" . $cf7_form_id . "' ";
            $data = $this->_wpdb->get_row($sql);
            if (!empty($data)) {
                $table = $data->cf7_table_prefix;
                $sql = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_title = '" . $cf7_form_title . "' WHERE cf7_form_id = '" . $cf7_form_id . "' ";
                $this->_wpdb->query($sql);
            } else {
                $sql_lookup_insert = "INSERT INTO " . WP_GVCCF_CF_DETAILS_TABLE . " (cf7_title, cf7_version, cf7_form_id,cf7_source )
                VALUES ('" . $cf7_form_title . "','" . $cf7_version . "','" . $cf7_form_id . "','" . $this->_wpdb->prefix . "posts')";
                if ($this->_wpdb->query($sql_lookup_insert)) {
                    $table = WP_GVCCF_CF_TABLE_PREFIX . $this->_wpdb->insert_id;
                } else {
                    $table = '';
                }
                $sql_lookup_update = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_table_prefix = %s,cf7_vcard_settings = %s WHERE id = %d ";
                $this->_wpdb->query($this->_wpdb->prepare($sql_lookup_update, $table, json_encode(wp_gvccf_get_cf7_default_settings()), $this->_wpdb->insert_id));
            }
            return $table;
        }

        /**
         * 
         * @global type $this->_wpdb
         * @param type $cf7_form_id
         * @param type $cf7_title
         * @param type $cf7_version
         * @param type $cf7_form
         * @return type
         */
        public function wp_gvccf_get_tbl_from_lookup($cf7_form_id, $cf7_title, $cf7_version, $cf7_form) {
            $result = $this->_wpdb->get_results($this->_wpdb->prepare("select cf7_table_prefix from " . WP_GVCCF_CF_DETAILS_TABLE . " where cf7_form_id = %d  AND cf7_title = %s AND cf7_version = %s AND cf7_source = %s AND cf7_status = 'NO' ", $cf7_form_id, $cf7_title, $cf7_version, $cf7_form));
            if (!empty($result[0]->cf7_table_prefix)) {
                return $result[0]->cf7_table_prefix;
            }
        }

        /**
         * Getting column names of the table.
         * 
         * @global type $this->_wpdb
         * @param type $table
         * @return type
         */
        public function wp_gvccf_get_table_columns($table) {
            $sql = "SELECT `COLUMN_NAME` 
                FROM `INFORMATION_SCHEMA`.`COLUMNS` 
                WHERE `TABLE_SCHEMA`='" . $this->_wpdb->dbname . "' 
                    AND `TABLE_NAME`='" . $table . "'";
            $db_table_fields = $this->_wpdb->get_results($sql);
            $table_fields = array();
            foreach ($db_table_fields as $value) {
                $table_fields[] = $value->COLUMN_NAME;
            }
            return $table_fields;
        }

        /**
         * 
         * @global type $this->_wpdb
         * @param type $arguments
         */
        public function wp_gvccf_update_cf7_table_settings($arguments = array()) {
            $result = wp_gvccf_get_cf7_settings($arguments['table']);
            $wp_gvccf_selected_fields = $result->selected_vcard_fields;
            foreach ($wp_gvccf_selected_fields as $key => $value) {
                if (in_array($value, $arguments['removed_columns'])) {
                    $wp_gvccf_selected_fields->$key = "";
                }
            }
            $settings_array = array(
                'is_send_vcard_enable' => $result->is_send_vcard_enable,
				'is_save_vcard_enable' => $result->is_save_vcard_enable,
                'selected_vcard_fields' => $wp_gvccf_selected_fields,
            );
            $settings_array = json_encode($settings_array);
            $sql_alter_lookup = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_vcard_settings = %s WHERE cf7_table_prefix = %s";
            $this->_wpdb->query($this->_wpdb->prepare($sql_alter_lookup, $settings_array, $arguments['table']));
        }

        /**
         * 
         * @global type $this->_wpdb
         * @return string
         */
        public function wp_gvccf_get_cf7_from() {
            if (wp_gvccf_check_cf7_version()) {
                $cf7_form = $this->_wpdb->prefix . 'posts';
            } else {
                $cf7_form = false;
            }
            return $cf7_form;
        }

    }

}