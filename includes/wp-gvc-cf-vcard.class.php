<?php

if (!defined('ABSPATH')) {
    exit;
}
/**
 * WP_gvc_vcard
 */
if (!class_exists('WP_gvc_vcard')) {

    class WP_gvc_vcard {

        public $vcard_first_name; //First Name
        public $vcard_last_name; //Last Name
        public $vcard_sur_name; //Surname
        public $vcard_full_name; //Full Name
        public $vcard_email_address; //Email Address
        public $vcard_home_contact_number; //Contact Number (HOME)
        public $vcard_home_cell_number; //Cell Number (HOME)
        public $vcard_work_fax_number; //Fax Number (WORK)
        public $vcard_work_contact_number; //Contact Number (WORK)
        public $vcard_work_url; //Work URL (WORK)
        public $vcard_personal_url; //Personal URL
        public $vcard_company; //Company
        public $vcard_company_designation; //Company Designation
        public $vcard_work_street; //Street (Work)
        public $vcard_work_city; //City (Work)
        public $vcard_work_state; //State (Work)
        public $vcard_work_zipcode; //Zipcode (Work)
        public $vcard_work_country; //Country (Work)
        public $vcard_home_street; //Street (Home)
        public $vcard_home_city; //City (Home)
        public $vcard_home_state; //State (Home)
        public $vcard_home_zipcode; //Zipcode (Home)
        public $vcard_home_country; //Country (Home)
        public $vcard_note; //NOTE
        public $file_name = 'vcard';    //  File Name Download or Save
        public $save_to = 'vcard';      //  Save To Address Folder To Save (on Server)
        private $vcard;      //  Vcard Data Set
        private $vcard_details = '';
        public $vcard_photo;

        /**
         * 
         * @param type $vcard_data
         */
        function create_vcard($vcard_data = array()) {

            if (is_array($vcard_data)) {
                $count = 0;
                foreach ($vcard_data as $vcard_value) {
					//print_r($vcard_value);
                    $this->file_name = isset($vcard_value['file_name']) ? $vcard_value['file_name'] : $this->file_name;
                    $this->save_to = isset($vcard_value['save_to']) ? $vcard_value['save_to'] : $this->save_to;
                    $this->vcard_first_name = isset($vcard_value['vcard_first_name']) ? $vcard_value['vcard_first_name'] : '';
                    $this->vcard_last_name = isset($vcard_value['vcard_last_name']) ? $vcard_value['vcard_last_name'] : '';
                    $this->vcard_sur_name = isset($vcard_value['vcard_sur_name']) ? $vcard_value['vcard_sur_name'] : '';
                    $this->vcard_full_name = isset($vcard_value['vcard_full_name']) ? $vcard_value['vcard_full_name'] : '';
                    $this->vcard_email_address = isset($vcard_value['vcard_email_address']) ? $vcard_value['vcard_email_address'] : '';
                    $this->vcard_home_contact_number = isset($vcard_value['vcard_home_contact_number']) ? $vcard_value['vcard_home_contact_number'] : '';
                    $this->vcard_home_cell_number = isset($vcard_value['vcard_home_cell_number']) ? $vcard_value['vcard_home_cell_number'] : '';
                    $this->vcard_work_fax_number = isset($vcard_value['vcard_work_fax_number']) ? $vcard_value['vcard_work_fax_number'] : '';
                    $this->vcard_work_contact_number = isset($vcard_value['vcard_work_contact_number']) ? $vcard_value['vcard_work_contact_number'] : '';
                    $this->vcard_work_url = isset($vcard_value['vcard_work_url']) ? $vcard_value['vcard_work_url'] : '';
                    $this->vcard_personal_url = isset($vcard_value['vcard_personal_url']) ? $vcard_value['vcard_personal_url'] : '';
                    $this->vcard_company = isset($vcard_value['vcard_company']) ? $vcard_value['vcard_company'] : '';
                    $this->vcard_company_designation = isset($vcard_value['vcard_company_designation']) ? $vcard_value['vcard_company_designation'] : '';
                    $this->vcard_work_street = isset($vcard_value['vcard_work_street']) ? $vcard_value['vcard_work_street'] : '';
                    $this->vcard_work_city = isset($vcard_value['vcard_work_city']) ? $vcard_value['vcard_work_city'] : '';
                    $this->vcard_work_state = isset($vcard_value['vcard_work_state']) ? $vcard_value['vcard_work_state'] : '';
                    $this->vcard_work_zipcode = isset($vcard_value['vcard_work_zipcode']) ? $vcard_value['vcard_work_zipcode'] : '';
                    $this->vcard_work_country = isset($vcard_value['vcard_work_country']) ? $vcard_value['vcard_work_country'] : '';
                    $this->vcard_home_street = isset($vcard_value['vcard_home_street']) ? $vcard_value['vcard_home_street'] : '';
                    $this->vcard_home_city = isset($vcard_value['vcard_home_city']) ? $vcard_value['vcard_home_city'] : '';
                    $this->vcard_home_state = isset($vcard_value['vcard_home_state']) ? $vcard_value['vcard_home_state'] : '';
                    $this->vcard_home_zipcode = isset($vcard_value['vcard_home_zipcode']) ? $vcard_value['vcard_home_zipcode'] : '';
                    $this->vcard_home_country = isset($vcard_value['vcard_home_country']) ? $vcard_value['vcard_home_country'] : '';
                    $this->vcard_note = isset($vcard_value['vcard_note']) ? $vcard_value['vcard_note'] : '';
                    $this->vcard_photo = isset($vcard_value['vcard_photo']) ? $vcard_value['vcard_photo'] : '';
                    $this->get_single_vcard();
                    $this->vcard_details .= $this->vcard;
                }
            }
        }

        /**
         * 
         * @return type
         */
        public function get_single_vcard() {
            $vcard_tz = date("O");
            $vcard_rev = date("Y-m-d");
            $this->vcard = "BEGIN:VCARD\r\n";
            $this->vcard .= "VERSION:3.0\r\n";
            if (!empty($this->vcard_full_name)) {
                $this->vcard .= "N:" . $this->vcard_full_name . "\r\n";
            } else if (!empty($this->vcard_sur_name) || !empty($this->vcard_first_name) || !empty($this->vcard_last_name)) {
                $this->vcard .= "N:" . $this->vcard_sur_name . ";" . $this->vcard_first_name . " " . $this->vcard_last_name . "\r\n";
            }
            if (!empty($this->vcard_work_street) || !empty($this->vcard_work_city) || !empty($this->vcard_work_state) || !empty($this->vcard_work_zipcode) || !empty($this->vcard_work_country)) {
                $this->vcard .= "ADR;INTL;PARCEL;WORK:;;" . $this->vcard_work_street . ";" . $this->vcard_work_city . ";" . $this->vcard_work_state . ";" . $this->vcard_work_zipcode . ";" . ";" . $this->vcard_work_country . ";" . "\r\n";
            }

            if (!empty($this->vcard_home_street) || !empty($this->vcard_home_city) || !empty($this->vcard_home_state) || !empty($this->vcard_home_zipcode) || !empty($this->vcard_home_country)) {
                $this->vcard .= "ADR;DOM;PARCEL;HOME:;;" . $this->vcard_home_street . ";" . $this->vcard_home_city . ";" . $this->vcard_home_state . ";" . $this->vcard_home_zipcode . ";" . ";" . $this->vcard_home_country . ";" . "\r\n";
            }
            if (!empty($this->vcard_email_address)) {
                $this->vcard .= "EMAIL;INTERNET:" . $this->vcard_email_address . "\r\n";
            }
            if (!empty($this->vcard_company)) {
                $this->vcard .= "ORG:" . $this->vcard_company . "\r\n";
            }
            if (!empty($this->vcard_company_designation)) {
                $this->vcard .= "TITLE:" . $this->vcard_company_designation . "\r\n";
            }
            if (!empty($this->vcard_work_contact_number)) {
                $this->vcard .= "TEL;WORK:" . $this->vcard_work_contact_number . "\r\n";
            }
            if (!empty($this->vcard_work_fax_number)) {
                $this->vcard .= "TEL;FAX;WORK:" . $this->vcard_work_fax_number . "\r\n";
            }
            if (!empty($this->vcard_home_cell_number)) {
                $this->vcard .= "TEL;CELL:" . $this->vcard_home_cell_number . "\r\n";
            }
            if (!empty($this->vcard_home_contact_number)) {
                $this->vcard .= "TEL;HOME:" . $this->vcard_home_contact_number . "\r\n";
            }
            if (!empty($this->vcard_work_url)) {
                $this->vcard .= "URL;WORK:" . $this->vcard_work_url . "\r\n";
            }
            if (!empty($this->vcard_personal_url)) {
                $this->vcard .= "URL:" . $this->vcard_personal_url . "\r\n";
            }
            if (!empty($this->vcard_note)) {
                $this->vcard .= "NOTE:" . $this->create_single_line($this->vcard_note) . "\r\n";
            }

            if (!empty($this->vcard_photo)) {
                $allowed = array('jpeg', 'png', 'jpg');
                $ext = pathinfo($this->vcard_photo, PATHINFO_EXTENSION);
                if (in_array($ext, $allowed)) {
                    //$handle = fopen($this->vcard_photo, "r"); // set the file handle only for reading the file
                    //$content = fread($handle, filesize($this->vcard_photo)); // reading the file
                    //fclose($handle);

                    $getPhoto               = file_get_contents($this->vcard_photo);
                    $b64vcard               = base64_encode($getPhoto);
                    $b64mline               = chunk_split($b64vcard,74,"\n");
                    $b64final               = preg_replace('/(.+)/', ' $1', $b64mline);
                    $photo                  = $b64final;

                    $this->vcard .= "PHOTO;ENCODING=b;TYPE=JPEG:";
                    $this->vcard .= $photo  . "\r\n";
                }               
            }
			$this->vcard .= "END:VCARD\n";
            return $this->vcard;
        }

        /**
         * 
         * @param type $randName
         * @return type
         */
        public function save_vcard($vcard_name = false) {
            if ($vcard_name) {
                $this->file_name = $this->file_name . '_' . $vcard_name;
            }
            $handel = @fopen(WP_GVCCF_DIR . $this->save_to . '/' . $this->file_name . '.vcf', 'w');
            @chmod(WP_GVCCF_DIR . $this->save_to . '/' . $this->file_name . '.vcf', 0777);
            $write = @fwrite($handel, $this->vcard_details, strlen($this->vcard_details));
            @fclose($handel);
            return $write ? true : false;
        }

        public function get_vcard_file_name() {
            return $this->file_name . '.vcf';
        }

        /**
         * Download vCard
         */
        public function download_vcard() {
            header("Content-type: text/directory");
            header("Content-Disposition: attachment; filename=" . $this->file_name . ".vcf" . "");
            header("Pragma: public");
            print $this->vcard_details;
            exit;
        }

        /**
         * This function is use to make multiline text to single line because vCard not support next line
         * 
         * @param type $value
         * @return type
         */
        public function create_single_line($value) {
            return preg_replace("!\s+!", " ", $value);
        }

        /**
         * For Delete the file of the vcard
         */
        public function wp_gvccf_delete_vcard_files($except_file = '') {
            $files = glob(WP_GVCCF_DIR . 'vcard/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (isset($except_file) && !empty($except_file) && $except_file == @end(explode('/', $file))) {
                        /* If file match with the current generate file then no need to delete that file */
                    } else {
                        unlink($file);
                    }
                }
            }
        }

        /**
         * 
         * @return array
         */
        public function wp_gvccf_get_vcard_fields() {
            $wp_gvccf_vcard_fields = array(
                'vcard_first_name' => 'First Name',
                'vcard_last_name' => 'Last Name',
                'vcard_sur_name' => 'Surname',
                'vcard_full_name' => 'Full Name',
                'vcard_email_address' => 'Email Address',
                'vcard_home_contact_number' => 'Contact Number (HOME)',
                'vcard_home_cell_number' => 'Cell Number (HOME)',
                'vcard_work_fax_number' => 'Fax Number (WORK)',
                'vcard_work_contact_number' => 'Contact Number (WORK)',
                'vcard_work_url' => 'Work URL (WORK)',
                'vcard_personal_url' => 'Personal URL',
                'vcard_company' => 'Company',
                'vcard_company_designation' => 'Company Designation',
                'vcard_work_street' => 'Street (Work)',
                'vcard_work_city' => 'City (Work)',
                'vcard_work_state' => 'State (Work)',
                'vcard_work_zipcode' => 'Zipcode (Work)',
                'vcard_work_country' => 'Country (Work)',
                'vcard_home_street' => 'Street (Home)',
                'vcard_home_city' => 'City (Home)',
                'vcard_home_state' => 'State (Home)',
                'vcard_home_zipcode' => 'Zipcode (Home)',
                'vcard_home_country' => 'Country (Home)',
                'vcard_note' => 'NOTE',
                'vcard_photo' => 'PICTURE',
            );
            return $wp_gvccf_vcard_fields;
        }
		
        /**
         * 
         * @return string
         */
        public function wp_gvccf_get_vcard_blank_fields() {
            $wp_gvccf_vcard_fields = array(
                'vcard_first_name' => '',
                'vcard_last_name' => '',
                'vcard_sur_name' => '',
                'vcard_full_name' => '',
                'vcard_email_address' => '',
                'vcard_home_contact_number' => '',
                'vcard_home_cell_number' => '',
                'vcard_work_fax_number' => '',
                'vcard_work_contact_number' => '',
                'vcard_work_url' => '',
                'vcard_personal_url' => '',
                'vcard_company' => '',
                'vcard_company_designation' => '',
                'vcard_work_street' => '',
                'vcard_work_city' => '',
                'vcard_work_state' => '',
                'vcard_work_zipcode' => '',
                'vcard_work_country' => '',
                'vcard_home_street' => '',
                'vcard_home_city' => '',
                'vcard_home_state' => '',
                'vcard_home_zipcode' => '',
                'vcard_home_country' => '',
                'vcard_note' => '',
                'vcard_photo' => '',
            );
            return $wp_gvccf_vcard_fields;
        }

    }

}
?>