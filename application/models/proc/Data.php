<?php

class Data {

    ///////////////////////////////
    //GRID FUCNCTIONS 
    ///////////////////////////////
    public function makeGridFilter($table, $grid_query) {
        $table_filter = array();
	if( isset($grid_query['filter']) ){
	    foreach ($grid_query['filter'] as $key => $val){
		if (preg_match('/[a-z_]+/', $key)){
		    $table_filter[] = "$key LIKE '%$val%'";
		}
	    }
	}
        return $table_filter;
    }

    public function getGridData($table, $grid_query = NULL, $table_select = '*', $table_where = '', $table_order = '') {
        $table_data = array();
        $table_data['total_rows'] = 0;
        $table_data['total_pages'] = 0;
        $table_data['page'] = 0;

        $table_filter = '';
        $table_where = $table_where ? "WHERE $table_where" : '';
        if ($grid_query) {
            $table_filter = $this->makeGridFilter($table, $grid_query);
            $table_filter = count($table_filter) ? 'WHERE ' . implode(' AND ', $table_filter) : '';
            if ($grid_query['limit'] == 0) {
                $table_data['total_rows'] = 0;
                $table_data['total_pages'] = 0;
                $table_data['page'] = 0;
                $table_limit = "";
            } else {
                $total_rows = $this->Base->get_row("SELECT COUNT(*) FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter", 0);
                $limit = $grid_query['limit'] ? $grid_query['limit'] : 30;
                $total_pages = ceil($total_rows / $limit);
                if ($grid_query['page'] > $total_pages) {
                    $grid_query['page'] = $total_pages;
                }
                if (!$grid_query['page'] || $grid_query['page'] < 1) {
                    $grid_query['page'] = 1;
                }
                $offset = ($grid_query['page'] - 1) * $limit;
                $table_data['total_rows'] = $total_rows;
                $table_data['total_pages'] = $total_pages;
                $table_data['page'] = $grid_query['page'];
                $table_limit = "LIMIT $offset, $limit";
            }
        } else {
            $table_limit = '';
        }
        $table_data['items'] = $this->Base->get_list("SELECT * FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter $table_order $table_limit");
        // $this->Base->msg("SELECT * FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter $table_order $table_limit");
        return $table_data;
    }

    public function getGridStructure($grid_name) {
        $json_str = file_get_contents("config/data_grids/$grid_name.json", true);
        if ($json_str) {
            $gridStructure = json_decode($json_str, true);
            if (!$gridStructure)
                $this->Base->response_wrn("Structure file is corrupted\n\n$json_str");
        }
        else {
            $field_list = $this->Base->get_field_list($grid_name);
            $gridStructure = array();
            $gridStructure['grid_name'] = $grid_name;
            $gridStructure['readonly'] = 1;
            $gridStructure['toolnames'] = array("download", "print");
            $gridStructure['identifier'] = $field_list['keys'] ? $field_list['keys'][0] : "";
            $gridStructure['columns'] = array();
            foreach ($field_list['full'] as $full) {
                $col_data = array();
                $col_data['field'] = $full['Field'];
                $col_data['name'] = $full['Field'];
                $gridStructure['columns'][] = $col_data;
            }
        }
        return $gridStructure;
    }

    public function getGridOut($grid_stucture, $grid_data, $out_type) {
        $this->Base->LoadClass('FileEngine');
        if ($out_type == '.print') {
            $file_name = $out_type;
            $this->Base->FileEngine->show_controls = true;
        } else {
            $file_name = "DataFrom_{$grid_stucture['grid_name']}$out_type";
        }
        $this->Base->FileEngine->grid_structure = $grid_stucture;
        $this->Base->FileEngine->tplModifier = function( $FileEngine, $Worksheet ) {
            $headerX = 0;
            $headerY = 1;
            $contentY = $headerY + 1;
            $headerTpl = $Worksheet->getCellByColumnAndRow($headerX, $headerY)->getValue();
            $cellTpl = $Worksheet->getCellByColumnAndRow($headerX, $headerY + 1)->getValue();
            foreach ($FileEngine->grid_structure['columns'] as $i => $column) {
                $Worksheet->getColumnDimension(chr(65 + $headerX + $i))->setWidth($column['width'] ? intval($column['width']) / 8 : -1 );
                $Worksheet->getCellByColumnAndRow($headerX + $i, $headerY)->setValue(str_replace('_name_', $column['name'], $headerTpl));
                $Worksheet->getCellByColumnAndRow($headerX + $i, $contentY)->setValue(str_replace('_field_', $column['field'], $cellTpl));
            }
            $alfaHeaderStart = chr(65 + $headerX);
            $alfaHeaderStop = chr(65 + $headerX + $i);
            $Worksheet->duplicateStyle($Worksheet->getStyle("$alfaHeaderStart$headerY"), "$alfaHeaderStart$headerY:$alfaHeaderStop$headerY");
            $Worksheet->duplicateStyle($Worksheet->getStyle("$alfaHeaderStart$contentY"), "$alfaHeaderStart$contentY:$alfaHeaderStop$contentY");
            return $Worksheet;
        };
        $this->Base->FileEngine->assign($grid_data, 'xlsx/TPL_Grid.xlsx');
        $this->Base->FileEngine->send($file_name);
        //exit;
    }

