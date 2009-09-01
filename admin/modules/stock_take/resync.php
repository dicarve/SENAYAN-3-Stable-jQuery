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

/* Stock Take */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

if (isset($_POST['resync'])) {
    // update stock item data against bibliographic and item data
    $update_q = $dbs->query('UPDATE stock_take_item AS sti
        LEFT JOIN item AS i ON sti.item_code=i.item_code
            LEFT JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
            LEFT JOIN mst_location AS loc ON i.location_id=loc.location_id
            LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
                LEFT JOIN mst_gmd AS g ON b.gmd_id=g.gmd_id
        SET sti.title=b.title, sti.gmd_name=g.gmd_name,
            sti.classification=b.classification, sti.call_number=b.call_number,
            sti.coll_type_name=ct.coll_type_name');
    if (!$dbs->error) {
        $aff_rows = $dbs->affected_rows;
        // record to log
        utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'stock_take', 'Stock Take Re-Synchronization');
        echo '<script type="text/javascript">'."\n";
        echo 'parent.$(\'resyncInfo\').update(\''.$aff_rows.' Stock Take\\\'s Item Data Successfully Synchronized!\');'."\n";
        echo 'parent.$(\'resyncInfo\').setStyle( {display: \'block\'} );'."\n";
        echo '</script>';
    } else {
        echo '<script type="text/javascript">'."\n";
        echo 'parent.$(\'resyncInfo\').update(\'Stock Take\\\'s Item Data FAILED to Synchronized!\');'."\n";
        echo 'parent.$(\'resyncInfo\').setStyle( {color: \'red\', display: \'block\'} );'."\n";
        echo '</script>';
    }
    exit();
}

echo '<div class="infoBox">'.__('Re-synchronize will only update current stock take\'s item data. It won\'t update any new bibliographic or item data that were inserted in the middle of stock take proccess')."\n";
echo '<hr size="1" />'."\n";
echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post" target="resyncSubmit">'."\n";
echo '<input type="submit" name="resync" value="'.__('Resynchronize Now').'" class="button" />'."\n";
echo '</form>'."\n";
echo '<iframe name="resyncSubmit" style="width: 0; height: 0; visibility: hidden;"></iframe>'."\n";
echo '</div>';
echo '<div id="resyncInfo" style="display: none; padding: 5px; font-weight: bold; border: 1px solid #999999;">&nbsp;</div>';
?>
