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

/* Bibliography Management section */

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
$can_read = utility::havePrivilege('bibliography', 'r');
$can_write = utility::havePrivilege('bibliography', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.lang_sys_common_unauthorized.'</div>');
}

$in_pop_up = false;
// check if we are inside pop-up window
if (isset($_GET['inPopUp'])) {
    $in_pop_up = true;
}

/* RECORD OPERATION */
if (isset($_POST['saveData']) AND $can_read AND $can_write) {
    $title = trim(strip_tags($_POST['title']));
    // check form validity
    if (empty($title)) {
        utility::jsAlert(lang_mod_biblio_alert_title_empty);
        exit();
    } else {
        $data['title'] = $dbs->escape_string($title);
        $data['edition'] = trim($dbs->escape_string(strip_tags($_POST['edition'])));
        $data['gmd_id'] = $_POST['gmdID'];
        $data['isbn_issn'] = trim($dbs->escape_string(strip_tags($_POST['isbn_issn'])));
        $data['classification'] = trim($dbs->escape_string(strip_tags($_POST['class'])));
        // check publisher
        if ($_POST['publisherID'] != '0') {
            $data['publisher_id'] = intval($_POST['publisherID']);
        } else {
            if (!empty($_POST['publ_search_str'])) {
                $new_publisher = trim(strip_tags($_POST['publ_search_str']));
                $new_id = utility::getID($dbs, 'mst_publisher', 'publisher_id', 'publisher_name', $new_publisher);
                if ($new_id) {
                    $data['publisher_id'] = $new_id;
                } else {
                    $data['publisher_id'] = 'literal{NULL}';
                }
            } else {
                $data['publisher_id'] = 'literal{NULL}';
            }
        }
        $data['publish_year'] = trim($dbs->escape_string(strip_tags($_POST['year'])));
        $data['collation'] = trim($dbs->escape_string(strip_tags($_POST['collation'])));
        $data['series_title'] = trim($dbs->escape_string(strip_tags($_POST['seriesTitle'])));
        $data['call_number'] = trim($dbs->escape_string(strip_tags($_POST['callNumber'])));
        $data['language_id'] = trim($dbs->escape_string(strip_tags($_POST['languageID'])));
        // check place
        if ($_POST['placeID'] != '0') {
            $data['publish_place_id'] = intval($_POST['placeID']);
        } else {
            if (!empty($_POST['plc_search_str'])) {
                $new_place = trim(strip_tags($_POST['plc_search_str']));
                $new_id = utility::getID($dbs, 'mst_place', 'place_id', 'place_name', $new_place);
                if ($new_id) {
                    $data['publish_place_id'] = $new_id;
                } else {
                    $data['publish_place_id'] = 'literal{NULL}';
                }
            } else {
                $data['publish_place_id'] = 'literal{NULL}';
            }
        }
        $data['notes'] = trim($dbs->escape_string(strip_tags($_POST['notes'])));
        $data['opac_hide'] = ($_POST['opacHide'] == '0')?'literal{0}':'1';
        $data['promoted'] = ($_POST['promote'] == '0')?'literal{0}':'1';
        // labels
        $arr_label = array();
        foreach ($_POST['labels'] as $label) {
            if (trim($label) != '') {
                $arr_label[] = array($label, isset($_POST['label_urls'][$label])?$_POST['label_urls'][$label]:null );
            }
        }
        $data['labels'] = $arr_label?serialize($arr_label):'literal{NULL}';
        $data['frequency_id'] = ($_POST['frequencyID'] == '0')?'literal{0}':(integer)$_POST['frequencyID'];
        $data['spec_detail_info'] = trim($dbs->escape_string(strip_tags($_POST['specDetailInfo'])));
        $data['input_date'] = date('Y-m-d H:i:s');
        $data['last_update'] = date('Y-m-d H:i:s');
        // image uploading
        if (!empty($_FILES['image']) AND $_FILES['image']['size']) {
            // create upload object
            $image_upload = new simbio_file_upload();
            $image_upload->setAllowableFormat($sysconf['allowed_images']);
            $image_upload->setMaxSize($sysconf['max_image_upload']*1024);
            $image_upload->setUploadDir(IMAGES_BASE_DIR.'docs');
            // upload the file and change all space characters to underscore
            $img_upload_status = $image_upload->doUpload('image', preg_replace('@\s+@i', '_', $_FILES['image']['name']));
            if ($img_upload_status == UPLOAD_SUCCESS) {
                $data['image'] = $dbs->escape_string($image_upload->new_filename);
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography', $_SESSION['realname'].' upload image file '.$image_upload->new_filename);
                utility::jsAlert(lang_mod_biblio_alert_image_uploaded);
            } else {
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography', 'ERROR : '.$_SESSION['realname'].' FAILED TO upload image file '.$image_upload->new_filename.', with error ('.$image_upload->error.')');
                utility::jsAlert(lang_mod_biblio_alert_image_uploaded);
            }
        }

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // remove input date
            unset($data['input_date']);
            // filter update record ID
            $updateRecordID = (integer)$_POST['updateRecordID'];
            // update the data
            $update = $sql_op->update('biblio', $data, 'biblio_id='.$updateRecordID);
            // send an alert
            if ($update) {
                utility::jsAlert(lang_mod_biblio_alert_updated_ok);
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography', $_SESSION['realname'].' update bibliographic data ('.$data['title'].') with biblio_id ('.$_POST['itemID'].')');
                // close window OR redirect main page
                if ($in_pop_up) {
                    $itemCollID = (integer)$_POST['itemCollID'];
                    echo '<script type="text/javascript">parent.opener.setContent(\'mainContent\', parent.opener.getLatestAJAXurl(), \'post\', \''.( $itemCollID?'itemID='.$itemCollID.'&detail=true':'' ).'\');</script>';
                    echo '<script type="text/javascript">parent.window.close();</script>';
                } else {
                    echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(parent.getPreviousAJAXurl(), \'get\');</script>';
                }
            } else { utility::jsAlert(lang_mod_biblio_alert_failed_to_update."\n".$sql_op->error); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            $insert = $sql_op->insert('biblio', $data);
            if ($insert) {
                // get auto id of this record
                $last_biblio_id = $sql_op->insert_id;
                // add authors
                if ($_SESSION['biblioAuthor']) {
                    foreach ($_SESSION['biblioAuthor'] as $author) {
                        $sql_op->insert('biblio_author', array('biblio_id' => $last_biblio_id, 'author_id' => $author[0], 'level' => $author[1]));
                    }
                }
                // add topics
                if ($_SESSION['biblioTopic']) {
                    foreach ($_SESSION['biblioTopic'] as $topic) {
                        $sql_op->insert('biblio_topic', array('biblio_id' => $last_biblio_id, 'topic_id' => $topic[0], 'level' => $topic[1]));
                    }
                }
                // add attachment
                if ($_SESSION['biblioAttach']) {
                    foreach ($_SESSION['biblioAttach'] as $attachment) {
                        $sql_op->insert('biblio_attachment', array('biblio_id' => $last_biblio_id, 'file_id' => $attachment['file_id'], 'access_type' => $attachment['access_type']));
                    }
                }
                utility::jsAlert(lang_mod_biblio_alert_new_added);
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography', $_SESSION['realname'].' insert bibliographic data ('.$data['title'].') with biblio_id ('.$last_biblio_id.')');
                // clear related sessions
                $_SESSION['biblioAuthor'] = array();
                $_SESSION['biblioTopic'] = array();
                $_SESSION['biblioAttach'] = array();
                echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.MODULES_WEB_ROOT_DIR.'bibliography/index.php\', \'post\', \'itemID='.$last_biblio_id.'&detail=true\');</script>';
            } else { utility::jsAlert(lang_mod_biblio_alert_failed_to_save."\n".$sql_op->error); }
            exit();
        }
    }
    exit();
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }
    /* DATA DELETION PROCESS */
    // create sql op object
    $sql_op = new simbio_dbop($dbs);
    $failed_array = array();
    $error_num = 0;
    $still_have_item = array();
    if (!is_array($_POST['itemID'])) {
        // make an array
        $_POST['itemID'] = array((integer)$_POST['itemID']);
    }
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
        $itemID = (integer)$itemID;
        // check if this biblio data still have an item
        $biblio_item_q = $dbs->query('SELECT b.title, COUNT(item_id) FROM biblio AS b
            LEFT JOIN item AS i ON b.biblio_id=i.biblio_id
            WHERE b.biblio_id='.$itemID.' GROUP BY title');
        $biblio_item_d = $biblio_item_q->fetch_row();
        if ($biblio_item_d[1] < 1) {
            if (!$sql_op->delete('biblio', "biblio_id=$itemID")) {
                $error_num++;
            } else {
                // write log
                utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'bibliography', $_SESSION['realname'].' DELETE bibliographic data ('.$biblio_item_d[0].') with biblio_id ('.$itemID.')');
                // delete related data
                $sql_op->delete('biblio_topic', "biblio_id=$itemID");
                $sql_op->delete('biblio_author', "biblio_id=$itemID");
                $sql_op->delete('biblio_attachment', "biblio_id=$itemID");
            }
        } else {
            $still_have_item[] = substr($biblio_item_d[0], 0, 45).'... still have '.$biblio_item_d[1].' copies';
            $error_num++;
        }
    }

    if ($still_have_item) {
        $titles = '';
        foreach ($still_have_item as $title) {
            $titles .= $title."\n";
        }
        utility::jsAlert(lang_mod_biblio_alert_list_not_deleted."\n".$titles);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
        exit();
    }
    // error alerting
    if ($error_num == 0) {
        utility::jsAlert(lang_mod_biblio_alert_data_selected_deleted);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(lang_mod_biblio_alert_data_selected_not_deleted);
        echo '<script type="text/javascript">parent.$(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}
/* RECORD OPERATION END */

if (!$in_pop_up) {
/* search form */
?>
<fieldset class="menuBox">
<div class="menuBoxInner biblioIcon">
    <?php echo strtoupper(lang_mod_biblio); ?> - <a href="<?php echo MODULES_WEB_ROOT_DIR; ?>bibliography/index.php?action=detail" class="ajaxLink"><?php echo lang_mod_biblio_add; ?></a>
    &nbsp; <a href="<?php echo MODULES_WEB_ROOT_DIR; ?>bibliography/index.php" class="ajaxLink"><?php echo lang_mod_biblio_list; ?></a>
    <hr />
    <form name="search" id="ajaxSearchForm" action="<?php echo MODULES_WEB_ROOT_DIR; ?>bibliography/index.php" method="get" style="display: inline;"><?php echo lang_sys_common_form_search; ?> :
    <input type="text" name="keywords" id="keywords" size="30" />
    <select name="field"><option value="0"><?php echo lang_mod_biblio_field_opt_all; ?></option><option value="title"><?php echo lang_mod_biblio_field_opt_title; ?> </option><option value="subject"><?php echo lang_mod_biblio_field_opt_subject; ?></option><option value="author"><?php echo lang_mod_biblio_field_opt_author; ?></option><option value="isbn"><?php echo lang_mod_biblio_field_opt_isbn; ?></option><option value="publisher"><?php echo lang_mod_biblio_field_opt_publisher; ?></option></select>
    <input type="submit" value="<?php echo lang_sys_common_form_search; ?>" class="button" />
    </form>
</div>
</fieldset>
<script type="text/javascript">
// focus keywords text field
$('keywords').focus();
</script>
<?php
/* search form end */
}
/* main content */
if (isset($_POST['detail']) OR (isset($_GET['action']) AND $_GET['action'] == 'detail')) {
    if (!($can_read AND $can_write)) {
        die('<div class="errorBox">'.lang_sys_common_unauthorized.'</div>');
    }
    /* RECORD FORM */
    // try query
    $itemID = (integer)isset($_POST['itemID'])?$_POST['itemID']:0;
    $rec_q = $dbs->query('SELECT b.*, p.publisher_name, pl.place_name FROM biblio AS b
        LEFT JOIN mst_publisher AS p ON b.publisher_id=p.publisher_id
        LEFT JOIN mst_place AS pl ON b.publish_place_id=pl.place_id
        WHERE biblio_id='.$itemID);
    $rec_d = $rec_q->fetch_assoc();

    // create new instance
    $form = new simbio_form_table('mainForm', $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'], 'post');
    $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_save.'" class="button"';
    // form table attributes
    $form->table_attr = 'align="center" class="formTable" cellpadding="5" cellspacing="0"';
    $form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
    $form->table_content_attr = 'class="alterCell2"';

    $visibility = 'makeVisible';
    // edit mode flag set
    if ($rec_q->num_rows > 0) {
        $form->edit_mode = true;
        // record ID for delete process
        if (!$in_pop_up) {
            // form record id
            $form->record_id = $itemID;
        } else {
            $form->addHidden('updateRecordID', $itemID);
            $form->addHidden('itemCollID', $_POST['itemCollID']);
            $form->back_button = false;
        }
        // form record title
        $form->record_title = $rec_d['title'];
        // submit button attribute
        $form->submit_button_attr = 'name="saveData" value="'.lang_sys_common_form_update.'" class="button"';
        // element visibility class toogle
        $visibility = 'makeHidden';
    }

    /* Form Element(s) */
    // biblio title
    $form->addTextField('textarea', 'title', lang_mod_biblio_field_title.'*', $rec_d['title'], 'rows="1" style="width: 100%; overflow: auto;"');
    // biblio edition
    $form->addTextField('text', 'edition', lang_mod_biblio_field_edition, $rec_d['edition'], 'style="width: 40%;"');
    // biblio specific detail info/area
    $form->addTextField('textarea', 'specDetailInfo', lang_mod_biblio_field_specific_detail, $rec_d['spec_detail_info'], 'rows="2" style="width: 100%"');
    // biblio item add
    if (!$in_pop_up AND $form->edit_mode) {
        $str_input = '<div class="makeHidden"><a href="javascript: openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_item.php?inPopUp=true&action=detail&biblioID='.$rec_d['biblio_id'].'\', \'popItem\', 600, 400, true)">'.lang_mod_biblio_link_item_add.'</a></div>';
        $str_input .= '<iframe name="itemIframe" id="itemIframe" class="borderAll" style="width: 100%; height: 70px;" src="'.MODULES_WEB_ROOT_DIR.'bibliography/iframe_item_list.php?biblioID='.$rec_d['biblio_id'].'"></iframe>'."\n";
        $form->addAnything('Item(s) Data', $str_input);
    }
    // biblio authors
        $str_input = '<div class="'.$visibility.'"><a href="javascript: openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_author.php?biblioID='.$rec_d['biblio_id'].'\', \'popAuthor\', 500, 200, true)">'.lang_mod_biblio_link_author_add.'</a></div>';
        $str_input .= '<iframe name="authorIframe" id="authorIframe" class="borderAll" style="width: 100%; height: 70px;" src="'.MODULES_WEB_ROOT_DIR.'bibliography/iframe_author.php?biblioID='.$rec_d['biblio_id'].'"></iframe>';
    $form->addAnything(lang_mod_biblio_field_authors, $str_input);
    // biblio gmd
        // get gmd data related to this record from database
        $gmd_q = $dbs->query('SELECT gmd_id, gmd_name FROM mst_gmd');
        $gmd_options = array();
        while ($gmd_d = $gmd_q->fetch_row()) {
            $gmd_options[] = array($gmd_d[0], $gmd_d[1]);
        }
    $form->addSelectList('gmdID', lang_mod_biblio_field_gmd, $gmd_options, $rec_d['gmd_id']);
    // biblio publish frequencies
        // get frequency data related to this record from database
        $freq_q = $dbs->query('SELECT frequency_id, frequency FROM mst_frequency');
        $freq_options[] = array('0', lang_mod_biblio_field_opt_none);
        while ($freq_d = $freq_q->fetch_row()) {
            $freq_options[] = array($freq_d[0], $freq_d[1]);
        }
        $str_input = simbio_form_element::selectList('frequencyID', $freq_options, $rec_d['frequency_id']);
        $str_input .= '&nbsp;';
        $str_input .= ' '.lang_mod_biblio_field_frequency_explain;
    $form->addAnything(lang_mod_masterfile_frequency, $str_input);
    // biblio ISBN/ISSN
    $form->addTextField('text', 'isbn_issn', lang_mod_biblio_field_isbn, $rec_d['isbn_issn'], 'style="width: 40%;"');
    // biblio classification
    $form->addTextField('text', 'class', lang_mod_biblio_field_class, $rec_d['classification'], 'style="width: 40%;"');
    // biblio publisher
        // AJAX expression
        $ajax_exp = "ajaxFillSelect('AJAX_lookup_handler.php', 'mst_publisher', 'publisher_id:publisher_name', 'publisherID', $('publ_search_str').getValue())";
        if ($rec_d['publisher_name']) {
            $publ_options[] = array($rec_d['publisher_id'], $rec_d['publisher_name']);
        }
        $publ_options[] = array('0', lang_mod_biblio_field_publisher);
        // string element
        $str_input = simbio_form_element::selectList('publisherID', $publ_options, '', 'style="width: 50%;"');
        $str_input .= '&nbsp;';
        $str_input .= simbio_form_element::textField('text', 'publ_search_str', '', 'style="width: 45%;" onkeyup="'.$ajax_exp.'"');
    $form->addAnything(lang_mod_biblio_field_publisher, $str_input);
    // biblio publish year
    $form->addTextField('text', 'year', lang_mod_biblio_field_publish_year, $rec_d['publish_year'], 'style="width: 40%;"');
    // biblio publish place
        // AJAX expression
        $ajax_exp = "ajaxFillSelect('AJAX_lookup_handler.php', 'mst_place', 'place_id:place_name', 'placeID', $('plc_search_str').getValue())";
        // string element
        if ($rec_d['place_name']) {
            $plc_options[] = array($rec_d['publish_place_id'], $rec_d['place_name']);
        }
        $plc_options[] = array('0', lang_mod_biblio_field_publish_place);
        $str_input = simbio_form_element::selectList('placeID', $plc_options, '', 'style="width: 50%;"');
        $str_input .= '&nbsp;';
        $str_input .= simbio_form_element::textField('text', 'plc_search_str', '', 'style="width: 45%;" onkeyup="'.$ajax_exp.'"');
    $form->addAnything(lang_mod_biblio_field_publish_place, $str_input);
    // biblio collation
    $form->addTextField('text', 'collation', lang_mod_biblio_field_collation, $rec_d['collation'], 'style="width: 40%;"');
    // biblio series title
    $form->addTextField('textarea', 'seriesTitle', lang_mod_biblio_field_series, $rec_d['series_title'], 'rows="1" style="width: 100%;"');
    // biblio call_number
    $form->addTextField('text', 'callNumber', lang_mod_biblio_field_call_number, $rec_d['call_number'], 'style="width: 40%;"');
    // biblio topics
        $str_input = '<div class="'.$visibility.'"><a href="javascript: openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_topic.php?biblioID='.$rec_d['biblio_id'].'\', \'popTopic\', 500, 200, true)">'.lang_mod_biblio_link_topic_add.'</a></div>';
        $str_input .= '<iframe name="topicIframe" id="topicIframe" class="borderAll" style="width: 100%; height: 70px;" src="'.MODULES_WEB_ROOT_DIR.'bibliography/iframe_topic.php?biblioID='.$rec_d['biblio_id'].'"></iframe>';
    $form->addAnything(lang_mod_biblio_field_topic, $str_input);
    // biblio language
        // get language data related to this record from database
        $lang_q = $dbs->query("SELECT language_id, language_name FROM mst_language");
        $lang_options = array();
        while ($lang_d = $lang_q->fetch_row()) {
            $lang_options[] = array($lang_d[0], $lang_d[1]);
        }
    $form->addSelectList('languageID', lang_mod_biblio_field_lang, $lang_options, $rec_d['language_id']);
    // biblio note
    $form->addTextField('textarea', 'notes', lang_mod_biblio_field_notes, $rec_d['notes'], 'style="width: 100%;" rows="2"');
    // biblio cover image
    if (!trim($rec_d['image'])) {
        $str_input = simbio_form_element::textField('file', 'image');
        $str_input .= ' Maximum '.$sysconf['max_image_upload'].' KB';
        $form->addAnything(lang_mod_biblio_field_image, $str_input);
    } else {
        $str_input = '<a href="'.SENAYAN_WEB_ROOT_DIR.'images/docs/'.$rec_d['image'].'" target="_blank"><strong>'.$rec_d['image'].'</strong></a><br />';
        $str_input .= simbio_form_element::textField('file', 'image');
        $str_input .= ' Maximum '.$sysconf['max_image_upload'].' KB';
        $form->addAnything(lang_mod_biblio_field_image, $str_input);
    }
    // biblio file attachment
    $str_input = '<div class="'.$visibility.'"><a href="javascript: openWin(\''.MODULES_WEB_ROOT_DIR.'bibliography/pop_attach.php?biblioID='.$rec_d['biblio_id'].'\', \'popAttach\', 600, 200, true)">'.lang_mod_biblio_link_attachment_add.'</a></div>';
    $str_input .= '<iframe name="attachIframe" id="attachIframe" class="borderAll" style="width: 100%; height: 70px;" src="'.MODULES_WEB_ROOT_DIR.'bibliography/iframe_attach.php?biblioID='.$rec_d['biblio_id'].'"></iframe>';
    $form->addAnything(lang_mod_biblio_field_attachment, $str_input);
    // biblio hide from opac
    $hide_options[] = array('0', lang_mod_biblio_field_opt_show);
    $hide_options[] = array('1', lang_mod_biblio_field_opt_hide);
    $form->addRadio('opacHide', lang_mod_biblio_field_hide_opac, $hide_options, $rec_d['opac_hide']?'1':'0');
    // biblio promote to front page
    $promote_options[] = array('0', lang_mod_biblio_field_opt_promotefalse);
    $promote_options[] = array('1', lang_mod_biblio_field_opt_promotetrue);
    $form->addRadio('promote', lang_mod_biblio_field_promote, $promote_options, $rec_d['promoted']?'1':'0');
    // biblio labels
        $arr_labels = !empty($rec_d['labels'])?unserialize($rec_d['labels']):array();
        if ($arr_labels) {
            foreach ($arr_labels as $label) { $arr_labels[$label[0]] = $label[1]; }
        }
        $str_input = '';
        // get label data from database
        $label_q = $dbs->query("SELECT * FROM mst_label LIMIT 20");
        while ($label_d = $label_q->fetch_assoc()) {
            $checked = isset($arr_labels[$label_d['label_name']])?' checked':'';
            $url = isset($arr_labels[$label_d['label_name']])?$arr_labels[$label_d['label_name']]:'';
            $str_input .= '<div '
                .'style="background: url('.SENAYAN_WEB_ROOT_DIR.IMAGES_DIR.'/labels/'.$label_d['label_image'].') left center no-repeat; padding-left: 30px; height: 45px;"> '
                .'<input type="checkbox" name="labels[]" value="'.$label_d['label_name'].'"'.$checked.' /> '.$label_d['label_desc']
                .'<div>URL : <input type="text" title="Enter a website link/URL to make this label clickable" '
                .'name="label_urls['.$label_d['label_name'].']" size="50" maxlength="300" value="'.$url.'" /></div></div>';
        }
    $form->addAnything('Label', $str_input);
    // $form->addCheckBox('labels', 'Label', $label_options, explode(' ', $rec_d['labels']));

    // edit mode messagge
    if ($form->edit_mode) {
        echo '<div class="infoBox" style="overflow: auto;">'
            .'<div style="float: left; width: 80%;">'.lang_mod_biblio_common_edit_message.' : <b>'.$rec_d['title'].'</b>  <br />'.lang_mod_biblio_common_last_update.$rec_d['last_update'].'</div>';
            if ($rec_d['image']) {
                if (file_exists(IMAGES_BASE_DIR.'docs/'.$rec_d['image'])) {
                    $upper_dir = '';
                    if ($in_pop_up) {
                        $upper_dir = '../../';
                    }
                    echo '<div style="float: right;"><img src="'.$upper_dir.'../lib/phpthumb/phpThumb.php?src=../../images/docs/'.urlencode($rec_d['image']).'&w=53" style="border: 1px solid #999999" /></div>';
                }
            }
        echo '</div>'."\n";
    }
    // print out the form object
    echo $form->printOut();
    // block iframes on edit mode
    if ($form->edit_mode) {
        echo '<script type="text/javascript">registerBlockedIframes([\'itemIframe\', \'authorIframe\', \'topicIframe\', \'attachIframe\']);</script>';
    }
} else {
    require SIMBIO_BASE_DIR.'simbio_UTILS/simbio_tokenizecql.inc.php';
    require LIB_DIR.'biblio_list.inc.php';
    /* BIBLIOGRAPHY LIST */
    // callback function to show title and authors in datagrid
    function showTitleAuthors($obj_db, $array_data)
    {
        // biblio author detail
        $_biblio_q = $obj_db->query('SELECT b.title, a.author_name, opac_hide, promoted FROM biblio AS b
            LEFT JOIN biblio_author AS ba ON b.biblio_id=ba.biblio_id
            LEFT JOIN mst_author AS a ON ba.author_id=a.author_id
            WHERE b.biblio_id='.$array_data[0]);
        $_authors = '';
        while ($_biblio_d = $_biblio_q->fetch_row()) {
            $_title = $_biblio_d[0];
            $_authors .= $_biblio_d[1].' - ';
            $_opac_hide = (integer)$_biblio_d[2];
            $_promoted = (integer)$_biblio_d[3];
        }
        $_authors = substr_replace($_authors, '', -3);
        $_output = '<div style="float: left;"><b>'.$_title.'</b><br /><i>'.$_authors.'</i></div>';
        // check for opac hide flag
        if ($_opac_hide) {
            $_output .= '<div style="float: right; width: 20px; height: 20px;" class="lockFlagIcon" title="Hidden in OPAC">&nbsp;</div>';
        }
        // check for promoted flag
        if ($_promoted) {
            $_output .= '<div style="float: right; width: 20px; height: 20px;" class="homeFlagIcon" title="Promoted To Homepage">&nbsp;</div>';
        }
        return $_output;
    }

    // create datagrid
    $datagrid = new simbio_datagrid();
    if ($can_read AND $can_write) {
        $datagrid->setSQLColumn('biblio.biblio_id', 'biblio.biblio_id AS bid',
            'biblio.title AS \''.lang_mod_biblio_field_title.'\'',
            'biblio.isbn_issn AS \''.lang_mod_biblio_field_isbn.'\'',
            'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #FF0000;">'.lang_mod_biblio_field_copies_none.'</strong>\') AS \''.lang_mod_biblio_field_copies.'\'',
            'biblio.last_update AS \''.lang_mod_biblio_field_update.'\'');
        $datagrid->modifyColumnContent(2, 'callback{showTitleAuthors}');
    } else {
        $datagrid->setSQLColumn('biblio.biblio_id AS bid', 'biblio.title AS \''.lang_mod_biblio_field_title.'\'',
            'biblio.isbn_issn AS \''.lang_mod_biblio_field_isbn.'\'',
            'IF(COUNT(item.item_id)>0, COUNT(item.item_id), \'<strong style="color: #FF0000;">'.lang_mod_biblio_field_copies_none.'</strong>\') AS \''.lang_mod_biblio_field_copies.'\'',
            'biblio.last_update AS \''.lang_mod_biblio_field_update.'\'');
        // modify column value
        $datagrid->modifyColumnContent(1, 'callback{showTitleAuthors}');
    }
    $datagrid->invisible_fields = array(0);
    $datagrid->setSQLorder('biblio.last_update DESC');

    // is there any search
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $keywords = $dbs->escape_string(trim($_GET['keywords']));
        $searchable_fields = array('title', 'author', 'subject', 'isbn', 'publisher');
        if ($_GET['field'] != '0' AND in_array($_GET['field'], $searchable_fields)) {
            $field = $_GET['field'];
            $search_str = $field.'='.$keywords;
        } else {
            $search_str = '';
            foreach ($searchable_fields as $search_field) {
                $search_str .= $search_field.'='.$keywords.' OR ';
            }
            $search_str = substr_replace($search_str, '', -4);
        }

        $biblio_list = new biblio_list($dbs);
        $criteria = $biblio_list->setSQLcriteria($search_str);
    }

    if (isset($criteria)) {
        $datagrid->setSQLcriteria('('.$criteria['sql_criteria'].')');
    }
    // table spec
    $table_spec = 'biblio LEFT JOIN item ON biblio.biblio_id=item.biblio_id';

    // set group by
    $datagrid->sql_group_by = 'biblio.biblio_id';

    // set table and table header attributes
    $datagrid->table_attr = 'align="center" class="dataList" cellpadding="5" cellspacing="0"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    // set delete proccess URL
    $datagrid->chbox_form_URL = $_SERVER['PHP_SELF'];
    $datagrid->debug = true;

    // put the result into variables
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read AND $can_write));
    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $msg = str_replace('{result->num_rows}', $datagrid->num_rows, lang_sys_common_search_result_info);
        echo '<div class="infoBox">'.$msg.' : "'.$_GET['keywords'].'"<div>'.lang_sys_common_query_msg1.' <b>'.$datagrid->query_time.'</b> '.lang_sys_common_query_msg2.'</div></div>';
    }

    echo $datagrid_result;
}
/* main content end */
?>
