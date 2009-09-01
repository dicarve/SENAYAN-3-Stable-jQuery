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

/* Global application configuration */

if (!defined('SENAYAN_BASE_DIR')) {
    // main system configuration
    require '../../../sysconfig.inc.php';
    // start the session
    require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
}

require SENAYAN_BASE_DIR.'admin/default/session_check.inc.php';
require SIMBIO_BASE_DIR.'simbio_FILE/simbio_directory.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/form_maker/simbio_form_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO_BASE_DIR.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('system', 'r');
$can_write = utility::havePrivilege('system', 'w');

// only administrator have privileges to change global settings
if (!($can_read AND $can_write) OR $_SESSION['uid'] != 1) {
    die('<div class="errorBox">'.lang_sys_common_no_privilege.'</div>');
}
?>
<fieldset class="menuBox">
    <div class="menuBoxInner systemIcon">
        <?php echo strtoupper(lang_sys_configuration).'<hr />'.lang_sys_configuration_description; ?>
    </div>
</fieldset>
<?php
/* main content */
/* Config Vars EDIT FORM */
/* Config Vars update process */
if (isset($_POST['updateData'])) {
    // reset/truncate setting table content
    $dbs->query('TRUNCATE TABLE setting');
    // library name
    $library_name = $dbs->escape_string(strip_tags(trim($_POST['library_name'])));
    $dbs->query('INSERT INTO setting VALUES (NULL, \'library_name\', \''.$dbs->escape_string(serialize($library_name)).'\')');

    // library subname
    $library_subname = $dbs->escape_string(strip_tags(trim($_POST['library_subname'])));
    $dbs->query('INSERT INTO setting VALUES (NULL, \'library_subname\', \''.$dbs->escape_string(serialize($library_subname)).'\')');

    // initialize template arrays
    $template = array('theme' => $sysconf['template']['theme'], 'css' => $sysconf['template']['css']);
    $admin_template = array('theme' => $sysconf['admin_template']['theme'], 'css' => $sysconf['admin_template']['css']);

    // template
    $template['theme'] = $_POST['template'];
    $template['css'] = str_replace($sysconf['template']['theme'], $template['theme'], $sysconf['template']['css']);
    $dbs->query('INSERT INTO setting VALUES (NULL, \'template\', \''.$dbs->escape_string(serialize($template)).'\')');

    // admin template
    $admin_template['theme'] = $_POST['admin_template'];
    $admin_template['css'] = str_replace($sysconf['admin_template']['theme'], $admin_template['theme'], $sysconf['admin_template']['css']);
    $dbs->query('INSERT INTO setting VALUES (NULL, \'admin_template\', \''.$dbs->escape_string(serialize($admin_template)).'\')');

    // language
    $dbs->query('INSERT INTO setting VALUES (NULL, \'default_lang\', \''.$dbs->escape_string(serialize($_POST['default_lang'])).'\')');

    // opac num result
    $dbs->query('INSERT INTO setting VALUES (NULL, \'opac_result_num\', \''.$dbs->escape_string(serialize($_POST['opac_result_num'])).'\')');

    // promoted titles in homepage
    $dbs->query('INSERT INTO setting VALUES (NULL, \'enable_promote_titles\', \''.$dbs->escape_string(serialize($_POST['enable_promote_titles'])).'\')');

    // quick return
    $quick_return = $_POST['quick_return'] == '1'?true:false;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'quick_return\', \''.$dbs->escape_string(serialize($quick_return)).'\')');

    // loan limit override
    $loan_limit_override = $_POST['loan_limit_override'] == '1'?true:false;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'loan_limit_override\', \''.$dbs->escape_string(serialize($loan_limit_override)).'\')');

    // xml detail
    $xml_detail = $_POST['enable_xml_detail'] == '1'?true:false;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'enable_xml_detail\', \''.$dbs->escape_string(serialize($xml_detail)).'\')');

    // xml result
    $xml_result = $_POST['enable_xml_result'] == '1'?true:false;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'enable_xml_result\', \''.$dbs->escape_string(serialize($xml_result)).'\')');

    // file download
    $file_download = $_POST['allow_file_download'] == '1'?true:false;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'allow_file_download\', \''.$dbs->escape_string(serialize($file_download)).'\')');

    // session timeout
    $session_timeout = intval($_POST['session_timeout']) >= 1800?$_POST['session_timeout']:1800;
    $dbs->query('INSERT INTO setting VALUES (NULL, \'session_timeout\', \''.$dbs->escape_string(serialize($session_timeout)).'\')');

    // write log
    utility::writeLogs($dbs, 'staff', $_SESSION['uid'], 'system', $_SESSION['realname'].' change application global configuration');
    utility::jsAlert(lang_sys_conf_alert_save);
    echo '<script type="text/javascript">parent.location.href = \'../../index.php?mod=system\';</script>';
}
/* Config Vars update process end */

// create new instance
$form = new simbio_form_table('mainForm', $_SERVER['PHP_SELF'], 'post');
$form->submit_button_attr = 'name="updateData" value="'.lang_sys_conf_form_button_save.'" class="button"';

