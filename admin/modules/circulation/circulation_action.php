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

/* circulation transaction process */

if (!defined('DIRECT_INCLUDE')) {
    // main system configuration
    require '../../../sysconfig.inc.php';
    // start the session
    require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
}
require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';
require SIMBIO_BASE_DIR.'simbio_UTILS/simbio_date.inc.php';
require MODULES_BASE_DIR.'membership/member_base_lib.inc.php';
require MODULES_BASE_DIR.'circulation/circulation_base_lib.inc.php';

// transaction is finished
if (isset($_POST['finish'])) {
    // create circulation object
    $memberID = $_SESSION['memberID'];
    $circulation = new circulation($dbs, $memberID);
    // finish loan transaction
    $flush = $circulation->finishLoanSession();
    if ($flush == TRANS_FLUSH_ERROR) {
        // write log
        utility::writeLogs($dbs, 'member', $memberID, 'circulation', 'ERROR : '.$_SESSION['realname'].' FAILED finish circulation transaction with member ('.$memberID.')');
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_common_loan_not_saved.'\');';
        echo '</script>';
    } else {
        // write log
        utility::writeLogs($dbs, 'member', $memberID, 'circulation', $_SESSION['realname'].' finish circulation transaction with member ('.$memberID.')');
        // send message
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_common_finished_loan_confirmation.'\');';
        echo 'parent.$(\'#mainContent\').simbioAJAX(\''.MODULES_WEB_ROOT_DIR.'circulation/index.php\', \'post\', \'finishID='.$memberID.'\');';
        echo '</script>';
    }
    exit();
}


// return and extend process
if (isset($_POST['process']) AND isset($_POST['loanID'])) {
    $loanID = intval($_POST['loanID']);
    // get loan data
    $loan_q = $dbs->query('SELECT item_code FROM loan WHERE loan_id='.$loanID);
    $loan_d = $loan_q->fetch_row();
    // create circulation object
    $circulation = new circulation($dbs, $_SESSION['memberID']);
    if ($_POST['process'] == 'return') {
        $return_status = $circulation->returnItem($loanID);
        // write log
        utility::writeLogs($dbs, 'member', $_SESSION['memberID'], 'circulation', $_SESSION['realname'].' return item '.$loan_d[0].' for member ('.$_SESSION['memberID'].')');
        echo '<script type="text/javascript">';
        if ($circulation->loan_have_overdue) {
            echo "\n".'alert(\''.lang_mod_circ_common_fines_inserted.'\');'."\n";
        }
        if ($return_status === ITEM_RESERVED) {
            echo 'location.href = \'loan_list.php?reserveAlert='.urlencode($loan_d[0]).'\';';
        } else { echo 'location.href = \'loan_list.php\';'; }
        echo '</script>';
    } else {
        // set holiday settings
        $circulation->holiday_dayname = $_SESSION['holiday_dayname'];
        $circulation->holiday_date = $_SESSION['holiday_date'];
        $extend_status = $circulation->extendItemLoan($loanID);
        if ($extend_status === ITEM_RESERVED) {
            echo '<script type="text/javascript">';
            echo 'alert(\''.lang_mod_circ_extend_noextend_confirmation.'\');';
            echo 'location.href = \'loan_list.php\';';
            echo '</script>';
        } else {
            // write log
            utility::writeLogs($dbs, 'member', $_SESSION['memberID'], 'circulation', $_SESSION['realname'].' extend loan for item '.$loan_d[0].' for member ('.$_SESSION['memberID'].')');
            echo '<script type="text/javascript">';
            echo 'alert(\''.lang_mod_circ_common_alert_extended_success.'\');';
            if ($circulation->loan_have_overdue) {
                echo "\n".'alert(\''.lang_mod_circ_common_fines_inserted.'\');'."\n";
            }
            echo 'location.href = \'loan_list.php\';';
            echo '</script>';
        }
    }
    exit();
}