    public function checkGridField($grid_name, $field_name) {
        /*
         * Maybe it is necessary to store last grid structure on svar?
         */
        $this->cached_fields[$grid_name] = $this->getGridStructure($grid_name);
        foreach ($this->cached_fields[$grid_name]['columns'] as $col)
            if ($col['field'] == $field_name)
                return true;
        return false;
    }

    public function deleteGridRows($table_name, $delIds) {
        $where = array();
        foreach ($delIds as $rowkey) {
            $where[] = $this->makeWhere($table_name, $rowkey);
        }
        $where = count($where) ? implode(' OR ', $where) : '';
        if ($where)
            $this->Base->query("DELETE FROM $table_name WHERE $where");
    }

    public function insertGridRow($table_name, $newrow) {
        $set = $this->makeSet($table_name, $newrow);
        if ($set)
            $this->Base->query("INSERT INTO $table_name SET $set");
    }

    public function updateGridRow($table_name, $rowkey, $value) {
        $set = $this->makeSet($table_name, $value);
        $where = $this->makeSet($table_name, $rowkey);
        if ($set && $where)
            $this->Base->query("UPDATE $table_name SET $set WHERE $where");
    }

    private function makeSet($table_name, $row) {
        $set = array();
        foreach ($row as $key => $val) {
            if (!$this->checkGridField($table_name, $key)) {
                $this->Base->msg($key . ' Fake field!');
                return;
            }
            $set[] = "$key='" . addslashes($val) . "'";
        }
        return count($set) ? implode(', ', $set) : '';
    }

    private function makeWhere($table_name, $rowkey) {
        $where = array();
        foreach ($rowkey as $key => $val) {
            $where[] = "$key='" . addslashes($val) . "'";
        }
        return count($where) ? implode(' AND ', $where) : '';
    }

