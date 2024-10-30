<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 
 * @global type $wpdb
 * @global type $wp_gvccf_vcard_object
 */
if (!function_exists('wp_gvccf_check_download_request')) {

    function wp_gvccf_check_download_request() {
        global $wpdb;
        if (isset($_REQUEST['wp-gvc-cf-download-id']) && strlen(trim($_REQUEST['wp-gvc-cf-download-id'])) > 0) {
            if (isset($_REQUEST['wp-gvc-cf']) && strlen(trim($_REQUEST['wp-gvc-cf'])) > 0) {
                $record_id = $_REQUEST['wp-gvc-cf-download-id'];
                $selected_contact_form = $_REQUEST['wp-gvc-cf'];
                $sql = $wpdb->prepare("SELECT cf7_table_prefix FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id='" . $selected_contact_form . "' ");
                $result = $wpdb->get_row($sql);
                if (!empty($result)) {
                    $table = $result->cf7_table_prefix;
                    if (wp_gvccf_cf_settings_available($table)) {
                        $sql = $wpdb->prepare("SELECT * FROM " . $table . " WHERE id='" . $record_id . "' ");
                        $data = $wpdb->get_row($sql);
                        if (!empty($data)) {
                            $submitted_data = (array) $data;
                            $result = wp_gvccf_get_cf7_settings($table);
                            $wp_gvccf_selected_fields = $result->selected_vcard_fields;
                            $wp_gvccf_selected_fields_with_details = array();
                            foreach ($wp_gvccf_selected_fields as $key => $value) {
                                if (in_array($value, array_keys($submitted_data))) {
                                    $wp_gvccf_selected_fields_with_details[$key] = $submitted_data[$value];
                                }
                            }
                            if (count($wp_gvccf_selected_fields_with_details) > 0) {
                                $wp_gvccf_selected_fields_with_details = array($wp_gvccf_selected_fields_with_details);
                                global $wp_gvccf_vcard_object;
                                $wp_gvccf_vcard_object->create_vcard($wp_gvccf_selected_fields_with_details);
                                $wp_gvccf_vcard_object->save_vcard($wpdb->insert_id);
                                $wp_gvccf_vcard_object->download_vcard();
                            }
                        }
                    }
                }
            }
        }

        /* For all record download */
        if (isset($_REQUEST['wp-gvc-cf-download-all']) && strlen(trim($_REQUEST['wp-gvc-cf-download-all'])) == '1') {
            if (isset($_REQUEST['wp-gvc-cf']) && strlen(trim($_REQUEST['wp-gvc-cf'])) > 0) {
                $selected_contact_form = $_REQUEST['wp-gvc-cf'];
                $sql = $wpdb->prepare("SELECT cf7_table_prefix FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id='" . $selected_contact_form . "' ");
                $result = $wpdb->get_row($sql);
                if (!empty($result)) {
                    $table = $result->cf7_table_prefix;
                    if (wp_gvccf_cf_settings_available($table)) {
                        $sql = $wpdb->prepare("SELECT * FROM " . $table . "");
                        $data = $wpdb->get_results($sql);
                        if (!empty($data)) {
                            $wp_gvccf_selected_fields_with_details = array();
                            foreach ($data as $data_key => $data_value) {
                                $submitted_data = (array) $data_value;
                                $result = wp_gvccf_get_cf7_settings($table);
                                $wp_gvccf_selected_fields = $result->selected_vcard_fields;
                                foreach ($wp_gvccf_selected_fields as $key => $value) {
                                    if (in_array($value, array_keys($submitted_data))) {
                                        $wp_gvccf_selected_fields_with_details[$data_key][$key] = $submitted_data[$value];
                                    }
                                }
                            }
                            if (count($wp_gvccf_selected_fields_with_details) > 0) {
                                $wp_gvccf_selected_fields_with_details = $wp_gvccf_selected_fields_with_details;
                                global $wp_gvccf_vcard_object;
                                $wp_gvccf_vcard_object->create_vcard($wp_gvccf_selected_fields_with_details);
                                $wp_gvccf_vcard_object->save_vcard($wpdb->insert_id);
                                $wp_gvccf_vcard_object->download_vcard();
                            }
                        }
                    }
                }
            }
        }
        /* For Delete record */
        if (isset($_REQUEST['wp-gvc-cf-delete-id']) && strlen(trim($_REQUEST['wp-gvc-cf-delete-id'])) > 0) {
            if (isset($_REQUEST['wp-gvc-cf']) && strlen(trim($_REQUEST['wp-gvc-cf'])) > 0) {
                $record_id = $_REQUEST['wp-gvc-cf-delete-id'];
                $selected_contact_form = $_REQUEST['wp-gvc-cf'];
                $sql = $wpdb->prepare("SELECT cf7_table_prefix FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id='" . $selected_contact_form . "' ");
                $result = $wpdb->get_row($sql);
                if (!empty($result)) {
                    if (isset($result->cf7_table_prefix) && strlen(trim($result->cf7_table_prefix)) > 0) {
                        $delete = 'DELETE FROM ' . $result->cf7_table_prefix . ' WHERE id = "' . $record_id . '" ';
                        if ($wpdb->query($delete)) {
                            update_option('wp_gvccf_delete_msg', 'Record deleted successfully');
                        }
                    }
                }
            }
        }
    }

}

add_action('plugins_loaded', 'wp_gvccf_check_download_request');

/**
 * For checking shortocode submitted by the form
 * @global type $wpdb
 */
