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
/* Loan History By Members */

// main system configuration
require '../../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('circulation', 'r');
$can_write = utility::havePrivilege('circulation', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_no_privilage.'</div>');
}

require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_datagrid.inc.php';
require MODULES_BASE_DIR.'reporting/report_datagrid.inc.php';

$page_title = 'Loan History Report';
$reportView = false;
$num_recs_show = 20;
if (isset($_GET['reportView'])) {
    $reportView = true;
}

if (!$reportView) {
?>
    <!-- filter -->
    <fieldset style="margin-bottom: 3px;">
    <legend style="font-weight: bold"><?php echo strtoupper(lang_mod_circ_transaction_history); ?> - <?php echo lang_mod_reporting_form_generic_header; ?></legend>
    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">
    <div id="filterForm">
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_membership_field_member_id.'/'.lang_mod_membership_field_name; ?></div>
            <div class="divRowContent">
            <?php
            echo simbio_form_element::textField('text', 'id_name', '', 'style="width: 50%"');
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_biblio_field_title; ?></div>
            <div class="divRowContent">
            <?php
            echo simbio_form_element::textField('text', 'title', '', 'style="width: 50%"');
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_biblio_item_field_itemcode; ?></div>
            <div class="divRowContent">
            <?php
            echo simbio_form_element::textField('text', 'itemCode', '', 'style="width: 50%"');
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_report_loanmember_form_loanfrom; ?></div>
            <div class="divRowContent">
            <?php
            echo simbio_form_element::dateField('startDate', '2000-01-01');
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_report_loanmember_form_loanto; ?></div>
            <div class="divRowContent">
            <?php
            echo simbio_form_element::dateField('untilDate', date('Y-m-d'));
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_biblio_item_common_status_description; ?></div>
            <div class="divRowContent">
            <select name="loanStatus"><option value="ALL"><?php echo lang_sys_common_all; ?></option><option value="0"><?php echo lang_mod_biblio_item_common_status_onloan; ?></option><option value="1"><?php echo lang_mod_biblio_item_common_status_returned; ?></option></select>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_report_common_form_recspage; ?></div>
            <div class="divRowContent"><input type="text" name="recsEachPage" size="3" maxlength="3" value="<?php echo $num_recs_show; ?>" /> <?php echo lang_mod_report_common_form_recspage_help; ?></div>
        </div>
    </div>
    <div style="padding-top: 10px; clear: both;">
    <input type="submit" name="applyFilter" value="<?php echo lang_mod_reporting_form_button_filter_apply; ?>" />
    <input type="button" name="moreFilter" value="<?php echo lang_mod_reporting_form_button_filter_options_show; ?>" onclick="showHideTableRows('filterForm', 1, this, '<?php echo lang_mod_reporting_form_button_filter_options_show; ?>', '<?php echo lang_mod_reporting_form_button_filter_options_hide; ?>')" />
    <input type="hidden" name="reportView" value="true" />
    </div>
    </form>
    </fieldset>
    <script type="text/javascript">hideRows('filterForm', 1);</script>
    <!-- filter end -->
    <div class="dataListHeader" style="height: 35px;">
    <input type="button" value="<?php echo lang_mod_reporting_form_button_print; ?>" style="margin-top: 9px; margin-left: 5px; margin-right: 5px;"
    onclick="javascript: reportView.print();" />
    &nbsp;<span id="pagingBox">&nbsp;</span></div>
    <iframe name="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true'; ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
<?php
} else {
    ob_start();
    // table spec
    $table_spec = 'loan AS l
    LEFT JOIN member AS m ON l.member_id=m.member_id
    LEFT JOIN item AS i ON l.item_code=i.item_code
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id';

    // create datagrid
    $reportgrid = new report_datagrid();
    $reportgrid->setSQLColumn('m.member_id AS \''.lang_mod_membership_field_member_id.'\'',
        'm.member_name AS \''.lang_mod_membership_field_name.'\'',
        'l.item_code AS \''.lang_mod_biblio_item_field_itemcode.'\'',
        'b.title AS \''.lang_mod_biblio_field_title.'\'',
        'l.loan_date AS \''.lang_mod_circ_tblheader_loan_date.'\'',
        'l.due_date AS \''.lang_mod_circ_tblheader_due_date.'\'', 'l.is_return AS \''.lang_mod_biblio_item_common_status_description.'\'');
    $reportgrid->setSQLorder('l.loan_date DESC');

    $criteria = 'm.member_id IS NOT NULL ';
    if (isset($_GET['id_name']) AND !empty($_GET['id_name'])) {
        $id_name = $dbs->escape_string($_GET['id_name']);
        $criteria .= ' AND (m.member_id LIKE \'%'.$id_name.'%\' OR m.member_name LIKE \'%'.$id_name.'%\')';
    }
    if (isset($_GET['title']) AND !empty($_GET['title'])) {
        $keyword = $dbs->escape_string(trim($_GET['title']));
        $words = explode(' ', $keyword);
        if (count($words) > 1) {
            $concat_sql = ' AND (';
            foreach ($words as $word) {
                $concat_sql .= " (b.title LIKE '%$word%') AND";
            }
            // remove the last AND
            $concat_sql = substr_replace($concat_sql, '', -3);
            $concat_sql .= ') ';
            $criteria .= $concat_sql;
        } else {
            $criteria .= ' AND b.title LIKE \'%'.$keyword.'%\'';
        }
    }
    if (isset($_GET['itemCode']) AND !empty($_GET['itemCode'])) {
        $item_code = $dbs->escape_string(trim($_GET['itemCode']));
        $criteria .= ' AND i.item_code=\''.$item_code.'\'';
    }
    // loan date
    if (isset($_GET['startDate']) AND isset($_GET['untilDate'])) {
        $criteria .= ' AND (TO_DAYS(l.loan_date) BETWEEN TO_DAYS(\''.$_GET['startDate'].'\') AND
            TO_DAYS(\''.$_GET['untilDate'].'\'))';
    }
    // loan status
    if (isset($_GET['loanStatus']) AND $_GET['loanStatus'] != 'ALL') {
        $loanStatus = (integer)$_GET['loanStatus'];
        $criteria .= ' AND is_return='.$loanStatus;
    }
    if (isset($_GET['recsEachPage'])) {
        $recsEachPage = (integer)$_GET['recsEachPage'];
        $num_recs_show = ($recsEachPage >= 20 && $recsEachPage <= 200)?$recsEachPage:$num_recs_show;
    }
    $reportgrid->setSQLCriteria($criteria);

   // callback function to show title and authors
    function loanStatus($obj_db, $array_data)
    {
        if ($array_data[6] == 0) {
            return '<strong>'.lang_mod_biblio_item_common_status_onloan.'</strong>';
        } else {
            return lang_mod_biblio_item_common_status_returned;
        }
    }
    // modify column value
    $reportgrid->modifyColumnContent(6, 'callback{loanStatus}');


    // set table and table header attributes
    $reportgrid->table_attr = 'align="center" id="dataListPrinted" cellpadding="3" cellspacing="1"';
    $reportgrid->table_header_attr = 'class="dataListHeaderPrinted"';

    // put the result into variables
    echo $reportgrid->createDataGrid($dbs, $table_spec, $num_recs_show);

    echo '<script type="text/javascript">'."\n";
    echo 'parent.$(\'pagingBox\').update(\''.str_replace(array("\n", "\r", "\t"), '', $reportgrid->paging_set).'\');'."\n";
    echo '</script>';

    $content = ob_get_clean();
    // include the page template
    require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
}
?>