    public function loadFromFile($table_name, $file_name) {
        require_once "libraries/report/PHPExcel.php";
        $structure = $this->getGridStructure($table_name);
        $this->PHPexcel = PHPExcel_IOFactory::load($file_name);
        if ($this->PHPexcel) {
            $this->Worksheet = $this->PHPexcel->getActiveSheet();
            foreach ($this->Worksheet->getRowIterator() as $row) {
                $i = 0;
                $rowfields = array();
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    $field = $structure['columns'][$i]['field'];
                    if ($i == 0 && $value == '' || !$field)
                        break;
                    if ($value == '-') {
                        $i++;
                        continue;
                    }
                    $rowfields[] = "$field='" . addslashes($value) . "'";
                    $i++;
                }
                if (!empty($rowfields)){
                    $this->replaceTableRow($table_name, $rowfields);
                }
            }
            $this->Base->msg("OK $i");
            return true;
        } else
            return false;
    }

    private function replaceTableRow($table_name, $rowfields) {
        $set = implode(',', $rowfields);
        $this->Base->query("INSERT INTO $table_name SET $set ON DUPLICATE KEY UPDATE $set",false);
    }

    /////////////////////////DATA TABLE FUNCTIONS/////////////////////////////
    public function getTableData($table, $table_query = NULL, $table_select = '*', $table_where = '', $table_order = '') {
        $table_data = array();
        $table_data['total_rows'] = 0;
        $table_data['total_pages'] = 0;
        $table_data['page'] = 0;

        $table_filter = $this->get_where($table_query['cols'], $table_query['vals']);
        $table_where = $table_where ? "WHERE $table_where" : '';

        if ($table_query) {
            if ($table_query['limit'] == 0) {
                $table_data['total_rows'] = 0;
                $table_data['total_pages'] = 0;
                $table_data['page'] = 0;
                $table_limit = "";
            } else {
                $total_rows = $this->Base->get_row("SELECT COUNT(*) FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter", 0);
                $limit = $table_query['limit'] ? $table_query['limit'] : 30;
                $total_pages = ceil($total_rows / $limit);
                if ($table_query['page'] > $total_pages)
                    $table_query['page'] = $total_pages;
                if (!$table_query['page'] || $table_query['page'] < 1)
                    $table_query['page'] = 1;
                $offset = ($table_query['page'] - 1) * $limit;

                $table_data['total_rows'] = $total_rows;
                $table_data['total_pages'] = $total_pages;
                $table_data['page'] = $table_query['page'];
                $table_limit = "LIMIT $offset, $limit";
            }
        }
        else {
            $table_limit = '';
        }

        $table_data['rows'] = array();
        $res = $this->Base->query("SELECT * FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter $table_order $table_limit");
        //$this->Base->msg("SELECT * FROM (SELECT $table_select FROM $table $table_where) AS t $table_filter $table_order $table_limit");

        while ($row = mysql_fetch_row($res)) {
            $table_data['rows'][] = $row;
        }
        mysql_free_result($res);

        return $table_data;
    }

    public function getTableStructure($table_name, $column_property = 'all', $fromdb = false) {
        $json_str = file_get_contents("config/data_tables/$table_name.json", true);
        if ($json_str && !$fromdb) {
            $table_structure = json_decode($json_str, true);
            if (!$table_structure)
                $this->Base->response_wrn("Structure file corrupted\n\n$json_str");

            if ($column_property != 'all') {
                $new_columns = array();
                foreach ($table_structure['columns'] as $col) {
                    $new_columns[] = $col[$column_property];
                }
                $table_structure['columns'] = $new_columns;
            }
        } else {
            $field_list = $this->Base->get_field_list($table_name);
            $table_structure = array();

            $table_structure['readonly'] = 1;
            $table_structure['table_name'] = $table_name;
            $table_structure['toolnames'] = array("download", "print");
            $table_structure['columns'] = array();
            foreach ($field_list['full'] as $full) {
                $col_data = array();
                $col_data['field'] = $full['Field'];
                $col_data['name'] = $full['Field'];
                $col_data['type'] = (strpos($full['Type'], 'int') === false && strpos($full['Type'], 'double') === false ? 'string' : 'number');
                if ($full['Key']) {
                    $col_data['readonly'] = 1;
                    $col_data['is_key'] = 1;
                }
                if ($column_property == 'all')
                    array_push($table_structure['columns'], $col_data);
                else
                    array_push($table_structure['columns'], $col_data[$column_property]);
            }
        }
        return $table_structure;
    }

    public function checkField($table_name, $field_name) {//Not working
        if (!$this->cached_fields[$table_name]) {
            $this->cached_fields[$table_name] = $this->getTableStructure($table_name, 'field');
        }
        $table_structure = $this->cached_fields[$table_name];
        if (in_array($field_name, $table_structure['columns']))
            return true;
        else
            return false;
    }

    public function setTableData($table_name, $table_data) {
        $this->Base->set_level(2);
        $field_list = $this->Base->get_field_list($table_name);
        foreach ($table_data as $table_row) {
            $set = $this->make_set($field_list, $table_row);
            $where = $this->make_where($field_list, $table_row);

            $this->Base->query("UPDATE $table_name SET $set WHERE $where");
            if (!mysql_affected_rows()) {
                $this->Base->query("INSERT INTO $table_name SET $set", false);
            }
        }
    }

    public function deleteRows($table_name, $delete_ids) {
        $where = $this->make_where_from_id($table_name, $delete_ids);
        $this->Base->query("DELETE FROM $table_name WHERE $where");
    }

    public function updateRow($table_name, $update_id, $update_col, $update_val) {
        $where = $this->make_where_from_id($table_name, $update_id);
        $this->Base->query("UPDATE $table_name SET $update_col='$update_val' WHERE $where");
    }

    public function insertRow($table_name, $insert_id) {
        $set = "SET " . $this->make_set_from_id($table_name, $insert_id);
        $this->Base->query("INSERT INTO $table_name $set");
    }

    /////////////////////////TREE FUNCTIONS/////////////////////////////
    public function getSubBranchIds($table_name, $branch_id) {
        $branch_ids = array($branch_id);
        $res = $this->Base->query("SELECT branch_id FROM $table_name WHERE parent_id='$branch_id'");
        while ($row = mysql_fetch_row($res)) {
            $sub_branch_ids = $this->getSubBranchIds($table_name, $row[0]);
            $branch_ids = array_merge($branch_ids, $sub_branch_ids);
        }
        mysql_free_result($res);
        return $branch_ids;
    }

    private function getTopBranch($table_name, $branch_id) {
        $top_id = $this->Base->get_row("SELECT parent_id FROM $table_name WHERE branch_id='$branch_id'", 0);
        if ($top_id == 0)
            return $branch_id;
        return $this->getTopBranch($top_id);
    }

    public function getTreeChildren($table_name, $parent_id, $bid_alias = 'branch_id', $pid_alias = 'parent_id', $label_alias = 'label', $depth = 'all') {
        $user_level = $this->Base->svar('user_level');
        $branches = array();
        $res = $this->Base->query("SELECT branch_id as '$bid_alias',parent_id as '$pid_alias',label as '$label_alias', branch_data, level FROM $table_name WHERE parent_id='$parent_id' AND level<='$user_level' ORDER BY is_leaf, label");
        while ($branch = mysql_fetch_assoc($res)) {
            if ($branch['branch_data']) {
                $bdata = json_decode($branch['branch_data']);
                unset($branch['branch_data']);
                foreach ($bdata as $param => $val)
                    $branch[$param] = $val;
            }
            if ($depth == 'all') {
                $branch['item'] = $this->getTreeChildren($table_name, $branch['id'], $bid_alias, $pid_alias, $label_alias, $depth);
            } else if ($depth == 'toplevel') {
                $children_count = $this->Base->get_row("SELECT COUNT(*) FROM $table_name WHERE parent_id=" . $branch[$bid_alias], 0);
            } else
                return;
            if (isset($branch['item']) || $children_count) {
                $branch['child'] = 1;
                $branch['im0'] = 'folderClosed.gif';
            } else
                $branch['child'] = 0;
            if ($branch['level']) {
                $branch['im0'] = $branch['im1'] = $branch['im2'] = 'lock.gif';
            }
            $branches[] = $branch;
        }
        mysql_free_result($res);
        return $branches;
    }

    public function insertTreeBranch($table_name, $parent_id, $label, $is_leaf, $branch_data) {
        $parent = $this->Base->get_row("SELECT is_leaf,level,top_id FROM $table_name WHERE branch_id='$parent_id'");
        if ($parent['is_leaf'])
            return -1;
        //$top_id=$this->getTopBranch()
        $this->Base->query("INSERT INTO $table_name SET top_id='{$parent['top_id']}', level='{$parent['level']}', parent_id='$parent_id', label='$label', is_leaf='$is_leaf', branch_data='$branch_data'");
        $new_branch_id = mysql_insert_id();
        if ($parent_id == 0) {
            //New branch is root so top_id==branch_id;
            $this->Base->query("UPDATE $table_name SET top_id=branch_id WHERE branch_id=$new_branch_id");
        } else {
            //$this->Base->updateTreeBranchPath($table_name,$new_branch_id);
        }
        return $new_branch_id;
    }

    public function updateTreeBranch($table_name, $branch_id, $parent_id, $label, $is_leaf = NULL, $branch_data = NULL) {
        $parent = $this->Base->get_row("SELECT is_leaf,level,top_id FROM $table_name WHERE branch_id='$parent_id'");
        $branch = $this->Base->get_row("SELECT top_id FROM $table_name WHERE branch_id='$branch_id'");
        if (!$parent['is_leaf']) {
            $top_id = $parent_id == 0 ? $branch_id : $parent['top_id'];
            $set = '';
            $set.=$is_leaf !== NULL ? ",is_leaf='$is_leaf'" : '';
            $set.=$branch_data !== NULL ? ",branch_data='$branch_data'" : '';
            $this->Base->query("UPDATE $table_name SET top_id='$top_id', parent_id='$parent_id',label='$label' $set WHERE branch_id='$branch_id'");
            /*
             * UPDATING top_id of nested branches if changed
             */
            if ($branch['top_id'] != $top_id) {
                $sub_parents_ids = $this->getSubBranchIds($table_name, $branch_id);
                $sub_parents_where = "branch_id='" . implode("' OR branch_id='", $sub_parents_ids) . "'";
                $this->Base->query("UPDATE $table_name SET top_id=$top_id WHERE $sub_parents_where");
            }
        }
        //$this->Base->updateTreeBranchPath($table_name,$branch_id);
        return $this->Base->get_row("SELECT * FROM $table_name WHERE branch_id='$branch_id'");
    }

    public function updateTreeBranchPath($table_name, $branch_id) {
        //$this->Base->query("SET @old_path:='',@new_path:=''");
        $this->Base->query(
                "UPDATE $table_name t1
						LEFT JOIN
					$table_name t2 ON t1.parent_id = t2.branch_id 
				SET 
					t1.path = @old_path:=COALESCE(t1.path,''),
					t1.path = @new_path:=CONCAT(COALESCE(t2.path, ''), '>', t1.label)
				WHERE
					t1.branch_id = $branch_id");
        $this->Base->query(
                "UPDATE $table_name 
				SET 
					path = REPLACE(path, @old_path, @new_path)
				WHERE
					path LIKE CONCAT(@old_path, '>%');");
    }

    public function deleteTreeBranch($table_name, $branch_id) {
        $sub_parents_ids = $this->getSubBranchIds($table_name, $branch_id);
        $sub_parents_where = "branch_id='" . implode("' OR branch_id='", $sub_parents_ids) . "'";
        $this->Base->query("START TRANSACTION");
        $this->Base->query("DELETE FROM $table_name WHERE $sub_parents_where");
        $this->Base->query("COMMIT");
        return true;
    }

    public function lockTreeBranch($table_name, $branch_id, $level) {
        $user_level = $this->Base->svar('user_level');
        if ($level > $user_level)
            return;
        $ids = $this->getSubBranchIds($table_name, $branch_id);
        $ids = implode("' OR branch_id='", $ids);
        $this->Base->query("UPDATE $table_name SET level='$level' WHERE branch_id='$ids'");
    }

    //////////////////////////////////////////////
    //             PRIVATE FUNCTIONS
    //////////////////////////////////////////////
    protected function get_where($cols, $vals, $custom_where = '') {
        if (!empty($cols)) {
            $cases = array();
            for ($i = 0; $i < count($cols); $i++) {
                $cases[] = "$cols[$i] LIKE '%" . str_replace(' ', '%', $vals[$i]) . "%'";
            }
            $where = 'WHERE (' . implode(' AND ', $cases) . ')';
            if ($custom_where)
                $where.=" AND ( $custom_where )";
        }
        else if (!empty($custom_where)) {
            $where = "WHERE $custom_where";
        } else {
            $where = '';
        }
        return $where;
    }

    private function make_where_from_id($table_name, $ids) {
        // $ids [["key1","key2"],["key3","key4"]]
        $table_keys = $this->Base->get_field_list($table_name);
        $where = '';
        foreach ($ids as $id) {
            $where_and = '';
            for ($i = 0; $i < count($table_keys['keys']); $i++) {
                $where_and.=" AND " . $table_keys['keys'][$i] . "='$id[$i]'";
            }
            $where_and = substr($where_and, 4);
            $where.='  OR ' . $where_and;
        }
        return substr($where, 4);
    }

    private function make_set_from_id($table_name, $ids) {
        // $ids ["key1","key2"]
        $table_keys = $this->Base->get_field_list($table_name);

        $set = '';
        for ($i = 0; $i < count($table_keys['keys']); $i++) {
            $set.=", " . $table_keys['keys'][$i] . "='$ids[$i]'";
        }
        return substr($set, 1);
    }

    public function make_set($field_list, $table_row) {
        $set = '';
        for ($i = 0; $i < $field_list['count']; $i++) {
            if ($table_row[$i + 1] == '-')
                continue;
            $set.=", " . $field_list['columns'][$i] . "='" . addslashes($table_row[$i + 1]) . "'";
        }
        return substr($set, 1);
    }

    public function make_where($field_list, $table_row) {
        $where = '';
        $key_count = count($field_list['keys']);
        for ($i = 0; $i < $key_count; $i++) {
            $where.=" AND " . $field_list['keys'][$i] . "='" . $table_row[$i + 1] . "'";
        }
        return substr($where, 4);
    }

}

?>