// add temporary item to session
if (isset($_POST['tempLoanID'])) {
    // create circulation object
    $circulation = new circulation($dbs, $_SESSION['memberID']);
    // set holiday settings
    $circulation->holiday_dayname = $_SESSION['holiday_dayname'];
    $circulation->holiday_date = $_SESSION['holiday_date'];
    // add item to loan session
    $add = $circulation->addLoanSession($_POST['tempLoanID']);
    if ($add == LOAN_LIMIT_REACHED) {
        echo '<html>';
        echo '<body>';
        if ($sysconf['loan_limit_override']) {
            // hidden form holding item code
            echo '<form method="post" name="overrideForm" action="'.MODULES_WEB_ROOT_DIR.'circulation/circulation_action.php"><input type="hidden" name="overrideID" value="'.$_POST['tempLoanID'].'" /></form>';
            echo '<script type="text/javascript">';
            echo 'var confOverride = confirm(\''.lang_mod_circ_common_alert_error_limit_reach.'\' + "\n" + \''.lang_mod_circ_common_overide_confirmation.'\');';
            echo 'if (confOverride) { ';
            echo 'document.overrideForm.submit();';
            echo '} else { self.location.href = \'loan.php\';}';
            echo '</script>';
        } else {
            echo '<script type="text/javascript">';
            echo 'alert(\''.lang_mod_circ_common_alert_error_limit_reach.'\');';
            echo 'location.href = \'loan.php\';';
            echo '</script>';
        }
        echo '</body>';
        echo '</html>';
        exit();
    } else if ($add == ITEM_RESERVED) {
        // hidden form holding item code
        echo '<html>';
        echo '<body>';
        echo '<form method="post" name="overrideForm" action="'.MODULES_WEB_ROOT_DIR.'circulation/circulation_action.php">';
        echo '<input type="hidden" name="overrideID" value="'.$_POST['tempLoanID'].'" /></form>';
        echo '<script type="text/javascript">';
        echo 'var confOverride = confirm(\''.lang_mod_circ_alert_on_resereved.'\' + "\n" + \''.lang_mod_circ_common_overide_confirmation.'\');';
        echo 'if (confOverride) { ';
        echo 'document.overrideForm.submit();';
        echo '} else { self.location.href = \'loan.php\';}';
        echo '</script>';
        echo '</body>';
        echo '</html>';
        exit();
    } else if ($add == ITEM_NOT_FOUND) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_alert_item_not_registered.'\');';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    } else if ($add == ITEM_UNAVAILABLE) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_alert_item_not_available.'\');';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    } else if ($add == LOAN_NOT_PERMITTED) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_alert_member_expired.'\');';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    } else if ($add == LOAN_NOT_PERMITTED_PENDING) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_alert_member_pending.'\');';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    } else if ($add == ITEM_LOAN_FORBID) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_alert_not_for_loan.'\');';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    } else {
        echo '<script type="text/javascript">';
        echo 'location.href = \'loan.php\';';
        echo '</script>';
    }
    exit();
}


// loan limit override
if (isset($_POST['overrideID']) AND !empty($_POST['overrideID'])) {
    // define constant
    define('IGNORE_LOAN_RULES', 1);
    // create circulation object
    $circulation = new circulation($dbs, $_SESSION['memberID']);
    // set holiday settings
    $circulation->holiday_dayname = $_SESSION['holiday_dayname'];
    $circulation->holiday_date = $_SESSION['holiday_date'];
    // add item to loan session
    $add = $circulation->addLoanSession($_POST['overrideID']);
    echo '<script type="text/javascript">';
    echo 'location.href = \'loan.php\';';
    echo '</script>';
    exit();
}


// remove temporary item session
if (isset($_GET['removeID'])) {
    // create circulation object
    $circulation = new circulation($dbs, $_SESSION['memberID']);
    // remove item from loan session
    $circulation->removeLoanSession($_GET['removeID']);
    echo '<script type="text/javascript">';
    $msg = str_replace('{removeID}', $_GET['removeID'], lang_mod_circ_alert_item_remove_from_session);
    echo 'alert(\''.$msg.'\');';
    echo 'location.href = \'loan.php\';';
    echo '</script>';
    exit();
}


