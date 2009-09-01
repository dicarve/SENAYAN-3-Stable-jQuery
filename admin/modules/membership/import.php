<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Member Import section */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_FILE/simbio_file_upload.inc.php';

// privileges checking
$can_read = utility::havePrivilege('membership', 'r');
$can_write = utility::havePrivilege('membership', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_no_privilage.'</div>');
}

// max chars in line for file operations
$max_chars = 4096;

if (isset($_POST['doImport'])) {
    // check for form validity
    if (!$_FILES['importFile']['name']) {
        utility::jsAlert(lang_mod_member_import_alert_file_noempty);
        exit();
    } else if (empty($_POST['fieldSep']) OR empty($_POST['fieldEnc'])) {
        utility::jsAlert(lang_mod_member_import_alert_required_noempty);
        exit();
    } else {
        // set PHP time limit
        set_time_limit(7200);
        // set ob implicit flush
        ob_implicit_flush();
        // create upload object
        $upload = new simbio_file_upload();
        // get system temporary directory location
        $temp_dir = sys_get_temp_dir();
        // set max size
        $max_size = $sysconf['max_upload']*1024;
        $upload->setAllowableFormat(array('.csv'));
        $upload->setMaxSize($max_size);
        $upload->setUploadDir($temp_dir);
        $upload_status = $upload->doUpload('importFile');
        if ($upload_status != UPLOAD_SUCCESS) {
            utility::jsAlert(lang_mod_member_import_alert_fail.' '.($sysconf['max_upload']/1024).' MB');
            exit();
        }
        // uploaded file path
        $uploaded_file = $temp_dir.DIRECTORY_SEPARATOR.$_FILES['importFile']['name'];
        $row_count = 0;
        // check for import setting
        $record_num = intval($_POST['recordNum']);
        $field_enc = trim($_POST['fieldEnc']);
        $field_sep = trim($_POST['fieldSep']);
        $record_offset = intval($_POST['recordOffset']);
        $record_offset = $record_offset-1;
        // get current datetime
        $curr_datetime = date('Y-m-d H:i:s');
        $curr_datetime = '\''.$curr_datetime.'\'';
        // foreign key id cache
        $mtype_id_cache = array();
        // read file line by line
        $inserted_row = 0;
        $file = fopen($uploaded_file, 'r');
        while (!feof($file)) {
            // record count
            if ($record_num > 0 AND $row_count == $record_num) {
                break;
            }
            // go to offset
            if ($row_count < $record_offset) {
                // pass and continue to next loop
                $row = fgets($file, $max_chars);
                $row_count++;
                continue;
            } else {
                // get an array of field
                $field = fgetcsv($file, $max_chars, $field_sep, $field_enc);
                if ($field) {
                    // strip escape chars from all fields
                    foreach ($field as $idx => $value) {
                        $field[$idx] = str_replace('\\', '', trim($value));
                        $field[$idx] = $dbs->escape_string($field[$idx]);
                    }
                    // strip leading field encloser if any
                    $member_id = preg_replace('@^\\\s*'.$field_enc.'@i', '', $field[0]);
                    $member_id = '\''.$member_id.'\'';
                    $member_name = '\''.$field[1].'\'';
                    $gender = $field[2];
                    $member_type_id = utility::getID($dbs, 'mst_member_type', 'member_type_id', 'member_type_name', $field[3], $mtype_id_cache);
                    $member_email = $field[4]?'\''.$field[4].'\'':'NULL';
                    $member_address = $field[5]?'\''.$field[5].'\'':'NULL';
                    $postal_code = $field[6]?'\''.$field[6].'\'':'NULL';
                    $inst_name = $field[7]?'\''.$field[7].'\'':'NULL';
                    $is_new = $field[8]?$field[8]:'0';
                    $member_image = $field[9]?'\''.$field[9].'\'':'NULL';
                    $pin = $field[10]?'\''.$field[10].'\'':'NULL';
                    $member_phone = $field[11]?'\''.$field[11].'\'':'NULL';
                    $member_fax = $field[12]?'\''.$field[12].'\'':'NULL';
                    $member_since_date = '\''.$field[13].'\'';
                    $register_date = '\''.$field[14].'\'';
                    $expire_date = '\''.$field[15].'\'';
                    $member_notes = preg_replace('@\\\s*'.$field_enc.'$@i', '', $field[16]);
                    $member_notes = $member_notes?'\''.$member_notes.'\'':'NULL';
                    // sql insert string
                    $sql_str = "INSERT IGNORE INTO member
                        (member_id, member_name, gender,
                        member_type_id, member_email, member_address, postal_code,
                        inst_name, is_new, member_image, pin, member_phone,
                        member_fax, member_since_date, register_date,
                        expire_date, member_notes,
                        input_date, last_update)
                            VALUES ($member_id, $member_name, $gender,
                            $member_type_id, $member_email, $member_address, $postal_code,
                            $inst_name, $is_new,
                            $member_image, $pin, $member_phone,
                            $member_fax, $member_since_date, $register_date,
                            $expire_date, $member_notes,
                            $curr_datetime, $curr_datetime)";
                    // send query
                    @$dbs->query($sql_str);
                    if (!$dbs->error) {
                        $inserted_row++;
                    } else {
                        echo $sql_str.'<br />';
                        echo $dbs->error.'<hr />';
                    }
                }
                $row_count++;
            }
        }
        // close file handle
        fclose($file);
        utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'membership', 'Importing '.$inserted_row.' members data from file : '.$_FILES['importFile']['name']);
        echo '<script type="text/javascript">'."\n";
        echo 'parent.$(\'importInfo\').update(\'<strong>'.$inserted_row.'</strong> '.lang_mod_member_import_info_record_uploaded.' <strong>'.$_POST['recordOffset'].'</strong>\');'."\n";
        echo 'parent.$(\'importInfo\').setStyle( {display: \'block\'} );'."\n";
        echo '</script>';
        exit();
    }
}

?>
<fieldset class="menuBox">
<div class="menuBoxInner importIcon">
    <?php echo strtoupper(lang_mod_membership_import_data).'<hr />'.lang_mod_membership_import_data_description; ?>
</div>
</fieldset>
<div id="importInfo" class="infoBox" style="display: none;">&nbsp;</div><div id="importError" class="errorBox" style="display: none;">&nbsp;</div>
<?php

// create new instance
$form = new simbio_form_table('mainForm', $_SERVER['PHP_SELF'].'', 'post');
$form->submit_button_attr = 'name="doImport" value="'.lang_mod_member_import_button_start.'" class="button"';

// form table attributes
$form->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
$form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
$form->table_content_attr = 'class="alterCell2"';

/* Form Element(s) */
// csv files
$str_input = simbio_form_element::textField('file', 'importFile');
$str_input .= ' Maximum '.$sysconf['max_upload'].' KB';
$form->addAnything(lang_mod_member_import_field_file.'*', $str_input);
// field separator
$form->addTextField('text', 'fieldSep', lang_mod_member_import_field_field_separator.'*', ''.htmlentities(',').'', 'style="width: 10%;"');
//  field enclosed
$form->addTextField('text', 'fieldEnc', lang_mod_member_import_field_field_enclosed.'*', ''.htmlentities('"').'', 'style="width: 10%;"');
// number of records to import
$form->addTextField('text', 'recordNum', lang_mod_member_import_field_record_number, '0', 'style="width: 10%;"');
// records offset
$form->addTextField('text', 'recordOffset', lang_mod_member_import_field_record_offset, '1', 'style="width: 10%;"');
// output the form
echo $form->printOut();
?>
