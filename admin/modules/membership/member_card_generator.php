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

/* Member card print */

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
$can_read = utility::havePrivilege('membership', 'r');

if (!$can_read) {
    die('<div class="errorBox">You dont have enough privileges to view this section</div>');
}

// local settings
$max_print = 10;

// clean print queue
if (isset($_GET['action']) AND $_GET['action'] == 'clear') {
    // update print queue count object
    echo '<script type="text/javascript">parent.$(\'queueCount\').update(\'0\');</script>';
    utility::jsAlert(lang_mod_biblio_common_print_cleared);
    unset($_SESSION['card']);
    exit();
}

if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!$can_read) {
        die();
    }
    if (!is_array($_POST['itemID'])) {
        // make an array
        $_POST['itemID'] = array($_POST['itemID']);
    }
    // loop array
    if (isset($_SESSION['card'])) {
        $print_count = count($_SESSION['card']);
    } else {
        $print_count = 0;
    }
    // card size
    $size = 2;
    // create AJAX request
    echo '<script type="text/javascript" src="'.JS_WEB_ROOT_DIR.'prototype.js"></script>';
    echo '<script type="text/javascript">';
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        if ($print_count == $max_print) {
            $limit_reach = true;
            break;
        }
        if (isset($_SESSION['card'][$itemID])) {
            continue;
        }
        if (!empty($itemID)) {
            $card_text = trim($itemID);
            echo 'new Ajax.Request(\''.SENAYAN_WEB_ROOT_DIR.'lib/phpbarcode/barcode.php?code='.$card_text.'&encoding='.$sysconf['barcode_encoding'].'&scale='.$size.'&mode=png\', { method: \'get\', onFailure: function(sendAlert) { alert(\'Error creating card!\'); } });'."\n";
            // add to sessions
            $_SESSION['card'][$itemID] = $itemID;
            $print_count++;
        }
    }
    echo '</script>';
    if (isset($limit_reach)) {
        $msg = str_replace('{max_print}', $max_print, lang_mod_biblio_alert_print_no_add_queue);
        utility::jsAlert($msg);
    } else {
        // update print queue count object
        echo '<script type="text/javascript">parent.$(\'queueCount\').update(\''.$print_count.'\');</script>';
        utility::jsAlert(lang_mod_biblio_alert_print_add_ok);
    }
    exit();
}

