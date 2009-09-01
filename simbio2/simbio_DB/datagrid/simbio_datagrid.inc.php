<?php
/**
 * simbio_datagrid class
 * SQL datagrid creator
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

class simbio_datagrid extends simbio_table
{
    /**
     * Private properties
     */
    private $grid_real_q = false;

    /**
     * Protected properties
     */
    protected $grid_result_fields = array();
    protected $grid_result_rows = array();
    protected $sql_table = '';
    protected $sql_column = '';
    protected $sql_criteria = '';
    protected $sql_order = '';
    protected $primary_keys = array();
    protected $no_sort_column = array();
    protected $modified_content = array();
    protected $editable = false;

    /**
     * Public properties
     */
    public $debug = false;
    public $num_rows = 0;
    public $chbox_form_URL = '';
    public $alt_row_color = '#FFFFFF';
    public $alt_row_color_2 = '#CCCCCC';
    public $edit_link_text = '';
    public $table_name = 'datagrid';
    public $column_width = array();
    public $sort_column = array();
    public $sql_group_by = '';
    public $select_flag = '';
    public $chbox_property = array('itemID', lang_sys_common_tblheader_delete);
    public $edit_property = array('itemID', lang_sys_common_tblheader_edit);
    public $chbox_action_button = false;
    public $chbox_confirm_msg = false;
    public $current_page = 1;
    public $query_time = 1;
    # are we using AJAX or not
    public $using_AJAX = true;
    public $invisible_fields = array();

    /**
     * Method to create datagrid
     *
     * @param   object  $obj_db
     * @param   string  $str_db_table
     * @param   integer $int_num2show
     * @param   boolean $bool_editable
     * @return  string
     */
    public function createDataGrid($obj_db, $str_db_table = '', $int_num2show = 30, $bool_editable = false)
    {
        // check database connection
        if (!$obj_db OR $obj_db->error) {
            $_error = '<div style="padding: 5px; margin: 3px; border: 1px dotted #FF0000; color: #FF0000;">';
            $_error .= 'ERROR : Cant create datagrid, database connection error!';
            $_error .= '</div>';
            return $_error;
        }

        // set editable flag
        $this->editable = $bool_editable;

        if (!$this->chbox_confirm_msg) {
            $this->chbox_confirm_msg = defined('lang_sys_common_confirm_delete_selected')?lang_sys_common_confirm_delete_selected:'Are You Sure To Delete Selected Data?';
        }

        if (!$this->chbox_action_button) {
            $this->chbox_action_button = defined('lang_sys_common_button_delete_selected')?lang_sys_common_button_delete_selected:'Delete Selected Data';
        }

        $this->sql_table = $str_db_table;
        $this->highlight_row = true;

        if (empty($this->sql_table)) {
            die('simbio_datagrid : Table not specified yet');
        }

        // get page number from http get var
        if (isset($_GET['page']) AND $_GET['page'] > 1) {
            $this->current_page = (integer) $_GET['page'];
        }

        // count the row offset
        if ($this->current_page <= 1) {
            $_offset = 0;
        } else {
            $_offset = ($this->current_page*$int_num2show) - $int_num2show;
        }

        // change the record sorting if there fld var in URL
        $_dir = 'ASC';
        $_next_dir = 'DESC';
        $_sort_dir_info = lang_sys_common_tblheader_hover_sort_asc;
        if (isset($_GET['fld']) AND !empty($_GET['fld'])) {
            $this->sql_order = 'ORDER BY `'.urldecode($_GET['fld']).'` ';
        }
        // record order direction
        if (isset($_GET['dir']) AND ($_dir = trim($_GET['dir']))) {
            if ($_dir == 'DESC') {
                $_next_dir = 'ASC';
            } else {
                $_next_dir = 'DESC';
                $_sort_dir_info = lang_sys_common_tblheader_hover_sort_desc;
            }
            // append sort direction
            $this->sql_order .= $_dir;
        }

        // check group by
        if ($this->sql_group_by) {
            $this->sql_group_by = ' GROUP BY '.$this->sql_group_by;
        }

        // sql string
        $_sql_str = 'SELECT SQL_CALC_FOUND_ROWS '.$this->select_flag.' '.$this->sql_column.
            ' FROM '.$this->sql_table.' '.$this->sql_criteria.
            ' '.$this->sql_group_by.' '.$this->sql_order." LIMIT $int_num2show OFFSET $_offset";

        // for debugging purpose only
        // return $_sql_str;

        // real query
        $_start = function_exists('microtime')?microtime(true):time();
        $this->grid_real_q = $obj_db->query($_sql_str);
        $_end = function_exists('microtime')?microtime(true):time();
        $this->query_time = round($_end-$_start, 5);
        // if the query error
        if (!$this->grid_real_q OR $obj_db->error) {
            $_error = '<div style="padding: 5px; margin: 3px; border: 1px dotted #FF0000; color: #FF0000;">';
            $_error .= 'ERROR<br />';
            $_error .= 'MySQL Server said : '.$obj_db->error.'';
            if ($this->debug) {
                $_error .= '<br />With SQL Query : '.strip_tags($_sql_str).'';
            }
            $_error .= '</div>';
            return $_error;
        }
        // check if there any rows returned
        if ($this->grid_real_q->num_rows < 1) {
            return $this->printTable();
        }

        // fetch total number of data
        $_num_query = $obj_db->query('SELECT FOUND_ROWS()');
        $_data = $_num_query->fetch_row();
        $this->num_rows = $_data[0];

        // check the query string and rebuild with urlencoded value
        $_url_query_str = '';
        if (isset($_SERVER['QUERY_STRING']) AND !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $arr_query_var);
            // rebuild query str without "fld" and "dir" var
            foreach ($arr_query_var as $varname => $varvalue) {
                if (!is_scalar($varvalue)) {
                    continue;
                }
                $varvalue = urlencode($varvalue);
                if ($varname != 'fld' AND $varname != 'dir') {
                    $_url_query_str .= $varname.'='.$varvalue.'&';
                }
            }
        }

        // make all field name link for sorting
        $this->grid_result_fields = array();
        // adding record order links to field name header
        foreach ($this->grid_real_q->fetch_fields() as $_fld) {
            // check if the column is not listed in no_sort_column array properties
            if (!in_array($_fld->name, $this->no_sort_column) AND isset($this->sort_column[$_fld->name])) {
                $_order_by = 'fld='.urlencode($this->sort_column[$_fld->name]).'&dir='.$_next_dir;
                if ($this->using_AJAX) {
                    $this->grid_result_fields[] = '<a onmouseover="window.status = \'\'; return true;" '
                        .'href="javascript: setContent(\'mainContent\', \''.$_SERVER['PHP_SELF'].'?'.$_url_query_str.$_order_by.'\', \'get\');" title="'.lang_sys_common_tblheader_hover_sort.' '.$_fld->name.' '.$_sort_dir_info.'">'.$_fld->name.'</a>';
                } else {
                    $this->grid_result_fields[] = '<a href="'.$_SERVER['PHP_SELF'].'?'.$_url_query_str.$_order_by.'" title="'.lang_sys_common_tblheader_hover_sort.' '.$_fld->name.' '.$_sort_dir_info.'">'.$_fld->name.'</a>';
                }
            } else {
                $this->grid_result_fields[] = $_fld->name;
            }
        }

        // table header and invisible fields shifting
        // if the table is editable
        if ($this->editable) {
            // invisible fields shifting value
            $_shift = 1;
            // chbox and edit property checking
            if ($this->chbox_property AND $this->edit_property) {
                $_edit_header_fields = array($this->chbox_property[1], $this->edit_property[1]);
                $_shift = 2;
            } else if ($this->chbox_property AND !$this->edit_property) {
                $_edit_header_fields = array($this->chbox_property[1]);
            } else {
                $_edit_header_fields = array($this->edit_property[1]);
            }
            // concat arrays
            unset($this->grid_result_fields[0]);
            $this->grid_result_fields = array_merge($_edit_header_fields, $this->grid_result_fields);
            // invisible field shifting
            if ($this->invisible_fields) {
                $_shifted_inv_fld = array();
                foreach ($this->invisible_fields as $_inv_fld) {
                    $_shifted_inv_fld[] = $_inv_fld+$_shift;
                }
                $this->invisible_fields = $_shifted_inv_fld;
            }
        }

        // field count
        $_field_cnt = count($this->grid_result_fields);

        $_row = 1;
        // records
        while ($_data = $this->grid_real_q->fetch_row()) {
            $this->grid_result_rows[$_row] = $_data;
            // modified content
            foreach ($this->modified_content as $_field_num => $_new_content) {
                // change the value of modified column
                if (isset($this->grid_result_rows[$_row][$_field_num])) {
                    // run callback function php script if the string is embraced by "callback{*}"
                    if (preg_match('@^callback\{.+\}@i', $_new_content)) {
                        // strip the "callback{" and "}" string to empty string
                        $_callback_func = str_replace(array('callback{', '}'), '', $_new_content);
                        if (function_exists($_callback_func)) {
                            // call the function
                            $this->grid_result_rows[$_row][$_field_num] = $_callback_func($obj_db, $this->grid_result_rows[$_row], $_field_num);
                        } else { $this->grid_result_rows[$_row][$_field_num] = $_callback_func; }
                    } else {
                        // replace the "{column_value}" marker with real column value
                        $this->grid_result_rows[$_row][$_field_num] = str_replace('{column_value}', $this->grid_result_rows[$_row][$_field_num], trim($_new_content));
                    }
                }
            }

            // if the table is editable
            // make delete checkbox and edit button
            if ($this->editable) {
                // reset edit_fields array
                $_edit_fields = array();
                // check if checkbox array is included
                if ($this->chbox_property) {
                    $_del_chbox = '<input type="checkbox" name="'.$this->chbox_property[0].'[]" value="'.$this->grid_result_rows[$_row][0].'" id="cbRow'.$_row.'" />';
                    $_edit_fields[] = $_del_chbox;
                }
                // check if edit link array is included
                if ($this->edit_property) {
                    if ($this->using_AJAX) {
                        $_edit_link = '<a href="#" class="editLink ajaxLink" '
                            .'onclick="setContent(\'mainContent\', \''.$_SERVER['PHP_SELF'].'?'.$_url_query_str.'\', \'post\', \''.$this->edit_property[0].'='.$this->grid_result_rows[$_row][0].'&detail=true\', true);" title="Detail">'.( $this->edit_link_text?$this->edit_link_text:'&nbsp;' ).'</a>';
                    } else {
                        $_edit_link = '<a href="#" class="editLink" '
                            .'onclick="showDetailForm(\'notAJAXhiddenForm\', \''.$_SERVER['PHP_SELF'].'?'.$_url_query_str.'\', \''.$this->grid_result_rows[$_row][0].'\')" title="Edit">'.( $this->edit_link_text?$this->edit_link_text:'&nbsp;' ).'</a>';
                    }
                    $_edit_fields[] = $_edit_link;
                }
                // unset the first element (ID field)
                unset($this->grid_result_rows[$_row][0]);
                $this->grid_result_rows[$_row] = array_merge($_edit_fields, $this->grid_result_rows[$_row]);
            }

            // editable field style and column width modification
            for ($f = 0; $f < $_field_cnt; $f++) {
                if (($this->chbox_property AND $this->edit_property) AND ($f < 2) AND $this->editable) {
                    $this->setCellAttr($_row, $f, 'align="center" valign="top" style="width: 5%;"');
                } else {
                    // checking for special field width value set by column_width property array
                    $_row_attr = 'valign="top"';
                    if ($this->editable) {
                        if (($this->chbox_property AND $this->edit_property) AND isset($this->column_width[$f-2])) {
                            $_row_attr .= ' style="width: '.$this->column_width[$f-2].';"';
                        } else if ( (($this->chbox_property AND !$this->edit_property) OR (!$this->chbox_property AND $this->edit_property)) AND isset($this->column_width[$f-1])) {
                            $_row_attr .= ' style="width: '.$this->column_width[$f-1].';"';
                        }
                    } else {
                        if (isset($this->column_width[$f])) {
                            $_row_attr .= ' style="width: '.$this->column_width[$f].';"';
                        }
                    }
                    $this->setCellAttr($_row, $f, $_row_attr);
                }
            }
            $_row++;
        }

        // free resultset memory
        $this->grid_real_q->free_result();

        // return the formatted output
        return $this->makeOutput($int_num2show);
    }


    /**
     * Method to format an output of datagrid
     *
     * @param   integer $int_num2show
     * @return  string
     */
    protected function makeOutput($int_num2show = 30)
    {
        // remove invisible fields
        $this->removeInvisibleField();
        // get fields array and set the table header
        $this->setHeader($this->grid_result_fields);
        // data loop
        foreach ($this->grid_result_rows as $_data) {
            // append array to table
            $this->appendTableRow($_data);
        }

        // init buffer return var
        $_buffer = '';

        // create paging
        $_paging =  null;
        if ($this->num_rows > $int_num2show) {
            if ($this->using_AJAX) {
                $_paging = simbio_paging_ajax::paging($this->num_rows, $int_num2show, 5);
            } else {
                $_paging = simbio_paging::paging($this->num_rows, $int_num2show, 5);
            }
        }
        // setting form target
        $_target = '_self';
        $_iframe = '<form name="notAJAXhiddenForm" id="notAJAXhiddenForm" action="" method="post" style="display: inline;"><input type="hidden" name="itemID" value="" /><input type="hidden" name="detail" value="true" /></form>';
        if ($this->using_AJAX) {
            $_target = 'submitExec';
            $_iframe = '<iframe name="submitExec" style="visibility: hidden; width: 100%; height: 0;"></iframe>'."\n";
            // below is for debugging purpose only
            // $_iframe = '<iframe name="submitExec" style="visibility: visible; width: 100%; height: 300px;"></iframe>'."\n";
        }
        // if editable
        if ($this->editable) {
            $_buffer .= '<form action="'.$this->chbox_form_URL.'" name="'.$this->table_name.'" id="'.$this->table_name.'" target="'.$_target.'" method="post" style="display: inline;">'."\n";

            if (defined('lang_sys_common_form_checkbox_all')) {
                $_check_all = lang_sys_common_form_checkbox_all;
            }

            if (defined('lang_sys_common_form_uncheckbox_all')) {
                $_uncheck_all = lang_sys_common_form_uncheckbox_all;
            }

            // action buttons group
            $_button_grp = '<table cellspacing="0" cellpadding="5" style="background-color: #dcdcdc; width: 100%;"><tr>';
            // if checkbox is include then show button
            if ($this->chbox_property) {
                $_button_grp .= '<td><input type="button" value="'.$this->chbox_action_button.'" class="button confSubmit" /> '
                    .'<input type="button" value="'.$_check_all.'" class="button check-all" /> '
                    .'<input type="button" value="'.$_uncheck_all.'" class="button uncheck-all" /> '
                    .'</td>';
            }

            // paging
            if ($_paging) {
                $_button_grp .= '<td align="right">'.$_paging."\n".'</td>';
            }
            $_button_grp .= '</tr></table>'."\n";

            // table grid
            $_buffer .= $_button_grp.$this->printTable().$_button_grp
                .'<input type="hidden" name="itemAction" value="true" />'
                .'<input type="hidden" name="lastQueryStr" value="'.$_SERVER['QUERY_STRING'].'" />'."\n"
                .'</form>'."\n"
                .$_iframe;
        } else {
            // paging
            $_button_grp = '';
            if ($_paging) {
                $_button_grp .= '<table cellspacing="0" cellpadding="5" class="notprinted" style="background-color: #dcdcdc; width: 100%;">'
                    .'<tr><td align="right">'.$_paging."\n".'</td></tr></table>';
            }

            $_buffer .= $_button_grp.$this->printTable().$_button_grp;
        }

        return $_buffer;
    }


    /**
     * Method to set datagrid fields
     *
     * @param   string  $sql_field
     * @return  void
     */
    public function setSQLColumn()
    {
        $_args_num = func_num_args();
        if ($_args_num < 1) {
            $this->sql_column = '*';
        } else if ($_args_num == 1) {
            $this->sql_column = func_get_arg(0);
        } else {
            // get all function arguments
            $columns = func_get_args();
            // iterate all arguments
            foreach ($columns as $_field) {
                $_column_alias = '';
                $_real_column = '';
                if (preg_match('/\sAS\s/i', $_field)) {
                    $_field_n_alias = explode(' AS ', $_field);
                    $_real_column = $_field_n_alias[0];
                    $_column_alias = str_replace("'", '', $_field_n_alias[1]);
                } else {
                    $_real_column = $_field;
                    $_column_alias = $_field;
                }
                // store to class properties
                $this->sql_column .= $_field.', ';
                // $this->sort_column[trim($_column_alias)] = trim($_real_column);
                $this->sort_column[trim($_column_alias)] = trim($_column_alias);
            }

            // remove the last comma
            $this->sql_column = substr_replace($this->sql_column, ' ', -2);
        }
        // for debugging purpose only
        // var_dump($this->sort_column); die();
    }


    /**
     * Method to set SQL criteria (WHERE definition) of datagrid
     *
     * @param   string  $str_where_clause
     * @return  void
     */
    public function setSQLCriteria($str_where_clause)
    {
        if (!$str_where_clause) {
            // do nothing
        } else {
            // remove WHERE word if exist
            $str_where_clause = preg_replace("/^WHERE\s/i", '', $str_where_clause);
            $this->sql_criteria = 'WHERE '.$str_where_clause;
        }
    }


    /**
     * Method to set ordering of datagrid
     *
     * @param   string  $str_order_column
     */
    public function setSQLorder($str_order_column)
    {
        if (!$str_order_column) {
            // do nothing
        } else {
            // remove WHERE word if exist
            $this->sql_order = 'ORDER BY '.$str_order_column;
        }
    }


    /**
     * Method to disable sorting link of certain fields in datagrid
     *
     * @param   integer $field_number
     * @return  void
     */
    public function disableSort()
    {
        if (func_num_args() > 0) {
            $this->no_sort_column = func_get_args();
        }
    }


    /**
     * Method to modify column content of field in datagrid
     *
     * @param   integer $int_column_no
     * @param   string  $str_new_value
     * @return  void
     */
    public function modifyColumnContent($int_column_no, $str_new_value)
    {
        $this->modified_content[$int_column_no] = $str_new_value;
    }


    /**
     * Method to remove invisible field
     *
     * @return  void
     */
    protected function removeInvisibleField()
    {
        if (!$this->invisible_fields OR !$this->grid_result_rows) return;
        $_result_rows_buffer = array();
        foreach ($this->grid_result_rows as $_data) {
            foreach ($this->invisible_fields as $_inv_fld) {
                unset($_data[$_inv_fld]);
                // remove header field to
                unset($this->grid_result_fields[$_inv_fld]);
            }
            $_result_rows_buffer[] = $_data;
        }
        $this->grid_result_rows = $_result_rows_buffer;
    }
}

?>
