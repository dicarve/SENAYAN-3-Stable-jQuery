<?php
/**
 * SENAYAN application bootstrap files
 *
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 * Some modifications & patches by Hendro Wicaksono (hendrowicaksono@yahoo.com)
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

// required file
require 'sysconfig.inc.php';
if ($sysconf['template']['base'] == 'html') {
    require SIMBIO_BASE_DIR.'simbio_GUI/template_parser/simbio_template_parser.inc.php';
}

// page title
$page_title = $sysconf['library_name'].' | '.$sysconf['library_subname'].' :: OPAC';
// default library info
$info = __('Web Online Public Access Catalog - Use the search options to find documents quickly');
// total opac result page
$total_pages = 1;
// default header info
$header_info = '';
// HTML metadata
$metadata = '';

// start the output buffering for main content
ob_start();
require LIB_DIR.'contents/common.inc.php';
if (isset($_GET['p'])) {
    $path = trim($_GET['p']);
    // some extra checking
    $path = preg_replace('@^(http|https|ftp|sftp|file|smb):@i', '', $path);
    // check if the file exists
    if (file_exists(LIB_DIR.'contents/'.$path.'.inc.php')) {
        include LIB_DIR.'contents/'.$path.'.inc.php';
        if ($path != "show_detail") {
            $metadata = '<meta name="robots" content="noindex, follow">';
        }
    } else {
        // get content data from database
        $metadata = '<meta name="robots" content="noindex, follow">';
        include LIB_DIR.'content.inc.php';
        $content = new content();
        $content_data = $content->get($dbs, $path);
        if ($content_data) {
            $page_title = $content_data['Title'];
            $info = '<div class="contentTitle">'.$content_data['Title'].'</div>';
            echo '<div class="contentDesc">'.$content_data['Content'].'</div>';
            unset($content_data);
        }
    }
} else {
    $metadata = '<meta name="robots" content="noindex, follow">';
    // homepage header info
    if (!isset($_GET['p'])) {
        if ((!isset($_GET['keywords'])) AND (!isset($_GET['page'])) AND (!isset($_GET['title'])) AND (!isset($_GET['author'])) AND (!isset($_GET['subject'])) AND (!isset($_GET['location']))) {
            // get content data from database
            include LIB_DIR.'content.inc.php';
            $content = new content();
            $content_data = $content->get($dbs, 'headerinfo');
            if ($content_data) {
                $header_info = '<div id="headerInfo">'.$content_data['Content'].'</div>';
                unset($content_data);
            }
        }
    }
    include LIB_DIR.'contents/default.inc.php';
}
// main content grab
$main_content = ob_get_clean();

// template output
if ($sysconf['template']['base'] == 'html') {
    // create the template object
    $template = new simbio_template_parser($sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/index_template.html');
    // assign content to markers
    $template->assign('<!--PAGE_TITLE-->', $page_title);
    $template->assign('<!--CSS-->', $sysconf['template']['css']);
    $template->assign('<!--INFO-->', $info);
    $template->assign('<!--LIBRARY_NAME-->', $sysconf['library_name']);
    $template->assign('<!--LIBRARY_SUBNAME-->', $sysconf['library_subname']);
    $template->assign('<!--GMD_LIST-->', $gmd_list);
    $template->assign('<!--COLLTYPE_LIST-->', $colltype_list);
    $template->assign('<!--LOCATION_LIST-->', $location_list);
    $template->assign('<!--LANGUAGE_SELECT-->', $language_select);
    $template->assign('<!--ADVSEARCH_AUTHOR-->', $advsearch_author);
    $template->assign('<!--ADVSEARCH_TOPIC-->', $advsearch_topic);
    $template->assign('<!--HEADER_INFO-->', $header_info);
    $template->assign('<!--MAIN_CONTENT-->', $main_content);
    if ($metadata) { $template->assign('<!--METADATA-->', $metadata); }
    // print out the template
    $template->printOut();
} else if ($sysconf['template']['base'] == 'php') {
    require $sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/index_template.inc.php';
}
?>