// quick return proccess
if (isset($_POST['quickReturnID']) AND $_POST['quickReturnID']) {
    // get loan data
    $loan_info_q = $dbs->query("SELECT l.*,m.member_id,m.member_name,b.title FROM loan AS l
        LEFT JOIN item AS i ON i.item_code=l.item_code
        LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
        LEFT JOIN member AS m ON l.member_id=m.member_id
        WHERE l.item_code='".$dbs->escape_string($_POST['quickReturnID'])."' AND is_lent=1 AND is_return=0");
    if ($loan_info_q->num_rows < 1) {
        echo '<div class="errorBox">'.lang_mod_circ_common_item_already_return.'</div>';
    } else {
        $return_date = date('Y-m-d');
        // get data
        $loan_d = $loan_info_q->fetch_assoc();
        // create circulation object
        $circulation = new circulation($dbs, $loan_d['member_id']);
        // check for overdue
        $overdue = $circulation->countOverdueValue($loan_d['loan_id'], $return_date);
        // check overdue
        if ($overdue) {
            $msg = str_replace('{overdueDays}', $overdue['days'],lang_mod_circ_common_return_overdue);
            $loan_d['title'] .= '<div style="color: red; font-weight: bold;">'.$msg.$overdue['value'].'</div>';
        }
        // return item
        $return_status = $circulation->returnItem($loan_d['loan_id']);
        if ($return_status === ITEM_RESERVED) {
            // get reservation data
            $reserve_q = $dbs->query('SELECT r.member_id, m.member_name
                FROM reserve AS r
                LEFT JOIN member AS m ON r.member_id=m.member_id
                WHERE item_code=\''.$loan_d['item_code'].'\' ORDER BY reserve_date DESC');
            $reserve_d = $reserve_q->fetch_row();
            $member = $reserve_d[1].' ('.$reserve_d[0].')';
            $reserve_msg = str_replace(array('{itemCode}', '{member}'), array($loan_d['item_code'], $member), lang_mod_circ_reserve_alert_after_return);
            $loan_d['title'] .= '<div>'.$reserve_msg.'</div>';
        }
        // write log
        utility::writeLogs($dbs, 'member', $loan_d['member_id'], 'circulation', $_SESSION['realname'].' return item ('.$_POST['quickReturnID'].') with title ('.$loan_d['title'].') with Quick Return method');
        // show loan information
        include SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
        // create table object
        $table = new simbio_table();
        $table->table_attr = 'class="border" style="width: 100%; margin-bottom: 5px;" cellpadding="5" cellspacing="0"';
        // append data to table row
        $table->appendTableRow(array('Item '.$_POST['quickReturnID'].lang_mod_circ_common_item_return_ok.$return_date));
        $table->appendTableRow(array(lang_mod_circ_tblheader_title, $loan_d['title']));
        $table->appendTableRow(array(lang_mod_circ_field_member_name, $loan_d['member_name'], lang_mod_circ_field_member_id, $loan_d['member_id']));
        $table->appendTableRow(array(lang_mod_circ_tblheader_loan_date, $loan_d['loan_date'], lang_mod_circ_tblheader_due_date, $loan_d['due_date']));
        // set the cell attributes
        $table->setCellAttr(1, null, 'class="dataListHeader" style="color: #FFFFFF; font-weight: bold;" colspan="4"');
        $table->setCellAttr(2, 0, 'class="alterCell"');
        $table->setCellAttr(2, 1, 'class="alterCell2" colspan="3"');
        $table->setCellAttr(3, 0, 'class="alterCell" width="15%"');
        $table->setCellAttr(3, 1, 'class="alterCell2" width="35%"');
        $table->setCellAttr(3, 2, 'class="alterCell" width="15%"');
        $table->setCellAttr(3, 3, 'class="alterCell2" width="35%"');
        $table->setCellAttr(4, 0, 'class="alterCell" width="15%"');
        $table->setCellAttr(4, 1, 'class="alterCell2" width="35%"');
        $table->setCellAttr(4, 2, 'class="alterCell" width="15%"');
        $table->setCellAttr(4, 3, 'class="alterCell2" width="35%"');
        // print out the table
        echo $table->printTable();
    }
    exit();
}


// add reservation
if (isset($_POST['reserveItemID'])) {
    $item_id = trim($_POST['reserveItemID']);
    if (!$item_id) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_reserve_alert_nod_data.'\');';
        echo 'location.href = \'reserve_list.php\';';
        echo '</script>';
        die();
    }
    // get reservation limit from member type
    $reserve_limit_q = $dbs->query('SELECT reserve_limit FROM mst_member_type WHERE member_type_id='.(integer)$_SESSION['memberTypeID']);
    $reserve_limit_d = $reserve_limit_q->fetch_row();
    // get current reservation data for this member
    $current_reserve_q = $dbs->query('SELECT COUNT(reserve_id) FROM reserve WHERE member_id=\''.trim($_SESSION['memberID']).'\'');
    $current_reserve_d = $current_reserve_q->fetch_row();
    if ($current_reserve_d[0] >= $reserve_limit_d[0]) {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_reserve_alert_reach_limit.'\');';
        echo 'location.href = \'reserve_list.php\';';
        echo '</script>';
        die();
    }

    // get biblio data for this item
    $biblio_q = $dbs->query('SELECT i.biblio_id, ist.rules FROM biblio AS b
        LEFT JOIN item AS i ON b.biblio_id=i.biblio_id
        LEFT JOIN mst_item_status AS ist ON i.item_status_id=ist.item_status_id
        WHERE i.item_code=\''.$dbs->escape_string($item_id).'\'');
    $biblio_d = $biblio_q->fetch_row();
    // check if this item is forbidden
    if (!empty($biblio_d[1])) {
        $arr_rules = @unserialize($biblio_d[1]);
        if ($arr_rules) {
            if (in_array(NO_LOAN_TRANSACTION, $arr_rules)) {
                echo '<script type="text/javascript">';
                echo 'alert(\''.lang_mod_circ_reserve_alert_forbidden.'\');';
                echo 'location.href = \'reserve_list.php\';';
                echo '</script>';
                die();
            }
        }
    }
    // get the availability status
    $avail_q = $dbs->query('SELECT COUNT(l.loan_id) FROM loan AS l
        WHERE l.item_code=\''.$item_id.'\' AND l.is_lent=1 AND l.is_return=0 AND l.member_id!=\''.$_SESSION['memberID'].'\'');
    $avail_d = $avail_q->fetch_row();
    if ($avail_d[0] > 0) {
        // write log
        utility::writeLogs($dbs, 'member', $_SESSION['memberID'], 'circulation', $_SESSION['realname'].' reserve item '.$item_id.' for member ('.$_SESSION['memberID'].')');
        // add reservation to database
        $reserve_date = date('Y-m-d H:i:s');
        $dbs->query('INSERT INTO reserve(member_id, biblio_id, item_code, reserve_date) VALUES (\''.$_SESSION['memberID'].'\', \''.$biblio_d[0].'\', \''.$item_id.'\', \''.$reserve_date.'\')');
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_reserve_alert_success.'\');';
        echo 'location.href = \'reserve_list.php\';';
        echo '</script>';
    } else {
        echo '<script type="text/javascript">';
        echo 'alert(\''.lang_mod_circ_reserve_alert_available.'\');';
        echo 'location.href = \'reserve_list.php\';';
        echo '</script>';
    }
    exit();
}