// card pdf download
if (isset($_GET['action']) AND $_GET['action'] == 'print') {
    // check if label session array is available
    if (!isset($_SESSION['card'])) {
        utility::jsAlert(lang_mod_biblio_common_print_no_data);
        die();
    }
    if (count($_SESSION['card']) < 1) {
        utility::jsAlert(lang_mod_biblio_common_print_no_data);
        die();
    }
    // concat all ID together
    $member_ids = '';
    foreach ($_SESSION['card'] as $id) {
        $member_ids .= '\''.$id.'\',';
    }
    // strip the last comma
    $member_ids = substr_replace($member_ids, '', -1);
    // send query to database
    $member_q = $dbs->query('SELECT m.member_name, m.member_id, m.member_image, mt.member_type_name FROM member AS m
        LEFT JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
        WHERE m.member_id IN('.$member_ids.')');
    $member_datas = array();
    while ($member_d = $member_q->fetch_assoc()) {
        if ($member_d['member_id']) {
            $member_datas[] = $member_d;
        }
    }

    // include printed settings configuration file
    include SENAYAN_BASE_DIR.'admin'.DIRECTORY_SEPARATOR.'admin_template'.DIRECTORY_SEPARATOR.'printed_settings.inc.php';
    // check for custom template settings
    $custom_settings = SENAYAN_BASE_DIR.'admin'.DIRECTORY_SEPARATOR.$sysconf['admin_template']['dir'].DIRECTORY_SEPARATOR.$sysconf['template']['theme'].DIRECTORY_SEPARATOR.'printed_settings.inc.php';
    if (file_exists($custom_settings)) {
        include $custom_settings;
    }
    // chunk cards array
    $chunked_card_arrays = array_chunk($member_datas, $card_items_per_row);
    // create html ouput
    $html_str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
    $html_str .= '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>Member card Label Print Result</title>'."\n";
    $html_str .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
    $html_str .= '<style type="text/css">'."\n";
    $html_str .= 'body { padding: 0; margin: 1cm; font-family: '.$card_fonts.'; }'."\n";
    $html_str .= '.labelStyle { width: '.$card_box_width.'cm; height: '.$card_box_height.'cm; text-align: center; margin: '.$card_items_margin.'cm; border: 1px solid #666666; padding: 5px;}'."\n";
    $html_str .= '.labelHeaderStyle { background-color: #CCCCCC; font-weight: bold; padding: 5px; margin-bottom: 5px; }'."\n";
    $html_str .= '#photo { border: 1px solid #666666; float: left; width: '.$card_photo_width.'cm; height: '.$card_photo_height.'cm; overflow: hidden; }'."\n";
    $html_str .= '#photo img { width: 100%; }'."\n";
    $html_str .= '#bio { float: left; padding-left: 5px; text-align: left; }'."\n";
    $html_str .= '</style>'."\n";
    $html_str .= '</head>'."\n";
    $html_str .= '<body>'."\n";
    $html_str .= '<table style="margin: 0; padding: 0;" cellspacing="0" cellpadding="0">'."\n";
    // loop the chunked arrays to row
    foreach ($chunked_card_arrays as $card_rows) {
        $html_str .= '<tr>'."\n";
        foreach ($card_rows as $card) {
            $html_str .= '<td valign="top">';
            $html_str .= '<div class="labelStyle">';
            if (trim($card_header_text) != '') { $html_str .= '<div class="labelHeaderStyle">'.$card_header_text.'</div>'; }
            $html_str .= '<div id="photo">';
            $html_str .= '<img src="'.SENAYAN_WEB_ROOT_DIR.IMAGES_DIR.'/persons/'.$card['member_image'].'" border="0" />';
            $html_str .= '</div>';
            $html_str .= '<div id="bio">';
            $html_str .= '<div>'.( $card_include_field_label?lang_mod_membership_field_member_id.' : ':'' ).'<strong>'.$card['member_id'].'</strong></div>';
            $html_str .= '<div>'.( $card_include_field_label?lang_mod_membership_field_name.' : ':'' ).'<strong>'.$card['member_name'].'</strong></div>';
            $html_str .= '<div>'.( $card_include_field_label?lang_mod_membership_field_membership_type.' : ':'' ).'<strong>'.$card['member_type_name'].'</strong></div>';
            $html_str .= '<div style="text-align: center;"><img src="'.SENAYAN_WEB_ROOT_DIR.IMAGES_DIR.'/barcodes/'.str_replace(array(' '), '_', $card['member_id']).'.png" style="width: '.$card_barcode_scale.'%; margin-top: 10px;" border="0" /></div>';
            $html_str .= '</div>';
            $html_str .= '</div>';
            $html_str .= '</td>';
        }
        $html_str .= '<tr>'."\n";
    }
    $html_str .= '</table>'."\n";
    $html_str .= '<script type="text/javascript">self.print();</script>'."\n";
    $html_str .= '</body></html>'."\n";
    // unset the session
    unset($_SESSION['card']);
    // write to file
    $file_write = @file_put_contents(FILES_UPLOAD_DIR.'member_card_gen_print_result.html', $html_str);
    if ($file_write) {
        // update print queue count object
        echo '<script type="text/javascript">parent.$(\'queueCount\').update(\'0\');</script>';
        // open result in window
        echo '<script type="text/javascript">parent.openWin(\''.SENAYAN_WEB_ROOT_DIR.FILES_DIR.'/member_card_gen_print_result.html\', \'popItemcardGen\', 800, 500, true)</script>';
    } else { utility::jsAlert('ERROR! Cards failed to generate, possibly because '.SENAYAN_BASE_DIR.FILES_DIR.' directory is not writable'); }
    exit();
}

?>
<fieldset class="menuBox">
<div class="menuBoxInner printIcon">
    <?php echo strtoupper(lang_mod_membership_card_generator); ?> - <a target="blindSubmit" href="<?php echo MODULES_WEB_ROOT_DIR; ?>membership/member_card_generator.php?action=print" class="headerText2"><?php echo lang_mod_biblio_tools_card_generator_print_select; ?></a>
    &nbsp;<a onmouseover="return noStatus()" target="blindSubmit" href="<?php echo MODULES_WEB_ROOT_DIR; ?>membership/member_card_generator.php?action=clear" class="headerText2" style="color: #FF0000;"><?php echo lang_mod_biblio_tools_card_generator_print_clear; ?></a>
    <hr />
    <form name="search" action="blank.html" target="blindSubmit" onsubmit="$('doSearch').click();" id="search" method="get" style="display: inline;"><?php echo lang_sys_common_form_search_field; ?>:
    <input type="text" name="keywords" size="30" />
    <input type="button" id="doSearch" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>membership/member_card_generator.php?' + $('search').serialize(), 'post')" value="<?php echo lang_sys_common_form_search; ?>" class="button" />
    </form>
    <div style="margin-top: 3px;">
    <?php
    echo lang_mod_biblio_tools_common_print_msg1.' <font style="color: #FF0000">'.$max_print.'</font> '.lang_mod_biblio_tools_common_print_msg2.' ';
    if (isset($_SESSION['card'])) {
        echo '<font id="queueCount" style="color: #FF0000">'.count($_SESSION['card']).'</font>';
    } else { echo '<font id="queueCount" style="color: #FF0000">0</font>'; }
    echo ' '.lang_mod_biblio_tools_common_print_msg3;
    ?>
    </div>
</div>
</fieldset>
<?php
/* search form end */
/* ITEM LIST */
// table spec
$table_spec = 'member AS m
    LEFT JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id';
// create datagrid
$datagrid = new simbio_datagrid();
$datagrid->setSQLColumn('m.member_id',
    'm.member_id AS \''.lang_mod_membership_field_member_id.'\'',
    'm.member_name AS \''.lang_mod_membership_field_name.'\'',
    'mt.member_type_name AS \''.lang_mod_membership_field_membership_type.'\'');
$datagrid->setSQLorder('m.last_update DESC');
// is there any search
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $keyword = $dbs->escape_string(trim($_GET['keywords']));
    $words = explode(' ', $keyword);
    if (count($words) > 1) {
        $concat_sql = ' (';
        foreach ($words as $word) {
            $concat_sql .= " (m.member_id LIKE '%$word%' OR m.member_name LIKE '%$word%'";
        }
        // remove the last AND
        $concat_sql = substr_replace($concat_sql, '', -3);
        $concat_sql .= ') ';
        $datagrid->setSQLCriteria($concat_sql);
    } else {
        $datagrid->setSQLCriteria("m.member_id LIKE '%$keyword%' OR m.member_name LIKE '%$keyword%'");
    }
}
// set table and table header attributes
$datagrid->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// edit and checkbox property
$datagrid->edit_property = false;
$datagrid->chbox_property = array('itemID', lang_sys_common_tblheader_add);
$datagrid->chbox_action_button = lang_mod_biblio_common_form_print_queue;
$datagrid->chbox_confirm_msg = lang_mod_biblio_common_print_queue_confirm;
$datagrid->column_width = array('10%', '70%', '15%');
// set checkbox action URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, $can_read);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    echo '<div class="infoBox">'.lang_mod_membership_common_found_text_1.' '.$datagrid->num_rows.' '.lang_mod_membership_common_found_text_2.': "'.$_GET['keywords'].'"</div>';
}
echo $datagrid_result;
/* main content end */

?>
