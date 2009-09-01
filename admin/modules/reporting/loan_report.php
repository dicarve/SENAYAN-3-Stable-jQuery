<?php
/**
 * Library loan general report
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

/* Reporting section */

// main system configuration
require '../../../sysconfig.inc.php';
// start the session
require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';

// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

/* loan report */
$table = new simbio_table();
$table->table_attr = 'align="center" class="border" cellpadding="5" cellspacing="0"';

// total number of loan transaction
$report_query = $dbs->query('SELECT COUNT(loan_id) FROM loan');
$report_data = $report_query->fetch_row();
$loan_report[__('Total Loan')] = $report_data[0];

// total number of loan transaction by GMD/medium
$report_query = $dbs->query('SELECT gmd_name, COUNT(loan_id) FROM loan AS l
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    INNER JOIN mst_gmd AS gmd ON b.gmd_id=gmd.gmd_id
    GROUP BY b.gmd_id ORDER BY COUNT(loan_id) DESC');
$report_data = '';
while ($data = $report_query->fetch_row()) {
    $report_data .= '<strong>'.$data[0].'</strong> : '.$data[1].', ';
}
$loan_report[__('Total Loan By GMD/Medium')] = $report_data;

// total number of loan transaction by Collection type
$report_query = $dbs->query('SELECT coll_type_name, COUNT(loan_id) FROM loan AS l
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN mst_coll_type AS ct ON i.coll_type_id=ct.coll_type_id
    GROUP BY i.coll_type_id ORDER BY COUNT(loan_id) DESC');
$report_data = '';
while ($data = $report_query->fetch_row()) {
    $report_data .= '<strong>'.$data[0].'</strong> : '.$data[1].', ';
}
$loan_report[__('Total Loan By Collection Type')] = $report_data;

// total number of loan transaction
$report_query = $dbs->query('SELECT COUNT(loan_id)
    FROM loan
    GROUP BY member_id, loan_date
    ORDER BY `COUNT(loan_id)` DESC');
$report_data = $report_query->num_rows;
$loan_report[__('Total Loan Transactions')] = $report_data;
$peak_transaction_data = $report_query->fetch_row();

// transaction average per day
$total_loan_days_query = $dbs->query('SELECT DISTINCT loan_date FROM loan');
$total_loan_days = $total_loan_days_query->num_rows;
$loan_report[__('Transaction Average (Per Day)')] = @ceil($loan_report[__('Total Loan Transactions')]/$total_loan_days);

// peak transaction
$loan_report[__('Total Peak Transaction')] = $peak_transaction_data[0];

// total members having loans
$report_query = $dbs->query('SELECT DISTINCT member_id FROM loan');
$report_data = $report_query->num_rows;
$loan_report[__('Members Already Had Loans')] = $report_data;

// total members having loans
// get total member that already not expired
$total_members_query = $dbs->query('SELECT COUNT(member_id) FROM member
    WHERE TO_DAYS(expire_date)>TO_DAYS(\''.date('Y-m-d').'\')');
$total_members_data = $total_members_query->fetch_row();
$loan_report[__('Members Never Have Loans Yet')] = $total_members_data[0]-$loan_report[__('Members Already Had Loans')];

// total overdued loand
$report_query = $dbs->query('SELECT COUNT(loan_id) FROM loan WHERE
    is_lent=1 AND is_return=0 AND TO_DAYS(due_date)>TO_DAYS(\''.date('Y-m-d').'\')');
$report_data = $report_query->fetch_row();
$loan_report[__('Total Overdued Loans')] = $report_data[0];

// table header
$table->setHeader(array(__('Loan Data Summary')));
$table->table_header_attr = 'class="dataListHeader" colspan="3"';
// initial row count
$row = 1;
foreach ($loan_report as $headings=>$report_data) {
    $table->appendTableRow(array($headings, ':', $report_data));
    // set cell attribute
    $table->setCellAttr($row, 0, 'class="alterCell" valign="top" style="width: 170px;"');
    $table->setCellAttr($row, 1, 'class="alterCell" valign="top" style="width: 1%;"');
    $table->setCellAttr($row, 2, 'class="alterCell2" valign="top" style="width: auto;"');
    // add row count
    $row++;
}

// if we are in print mode
if (isset($_GET['print'])) {
    // html strings
    $html_str = '<html><head><title>'.$sysconf['library_name'].' Membership General Statistic Report</title>';
    $html_str .= '<style type="text/css">'."\n";
    $html_str .= 'body {padding: 0.2cm}'."\n";
    $html_str .= 'body * {color: black; font-size: 11pt;}'."\n";
    $html_str .= 'table {border: 1px solid #000000;}'."\n";
    $html_str .= '.dataListHeader {background-color: #000000; color: white; font-weight: bold;}'."\n";
    $html_str .= '.alterCell {border-bottom: 1px solid #666666; background-color: #CCCCCC;}'."\n";
    $html_str .= '.alterCell2 {border-bottom: 1px solid #666666; background-color: #FFFFFF;}'."\n";
    $html_str .= '</style>'."\n";
    $html_str .= '</head>';
    $html_str .= '<body>'."\n";
    $html_str .= '<h3>'.$sysconf['library_name'].' - '.__('Loan Report').'</h3>';
    $html_str .= '<hr size="1" />';
    $html_str .= $table->printTable();
    $html_str .= '<script type="text/javascript">self.print();</script>'."\n";
    $html_str .= '</body></html>';
    // write to file
    $file_write = @file_put_contents(REPORT_FILE_BASE_DIR.'loan_stat_print_result.html', $html_str);
    if ($file_write) {
        // open result in new window
        echo '<script type="text/javascript">parent.openWin(\''.SENAYAN_WEB_ROOT_DIR.'/'.FILES_DIR.'/'.REPORT_DIR.'/loan_stat_print_result.html\', \'popMemberReport\', 800, 500, true)</script>';
    } else { utility::jsAlert('ERROR! Loan statistic report failed to generate, possibly because '.REPORT_FILE_BASE_DIR.' directory is not writable'); }
    exit();
}

?>
<fieldset class="menuBox">
<div class="menuBoxInner statisticIcon">
    <?php echo strtoupper(__('Loan Report')); ?>
    <hr />
    <form name="printForm" action="<?php echo $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']; ?>" target="submitPrint" id="printForm" method="get" style="display: inline;">
    <input type="hidden" name="print" value="true" /><input type="submit" value="<?php echo __('Download Report'); ?>" class="button" />
    </form>
    <iframe name="submitPrint" style="visibility: hidden; width: 0; height: 0;"></iframe>
</div>
</fieldset>
<?php
echo $table->printTable();
/* loan report end */
?>