// remove reservation item
if (isset($_POST['reserveID']) AND !empty($_POST['reserveID'])) {
    $reserveID = intval($_POST['reserveID']);
    // get reserve data
    $reserve_q = $dbs->query('SELECT item_code FROM reserve WHERE reserve_id='.$reserveID);
    $reserve_d = $reserve_q->fetch_row();
    // delete reservation record from database
    $dbs->query('DELETE FROM reserve WHERE reserve_id='.$reserveID);
    // write log
    utility::writeLogs($dbs, 'member', $_SESSION['memberID'], 'circulation', $_SESSION['realname'].' remove reservation for item '.$reserve_d[0].' for member ('.$_SESSION['memberID'].')');
    echo '<script type="text/javascript">';
    echo 'alert(\''.lang_mod_circ_reserve_alert_removed.'\');';
    echo 'location.href = \'reserve_list.php\';';
    echo '</script>';
    exit();
}


// removing fines
if (isset($_POST['removeFines'])) {
    foreach ($_POST['removeFines'] as $fines_id) {
        $fines_id = intval($fines_id);
        // change loan data
        $dbs->query("DELETE FROM fines WHERE fines_id=$fines_id");
    }
    echo '<script type="text/javascript">';
    echo 'alert(\''.lang_mod_circ_fines_alert_removed.'\');';
    echo 'location.href = \'fines_list.php\';';
    echo '</script>';
    exit();
}


