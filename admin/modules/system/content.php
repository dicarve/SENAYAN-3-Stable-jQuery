<?php
/**
 *
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

/* Dynamic content Management section */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/template_parser/simbio_template_parser.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging_ajax.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_datagrid.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('system', 'r');
$can_write = utility::havePrivilege('system', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_no_privilege.'</div>');
}

/* RECORD OPERATION */
if (isset($_POST['saveData'])) {
    $contentTitle = trim(strip_tags($_POST['contentTitle']));
    $contentPath = trim(strip_tags($_POST['contentPath']));
    // check form validity
    if (empty($contentTitle) OR empty($contentPath)) {
        utility::jsAlert(lang_sys_content_alert_noempty);
        exit();
    } else {
        $data['content_title'] = $dbs->escape_string(strip_tags(trim($contentTitle)));
        $data['content_path'] = strtolower($dbs->escape_string(strip_tags(trim($contentPath))));
        $data['content_desc'] = $dbs->escape_string(trim($_POST['contentDesc']));
        $data['input_date'] = date('Y-m-d H:i:s');
        $data['last_update'] = date('Y-m-d H:i:s');

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // remove input date
            unset($data['input_date']);
            // filter update record ID
            $updateRecordID = (integer)$_POST['updateRecordID'];
            // update the data
            $update = $sql_op->update('content', $data, 'content_id='.$updateRecordID);
            if ($update) {
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'system', $_SESSION['content_title'].' update content data ('.$data['content_title'].') with contentname ('.$data['contentname'].')');
                utility::jsAlert(lang_sys_content_alert_update_ok);
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.getPreviousAJAXurl(), \'post\');</script>';
            } else { utility::jsAlert(lang_sys_content_alert_update_fail."\nDEBUG : ".$sql_op->error); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            if ($sql_op->insert('content', $data)) {
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'system', $_SESSION['realname'].' add new content ('.$data['content_title'].') with contentname ('.$data['contentname'].')');
                utility::jsAlert(lang_sys_content_alert_save_ok);
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\', \'post\');</script>';
            } else { utility::jsAlert(lang_sys_content_alert_save_fail."\n".$sql_op->error); }
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
        $_POST['itemID'] = array((integer)$_POST['itemID']);
    }
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        $itemID = (integer)$itemID;
        // get content data
        $content_q = $dbs->query('SELECT content_title FROM content WHERE content_id='.$itemID);
        $content_d = $content_q->fetch_row();
        if (!$sql_op->delete('content', "content_id='$itemID'")) {
            $error_num++;
        } else {
            // write log
            utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'system', $_SESSION['realname'].' DELETE content ('.$content_d[0].')');
        }
    }

    // error alerting
    if ($error_num == 0) {
        utility::jsAlert(lang_sys_conf_module_common_alert_delete_success);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(lang_sys_conf_module_common_alert_delete_fail);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}
/* RECORD OPERATION END */

/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner systemIcon">
    <?php echo strtoupper(lang_sys_content); ?> - <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>system/content.php?action=detail', 'get');" class="headerText2"><?php echo lang_sys_content_new_add; ?></a>
    &nbsp; <a href="#" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>system/content.php', 'get');" class="headerText2"><?php echo lang_sys_content_list; ?></a>
    <hr />
    <form name="search" action="blank.html" target="blindSubmit" onsubmit="$('doSearch').click();" id="search" method="get" style="display: inline;"><?php echo lang_sys_common_form_search_field; ?> :
    <input type="text" name="keywords" size="30" />
    <input type="button" id="doSearch" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>system/content.php?' + $('search').serialize(), 'post')" value="<?php echo lang_sys_common_form_search; ?>" class="button" />