if (!function_exists('wp_gvccf_check_form_selection')) {

    function wp_gvccf_check_form_selection() {
        global $wpdb;
        if (isset($_POST['wp_gvccf_shortcode_submit']) && $_POST['wp_gvccf_shortcode_submit'] != '') {
            if (isset($_POST['wp_gvccf_cf_shortcode']) && $_POST['wp_gvccf_cf_shortcode'] != '') {
                $shortcode = $_POST['wp_gvccf_cf_shortcode'];
                $form_id = wp_gvccf_get_id_from_shortcode($shortcode);
                $form_post_id = "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_value LIKE '%" . $form_id . "%' ";
                $result_id = $wpdb->get_row($form_post_id);
                if ($form_id != '') {
                    $sql = $wpdb->prepare("SELECT id FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE cf7_form_id='" . $result_id->post_id . "' ");
                    $result = $wpdb->get_row($sql);
                    if (!empty($result)) {
                        if (isset($result->id) && $result->id != '') {
                            $cf7_form_names = wp_gvccf_get_all_cf7();
                            if (!empty($cf7_form_names)) {
                                foreach ($cf7_form_names as $key => $value) {
                                    if ($value['ID'] != $result->id) {
                                        $sql_lookup_update = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_vcard_settings=%s WHERE id=%d ";
                                        $wpdb->query($wpdb->prepare($sql_lookup_update, json_encode(wp_gvccf_get_cf7_default_settings()), $value['ID']));
                                    }
                                }
                            }
                            update_option('wp_gvccf_cf_shortcode', stripslashes($shortcode));
                            update_option('wp_gvccf_cf', $result->id);
                            update_option('wp_gvccf_cf_shortcode_error', '');
                        } else {
                            update_option('wp_gvccf_cf_shortcode', stripslashes($shortcode));
                            update_option('wp_gvccf_cf', '');
                            update_option('wp_gvccf_cf_shortcode_error', 'Invalid shortcode');
                        }
                    } else {
                        update_option('wp_gvccf_cf_shortcode', stripslashes($shortcode));
                        update_option('wp_gvccf_cf', '');
                        update_option('wp_gvccf_cf_shortcode_error', 'Invalid shortcode');
                    }
                } else {
                    update_option('wp_gvccf_cf_shortcode', stripslashes($shortcode));
                    update_option('wp_gvccf_cf', '');
                    update_option('wp_gvccf_cf_shortcode_error', 'Invalid shortcode');
                }
            } else {
                update_option('wp_gvccf_cf_shortcode', '');
                update_option('wp_gvccf_cf', '');
                update_option('wp_gvccf_cf_shortcode_error', 'Please enter shortcode');
            }
        }
    }

}


add_action('plugins_loaded', 'wp_gvccf_check_form_selection');

