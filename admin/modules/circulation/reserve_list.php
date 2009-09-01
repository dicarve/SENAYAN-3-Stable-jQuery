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

/* RESERVATION LIST IFRAME CONTENT */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';

if (!isset($_SESSION['memberID'])) { die(); }

require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/template_parser/simbio_template_parser.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// page title
$page_title = 'Member Reserve List';

// start the output buffering
ob_start();
?>
<!--reserve specific javascript functions-->
<script type="text/javascript">
function confirmProcess(intReserveID, strTitle)
{
    var confirmBox = confirm('<?php echo lang_mod_circ_loan_reserve_confirm_delete; ?>' + "\n" + strTitle);
    if (confirmBox) {
        // fill the hidden form value
        document.reserveHiddenForm.reserveID.value = intReserveID;
        // submit hidden form
        document.reserveHiddenForm.submit();
    }
}
</script>
<!--reserve specific javascript functions end-->

<!--item loan form-->
<div style="padding: 5px; background-color: #CCCCCC;">
    <form name="reserveForm" id="search" action="circulation_action.php" method="post" style="display: inline;">
        <?php echo lang_mod_circ_reserve_field_search_collection; ?> :<br />
        <?php
        // AJAX expression
        $ajax_exp = "ajaxFillSelect('item_AJAX_lookup_handler.php', 'item', 'i.item_code:title', 'reserveItemID', $('bib_search_str').getValue())";
        $biblio_options[] = array('0', 'Title');
        echo simbio_form_element::textField('text', 'bib_search_str', '', 'style="width: 10%;" onkeyup="'.$ajax_exp.'"');
        echo simbio_form_element::selectList('reserveItemID', $biblio_options, '', 'class="marginTop" style="width: 70%;"');
        echo simbio_form_element::textField('submit', 'addReserve', lang_mod_circ_reserve_button_add_reserve);
        ?>
    </form>
</div>
<!--item loan form end-->

<?php
// check if there is member ID
if (isset($_SESSION['memberID'])) {
    $memberID = trim($_SESSION['memberID']);
    $reserve_list_q = $dbs->query("SELECT r.*, b.title FROM reserve AS r
        LEFT JOIN biblio AS b ON r.biblio_id=b.biblio_id
        WHERE r.member_id='$memberID'");

    // create table object
    $reserve_list = new simbio_table();
    $reserve_list->table_attr = 'align="center" style="width: 100%;" cellpadding="3" cellspacing="0"';
    $reserve_list->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    $reserve_list->highlight_row = true;
    // table header
    $headers = array(lang_mod_circ_tblheader_remove, lang_mod_circ_tblheader_title, lang_mod_circ_tblheader_item_code, lang_mod_circ_tblheader_reserve_date);
    $reserve_list->setHeader($headers);
    // row number init
    $row = 1;
    while ($reserve_list_d = $reserve_list_q->fetch_assoc()) {
        // alternate the row color
        $row_class = ($row%2 == 0)?'alterCell':'alterCell2';

        // remove reserve link
        $remove_link = '<a href="#" onclick="confirmProcess('.$reserve_list_d['reserve_id'].', \''.$reserve_list_d['title'].'\')" title="Remove Reservation" class="trashLink">&nbsp;</a>';
        // check if item/collection is available
        $avail_q = $dbs->query("SELECT COUNT(loan_id) FROM loan WHERE item_code='".$reserve_list_d['item_code']."' AND is_lent=1 AND is_return=0");
        $avail_d = $avail_q->fetch_row();
        if ($avail_d[0] < 1) {
            $reserve_list_d['title'] .= ' - <strong>'.lang_mod_circ_loan_reserve_status.'</strong>';
        }
        // check if reservation are already expired
        if ( (strtotime(date('Y-m-d'))-strtotime($reserve_list_d['reserve_date']))/(3600*24) > $sysconf['reserve_expire_periode'] ) {
            $reserve_list_d['title'] .= ' - <strong style="color: red;">'.lang_mod_circ_loan_reserve_expired.'</strong>';
        }
        // row colums array
        $fields = array(
            $remove_link,
            $reserve_list_d['title'],
            $reserve_list_d['item_code'],
            $reserve_list_d['reserve_date']
            );

        // append data to table row
        $reserve_list->appendTableRow($fields);
        // set the HTML attributes
        $reserve_list->setCellAttr($row, null, "valign='top' class='$row_class'");
        $reserve_list->setCellAttr($row, 0, "valign='top' align='center' class='$row_class' style='width: 5%;'");
        $reserve_list->setCellAttr($row, 1, "valign='top' class='$row_class' style='width: 70%;'");

        $row++;
    }

    echo $reserve_list->printTable();
    // hidden form for return and extend process
    echo '<form name="reserveHiddenForm" method="post" action="circulation_action.php"><input type="hidden" name="process" value="delete" /><input type="hidden" name="reserveID" value="" /></form>';
}

// get the buffered content
$content = ob_get_clean();
// include the page template
require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
?>