// form table attributes
$form->table_attr = 'align="center" class="formTable" cellpadding="5" cellspacing="0"';
$form->table_header_attr = 'class="alterCell" style="font-weight: bold;"';
$form->table_content_attr = 'class="alterCell2"';

// load settings from database
utility::loadSettings($dbs);

// version status
$form->addAnything('Senayan Version', '<strong>'.SENAYAN_VERSION.'</strong>');

// library name
$form->addTextField('text', 'library_name', lang_sys_conf_form_field_library, $sysconf['library_name'], 'style="width: 100%;"');

// library subname
$form->addTextField('text', 'library_subname', lang_sys_conf_form_field_library_subname, $sysconf['library_subname'], 'style="width: 100%;"');

/* Form Element(s) */
// public template
// scan template directory
$template_dir = SENAYAN_BASE_DIR.$sysconf['template']['dir'];
$dir = new simbio_directory($template_dir);
$dir_tree = $dir->getDirectoryTree(1);
// sort array by index
ksort($dir_tree);
// loop array
foreach ($dir_tree as $dir) {
    $tpl_options[] = array($dir, $dir);
}
$form->addSelectList('template', lang_sys_conf_form_field_public_template, $tpl_options, $sysconf['template']['theme']);

// admin template
// scan admin template directory
$admin_template_dir = SENAYAN_BASE_DIR.'admin'.DIRECTORY_SEPARATOR.$sysconf['admin_template']['dir'];
$dir = new simbio_directory($admin_template_dir);
$dir_tree = $dir->getDirectoryTree(1);
// sort array by index
ksort($dir_tree);
// loop array
foreach ($dir_tree as $dir) {
    $admin_tpl_options[] = array($dir, $dir);
}
$form->addSelectList('admin_template', lang_sys_conf_form_field_admin_template, $admin_tpl_options, $sysconf['admin_template']['theme']);

// application language
// scan language directory
$lang_dir = LIB_DIR.'lang';
$scan_dir = opendir($lang_dir);
// check if the directory is successfully open
if ($scan_dir) {
    // loop the directory content
    while (false !== ($filename = @readdir($scan_dir))) {
        if (is_file($lang_dir.DIRECTORY_SEPARATOR.$filename) AND $filename != '.' AND $filename != '..') {
            $lang = str_ireplace('.lang.inc.php', '', $filename);
            $lang_options[] = array($lang, ucfirst($lang));
        }
    }
} else {
    $lang_options[] = array('english', 'English');
}
$form->addSelectList('default_lang', lang_sys_conf_form_field_language, $lang_options, $sysconf['default_lang']);

// opac result list number
$result_num_options[] = array('10', '10');
$result_num_options[] = array('20', '20');
$result_num_options[] = array('30', '30');
$result_num_options[] = array('40', '40');
$result_num_options[] = array('40', '50');
$form->addSelectList('opac_result_num', lang_sys_conf_form_field_opac_result, $result_num_options, $sysconf['opac_result_num'] );

// homepage setting
$promote_options[] = array('1', 'Yes');
$form->addCheckBox('enable_promote_titles', lang_sys_conf_form_field_promote_titles, $promote_options, $sysconf['enable_promote_titles']?'1':'0');

// enable quick return
$options = null;
$options[] = array('0', lang_sys_conf_form_option_disable);
$options[] = array('1', lang_sys_conf_form_option_enable);
$form->addSelectList('quick_return', lang_sys_conf_form_field_quick_return, $options, $sysconf['quick_return']?'1':'0');

// enable loan limit overriden
$options = null;
$options[] = array('0', lang_sys_conf_form_option_disable);
$options[] = array('1', lang_sys_conf_form_option_enable);
$form->addSelectList('loan_limit_override', lang_sys_conf_form_field_limit_overide, $options, $sysconf['loan_limit_override']?'1':'0');

// enable bibliography xml detail
$options = null;
$options[] = array('0', lang_sys_conf_form_option_disable);
$options[] = array('1', lang_sys_conf_form_option_enable);
$form->addSelectList('enable_xml_detail', lang_sys_conf_form_field_opac_xml, $options, $sysconf['enable_xml_detail']?'1':'0');

// enable bibliography xml result set
$options = null;
$options[] = array('0', lang_sys_conf_form_option_disable);
$options[] = array('1', lang_sys_conf_form_option_enable);
$form->addSelectList('enable_xml_result', lang_sys_conf_form_field_xml_result, $options, $sysconf['enable_xml_result']?'1':'0');

// allow file attachment download
$options = null;
$options[] = array('0', lang_sys_conf_form_option_forbid);
$options[] = array('1', lang_sys_conf_form_option_allow);
$form->addSelectList('allow_file_download', lang_sys_conf_form_field_xml_file, $options, $sysconf['allow_file_download']?'1':'0');

// session timeout
$form->addTextField('text', 'session_timeout', lang_sys_conf_form_field_session, $sysconf['session_timeout'], 'style="width: 10%;"');

// print out the object
echo $form->printOut();
/* main content end */
?>