</form>
</div>
</fieldset>
<?php
/* main content */
if (isset($_POST['detail']) OR (isset($_GET['action']) AND $_GET['action'] == 'detail')) {
    if (!($can_read AND $can_write)) {
        die('<div class="errorBox">'.lang_sys_common_no_privilege.'</div>');
    }
    /* RECORD FORM */
    // try query
    $itemID = (integer)isset($_POST['itemID'])?$_POST['itemID']:0;
    $rec_q = $dbs->query('SELECT * FROM content WHERE content_id='.$itemID);
    $rec_d = $rec_q->fetch_assoc();

    // create new instance
    $form = new simbio_form_table('mainForm', $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'], 'post');
    $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_save.'" class="button"';

    // form table attributes
    $form->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
    $form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
    $form->table_content_attr = 'class="alterCell2"';

    // edit mode flag set
    if ($rec_q->num_rows > 0) {
        $form->edit_mode = true;
        // record ID for delete process
        // form record id
        $form->record_id = $itemID;
        // form record title
        $form->record_title = $rec_d['content_title'];
        // submit button attribute
        $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_update.'" class="button"';
    }

    /* Form Element(s) */
    // content title
    $form->addTextField('text', 'contentTitle', lang_sys_content_field_title.'*', $rec_d['content_title'], 'style="width: 100%;"');
    // content path
    $form->addTextField('text', 'contentPath', lang_sys_content_field_path.'*', $rec_d['content_path'], 'style="width: 50%;"');
    // content description
    $form->addTextField('textarea', 'contentDesc', lang_sys_content_field_desc, htmlentities($rec_d['content_desc'], ENT_QUOTES), 'style="width: 100%; height: 500px;"');

    // edit mode messagge
    if ($form->edit_mode) {
        echo '<div class="infoBox">'.lang_sys_content_common_edit_info,' : <b>'.$rec_d['content_title'].'</b> <br />'.lang_sys_content_common_last_update.$rec_d['last_update'].'</div>';
    }
    // print out the form object
    // init TinyMCE instance
    echo '<script type="text/javascript">tinyMCE.init({
        // Options
        mode : "exact", elements : "contentDesc", theme : "advanced",
        plugins : "safari,style,table,media,searchreplace,directionality,visualchars,xhtmlxtras,compat2x",
        skin : "o2k7", skin_variant : "silver",
        // Theme options
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,fontsizeselect,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,forecolor",
        theme_advanced_buttons2 : "undo,redo,|,tablecontrols,|,hr,removeformat,visualaid,|,charmap,media,|,ltr,rtl,|,search,replace",
        theme_advanced_buttons3 : null, theme_advanced_toolbar_location : "top", theme_advanced_toolbar_align : "center", theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : false, content_css : "'.(SENAYAN_WEB_ROOT_DIR.'admin/'.$sysconf['admin_template']['css']).'",
        });</script>';
    echo $form->printOut();
} else {
    /* USER LIST */
    // table spec
    $table_spec = 'content AS c';

    // create datagrid
    $datagrid = new simbio_datagrid();
    if ($can_read AND $can_write) {
        $datagrid->setSQLColumn('c.content_id',
            'c.content_title AS \''.lang_sys_content_field_title.'\'',
            'c.content_path AS \''.lang_sys_content_field_path.'\'',
            'c.last_update AS \''.lang_sys_content_common_last_update.'\'');
    } else {
        $datagrid->setSQLColumn('c.content_title AS \''.lang_sys_content_field_title.'\'',
            'c.content_path AS \''.lang_sys_content_field_path.'\'',
            'c.last_update AS \''.lang_sys_content_common_last_update.'\'');
    }
    $datagrid->setSQLorder('c.last_update DESC');

    // is there any search
    $criteria = 'c.content_id IS NOT NULL ';
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
       $keywords = $dbs->escape_string($_GET['keywords']);
       $criteria .= " AND MATCH(content_title, content_desc) AGAINST('$keywords')";
    }
    $datagrid->setSQLCriteria($criteria);

    // set table and table header attributes
    $datagrid->icon_edit = SENAYAN_WEB_ROOT_DIR.'admin/'.$sysconf['admin_template']['dir'].'/'.$sysconf['admin_template']['theme'].'/edit.gif';
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