/**
 * For the vCard Setting
 * 
 * @global type $wp_gvccf_vcard_object
 */if (!function_exists('wp_gvccf_generate_vcard_settings')) {

    function wp_gvccf_generate_vcard_settings() {

        if (wp_gvccf_cf_is_contact_form_available()) {
            global $wpdb;
            $cf7_form_names = wp_gvccf_all_cf_list_with_status();
            ?>
            <div class="wrap wp-gvc-cf-main-sec">
                <h1>Contact Form vCard Generator</h1>
                <br/>
                <?php
                if (count($cf7_form_names) > 0) {
                    $results = wp_gvccf_check_form_availability();
                    if ($results) {
                        $wp_gvccf_selected_contact_form = wp_gvccf_get_selected_cf();
                        echo wp_gvccf_get_details_modal();
                        ?>
                        <div class="wp-gvc-cf-sec">
                            <div class="wp-gvc-cf-sec-option">
                                <div class="wp-gvc-cf-shortcode-form">
                                    <div class="wp-gvc-cf-note">
                                        <p>
                                            To generate vCard from contact form inquiries you first need to enter Shortcode of the Contact Form. Please enter Shortcode of any one Contact Form, this plugin is not to generate vCard from multiple contact forms.
                                        </p>
                                    </div>
                                    <form action="" method="post">
                                        <?php
                                        $selected_form_shortcode = '';
                                        if (get_option('wp_gvccf_cf_shortcode') != '') {
                                            $selected_form_shortcode = get_option('wp_gvccf_cf_shortcode');
                                        }
                                        ?>
                                        <label><b>Enter Contact Form Shortcode: </b></label>
                                        <input name="wp_gvccf_cf_shortcode" class="wp-gvc-cf-input" value='<?php echo $selected_form_shortcode; ?>' placeholder="Enter shortcode" />
                                        <input type="submit" name="wp_gvccf_shortcode_submit" value="Enter" class="button button-primary button-large"/>
                                        <?php
                                        if (get_option('wp_gvccf_cf_shortcode_error') != '') {
                                            echo "<div class='wp-gvc-cf-error'>" . get_option('wp_gvccf_cf_shortcode_error') . "</div>";
                                        }
                                        ?>
                                    </form>
                                    <div class="wp-gvc-cf-note">
                                        <p><i><b>Note:</b></i></p> 
                                        <p><i>- When you change the Shortcode to use another form, contact form to vCard fields mapping values for the previous form will be discarded. You need to map values again for the new form.</i></p>
                                        <p><i>- Contact Form vCard Generator plugin provides an interface to map contact form fields to vCard fields. If you are looking to create new contact form and use that with this plugin then you don’t have to do anything. But if you are looking to use any existing contact form then you must edit and save that contact form.</i></p>
                                    </div>
                                </div>
                            </div>
                        </div>          
                        <div class="wp-gvc-cf-sec">
                            <?php
                            $active_tab = 'wp_gvccf_vf';
                            if (isset($_REQUEST['wp_gvccf_action']) && $_REQUEST['wp_gvccf_action'] == 'wp_gvccf_cf') {
                                $active_tab = 'wp_gvccf_cf';
                            } else if (isset($_REQUEST['wp_gvccf_action']) && $_REQUEST['wp_gvccf_action'] == 'wp_gvccf_setting') {
                                $active_tab = 'wp_gvccf_setting';
                            }
                            ?>
                            <h2 class="nav-tab-wrapper">
                                <a href="javascript:void(0);" class="nav-tab <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_vf' ? 'nav-tab-active' : ''; ?>" data-tab="wp_gvccf_vf">Contact Form to vCard Fields Mapping</a>
                                <a href="javascript:void(0);" class="nav-tab <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_cf' ? 'nav-tab-active' : ''; ?>" data-tab="wp_gvccf_cf">Download vCards</a>
                                <a href="javascript:void(0);" class="nav-tab <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_setting' ? 'nav-tab-active' : ''; ?>" data-tab="wp_gvccf_setting">Settings</a>
                            </h2>
                            <div class="wp-gvc-cf-row">
                                <div class="wp-gvc-cf-setting <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_vf' ? 'active' : ''; ?>" data-tab-view="wp_gvccf_vf">
                                    <?php
                                    $display_success = false;
                                    if (isset($_POST["vcard-submit"])) {
                                        wp_gvccf_save_seleced_vcard_fields();
                                        $display_success = true;
                                    }
                                    ?>
                                    <form action="" method="POST" name="vcard-form" class="vcard-form">
                                        <input type="hidden" name="wp_gvccf_action" value="wp_gvccf_vf"/>
                                        <?php
                                        $display_error = false;
                                        global $wp_gvccf_vcard_object;
                                        $vcard_fields = $wp_gvccf_vcard_object->wp_gvccf_get_vcard_fields();

                                        if ($wp_gvccf_selected_contact_form != false) {
                                            $table_header = wp_gvccf_get_table_header($wp_gvccf_selected_contact_form);
                                            $cf7_vcard_settings = wp_gvccf_get_cf7_settings(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
                                            $wp_gvccf_selected_fields = $cf7_vcard_settings->selected_vcard_fields;

                                            echo '<div class="wp-gvc-cf-note">';
                                            echo '<p>To start generating vCard based on contact form inquiries you need to map contact form fields with vCard Properties. Each contact form field is having option to select vCard property. Please select vCard property for the fields you like to include in the vCard.</p>';
                                            echo '</div>';
                                            echo '<br/>';
                                            if ($table_header != false) {
                                                $total_columns = count($table_header);
                                                echo '<div class="wp-gvc-cf-small-table">';
                                                echo '<h2>Contact Form to vCard Fields Mapping</h2>';
                                                echo "<table class='wp-gvc-cf-table'>";
                                                echo '<tr>';
                                                echo '<th>Contact Form Fields</th>';
                                                echo '<th>vCard Properties</th>';
                                                echo '</tr>';
                                                foreach ($table_header as $key => $value) {
                                                    $value_slug = wp_gvccf_generate_slug($value);
                                                    $checked = "";
                                                    $disabled = "disabled";
                                                    if (in_array($value_slug, (array) $wp_gvccf_selected_fields)) {
                                                        $checked = "checked='checked'";
                                                        $disabled = "";
                                                    }
                                                    echo "<tr>";
                                                    echo "<td>";
                                                    echo "<label><input type='checkbox' class='vcard-select' " . $checked . " name='" . $value_slug . "' value='1'>" . ucwords($value) . "</label>";
                                                    echo "</td>";
                                                    echo "<td>";
                                                    $class = "";
                                                    if ($value_slug != '') {
                                                        $class = "wp-gvc-cf-selected-bg";
                                                    }
                                                    echo "<select name='" . $value_slug . "-select' class='vcard-select-option " . $class . " " . ($disabled == "" ? 'active' : '') . "' " . $disabled . ">";
                                                    echo "<option value=''>Select Field</option>";
                                                    foreach ($vcard_fields as $vcard_key => $vcard_value) {
                                                        $selected = "";
                                                        if ($wp_gvccf_selected_fields->$vcard_key == $value_slug) {
                                                            $selected = "selected='selected'";
                                                        }
                                                        echo "<option value='" . $vcard_key . "' " . $selected . ">" . $vcard_value . "</option>";
                                                    }
													
                                                    echo "</select>";
													
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                                echo "</table>";
                                                ?>
                                              
                                                <input type="submit" name="vcard-submit" class="button button-primary button-large" value="Save Settings"/>
                                                <?php
                                                if ($display_success) {
                                                    echo '<p class="wp-gvc-cf-success">Settings Saved Successfully</p>';
                                                }
                                                echo '</div>';
                                                echo '<div class="wp-gvc-cf-small-table">';
                                                echo '<h2 class="wp-gvc-cf-heading">Example:</h2>';
                                                echo '<img src="' . WP_GVCCF_URL . 'assets/images/example-img.png' . '" alt="Image Not Found"/>';
                                                echo '</div>';
                                                ?>
                                            </form>
                                            <?php
                                        } else {
                                            $display_error = true;
                                        }
                                    } else {
                                        $display_error = true;
                                    }


                                    if (isset($display_error) && $display_error) {
                                        ?>
                                        <table class="widefat fixed striped" cellspacing="0">
                                            <tbody>
                                                <tr>
                                                    <td align="center">
                                                        Contact form details not found
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="wp-gvc-cf-setting <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_cf' ? 'active' : ''; ?>" data-tab-view="wp_gvccf_cf">
                                    <?php
                                    $is_records_available = false;
                                    $table_data = wp_gvccf_get_table_data($wp_gvccf_selected_contact_form);
                                    if (isset($table_data['total_records']) && $table_data['total_records'] > 0) {
                                        $is_records_available = true;
                                    }
                                    if ($wp_gvccf_selected_contact_form != '') {
                                        ?>
                                        <div class="wp-gvc-nf-note">
                                            <p>
                                                These are contact form inquiries generated from the contact form shortcode used. You can download vCard for individual items using Download vCard link or you can download vCard with multiple entries using Download All Inquires In vCard button.
                                            </p>
                                            <?php
                                            if (get_option('wp_gvccf_delete_msg') != '') {
                                                echo '<p class="wp-gvc-cf-success">' . get_option('wp_gvccf_delete_msg') . '</p>';
                                                update_option('wp_gvccf_delete_msg', '');
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    }
                                    if (wp_gvccf_cf_settings_available(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form)) {
                                        if ($is_records_available) {
                                            ?>
                                            <div class="wp-gvc-cf-download-all">
                                                <?php
                                                $url = admin_url() . 'admin.php?page=' . WP_GVCCF_SAVE_CF7_ADMIN_MENU . '&wp-gvc-cf=' . $wp_gvccf_selected_contact_form . '&wp-gvc-cf-download-all=1';
                                                ?>
                                                <a href="<?php echo $url; ?>" class="button button-primary"/>Download All Together</a>
                                            </div>
                                            <?php
                                        }
                                    }
                                    $is_download_enable = wp_gvccf_cf_settings_available(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
                                    $is_download_enable_new_opt = wp_gvccf_cf_settings_available_and_downloadable(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
                                    $columns_to_show = 6;
                                    $table_header = wp_gvccf_get_table_header($wp_gvccf_selected_contact_form);
                                    if ($table_header != false) {
                                        $total_columns = count($table_header);
                                        ?>
                                        <div class="wp-gvc-cf-details-table">
                                            <table class="widefat fixed striped" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <?php
                                                        $count = 0;
                                                        foreach ($table_header as $key => $value) {
                                                            if ($count >= $columns_to_show) {
                                                                break;
                                                            }
                                                            $count++;
                                                            echo '<th id="columnname" class="manage-column column-columnname" scope="col">' . ucwords($value) . '</th>';
                                                        }
                                                        ?>
                                                    </tr>
                                                </thead>
                                                <tfoot>
                                                    <tr>
                                                        <?php
                                                        $count = 0;
                                                        foreach ($table_header as $key => $value) {
                                                            if ($count >= $columns_to_show) {
                                                                break;
                                                            }
                                                            $count++;
                                                            echo '<th id="columnname" class="manage-column column-columnname" scope="col">' . ucwords($value) . '</th>';
                                                        }
                                                        ?>
                                                    </tr>
                                                </tfoot>
                                                <tbody>
                                                    <?php
                                                    if (isset($table_data['total_records']) && $table_data['total_records'] > 0) {
                                                        foreach ($table_data['data'] as $key => $value) {
                                                            $download_url = '';
                                                            if ($is_download_enable) {
                                                                if (isset($value->id) && $value->id != ''  && $is_download_enable_new_opt !== false) {
                                                                    $download_url = admin_url() . 'admin.php?page=' . WP_GVCCF_SAVE_CF7_ADMIN_MENU . '&wp-gvc-cf=' . $wp_gvccf_selected_contact_form . '&wp-gvc-cf-download-id=' . $value->id;
                                                                }
                                                            }
                                                            if (isset($_REQUEST['wp-gvc-cf-page']) && strlen(trim($_REQUEST['wp-gvc-cf-page'])) > 0) {
                                                                $delete_url = admin_url() . 'admin.php?page=' . WP_GVCCF_SAVE_CF7_ADMIN_MENU . '&wp-gvc-cf=' . $wp_gvccf_selected_contact_form . '&wp_gvccf_action=wp_gvccf_cf&wp-gvc-cf-page=' . $_REQUEST['wp-gvc-cf-page'] . '&wp-gvc-cf-delete-id=' . $value->id;
                                                            } else {
                                                                $delete_url = admin_url() . 'admin.php?page=' . WP_GVCCF_SAVE_CF7_ADMIN_MENU . '&wp-gvc-cf=' . $wp_gvccf_selected_contact_form . '&wp_gvccf_action=wp_gvccf_cf&wp-gvc-cf-delete-id=' . $value->id;
                                                            }
                                                            if (isset($value->id) && $value->id != '') {
                                                                unset($value->id);
                                                            }
                                                            $current_details_data = array();
                                                            if ($total_columns > $columns_to_show) {
                                                                foreach ($value as $details_key => $details_value) {
                                                                    if (!is_null($details_value) && !empty($details_value)) {
                                                                        $current_details_data[ucwords(str_replace('_', ' ', $details_key))] = $details_value;
                                                                    }
                                                                }
                                                                $current_details_data = json_encode($current_details_data);
                                                            }
                                                            echo '<tr class="" valign="top">';
                                                            $count = 0;
                                                            foreach ($value as $table_value) {
                                                                if ($count >= $columns_to_show) {
                                                                    break;
                                                                }
                                                                $count++;
                                                                echo '<td class="column-columnname">' . $table_value;
                                                                if ($count == 1) {
                                                                    echo '<div class="row-actions">';
                                                                    $pipe_sign = '';
                                                                    $share = site_url().'/wp-content/plugins/contact-form-vcard-generator/vcard/vcard.vcf';
																	if ($download_url != '') {
                                                                        echo '<span><a href="' . $download_url . '">Download vCard</a></span>';
                                                                    }
                                                                    if (!empty($current_details_data)) {
                                                                        if ($download_url != '') {
                                                                            echo ' | ';
                                                                        }
                                                                        echo "<span><a href='javascript:void(0);' class='wp-gvc-cf-details' data-details='" . $current_details_data . "'>View Details</a></span>";
                                                                    }
                                                                    if ($download_url != '' || !empty($current_details_data)) {
                                                                        echo ' | ';
                                                                    }
                                                                    $download_url = '';
                                                                    echo '<span class="trash"><a href="javascript:void(0);" class="wp-gvc-cf-delete" data-href="' . $delete_url . '">Delete</a></span>';
                                                                    echo '</div>';
                                                                }
                                                                echo '</td>';
                                                            }
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        $total_columns = $columns_to_show > $total_columns ? $total_columns : $columns_to_show;
                                                        echo "<tr><td align='center' colspan='" . ($total_columns) . "'>Contact form details not found</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                            <?php
                                            if ($is_records_available) {
                                                echo '<div class="wp-gvc-cf-pagination">';
                                                echo isset($table_data['pagination']) ? $table_data['pagination'] : '';
                                                echo '</div>';
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    } else {
                                        ?>
                                        <table class="widefat fixed striped" cellspacing="0">
                                            <tbody>
                                                <tr>
                                                    <td align="center">
                                                        Contact form details not found
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="wp-gvc-cf-setting <?php echo isset($active_tab) && $active_tab == 'wp_gvccf_setting' ? 'active' : ''; ?>" data-tab-view="wp_gvccf_setting">
                                    <?php
                                    if ($wp_gvccf_selected_contact_form != '') {
                                        $display_success_settings = false;
                                        if (isset($_POST['vcard-setting-submit'])) {
                                            if (isset($_POST['wp_gvccf_action']) && $_POST['wp_gvccf_action'] == 'wp_gvccf_setting') {
                                                if (in_array($_POST['is_send_vcard_enable'], array('0', '1'))) {
                                                    $is_send_vcard_enable = $_POST['is_send_vcard_enable'];
                                                } else {
                                                    $is_send_vcard_enable = '0';
                                                }
                                                $is_save_vcard_enable = '1';
                                               
                                                $cf7_vcard_settings = wp_gvccf_get_cf7_settings(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
                                                $settings_array = array(
                                                    'is_send_vcard_enable' => $is_send_vcard_enable,
													'is_save_vcard_enable' => $is_save_vcard_enable,
                                                    'selected_vcard_fields' => $cf7_vcard_settings->selected_vcard_fields,
                                                );
                                                $settings_array = json_encode($settings_array);
                                                $sql_alter_lookup = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_vcard_settings=%s WHERE cf7_table_prefix=%s";
                                                $wpdb->query($wpdb->prepare($sql_alter_lookup, $settings_array, WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form));
                                                $display_success_settings = true;
                                            }
                                        }
                                        ?>
                                        <form action="" method="POST" name="vcard-form-setting" class="vcard-form-setting">
                                            <input type="hidden" name="wp_gvccf_action" value="wp_gvccf_setting"/>
                                            <?php
                                            $cf7_vcard_settings = wp_gvccf_get_cf7_settings(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
                                            $wp_gvccf_selected_fields = $cf7_vcard_settings->selected_vcard_fields;
                                            if (isset($cf7_vcard_settings->is_send_vcard_enable) && $cf7_vcard_settings->is_send_vcard_enable == "1") {
                                                $yes = 'checked="checked"';
                                                $no = '';
                                            } else {
                                                $no = 'checked="checked"';
                                                $yes = "";
                                            }
                                            echo '<br/>';
                                            echo '<div class="">';
                                            echo '<label><strong>Do you like to attach vCard with contact email?&nbsp;&nbsp;&nbsp;</strong></label>';
                                            echo '<label><input type="radio" name="is_send_vcard_enable" value="1" ' . $yes . '/>Yes&nbsp;&nbsp;&nbsp;</label>';
                                            echo '<label><input type="radio" name="is_send_vcard_enable" value="0" ' . $no . '/>No&nbsp;&nbsp;&nbsp;</label>';
                                            echo '</div>';
                                            ?>
                                            <br/>
										
                                  
                                            <input type="submit" name="vcard-setting-submit" class="button button-primary button-large" value="Save Settings"/>
                                            <?php
                                            if ($display_success_settings) {
                                                echo '<p class="wp-gvc-cf-success">Settings Saved Successfully</p>';
                                            }
                                            ?>
                                        </form>
                                        <?php
                                    } else {
                                        ?>
                                        <table class="widefat fixed striped" cellspacing="0">
                                            <tbody>
                                                <tr>
                                                    <td align="center">
                                                        Contact form details not found
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="wp-gvc-cf-sec">
                            <div class="wp-gvc-cf-sidebar-boxes-main">
                                <div class="wp-gvc-cf-sidebar-box">
                                    <h3 class="hndle">Need Help?</h3>
                                    <div class="inside">
                                        <ul>
                                            <li><a href="https://wordpress.org/plugins/<?php echo WP_GVCCF_SLUG; ?>/installation/" target="_blank">Installation Help</a></li>
                                            <li><a href="https://wordpress.org/plugins/<?php echo WP_GVCCF_SLUG; ?>/faq/" target="_blank">Frequently Asked Questions</a></li>
                                            <li><a href="https://wordpress.org/support/plugin/<?php echo WP_GVCCF_SLUG; ?>" target="_blank">Support Forum</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="wp-gvc-cf-sidebar-boxes-main">
                                <div class="wp-gvc-cf-sidebar-box">
                                    <h3 class="hndle">Support Contact Form vCard Generator</h3>
                                    <p>Please rate this plugin if you found it useful for your purpose. Your feedback and ratings will be a huge booster and inspire me to continue upgrading this plugin.</p>
                                    <p>I am really happy to help you in case if you find any trouble using this plugin.</p>
                                    <div class="wp-gvc-cf-rate-sec">
                                        <a href="https://wordpress.org/support/plugin/<?php echo WP_GVCCF_SLUG; ?>/reviews/#new-post" target="_blank" class="rate-link">Rate this plugin</a>
                                        <a href="https://wordpress.org/support/plugin/<?php echo WP_GVCCF_SLUG; ?>/reviews/#new-post" target="_blank" class="rate-star"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
                                    </div>
                                </div>
                            </div>
                            <div class="wp-gvc-cf-sidebar-boxes-main">
                                <div class="wp-gvc-cf-sidebar-box">
                                    <h3 class="hndle">Any Suggestions?</h3>
                                    <p>Please feel free to share your suggestions with me, it will be really helpful.</p>
                                    <div class="inside">
                                        <ul>
                                            <li>Email: <a href="mailto:info@freelancer-coder.com">info@freelancer-coder.com</a></li>
                                            <li>Web: <a href="https://freelancer-coder.com/" target="_blank">https://freelancer-coder.com</a></li>
                                            <li>Contact Us: <a href="https://freelancer-coder.com/contact-wordpress-developer/" target="_blank">https://freelancer-coder.com/contact-wordpress-developer/</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="notice notice-success">
                            <p>Contact Form vCard Generator plugin provides an interface to map contact form fields to vCard fields. If you are looking to create new contact form and use that with this plugin then you don’t have to do anything. But if you are looking to use any existing contact form then you must edit and save that contact form.</p>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="notice notice-success">
                        <p>Contact Form vCard Generator plugin provides an interface to map contact form fields to vCard fields. If you are looking to create new contact form and use that with this plugin then you don’t have to do anything. But if you are looking to use any existing contact form then you must edit and save that contact form.</p>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        } else {
            echo '<div class="wrap wp-gvc-cf-main-sec">';
            echo '<h1>Contact Form vCard Generator</h1>';
            echo '</div>';
        }
    }

}
/**
 * Checking for contact form deleted working status and generate an array from that. * 
 * 
 * @global type $wpdb
 */
if (!function_exists('wp_gvccf_all_cf_list_with_status')) {

    function wp_gvccf_all_cf_list_with_status() {
        global $wpdb;
        $wp_gvccf_cf7_select = wp_gvccf_get_all_cf7();
        $cf7_form_names = array();
        foreach ($wp_gvccf_cf7_select as $wp_gvccf_cf_name) {
            $tbl = $wpdb->query($wpdb->prepare("SHOW TABLES LIKE '%s'", $wp_gvccf_cf_name['cfdba_table']));
            if ($wpdb->num_rows == 1 && !empty($tbl)) {
                if ($wp_gvccf_cf_name['cf7_version'] >= WP_GVCCF_REQUIRED_CF_VERSION && $wp_gvccf_cf_name['cf7_source'] == $wpdb->prefix . 'posts') {
                    $post_exists = $wpdb->query($wpdb->prepare("select * from " . $wpdb->prefix . "posts where ID=%d", (int) $wp_gvccf_cf_name["cf7_form_id"]));
                    if ($wpdb->num_rows > 0 && !empty($post_exists)) {
                        $cf7_form_names['cf7-working'][$wp_gvccf_cf_name['ID']] = strtoupper($wp_gvccf_cf_name['form_title']);
                    } else {
                        $cf7_form_names['cf7-deleted'][$wp_gvccf_cf_name['ID']] = strtoupper($wp_gvccf_cf_name['form_title']);
                        $wpdb->query('UPDATE ' . WP_GVCCF_CF_DETAILS_TABLE . ' SET cf7_status="YES" WHERE id="' . $wp_gvccf_cf_name["ID"] . '" ');
                    }
                }
            }
        }
        return $cf7_form_names;
    }

}

/**
 * For getting all the form available in the database table 
 * 
 * @global type $wpdb
 * @return type
 */
if (!function_exists('wp_gvccf_get_all_cf7')) {

    function wp_gvccf_get_all_cf7() {
        global $wpdb;
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $contact_forms = $wpdb->get_results("select * from " . WP_GVCCF_CF_DETAILS_TABLE . " order by cf7_status desc");
        $form_name = array();
        if (!empty($contact_forms)) {
            foreach ($contact_forms as $contact_form) {
                $form_name[] = array('ID' => $contact_form->id, 'form_title' => $contact_form->cf7_title, 'cfdba_table' => $contact_form->cf7_table_prefix, 'cf7_version' => $contact_form->cf7_version, 'cf7_form_id' => $contact_form->cf7_form_id, 'cf7_source' => $contact_form->cf7_source, 'form_status' => $contact_form->cf7_status);
            }
        }
        return $form_name;
    }

}

/**
 * For generating vCard file on the selection of the vcard fields with contact form fields
 * 
 * Directly generate vcard file to download
 * 
 * Currently this function is not used anywhere in the plugin
 */
if (!function_exists('wp_gvccf_generate_vcard_from_details')) {

    function wp_gvccf_generate_vcard_from_details() {
        global $wp_gvccf_vcard_object;
        $vcard_fields = $wp_gvccf_vcard_object->wp_gvccf_get_vcard_fields();
        $wp_gvccf_selected_contact_form = wp_gvccf_get_selected_cf();
        $vcard_data = array();
        if (isset($_POST['vcard-submit'])) {
            $vcard_data_cf_key = array();
            foreach ($vcard_fields as $vcard_key => $vcard_value) {
                if (isset($_POST[$vcard_key]) && $_POST[$vcard_key] == '1') {
                    /* Store the selected value and table heading with the vcard field */
                    $vcard_data_cf_key[$vcard_key] = $_POST[$vcard_key . '-select'];
                }
            }
            /* Getting cf submitted details */
            $table_data = wp_gvccf_get_table_data($wp_gvccf_selected_contact_form);
            if (isset($table_data['total_records']) && $table_data['total_records'] > 0) {
                foreach ($table_data['data'] as $key => $value) {
                    $temp_array = array();
                    foreach ($vcard_data_cf_key as $temp_key => $temp_value) {
                        $temp_array[$temp_key] = $value[$temp_value];
                    }
                    $vcard_data[] = $temp_array; /* Store details into the vcard format array */
                }
            } else {
                
            }
            /* If the records found then generate vcard */
            if (count($vcard_data) > 0) {
                global $wp_gvccf_vcard_object;
                $wp_gvccf_vcard_object->create_vcard($vcard_data);
                $wp_gvccf_vcard_object->Download_vcard();
                exit;
            }
        }
    }

}

/**
 * Save vCard selected fields into the database to check which fields are selected
 */
if (!function_exists('wp_gvccf_save_seleced_vcard_fields')) {

    function wp_gvccf_save_seleced_vcard_fields() {
        global $wpdb;
        global $wp_gvccf_vcard_object;
        $vcard_fields = $wp_gvccf_vcard_object->wp_gvccf_get_vcard_fields();
        $wp_gvccf_selected_contact_form = wp_gvccf_get_selected_cf();
        $table_header = wp_gvccf_get_table_header($wp_gvccf_selected_contact_form);
        $vcard_data = array();
        if (isset($_POST['vcard-submit'])) {
            /* For contact form fields to vcard */
            $vcard_data_cf_key = array();
            foreach ($table_header as $table_header_key => $table_header_value) {
                $value_slug = wp_gvccf_generate_slug($table_header_value);
                if (isset($_POST[$value_slug]) && $_POST[$value_slug] == '1') {
                    /* Store the selected value and table heading with the vcard field */
                    $vcard_data_cf_key[$_POST[$value_slug . '-select']] = $value_slug;
                }
            }
            /* Updating the vcard selected option */
            $cf7_vcard_settings = wp_gvccf_get_cf7_settings(WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form);
            $wp_gvccf_selected_fields = $cf7_vcard_settings->selected_vcard_fields;
            $wp_gvccf_selected_fields_temp = $wp_gvccf_selected_fields;
            foreach ($wp_gvccf_selected_fields_temp as $key => $value) {
                if (isset($vcard_data_cf_key[$key]) && $vcard_data_cf_key[$key] != '') {
                    $wp_gvccf_selected_fields->$key = $vcard_data_cf_key[$key];
                } else {
                    $wp_gvccf_selected_fields->$key = '';
                }
            }

            $settings_array = array(
                'is_send_vcard_enable' => $cf7_vcard_settings->is_send_vcard_enable,
                'selected_vcard_fields' => $wp_gvccf_selected_fields,
            );
            $settings_array = json_encode($settings_array);
            $sql_alter_lookup = "UPDATE " . WP_GVCCF_CF_DETAILS_TABLE . " SET cf7_vcard_settings=%s WHERE cf7_table_prefix=%s";
            $wpdb->query($wpdb->prepare($sql_alter_lookup, $settings_array, WP_GVCCF_CF_TABLE_PREFIX . $wp_gvccf_selected_contact_form));
        }
    }

}

/**
 * Getting table data
 * 
 * @global type $wpdb
 * @param type $id
 * @return type
 */
if (!function_exists('wp_gvccf_get_table_data')) {

    function wp_gvccf_get_table_data($id = '') {
        global $wpdb;
        $table = WP_GVCCF_CF_TABLE_PREFIX . absint($id);
        $return_array = array(
            'total_records' => 0,
            'data' => array(),
        );
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return $return_array;
        } else {

            $db_fields = wp_gvccf_get_fields($table);
            if (!empty($db_fields)) {
                $exclude_columns = array('created_at');
                foreach ($db_fields as $db_field) {    // loop for column header 
                    if (!in_array($db_field, $exclude_columns)) {
                        $columns[] = $db_field;
                    }
                }
                $sql = $wpdb->prepare("SELECT " . implode(", ", $columns) . " FROM " . $table . " ORDER BY id DESC ");
                /* Getting records with pagination */
                $return_array = wp_gvccf_get_pagination($sql);
            }
        }
        return $return_array;
    }

}

/**
 * 
 * @global type $wpdb
 * @param type $sql
 * @return int
 */
if (!function_exists('wp_gvccf_get_pagination')) {

    function wp_gvccf_get_pagination($sql) {
        global $wpdb;
        $return_array = array('total_records' => 0, 'data' => array(), 'pagination' => '');
        $data = $wpdb->get_results($sql);
        $total_records = count($data);
        if ($total_records > 0) {
            if (isset($_GET['wp-gvc-cf-page'])) {
                if (is_numeric($_REQUEST['wp-gvc-cf-page'])) {
                    $page_number = intval(abs($_REQUEST['wp-gvc-cf-page']));
                } else {
                    $page_number = 1;
                }
            } else {
                $page_number = 1;
            }
            $last_page = ceil($total_records / WP_GVCCF_RECORD_PER_PAGE);
            if ($page_number < 1) {
                $page_number = 1;
            } else if ($page_number > $last_page) {
                $page_number = $last_page;
            }
            /* URL of the page */
            $php_self = admin_url() . "admin.php?page=" . WP_GVCCF_SAVE_CF7_ADMIN_MENU . "&wp-gvc-cf=" . wp_gvccf_get_selected_cf() . "&wp_gvccf_action=wp_gvccf_cf";
            $center_pages = "";
            $sub1 = $page_number - 1;
            $sub2 = $page_number - 2;
            $add1 = $page_number + 1;
            $add2 = $page_number + 2;
            $urlfield = '';
            if ($page_number == 1) {
                $center_pages .= '<a title="Page ' . $page_number . '" class="active">' . $page_number . '</a>';
                $center_pages .= '<a title="Page ' . $add1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $add1 . $urlfield . '">' . $add1 . '</a>';
            } else if ($page_number == $last_page) {
                $center_pages .= '<a title="Page ' . $sub1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $sub1 . $urlfield . '">' . $sub1 . '</a>';
                $center_pages .= '<a title="Page ' . $page_number . '" class="active 1">' . $page_number . '</a>';
            } else if ($page_number > 2 && $page_number < ($last_page - 1)) {
                $center_pages .= '<a title="Page ' . $sub2 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $sub2 . $urlfield . '">' . $sub2 . '</a>';
                $center_pages .= '<a title="Page ' . $sub1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $sub1 . $urlfield . '">' . $sub1 . '</a>';
                $center_pages .= '<a title="Page ' . $page_number . '" class="active" href="javascript:void(0);">' . $page_number . '</a>';
                $center_pages .= '<a title="Page ' . $add1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $add1 . $urlfield . '">' . $add1 . '</a>';
                $center_pages .= '<a title="Page ' . $add2 . '" class="12" href="' . $php_self . '&wp-gvc-cf-page=' . $add2 . $urlfield . '">' . $add2 . '</a>';
            } else if ($page_number > 1 && $page_number < $last_page) {
                $center_pages .= '<a title="Page ' . $sub1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $sub1 . $urlfield . '">' . $sub1 . '</a>';
                $center_pages .= '<a title="Page ' . $page_number . '" class="active">' . $page_number . '</a>';
                $center_pages .= '<a title="Page ' . $add1 . '" class="" href="' . $php_self . '&wp-gvc-cf-page=' . $add1 . $urlfield . '">' . $add1 . '</a>';
            }
            $limit = ' LIMIT ' . ($page_number - 1) * WP_GVCCF_RECORD_PER_PAGE . ', ' . WP_GVCCF_RECORD_PER_PAGE;
            $sql = $sql . $limit;
            $data = $wpdb->get_results($sql);
            $pagination_display = '';
            if ($last_page != "1") {
                if ($page_number != 1) {
                    $previous = $page_number - 1;
                    $first = 1;
                    $pagination_display .= '<a title="First Page" class="first " href="' . $php_self . '&wp-gvc-cf-page=' . $first . $urlfield . '">First</a>';
                    $pagination_display .= '<a title="Previous Page" href="' . $php_self . '&wp-gvc-cf-page=' . $previous . $urlfield . '">Previous</a>';
                }
                $pagination_display .= $center_pages;
                if ($page_number != $last_page) {
                    $nextPage = $page_number + 1;
                    $last = $last_page;
                    $pagination_display .= '<a title="Next Page" href="' . $php_self . '&wp-gvc-cf-page=' . $nextPage . $urlfield . '">Next</a>';
                    $pagination_display .= '<a title="Last Page" class="last" href="' . $php_self . '&wp-gvc-cf-page=' . $last . $urlfield . '">Last</a> ';
                }
            }
            $pagination_display .='';
            $return_array = array(
                'total_records' => $total_records,
                'data' => $data,
                'pagination' => $pagination_display,
            );
        } else {
            $return_array = array(
                'total_records' => 0,
                'data' => array(),
                'pagination' => '',
            );
        }
        return $return_array;
    }

}

/**
 * Check any forms are available or not
 * 
 * @return type
 */
if (!function_exists('wp_gvccf_check_form_availability')) {

    function wp_gvccf_check_form_availability() {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE cf7_table_prefix != '' AND cf7_status='NO' LIMIT 1 ");
        return $wpdb->get_results($sql);
    }

}

/**
 * Get selected form from the dropdown
 * 
 * @return boolean
 */
if (!function_exists('wp_gvccf_get_selected_cf')) {

    function wp_gvccf_get_selected_cf() {
        global $wpdb;
        $cf7_form_id = get_option('wp_gvccf_cf');

        $sql = $wpdb->prepare("SELECT * FROM " . WP_GVCCF_CF_DETAILS_TABLE . " WHERE id = '" . $cf7_form_id . "' AND cf7_status='NO'");
        if (empty($wpdb->get_results($sql))) {
            update_option('wp_gvccf_cf', '');
            update_option('wp_gvccf_cf_shortcode', '');
            update_option('wp_gvccf_cf_shortcode_error', '');
        }
        return get_option('wp_gvccf_cf');
    }

}

/**
 * 
 * @param type $string
 * @return type
 */
if (!function_exists('wp_gvccf_generate_slug')) {

    function wp_gvccf_generate_slug($string) {
        return str_replace(' ', '_', $string);
    }

}

/**
 * Getting table header with field names as available in the contact form 7
 * 
 * @global type $wpdb
 * @param type $id
 * @return type
 */
if (!function_exists('wp_gvccf_get_table_header')) {

    function wp_gvccf_get_table_header($id = '') {
        global $wpdb;
        $table_header = array();
        $table = WP_GVCCF_CF_TABLE_PREFIX . $id;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return false;
        } else {
            $db_fields = wp_gvccf_get_fields($table);
            if (!empty($db_fields)) {
                /* Exlude Columns */
                $exclude_columns = array('id', 'created_at');
                foreach ($db_fields as $db_field) {
                    if (!in_array($db_field, $exclude_columns)) {
                        $table_header[] = str_replace('_', ' ', $db_field);
                    }
                }
            }
        }
        return $table_header;
    }

}

/**
 * 
 * @global type $wpdb
 * @param type $tab
 * @param type $isHeader
 * @return string
 */
if (!function_exists('wp_gvccf_get_fields')) {

    function wp_gvccf_get_fields($tab) {
        global $wpdb;
        $row_fields = $wpdb->get_results("SHOW full COLUMNS FROM $tab");
        $sending_arr = array();
        if (!empty($row_fields)) {
            foreach ($row_fields as $k => $v) {
                $sending_arr[] = $v->Field;
            }
        }
        return $sending_arr;
    }

}

if (!function_exists('wp_gvccf_get_id_from_shortcode')) {

    function wp_gvccf_get_id_from_shortcode($string) {
        $string = stripslashes($string);
        $start = 'id="';
        $end = '"';
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

}

if (!function_exists('wp_gvccf_get_details_modal')) {

    function wp_gvccf_get_details_modal() {
        $html = '<div class="wp-gvc-cf-modal">
        <div class="wp-gvc-cf-modal-content">
            <span class="wp-gvc-cf-close">&times;
            </span>
            <h2>Contact Inquiry Details</h2>
            <div class="wp-gvc-cf-modal-table-sec">  
            <table class="wp-gvc-cf-modal-table">
                <tbody>
                </tbody>
            </table>
            </div>
        </div>
    </div>';
        return $html;
    }

}    