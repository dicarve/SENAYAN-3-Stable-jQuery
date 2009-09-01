<?php
/**
 * Copyright (C) 2009  Arie Nugraha (dicarve@yahoo.com)
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


/* Biblio Item List */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// page title
$page_title = 'Item List';
// get id from url
$biblioID = 0;
if (isset($_GET['biblioID']) AND !empty($_GET['biblioID'])) {
    $biblioID = (integer)$_GET['biblioID'];
}

// start the output buffer
ob_start();
// iframe blocker
if (isset($_GET['block'])) {
    echo '<div id="blocker" style="position: fixed; width: 100%; height: 100%; background-color: #CCCCCC; opacity: 0.3;">&nbsp;</div>';
}
?>
<script type="text/javascript">
function confirmProcess(int_biblio_id, int_item_id)
{
    var confirmBox = confirm('Are you sure to remove selected item?' + "\n" + 'Once deleted, it can\'t be restored!');
    if (confirmBox) {
        // set hidden element value
        document.hiddenActionForm.bid.value = int_biblio_id;
        document.hiddenActionForm.remove.value = int_item_id;
        // submit form
        document.hiddenActionForm.submit();
    }
}
</script>
<?php
/* main content */
if (isset($_POST['remove'])) {
    $id = (integer)$_POST['remove'];
    $bid = (integer)$_POST['bid'];
    $sql_op = new simbio_dbop($dbs);
    // check if the item still on loan
    $loan_q = $dbs->query('SELECT DISTINCT l.item_code, b.title FROM loan AS l
        LEFT JOIN item AS i ON l.item_code=i.item_code
        LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
        WHERE i.item_id='.$id.' AND l.is_lent=1 AND l.is_return=0');
    $loan_d = $loan_q->fetch_row();
    // send an alert if the member cant be deleted
    if ($loan_q->num_rows > 0) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.__('Item data can not be deleted because still on hold by members').'\');';
        echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
        echo '</script>';
    } else {
        if ($sql_op->delete('item', 'item_id='.$id)) {
            echo '<script type="text/javascript">';
            echo 'alert(\''.__('Item succesfully removed!').'\');';
            echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
            echo '</script>';
        } else {
            echo '<script type="text/javascript">';
            echo 'alert(\''.__('Item FAILED to removed!').'\');';
            echo 'self.location.href = \'iframe_item_list.php?biblioID='.$bid.'\';';
            echo '</script>';
        }
    }
}

// if biblio ID is set
if ($biblioID) {
    $table = new simbio_table();
    $table->table_attr = 'align="center" class="detailTable" style="width: 100%;" cellpadding="2" cellspacing="0"';

    // database list
    $item_q = $dbs->query('SELECT i.item_id, i.item_code, b.title, i.site, loc.location_name, ct.coll_type_name, st.item_status_name FROM item AS i
        LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
        LEFT JOIN mst_location AS loc ON i.location_id=loc.location_id
        LEFT JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
        LEFT JOIN mst_item_status AS st ON i.item_status_id=st.item_status_id
        WHERE i.biblio_id='.$biblioID);

    $row = 1;
    while ($item_d = $item_q->fetch_assoc()) {
        // alternate the row color
        $row_class = ($row%2 == 0)?'alterCell':'alterCell2';

        // links
        $edit_link = '<a href="#" onclick="openWin(\'pop_item.php?inPopUp=true&action=detail&biblioID='.$biblioID.'&itemID='.$item_d['item_id'].'\', \'popItem\', 600, 400, true)"
            style="text-decoration: underline;">Edit</a>';
        $remove_link = '<a href="#" onclick="confirmProcess('.$biblioID.', '.$item_d['item_id'].')"
            style="color: #FF0000; text-decoration: underline;">Delete</a>';
        $title = $item_d['item_code'];

        $table->appendTableRow(array($edit_link, $remove_link, $title, $item_d['location_name'], $item_d['site'], $item_d['coll_type_name'], $item_d['item_status_name']));
        $table->setCellAttr($row, null, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: auto;"');
        $table->setCellAttr($row, 0, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: 5%;"');
        $table->setCellAttr($row, 1, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: 10%;"');
        $table->setCellAttr($row, 2, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: 40%;"');

        $row++;
    }
    echo $table->printTable();
    // hidden form
    echo '<form name="hiddenActionForm" method="post" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="bid" value="0" /><input type="hidden" name="remove" value="0" /></form>';
}
/* main content end */
$content = ob_get_clean();
// include the page template
require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
?>
