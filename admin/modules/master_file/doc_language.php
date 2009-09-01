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

/* Document Language Management section */

require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging_ajax.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_datagrid.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('master_file', 'r');
$can_write = utility::havePrivilege('master_file', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_no_privilage.'</div>');
}

/* language_name update process */
if (isset($_POST['saveData']) AND $can_read AND $can_write) {
    $langID = trim(strip_tags($_POST['langID']));
    $langName = trim(strip_tags($_POST['langName']));
    // check form validity
    if (empty($langName) OR empty($langID)) {
        utility::jsAlert(lang_mod_masterfile_lang_alert_name_noempty);
        exit();
    } else {
        $data['language_id'] = $dbs->escape_string($langID);
        $data['language_name'] = $dbs->escape_string($langName);
        $data['input_date'] = date('Y-m-d');
        $data['last_update'] = date('Y-m-d');

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // remove input date
            unset($data['input_date']);
            // filter update record ID
            $updateRecordID = $dbs->escape_string(trim($_POST['updateRecordID']));
            // update the data
            $update = $sql_op->update('mst_language', $data, 'language_id=\''.$updateRecordID.'\'');
            if ($update) {
                utility::jsAlert(lang_mod_masterfile_lang_alert_update_ok);
                // update language ID in biblio table to keep data integrity
                $sql_op->update('biblio', array('language_id' => $data['language_id']), 'language_id=\''.$updateRecordID.'\'');
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.getPreviousAJAXurl(), \'post\');</script>';
            } else { utility::jsAlert(lang_mod_masterfile_lang_alert_update_fail."\nDEBUG : ".$sql_op->error); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            $insert = $sql_op->insert('mst_language', $data);
            if ($insert) {
                utility::jsAlert(lang_mod_masterfile_lang_alert_new_add_ok);
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\', \'post\');</script>';
            } else { utility::jsAlert(lang_mod_masterfile_lang_alert_add_fail."\nDEBUG : ".$sql_op->error); }
            exit();
        }
    }
    exit();
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }
    /* DATA DELETION PROCESS */
    $sql_op = new simbio_dbop($dbs);
    $failed_array = array();
    $error_num = 0;
    if (!is_array($_POST['itemID'])) {
        // make an array
        $_POST['itemID'] = array($dbs->escape_string(trim($_POST['itemID'])));
    }
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        $itemID = $dbs->escape_string(trim($itemID));
        if (!$sql_op->delete('mst_language', "language_id='$itemID'")) {
            $error_num++;
        }
    }

    // error alerting
    if ($error_num == 0) {
        utility::jsAlert(lang_mod_masterfile_lang_alert_all_delete_ok);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(lang_mod_masterfile_lang_alert_all_delete_fail);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}
/* language_name update process end */

/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner masterFileIcon">
    <?php echo strtoupper(lang_mod_masterfile_lang); ?> - <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/doc_language.php?action=detail', 'get');" class="headerText2"><?php echo lang_mod_masterfile_lang_new_add; ?></a>
    &nbsp; <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/doc_language.php', 'post');" class="headerText2"><?php echo lang_mod_masterfile_lang_list; ?></a>
    <hr />
    <form name="search" action="blank.html" target="blindSubmit" onsubmit="$('doSearch').click();" id="search" method="get" style="display: inline;"><?php echo lang_sys_common_form_search_field; ?> :
    <input type="text" name="keywords" size="30" />
    <input type="button" id="doSearch" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/doc_language.php?' + $('search').serialize(), 'post')" value="<?php echo lang_sys_common_form_search; ?>" class="button" />
    </form>
</div>
</fieldset>
<?php
/* search form end */
/* main content */
if (isset($_POST['detail']) OR (isset($_GET['action']) AND $_GET['action'] == 'detail')) {
    if (!($can_read AND $can_write)) {
        die('<div class="errorBox">'.lang_sys_common_no_privilage.'</div>');
    }
    /* RECORD FORM */
    $itemID = $dbs->escape_string(trim(isset($_POST['itemID'])?$_POST['itemID']:''));
    $rec_q = $dbs->query("SELECT * FROM mst_language WHERE language_id='$itemID'");
    $rec_d = $rec_q->fetch_assoc();

    // create new instance
    $form = new simbio_form_table('mainForm', $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'], 'post');
    $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_save_change.'" class="button"';

    // form table attributes
    $form->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
    $form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
    $form->table_content_attr = 'class="alterCell2"';

    // edit mode flag set
    if ($rec_q->num_rows > 0) {
        $form->edit_mode = true;
        // record ID for delete process
        $form->record_id = $itemID;
        // form record title
        $form->record_title = $rec_d['language_name'];
        // submit button attribute
        $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_update.'" class="button"';
    }

    /* Form Element(s) */
    // language ID
    $form->addTextField('text', 'langID', lang_mod_masterfile_lang_form_field_lang_code.'*', $rec_d['language_id'], 'style="width: 20%;" maxlength="5"');
    // language_name
    $form->addTextField('text', 'langName', lang_mod_masterfile_lang_form_field_name.'*', $rec_d['language_name'], 'style="width: 60%;"');

    // print out the form object
    echo '<div class="infoBox">'.lang_mod_masterfile_lang_common_edit_info.'<b>'.$rec_d['language_name'].'</b>  <br />'.lang_mod_masterfile_lang_common_last_update.$rec_d['last_update'].'</div>';
    echo $form->printOut();
} else {
    /* DOCUMENT LANGUAGE LIST */
    // table spec
    $table_spec = 'mst_language AS l';

    // create datagrid
    $datagrid = new simbio_datagrid();
    if ($can_read AND $can_write) {
        $datagrid->setSQLColumn('l.language_id', 'l.language_name AS \''.lang_mod_masterfile_lang_form_field_name.'\'', 'l.last_update AS \''.lang_mod_masterfile_lang_common_last_update.'\'');
    } else {
        $datagrid->setSQLColumn('l.language_name AS \''.lang_mod_masterfile_lang_form_field_name.'\'', 'l.last_update AS \''.lang_mod_masterfile_lang_common_last_update.'\'');
    }
    $datagrid->setSQLorder('language_name ASC');

    // is there any search
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
       $keywords = $dbs->escape_string($_GET['keywords']);
       $datagrid->setSQLCriteria("l.language_name LIKE '%$keywords%'");
    }

    // set table and table header attributes
    $datagrid->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    // set delete proccess URL
    $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];

    // put the result into variables
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read AND $can_write));
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $msg = str_replace('{result->num_rows}', $datagrid->num_rows, lang_sys_common_search_result_info);
        echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"</div>';
    }

    echo $datagrid_result;
}
/* main content end */
?>
