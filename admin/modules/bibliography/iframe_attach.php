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

/* Attachment List */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// page title
$page_title = 'Attachment List';
// get id from url
$biblioID = 0;
if (isset($_GET['biblioID']) AND !empty($_GET['biblioID'])) {
    $biblioID = (integer)$_GET['biblioID'];
}

// start the output buffer
ob_start();
// iframe blocker
if (isset($_GET['block'])) {
    echo '<div id="blocker" style="position: absolute; width: 100%; height: 100%; background-color: #CCCCCC; opacity: 0.3;">&nbsp;</div>';
}
?>
<script type="text/javascript">
function confirmProcess(int_biblio_id, int_file_id, str_file_name)
{
    // confirmation to remove file from repository
    var confirmBox = confirm('Are you sure to remove the file attachment data?');
    if (confirmBox) {
        // set hidden element value
        var confirmBox2 = confirm('Do you also want to remove ' + str_file_name + ' file from repository?');
        if (confirmBox2) {
            document.hiddenActionForm.alsoDeleteFile.value = '1';
        }
        document.hiddenActionForm.bid.value = int_biblio_id;
        document.hiddenActionForm.remove.value = int_file_id;
        // submit form
        document.hiddenActionForm.submit();
    }
}
</script>
<?php
/* main content */
// temporary attachment removal
if (isset($_GET['removesess'])) {
    $idx = (integer)$_GET['removesess'];
    // remove file from filesystem
    @unlink(REPO_BASE_DIR.str_replace('/', DIRECTORY_SEPARATOR, $_SESSION['biblioAttach'][$idx]['file_dir']).DIRECTORY_SEPARATOR.$_SESSION['biblioAttach'][$idx]['file_name']);
    // remove session array
    unset($_SESSION['biblioAttach'][$idx]);
    echo '<script type="text/javascript">';
    echo 'alert(\''.__('Attachment removed!').'\');';
    echo 'location.href = \'iframe_attach.php\';';
    echo '</script>';
}

if (isset($_POST['bid']) AND isset($_POST['remove'])) {
    $bid = (integer)$_POST['bid'];
    $file = (integer)$_POST['remove'];
    // query file data from database
    $file_q = $dbs->query('SELECT * FROM files WHERE file_id='.$file);
    $file_d = $file_q->fetch_assoc();
    // attachment data delete
    $sql_op = new simbio_dbop($dbs);
    $sql_op->delete('biblio_attachment', "file_id=$file AND biblio_id=$bid");

    echo '<script type="text/javascript">';
    if ($_POST['alsoDeleteFile'] == '1') {
        // remove file from repository and filesystem
        @unlink(REPO_BASE_DIR.str_replace('/', DIRECTORY_SEPARATOR, $file_d['file_dir']).DIRECTORY_SEPARATOR.$file_d['file_name']);
        echo 'alert(\'Attachment '.$file_d['file_name'].' succesfully removed!\');';
    }
    echo 'location.href = \'iframe_attach.php?biblioID='.$bid.'\';';
    echo '</script>';
}

// if biblio ID is set
if ($biblioID) {
    $table = new simbio_table();
    $table->table_attr = 'align="center" style="width: 100%;" cellpadding="2" cellspacing="0"';

    // database list
    $biblio_attach_q = $dbs->query('SELECT att.*,fl.* FROM biblio_attachment AS att
        LEFT JOIN files AS fl ON att.file_id=fl.file_id WHERE biblio_id='.$biblioID);

    $row = 1;
    $row_class = 'alterCell2';
    while ($biblio_attach_d = $biblio_attach_q->fetch_assoc()) {
        // alternate the row color
        $row_class = ($row%2 == 0)?'alterCell':'alterCell2';

        // remove link
        $remove_link = '<a href="#" onclick="confirmProcess('.$biblioID.', '.$biblio_attach_d['file_id'].', \''.$biblio_attach_d['file_name'].'\')"
            style="color: #FF0000; text-decoration: underline;">Delete</a>';

        // edit link
        $edit_link = '<a href="javascript: openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_attach.php?biblioID='.$biblioID.'&fileID='.$biblio_attach_d['file_id'].'\', \'popAttach\', 600, 300, true)">Edit</a>';

        // file link
        if (preg_match('@(video|audio|image)/.+@i', $biblio_attach_d['mime_type'])) {
            $file = '<a href="#" onclick="parent.openHTMLpop(\''.SENAYAN_WEB_ROOT_DIR.'index.php?p=multimediastream&fid='.$biblio_attach_d['file_id'].'&bid='.$biblio_attach_d['biblio_id'].'\', 400, 300, \''.$biblio_attach_d['file_title'].'\')">'.$biblio_attach_d['file_title'].'</a>';
        } else {
            $file = '<a href="'.SENAYAN_WEB_ROOT_DIR.'admin/view.php?fid='.urlencode($biblio_attach_d['file_id']).'" target="_blank">'.$biblio_attach_d['file_title'].'</a>';
        }

        $table->appendTableRow(array($remove_link, $edit_link, $file, $biblio_attach_d['file_desc']));
        $table->setCellAttr($row, 0, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: 5%;"');
        $table->setCellAttr($row, 1, 'valign="top" class="'.$row_class.'" style="font-weight: bold; width: 5%;"');
        $table->setCellAttr($row, 2, 'valign="top" class="'.$row_class.'" style="width: 40%;"');
        $table->setCellAttr($row, 3, 'valign="top" class="'.$row_class.'" style="width: 50%;"');

        $row++;
    }
    echo $table->printTable();
    // hidden form
    echo '<form name="hiddenActionForm" method="post" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="bid" value="0" /><input type="hidden" name="remove" value="0" /><input type="hidden" name="alsoDeleteFile" value="0" /></form>';
} else {
    if ($_SESSION['biblioAttach']) {
        $table = new simbio_table();
        $table->table_attr = 'align="center" style="width: 100%;" cellpadding="2" cellspacing="0"';

        $row = 1;
        $row_class = 'alterCell2';
        foreach ($_SESSION['biblioAttach'] as $idx=>$biblio_session) {
            // remove link
            $remove_link = '<a href="iframe_attach.php?removesess='.$idx.'" style="color: #000000; text-decoration: underline;">Remove</a>';

            $table->appendTableRow(array($remove_link, $biblio_session['file_name'], $biblio_session['last_update']));
            $table->setCellAttr($row, 0, 'valign="top" class="'.$row_class.'" style="font-weight: bold; background-color: #ffc466; width: 10%;"');
            $table->setCellAttr($row, 1, 'valign="top" class="'.$row_class.'" style="background-color: #ffc466; width: 60%;"');
            $table->setCellAttr($row, 2, 'valign="top" class="'.$row_class.'" style="background-color: #ffc466; width: 30%;"');

            $row++;
        }
        echo $table->printTable();
    }
}
/* main content end */
$content = ob_get_clean();
// include the page template
require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
?>
