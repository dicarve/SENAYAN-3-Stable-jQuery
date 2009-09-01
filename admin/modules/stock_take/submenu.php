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

/* Stock Take module submenu items */

$menu[] = array('Header', __('Stock Take'));
$menu[] = array(__('Stock Take History'), MODULES_WEB_ROOT_DIR.'stock_take/index.php', __('View Stock Take History'));
// check if there is any active stock take proccess
$stk_query = $dbs->query('SELECT * FROM stock_take WHERE is_active=1');
if ($stk_query->num_rows) {
    $menu[] = array(__('Current Stock Take'), MODULES_WEB_ROOT_DIR.'stock_take/current.php', __('View Current Stock Take Process'));
    $menu[] = array(__('Stock Take Report'), MODULES_WEB_ROOT_DIR.'stock_take/st_report.php', __('View Current Stock Take Report'));
    $menu[] = array(__('Finish Stock Take'), MODULES_WEB_ROOT_DIR.'stock_take/finish.php', __('Finish Current Stock Take Proccess'));
    $menu[] = array(__('Current Lost Item'), MODULES_WEB_ROOT_DIR.'stock_take/lost_item_list.php', __('View Lost Item in Current Stock Take Proccess'));
    $menu[] = array(__('Stock Take Log'), MODULES_WEB_ROOT_DIR.'stock_take/st_log.php', __('View Log of Current Stock Take Proccess'));
    $menu[] = array(__('Resynchronize'), MODULES_WEB_ROOT_DIR.'stock_take/resync.php', __('Resynchronize bibliographic data with current stock take'));
    $menu[] = array(__('Upload List'), MODULES_WEB_ROOT_DIR.'stock_take/st_upload.php', __('Upload List in text file'));
} else {
    $menu[] = array(__('Initialize'), MODULES_WEB_ROOT_DIR.'stock_take/init.php', __('Initialize New Stock Take Proccess'));
}
?>
