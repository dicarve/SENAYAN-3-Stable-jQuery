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

/* Serial Control Management section */

if (!defined('SENAYAN_BASE_DIR')) {
    // main system configuration
    require '../../../sysconfig.inc.php';
    // start the session
    require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
}

require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging_ajax.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_datagrid.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';
require SIMBIO_BASE_DIR.'simbio_FILE/simbio_file_upload.inc.php';

// privileges checking
$can_read = utility::havePrivilege('serial_control', 'r');
$can_write = utility::havePrivilege('serial_control', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner serialIcon">
    <?php echo strtoupper(__('Serial Control')); ?>
    <hr />
    <form name="search" action="blank.html" target="blindSubmit" onsubmit="$('doSearch').click();" id="search" method="get" style="display: inline;"><?php echo __('Search'); ?> :
    <input type="text" name="keywords" id="keywords" size="30" />
    <select name="field"><option value="0"><?php echo __('ALL'); ?></option><option value="title"><?php echo __('Title'); ?></option><option value="topic"><?php echo __('Subject(s)'); ?></option><option value="author_name"><?php echo __('Author(s)'); ?></option><option value="isbn_issn"><?php echo __('ISBN/ISSN'); ?></option></select>
    <input type="button" id="doSearch" onclick="setContent('mainContent', '<?php echo MODULES_WEB_ROOT_DIR; ?>serial_control/index.php?' + $('search').serialize(), 'post')" value="<?php echo __('Search'); ?>" class="button" />
    </form>
</div>
</fieldset>
<script type="text/javascript">
// focus text field
$('keywords').focus();
</script>
<?php
/* search form end */

/* main content */
/* SERIAL SUBSCRIPTION LIST */
// callback function
$count = 1;
function subscriptionDetail($obj_db, $array_data)
{
    global $can_read, $can_write, $count;
    $_output = '<div style="float: left;"><strong style="font-size: 120%;"><a href="#" title="Edit Bibliographic Data" onclick="openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_biblio.php?action=detail&inPopUp=true&itemID='.$array_data[0].'&itemCollID=0\', \'popSerialBiblio\', 600, 400, true)">'.$array_data[1].'</a></strong> ('.$array_data[2].')</div>';
    if ($can_read AND $can_write) {
        $_output .= ' <a href="#" class="addSubscription" onclick="javascript: $(\'subscriptionListCont'.$count.'\').show(); setIframeContent(\'subscriptionList'.$count.'\', \''.MODULES_WEB_ROOT_DIR.'serial_control/subscription.php?biblioID='.$array_data[0].'&action=detail\');" title="'.__('Add New Subscription').'">&nbsp;</a> ';
    }
    $_output .= ' <a href="#" class="viewSubscription" onclick="$(\'subscriptionListCont'.$count.'\').show(); setIframeContent(\'subscriptionList'.$count.'\', \''.MODULES_WEB_ROOT_DIR.'serial_control/subscription.php?biblioID='.$array_data[0].'\');" title="'.__('View Subscriptions').'">&nbsp;</a> ';
    $_output .= '<div id="subscriptionListCont'.$count.'" style="clear: both; display: none;">';
    $_output .= '<div><a href="#" style="font-weight: bold; color: red;" title="Close Box" onclick="$(\'subscriptionListCont'.$count.'\').hide()">'.__('CLOSE').'</a></div>';
    $_output .= '<iframe id="subscriptionList'.$count.'" src="'.MODULES_WEB_ROOT_DIR.'serial_control/subscription.php?biblioID='.$array_data[0].'" style="width: 100%; height: 270px;"></iframe>';
    $_output .= '</div>';
    $count++;
    return $_output;
}
// create datagrid
$datagrid = new simbio_datagrid();
$datagrid->setSQLColumn('b.biblio_id', 'b.title AS \''.__('Serial Title').'\'',
    'fr.frequency AS \'Frequency\'');
$datagrid->invisible_fields = array(0, 2);
$datagrid->modifyColumnContent(1, 'callback{subscriptionDetail}');
$datagrid->setSQLorder('b.last_update DESC');
// table alias and field relation
$tables['bsub'] = array('title', 'isbn_issn');
$tables['mt'] = array('topic');
if (isset($_GET['field']) AND !empty($_GET['field'])) {
    foreach ($tables as $table_alias=>$fields) {
        if (!in_array($_GET['field'], $fields)) {
            // remove unneeded array
            unset($tables[$table_alias]);
        }
    }
    // check if fields array is empty to prevent SQL error
    if (!$tables) {
        $tables['bsub'] = array('title', 'isbn_issn');
        $tables['mt'] = array('topic');
    }
}
// set default criteria
$criteria = 'bsub.frequency_id>0';
// is there any search
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $keyword = $dbs->escape_string(trim($_GET['keywords']));
    $words = explode(' ', $keyword);
    if (count($words) > 1) {
        $concat_sql = ' (';
        foreach ($words as $word) {
            $concat_sql .= '(';
            foreach ($tables as $table_alias => $fields) {
                foreach ($fields as $field) {
                    $concat_sql .= $table_alias.'.'.$field." LIKE '%$word%' OR ";
                }
            }
            // remove the last OR
            $concat_sql = substr_replace($concat_sql, '', -4);
            $concat_sql .= ') AND';
        }
        // remove the last AND
        $concat_sql = substr_replace($concat_sql, '', -3);
        $concat_sql .= ') ';
        $criteria = $concat_sql;
    } else {
        $concat_sql = '';
        foreach ($tables as $table_alias => $fields) {
            foreach ($fields as $field) {
                $concat_sql .= $table_alias.'.'.$field." LIKE '%$keyword%' OR ";
            }
        }
        // remove the last OR
        $concat_sql = substr_replace($concat_sql, '', -4);
        $criteria = $concat_sql;
    }
}
// subquery/view string
$subquery_str = '(SELECT DISTINCT bsub.biblio_id, bsub.title, bsub.frequency_id, bsub.last_update FROM biblio AS bsub
    LEFT JOIN biblio_topic AS bt ON bsub.biblio_id = bt.biblio_id
    LEFT JOIN mst_topic AS mt ON bt.topic_id = mt.topic_id WHERE '.$criteria.')';
// table spec
$table_spec = $subquery_str.' AS b
    LEFT JOIN mst_frequency AS fr ON b.frequency_id=fr.frequency_id';
// set table and table header attributes
$datagrid->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// put the result into variables
$datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, false);
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords')); //mfc
    echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"</div>';
}
echo $datagrid_result;
/* main content end */
?>
