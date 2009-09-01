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

/* Loan Class Report */

// main system configuration
require '../../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_no_privilage.'</div>');
}

require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/datagrid/simbio_datagrid.inc.php';
require MODULES_BASE_DIR.'reporting/report_datagrid.inc.php';

$page_title = 'Loan Report by Class Report';
$reportView = false;
if (isset($_GET['reportView'])) {
    $reportView = true;
}

if (!$reportView) {
?>
    <!-- filter -->
    <fieldset style="margin-bottom: 3px;">
    <legend style="font-weight: bold"><?php echo strtoupper(lang_mod_report_other_loansclass); ?> - <?php echo lang_mod_reporting_form_generic_header; ?></legend>
    <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" target="reportView">
    <div id="filterForm">
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_biblio_field_class; ?>:</div>
            <div class="divRowContent">
            <?php
            $class_options[] = array('0', lang_mod_report_loansclass_form_opt_class0);
            $class_options[] = array('1', lang_mod_report_loansclass_form_opt_class1);
            $class_options[] = array('2', lang_mod_report_loansclass_form_opt_class2);
            $class_options[] = array('2X', lang_mod_report_loansclass_form_opt_class2x);
            $class_options[] = array('3', lang_mod_report_loansclass_form_opt_class3);
            $class_options[] = array('4', lang_mod_report_loansclass_form_opt_class4);
            $class_options[] = array('5', lang_mod_report_loansclass_form_opt_class5);
            $class_options[] = array('6', lang_mod_report_loansclass_form_opt_class6);
            $class_options[] = array('7', lang_mod_report_loansclass_form_opt_class7);
            $class_options[] = array('8', lang_mod_report_loansclass_form_opt_class8);
            $class_options[] = array('9', lang_mod_report_loansclass_form_opt_class9);
            $class_options[] = array('NONDECIMAL', lang_mod_report_loansclass_form_opt_classx);
            echo simbio_form_element::selectList('class', $class_options);
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_mod_masterfile_colltype_form_field_colltype; ?></div>
            <div class="divRowContent">
            <?php
            $coll_type_q = $dbs->query('SELECT coll_type_id, coll_type_name FROM mst_coll_type');
            $coll_type_options = array();
            $coll_type_options[] = array('0', lang_sys_common_all);
            while ($coll_type_d = $coll_type_q->fetch_row()) {
                $coll_type_options[] = array($coll_type_d[0], $coll_type_d[1]);
            }
            echo simbio_form_element::selectList('collType', $coll_type_options);
            ?>
            </div>
        </div>
        <div class="divRow">
            <div class="divRowLabel"><?php echo lang_sys_common_year; ?></div>
            <div class="divRowContent">
            <?php
            $current_year = date('Y');
            $year_options = array();
            for ($y = $current_year; $y > 1999; $y--) {
                $year_options[] = array($y, $y);
            }
            echo simbio_form_element::selectList('year', $year_options, $current_year-1);
            ?>
            </div>
        </div>
    </div>
    <div style="padding-top: 10px; clear: both;">
    <input type="submit" name="applyFilter" value="<?php echo lang_mod_reporting_form_button_filter_apply; ?>" />
    <input type="hidden" name="reportView" value="true" />
    </div>
    </form>
    </fieldset>
    <!-- filter end -->
    <div class="dataListHeader" style="height: 35px;">
    <input type="button" value="<?php echo lang_mod_reporting_form_button_print; ?>" style="margin-top: 9px; margin-left: 5px; margin-right: 5px;" onclick="javascript: reportView.print();" />
    &nbsp;<span id="pagingBox">&nbsp;</span></div>
    <iframe name="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?reportView=true'; ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
<?php
} else {
    ob_start();
    // months array
    $months['01'] = lang_sys_common_month_short_01;
    $months['02'] = lang_sys_common_month_short_02;
    $months['03'] = lang_sys_common_month_short_03;
    $months['04'] = lang_sys_common_month_short_04;
    $months['05'] = lang_sys_common_month_short_05;
    $months['06'] = lang_sys_common_month_short_06;
    $months['07'] = lang_sys_common_month_short_07;
    $months['08'] = lang_sys_common_month_short_08;
    $months['09'] = lang_sys_common_month_short_09;
    $months['10'] = lang_sys_common_month_short_10;
    $months['11'] = lang_sys_common_month_short_11;
    $months['12'] = lang_sys_common_month_short_12;

    // table start
    $row_class = 'alterCellPrinted';
    $output = '<table align="center" class="border" style="width: 100%;" cellpadding="3" cellspacing="0">';

    // header
    $output .= '<tr>';
    $output .= '<td class="dataListHeaderPrinted">'.lang_mod_biblio_field_class.'</td>';
    foreach ($months as $month) {
        $output .= '<td class="dataListHeaderPrinted">'.$month.'</td>';
    }
    $output .= '</tr>';

    // class
    $class_num = isset($_GET['class'])?trim($_GET['class']):'0';
    // year
    $selected_year = date('Y')-1;
    if (isset($_GET['year']) AND !empty($_GET['year'])) {
        $selected_year = (integer)$_GET['year'];
    }
    // collection type
    $coll_type = null;
    if (isset($_GET['collType'])) {
        $coll_type = (integer)$_GET['collType'];
        $coll_type_q = $dbs->query('SELECT coll_type_name FROM mst_coll_type
            WHERE coll_type_id='.$coll_type);
        $coll_type_d = $coll_type_q->fetch_row();
        $coll_type_name = $coll_type_d[0];
    }

    $row_class = ($class_num%2 == 0)?'alterCellPrinted':'alterCellPrinted2';
    if ($class_num == 'NONDECIMAL') {
        $output .= '<tr><td class="'.$row_class.'"><strong style="font-size: 1.5em;">NON DECIMAL Classification</strong></td>';
    } else {
        $output .= '<tr><td class="'.$row_class.'"><strong style="font-size: 1.5em;">'.$class_num.'00</strong></td>';

        // count loan each month
        foreach ($months as $month_num => $month) {
            $loan_q = $dbs->query("SELECT COUNT(*) FROM biblio AS b
                LEFT JOIN item AS i ON b.biblio_id=i.biblio_id
                LEFT JOIN loan AS l ON l.item_code=i.item_code
                WHERE TRIM(classification) LIKE '$class_num%' AND l.loan_date LIKE '$selected_year-$month_num-%'".( !empty($coll_type)?" AND i.coll_type_id=$coll_type":'' ));
            $loan_d = $loan_q->fetch_row();
            if ($loan_d[0] > 0) {
                $output .= '<td class="'.$row_class.'"><strong style="font-size: 1.5em;">'.$loan_d[0].'</strong></td>';
            } else {
                $output .= '<td class="'.$row_class.'"><span style="color: #ff0000;">'.$loan_d[0].'</span></td>';
            }
        }

        $output .= '</tr>';

        $class_num2 = 0;
        // 2nd subclasses
        while ($class_num2 < 10) {
            $row_class = ($row_class == 'alterCellPrinted')?'alterCellPrinted2':'alterCellPrinted';

            $output .= '<tr><td class="'.$row_class.'"><strong>&nbsp;&nbsp;&nbsp;'.$class_num.$class_num2.'0</strong></td>';
            // count loan each month
            foreach ($months as $month_num => $month) {
                $loan_q = $dbs->query("SELECT COUNT(*) FROM biblio AS b
                    LEFT JOIN item AS i ON b.biblio_id=i.biblio_id
                    LEFT JOIN loan AS l ON l.item_code=i.item_code
                    WHERE TRIM(classification) LIKE '$class_num"."$class_num2%' AND l.loan_date LIKE '$selected_year-$month_num-%'".( !empty($coll_type)?" AND i.coll_type_id=$coll_type":'' ));
                $loan_d = $loan_q->fetch_row();
                if ($loan_d[0] > 0) {
                    $output .= '<td class="'.$row_class.'"><strong style="font-size: 1.5em;">'.$loan_d[0].'</strong></td>';
                } else {
                    $output .= '<td class="'.$row_class.'"><span style="color: #ff0000;">'.$loan_d[0].'</span></td>';
                }
            }

            $output .= '</tr>';
            $class_num2++;
        }
    }
    $output .= '</table>';

    // print out
    echo '<div class="printPageInfo">Loan Recap By Class <strong>'.$class_num.'</strong> for year <strong>'.$selected_year.'</strong>'.( isset($coll_type_name)?'<div>'.$coll_type_name.'</div>':'' ).'</div>'."\n";
    echo $output;

    $content = ob_get_clean();
    // include the page template
    require SENAYAN_BASE_DIR.'/admin/'.$sysconf['admin_template']['dir'].'/notemplate_page_tpl.php';
}
?>