// transaction is started
if (isset($_POST['memberID']) OR isset($_SESSION['memberID'])) {
    // create member object
    // if there is already member ID session
    if (isset($_SESSION['memberID'])) {
        $memberID = trim($_SESSION['memberID']);
    } else {
        // new transaction proccess
        // clear previous sessions
        $_SESSION['temp_loan'] = array();
        $memberID = trim(preg_replace('@\s*(<.+)$@i', '', $_POST['memberID']));
        // write log
        utility::writeLogs($dbs, 'member', $memberID, 'circulation', $_SESSION['realname'].' start transaction with member ('.$memberID.')');
    }
    $member = new member($dbs, $memberID);
    if (!$member->valid()) {
        # echo '<div class="errorBox">Member ID '.$memberID.' not valid (unregistered in database)</div>';
        echo '<div class="errorBox">'.lang_mod_circ_field_member_id.' '.$memberID.' '.lang_mod_circ_common_error_unregistered_member.'</div>';
    } else {
        // get member information
        $member_type_d = $member->getMemberTypeProp();
        // member type ID
        $_SESSION['memberTypeID'] = $member->member_type_id;
        // save member ID to the sessions
        $_SESSION['memberID'] = $member->member_id;
        // create renewed/reborrow session array
        $_SESSION['reborrowed'] = array();
        // check membership expire
        $_SESSION['is_expire'] = $member->isExpired();
        // check if membership is blacklisted
        $_SESSION['is_pending'] = $member->isPending();
        // set HTML buttons disable flag
        $disabled = '';
        $add_style = '';
        // check for expire date and pending state
        if ($_SESSION['is_expire'] OR $_SESSION['is_pending']) {
            $disabled = ' disabled ';
            $add_style = ' color: #999999; border-color: #CCCCCC;';
        }
        // show the member information
        echo '<table width="100%" class="border" style="margin-bottom: 5px;" cellpadding="5" cellspacing="0">'."\n";
        echo '<tr>'."\n";
        echo '<td class="dataListHeader" colspan="5">';
        // hidden form for transaction finish
        echo '<form id="finishForm" method="post" target="blindSubmit" action="'.MODULES_WEB_ROOT_DIR.'circulation/circulation_action.php" style="display: inline;"><input type="button" value="'.lang_mod_circ_button_finish_transaction.'" onclick="confSubmit(\'finishForm\', \''.lang_mod_circ_common_loan_confirmation.'\')" /><input type="hidden" name="finish" value="true" /></form>';
        echo '</td>';
        echo '</tr>'."\n";
        echo '<tr>'."\n";
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_member_name.'</strong></td><td class="alterCell2" width="30%">'.$member->member_name.'</td>';
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_member_id.'</strong></td><td class="alterCell2" width="30%">'.$member->member_id.'</td>';
        // member photo
        if ($member->member_image) {
            if (file_exists(IMAGES_BASE_DIR.'persons/'.$member->member_image)) {
                echo '<td class="alterCell2" valign="top" rowspan="3">';
                echo '<img src="'.SENAYAN_WEB_ROOT_DIR.'lib/phpthumb/phpThumb.php?src=../../images/persons/'.urlencode($member->member_image).'&w=53" style="border: 1px solid #999999" />';
                echo '</td>';
            }
        }
        echo '</tr>'."\n";
        echo '<tr>'."\n";
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_member_email.'</strong></td><td class="alterCell2" width="30%">'.$member->member_email.'</td>';
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_member_type.'</strong></td><td class="alterCell2" width="30%">'.$member->member_type_name.'</td>';
        echo '</tr>'."\n";
        echo '<tr>'."\n";
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_register_date.'</strong></td><td class="alterCell2" width="30%">'.$member->register_date.'</td>';
        // give notification about expired membership and pending
        $expire_msg = '';
        if ($_SESSION['is_expire']) {
            $expire_msg .= '<font style="color: #FF0000;">('.lang_mod_circ_common_error_expired_membership.')</font>';
        }
        echo '<td class="alterCell" width="15%"><strong>'.lang_mod_circ_field_expiry_date.'</strong></td><td class="alterCell2" width="30%">'.$member->expire_date.' '.$expire_msg.'</td>';
        echo '</tr>'."\n";
        // member notes and pending information
        if (!empty($member->member_notes) OR $_SESSION['is_pending']) {
            echo '<tr>'."\n";
            echo '<td class="alterCell" width="15%"><strong>Notes</strong></td><td class="alterCell2" colspan="4">';
            if ($member->member_notes) {
                echo '<div>'.$member->member_notes.'</div>';
            }
            if ($_SESSION['is_pending']) {
                echo '<div style="color: #FF0000;">('.lang_mod_circ_common_error_pending_membership.')</div>';
            }
            echo '</td>';
            echo '</tr>'."\n";
        }
        echo '</table>'."\n";
        // tab and iframe
        echo '<input type="button" style="width: 19%;'.$add_style.'" class="tab" value="'.lang_mod_circ_button_loans.'" onclick="setIframeContent(\'listsFrame\', \''.MODULES_WEB_ROOT_DIR.'circulation/loan.php\'); setTabClass(this);" '.$disabled.' />';
        echo '<input type="button" style="width: 19%;" class="tab tabSelected" value="'.lang_mod_circ_button_current_loans.'" onclick="setIframeContent(\'listsFrame\', \''.MODULES_WEB_ROOT_DIR.'circulation/loan_list.php\'); setTabClass(this);" />';
        if ($member_type_d['enable_reserve']) {
            echo '<input type="button" style="width: 19%;'.$add_style.'" class="tab" value="'.lang_mod_circ_button_reserve.'" onclick="setIframeContent(\'listsFrame\', \''.MODULES_WEB_ROOT_DIR.'circulation/reserve_list.php\'); setTabClass(this);" '.$disabled.' />';
        }
        echo '<input type="button" style="width: 19%;" class="tab" value="'.lang_mod_circ_button_fines.'" onclick="setIframeContent(\'listsFrame\', \''.MODULES_WEB_ROOT_DIR.'circulation/fines_list.php\'); setTabClass(this);" />';
        echo '<input type="button" style="width: 19%;" class="tab" value="'.lang_mod_circ_button_loan_history.'" onclick="setIframeContent(\'listsFrame\', \''.MODULES_WEB_ROOT_DIR.'circulation/member_loan_hist.php\'); setTabClass(this);" /><br />'."\n";
        echo '<iframe src="modules/circulation/loan_list.php" id="listsFrame" class="border" style="width: 100%; height: 200px;"></iframe>'."\n";
        echo '<div class="objectDragger" id="iframeDragger">&nbsp;</div>'."\n";
        echo '<script type="text/javascript">registerDraggerEvent(\'iframeDragger\', \'listsFrame\');</script>'."\n";
    }
    exit();
}

?>
