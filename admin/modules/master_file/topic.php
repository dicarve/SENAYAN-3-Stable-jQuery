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

/* Topic Management section */

// main system configuration
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

/* RECORD OPERATION */
if (isset($_POST['saveData']) AND $can_read AND $can_write) {
    $topic = trim(strip_tags($_POST['topic']));
    // check form validity
    if (empty($topic)) {
        utility::jsAlert(lang_mod_masterfile_topic_alert_name_noempty);
        exit();
    } else {
        $data['topic'] = $dbs->escape_string($topic);
        $data['topic_type'] = trim($dbs->escape_string($_POST['subjectType']));
        $data['auth_list'] = trim($dbs->escape_string(strip_tags($_POST['authList'])));
        $data['input_date'] = date('Y-m-d');
        $data['last_update'] = date('Y-m-d');

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // remove input date
            unset($data['input_date']);
            // filter update record ID
            $updateRecordID = (integer)$_POST['updateRecordID'];
            // update the data
            $update = $sql_op->update('mst_topic', $data, 'topic_id='.$updateRecordID);
            if ($update) {
                utility::jsAlert(lang_mod_masterfile_topic_alert_update_ok);
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.getPreviousAJAXurl(), \'post\');</script>';
            } else { utility::jsAlert(lang_mod_masterfile_topic_alert_update_fail."\nDEBUG : ".$sql_op->error); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            $insert = $sql_op->insert('mst_topic', $data);
            if ($insert) {
                utility::jsAlert(lang_mod_masterfile_topic_alert_new_add_ok);
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\', \'post\');</script>';
            } else { utility::jsAlert(lang_mod_masterfile_topic_alert_add_fail."\nDEBUG : ".$sql_op->error); }
            exit();
        }
    }
    exit();
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }
    /* DATA DELETION PROCESS */
    // create sql op object
    $sql_op = new simbio_dbop($dbs);
    $failed_array = array();
    $error_num = 0;
    if (!is_array($_POST['itemID'])) {
        // make an array
        $_POST['itemID'] = array((integer)$_POST['itemID']);
    }
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        $itemID = (integer)$itemID;
        if (!$sql_op->delete('mst_topic', 'topic_id='.$itemID)) {
            $error_num++;
        }
    }

    // error alerting
    if ($error_num == 0) {
        utility::jsAlert(lang_mod_masterfile_topic_alert_all_delete_ok);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(lang_mod_masterfile_topic_alert_all_delete_fail);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}
/* RECORD OPERATION END */

/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner masterFileIcon">
    <?php echo strtoupper(lang_mod_masterfile_topic); ?> - <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/topic.php?action=detail', 'get');" class="headerText2"><?php echo lang_mod_masterfile_topic_new_add; ?></a>
    &nbsp; <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/topic.php', 'get');" class="headerText2"><?php echo lang_mod_masterfile_topic_list; ?></a>
    <hr />
    <form name="search" action="blank.html" target="blindSubmit" onsubmit="$('doSearch').click();" id="search" method="get" style="display: inline;"><?php echo lang_sys_common_form_search_field; ?> :
    <input type="text" name="keywords" size="30" />
    <input type="button" id="doSearch" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>master_file/topic.php?' + $('search').serialize(), 'post')" value="<?php echo lang_sys_common_form_search; ?>" class="button" />
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
    $itemID = (integer)isset($_POST['itemID'])?$_POST['itemID']:0;
    $rec_q = $dbs->query('SELECT * FROM mst_topic WHERE topic_id='.$itemID);
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
        $form->record_title = $rec_d['topic'];
        // submit button attribute
        $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_update.'" class="button"';
    }

    /* Form Element(s) */
    // subject
    $form->addTextField('text', 'topic', lang_mod_masterfile_topic_form_field_name.'*', $rec_d['topic'], 'style="width: 60%;"');
    // subject type
    foreach ($sysconf['subject_type'] as $subj_type_id => $subj_type) {
        $subj_type_options[] = array($subj_type_id, $subj_type);
    }
    $form->addSelectList('subjectType', lang_mod_masterfile_topic_type, $subj_type_options, $rec_d['topic_type']);
    // authority list
    $form->addTextField('text', 'authList', lang_mod_masterfile_authority_files, $rec_d['auth_list'], 'style="width: 30%;"');

    // edit mode messagge
    if ($form->edit_mode) {
        echo '<div class="infoBox">'.lang_mod_masterfile_topic_common_edit_info.' : <b>'.$rec_d['topic'].'</b>  <br />'.lang_mod_masterfile_topic_common_last_update.$rec_d['last_update'].'</div>';
    }
    // print out the form object
    echo $form->printOut();
} else {
    /* TOPIC LIST */
    // table spec
    $table_spec = 'mst_topic AS t';

    $subj_type_fld = 1;
    // create datagrid
    $datagrid = new simbio_datagrid();
    if ($can_read AND $can_write) {
        $subj_type_fld = 2;
        $datagrid->setSQLColumn('t.topic_id',
            't.topic AS \''.lang_mod_masterfile_topic_form_field_name.'\'',
            't.topic_type AS \''.lang_mod_masterfile_topic_type.'\'',
            't.auth_list AS \''.lang_mod_masterfile_authority_files.'\'',
            't.last_update AS \''.lang_mod_masterfile_topic_common_last_update.'\'');
    } else {
        $datagrid->setSQLColumn('t.topic AS \''.lang_mod_masterfile_topic_form_field_name.'\'',
            't.topic_type AS \''.lang_mod_masterfile_topic_type.'\'',
            't.auth_list AS \''.lang_mod_masterfile_authority_files.'\'',
            't.last_update AS \''.lang_mod_masterfile_topic_common_last_update.'\'');
    }
    $datagrid->setSQLorder('topic ASC');

    // is there any search
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $keyword = $dbs->escape_string(trim($_GET['keywords']));
        $words = explode(' ', $keyword);
        if (count($words) > 1) {
            $concat_sql = ' (';
            foreach ($words as $word) {
                $concat_sql .= " t.topic LIKE '%$word%' AND";
            }
            // remove the last AND
            $concat_sql = substr_replace($concat_sql, '', -3);
            $concat_sql .= ') ';
            $datagrid->setSQLCriteria($concat_sql);
        } else {
            $datagrid->setSQLCriteria("t.topic LIKE '%$keyword%'");
        }
    }

    // set table and table header attributes
    $datagrid->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    // set delete proccess URL
    $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];

    // callback function to change value of subject type
    function callbackSubjectType($obj_db, $rec_d)
    {
        global $sysconf, $subj_type_fld;
        return $sysconf['subject_type'][$rec_d[$subj_type_fld]];
    }
    // modify column content
    $datagrid->modifyColumnContent($subj_type_fld, 'callback{callbackSubjectType}');
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
