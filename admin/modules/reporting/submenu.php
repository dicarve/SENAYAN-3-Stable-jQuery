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

/* Reporting module submenu items */

$menu[] = array('Header', __('Reporting'));
$menu[] = array(__('Collection Statistic'), MODULES_WEB_ROOT_DIR.'reporting/index.php', __('View Library Collection Statistic'));
$menu[] = array(__('Loan Report'), MODULES_WEB_ROOT_DIR.'reporting/loan_report.php', __('View Library Loan Report'));
$menu[] = array(__('Membership Report'), MODULES_WEB_ROOT_DIR.'reporting/member_report.php', __('View Membership Report'));
$menu[] = array('Header', __('Other Reports'));
// other/custom report menu
require MODULES_BASE_DIR.'reporting/customs/customs_report_list.inc.php';
?>
