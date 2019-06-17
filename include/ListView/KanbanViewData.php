<?php
/**
 * Created by CaroDev.
 * User: Jacky
 */

require_once 'include/ListView/ListViewData.php';

class KanbanViewData extends ListViewData
{
    /**
     * takes in a seed and creates the list view query based off of that seed
     * if the $limit value is set to -1 then it will use the default limit and offset values
     *
     * it will return an array with two key values
     *    1. 'data'=> this is an array of row data
     *  2. 'pageData'=> this is an array containg three values
     *            a.'ordering'=> array('orderBy'=> the field being ordered by , 'sortOrder'=> 'ASC' or 'DESC')
     *            b.'urls'=>array('baseURL'=>url used to generate other urls ,
     *                            'orderBy'=> the base url for order by
     *                            //the following may not be set (so check empty to see if they are set)
     *                            'nextPage'=> the url for the next group of results,
     *                            'prevPage'=> the url for the prev group of results,
     *                            'startPage'=> the url for the start of the group,
     *                            'endPage'=> the url for the last set of results in the group
     *            c.'offsets'=>array(
     *                                'current'=>current offset
     *                                'next'=> next group offset
     *                                'prev'=> prev group offset
     *                                'end'=> the offset of the last group
     *                                'total'=> the total count (only accurate if totalCounted = true otherwise it is either the total count if less than the limit or the total count + 1 )
     *                                'totalCounted'=> if a count query was used to get the total count
     *
     * @param SugarBean $seed
     * @param string $where
     * @param int :0 $offset
     * @param int :-1 $limit
     * @param string []:array() $filter_fields
     * @param array :array() $params
     *    Potential $params are
     * $params['distinct'] = use distinct key word
     * $params['include_custom_fields'] = (on by default)
     * $params['custom_XXXX'] = append custom statements to query
     * @param string :'id' $id_field
     * @return array('data'=> row data, 'pageData' => page data information, 'query' => original query string)
     */
    public function getListViewData($seed, $where, $offset = -1, $limit = -1, $filter_fields = array(), $params = array(), $id_field = 'id', $singleSelect = true, $id = null)
    {
        global $current_user;
        require_once 'include/SearchForm/SearchForm2.php';
        SugarVCR::erase($seed->module_dir);
        $this->seed =& $seed;
        $totalCounted = empty($GLOBALS['sugar_config']['disable_count_query']);
        $_SESSION['MAILMERGE_MODULE_FROM_LISTVIEW'] = $seed->module_dir;
        if (empty($_REQUEST['action']) || $_REQUEST['action'] != 'Popup') {
            $_SESSION['MAILMERGE_MODULE'] = $seed->module_dir;
        }

        $this->setVariableName($seed->object_name, $where, $this->listviewName, $id);

        $this->seed->id = '[SELECT_ID_LIST]';

        // if $params tell us to override all ordering
        if (!empty($params['overrideOrder']) && !empty($params['orderBy'])) {
            $order = $this->getOrderBy(strtolower($params['orderBy']), (empty($params['sortOrder']) ? '' : $params['sortOrder'])); // retreive from $_REQUEST
        } else {
            $order = $this->getOrderBy(); // retreive from $_REQUEST
        }

        // still empty? try to use settings passed in $param
        if (empty($order['orderBy']) && !empty($params['orderBy'])) {
            $order['orderBy'] = $params['orderBy'];
            $order['sortOrder'] = (empty($params['sortOrder']) ? '' : $params['sortOrder']);
        }

        //rrs - bug: 21788. Do not use Order by stmts with fields that are not in the query.
        // Bug 22740 - Tweak this check to strip off the table name off the order by parameter.
        // Samir Gandhi : Do not remove the report_cache.date_modified condition as the report list view is broken
        $orderby = $order['orderBy'];
        if (strpos($order['orderBy'], '.') && ($order['orderBy'] != "report_cache.date_modified")) {
            $orderby = substr($order['orderBy'], strpos($order['orderBy'], '.') + 1);
        }
        if ($orderby != 'date_entered' && !in_array($orderby, array_keys($filter_fields))) {
            $order['orderBy'] = '';
            $order['sortOrder'] = '';
        }

        if (empty($order['orderBy'])) {
            $orderBy = '';
        } else {
            $orderBy = $order['orderBy'] . ' ' . $order['sortOrder'];
            //wdong, Bug 25476, fix the sorting problem of Oracle.
            if (isset($params['custom_order_by_override']['ori_code']) && $order['orderBy'] == $params['custom_order_by_override']['ori_code'])
                $orderBy = $params['custom_order_by_override']['custom_code'] . ' ' . $order['sortOrder'];
        }

        if (empty($params['skipOrderSave'])) { // don't save preferences if told so
            $current_user->setPreference('listviewOrder', $order, 0, $this->var_name); // save preference
        }

        // If $params tells us to override for the special last_name, first_name sorting
        if (!empty($params['overrideLastNameOrder']) && $order['orderBy'] == 'last_name') {
            $orderBy = 'last_name ' . $order['sortOrder'] . ', first_name ' . $order['sortOrder'];
        }

        $ret_array = $seed->create_new_list_query($orderBy, $where, $filter_fields, $params, 0, '', true, $seed, $singleSelect);
        $ret_array['inner_join'] = '';
        if (!empty($this->seed->listview_inner_join)) {
            $ret_array['inner_join'] = ' ' . implode(' ', $this->seed->listview_inner_join) . ' ';
        }

        if (!is_array($params)) $params = array();
        if (!isset($params['custom_select'])) $params['custom_select'] = '';
        if (!isset($params['custom_from'])) $params['custom_from'] = '';
        if (!isset($params['custom_where'])) $params['custom_where'] = '';
        if (!isset($params['custom_order_by'])) $params['custom_order_by'] = '';
        $main_query = $ret_array['select'] . $params['custom_select'] . $ret_array['from'] . $params['custom_from'] . $ret_array['inner_join'] . $ret_array['where'] . $params['custom_where'] . $ret_array['order_by'] . $params['custom_order_by'];
        //C.L. - Fix for 23461
        if (empty($_REQUEST['action']) || $_REQUEST['action'] != 'Popup') {
            $_SESSION['export_where'] = $ret_array['where'];
        }
        if ($limit < -1) {
            $result = $this->db->query($main_query);
        } else {
            if ($limit == -1) {
                $limit = $this->getLimit();
            }
            $dyn_offset = $this->getOffset();
            if ($dyn_offset > 0 || !is_int($dyn_offset)) $offset = $dyn_offset;

            if (strcmp($offset, 'end') == 0) {
                $totalCount = $this->getTotalCount($main_query);
                $offset = (floor(($totalCount - 1) / $limit)) * $limit;
            }
            if ($this->seed->ACLAccess('ListView')) {
                $result = $this->db->limitQuery($main_query, $offset, $limit + 1);
            } else {
                $result = array();
            }

        }

        $data = array();

        $temp = clone $seed;

        $rows_raw = array();
        $rows = array();
        $count = 0;
        $idIndex = array();
        $id_list = '';

        while (($row = $this->db->fetchByAssoc($result)) != null) {
            if ($count < $limit) {
                $id_list .= ',\'' . $row[$id_field] . '\'';
                $idIndex[$row[$id_field]][] = count($rows);
                $rows_raw[$row['id']] = $row;
                $rows[] = $seed->convertRow($row);
            }
            $count++;
        }

        if (!empty($id_list)) {
            $id_list = '(' . substr($id_list, 1) . ')';
        }

        SugarVCR::store($this->seed->module_dir, $main_query);
        if ($count != 0) {
            //NOW HANDLE SECONDARY QUERIES
            if (!empty($ret_array['secondary_select'])) {
                $secondary_query = $ret_array['secondary_select'] . $ret_array['secondary_from'] . ' WHERE ' . $this->seed->table_name . '.id IN ' . $id_list;
                if (isset($ret_array['order_by'])) {
                    $secondary_query .= ' ' . $ret_array['order_by'];
                }

                $secondary_result = $this->db->query($secondary_query);

                $ref_id_count = array();
                while ($row = $this->db->fetchByAssoc($secondary_result)) {

                    $ref_id_count[$row['ref_id']][] = true;
                    foreach ($row as $name => $value) {
                        //add it to every row with the given id
                        foreach ($idIndex[$row['ref_id']] as $index) {
                            $rows[$index][$name] = $value;
                        }
                    }
                }

                $rows_keys = array_keys($rows);
                foreach ($rows_keys as $key) {
                    $rows[$key]['secondary_select_count'] = count($ref_id_count[$rows[$key]['ref_id']]);
                }
            }

            // retrieve parent names
            if (!empty($filter_fields['parent_name']) && !empty($filter_fields['parent_id']) && !empty($filter_fields['parent_type'])) {
                foreach ($idIndex as $id => $rowIndex) {
                    if (!isset($post_retrieve[$rows[$rowIndex[0]]['parent_type']])) {
                        $post_retrieve[$rows[$rowIndex[0]]['parent_type']] = array();
                    }
                    if (!empty($rows[$rowIndex[0]]['parent_id'])) $post_retrieve[$rows[$rowIndex[0]]['parent_type']][] = array('child_id' => $id, 'parent_id' => $rows[$rowIndex[0]]['parent_id'], 'parent_type' => $rows[$rowIndex[0]]['parent_type'], 'type' => 'parent');
                }
                if (isset($post_retrieve)) {
                    $parent_fields = $seed->retrieve_parent_fields($post_retrieve);
                    foreach ($parent_fields as $child_id => $parent_data) {
                        //add it to every row with the given id
                        foreach ($idIndex[$child_id] as $index) {
                            $rows[$index]['parent_name'] = $parent_data['parent_name'];
                        }
                    }
                }
            }

            $pageData = array();

            reset($rows);
            while ($row = current($rows)) {

                $temp = clone $seed;
                $dataIndex = count($data);

                $temp->setupCustomFields($temp->module_dir);
                $temp->loadFromRow($row);
                if (empty($this->seed->assigned_user_id) && !empty($temp->assigned_user_id)) {
                    $this->seed->assigned_user_id = $temp->assigned_user_id;
                }
                if ($idIndex[$row[$id_field]][0] == $dataIndex) {
                    $pageData['tag'][$dataIndex] = $temp->listviewACLHelper();
                } else {
                    $pageData['tag'][$dataIndex] = $pageData['tag'][$idIndex[$row[$id_field]][0]];
                }
                $data[$dataIndex] = $temp->get_list_view_data($filter_fields);
                $detailViewAccess = $temp->ACLAccess('DetailView');
                $editViewAccess = $temp->ACLAccess('EditView');
                $pageData['rowAccess'][$dataIndex] = array('view' => $detailViewAccess, 'edit' => $editViewAccess);
                $additionalDetailsAllow = $this->additionalDetails && $detailViewAccess && (file_exists(
                            'modules/' . $temp->module_dir . '/metadata/additionalDetails.php'
                        ) || file_exists('custom/modules/' . $temp->module_dir . '/metadata/additionalDetails.php'));
                $additionalDetailsEdit = $editViewAccess;
                if ($additionalDetailsAllow) {
                    if ($this->additionalDetailsAjax) {
                        $ar = $this->getAdditionalDetailsAjax($data[$dataIndex]['ID']);
                    } else {
                        $additionalDetailsFile = 'modules/' . $this->seed->module_dir . '/metadata/additionalDetails.php';
                        if (file_exists('custom/modules/' . $this->seed->module_dir . '/metadata/additionalDetails.php')) {
                            $additionalDetailsFile = 'custom/modules/' . $this->seed->module_dir . '/metadata/additionalDetails.php';
                        }
                        require_once($additionalDetailsFile);
                        $ar = $this->getAdditionalDetails($data[$dataIndex],
                            (empty($this->additionalDetailsFunction) ? 'additionalDetails' : $this->additionalDetailsFunction) . $this->seed->object_name,
                            $additionalDetailsEdit);
                    }
                    $pageData['additionalDetails'][$dataIndex] = $ar['string'];
                    $pageData['additionalDetails']['fieldToAddTo'] = $ar['fieldToAddTo'];
                }
                next($rows);
            }
        }
        $nextOffset = -1;
        $prevOffset = -1;
        $endOffset = -1;
        if ($count > $limit) {
            $nextOffset = $offset + $limit;
        }

        if ($offset > 0) {
            $prevOffset = $offset - $limit;
            if ($prevOffset < 0) $prevOffset = 0;
        }
        $totalCount = $count + $offset;

        if ($count >= $limit && $totalCounted) {
            $totalCount = $this->getTotalCount($main_query);
        }
        SugarVCR::recordIDs($this->seed->module_dir, array_keys($idIndex), $offset, $totalCount);
        $module_names = array(
            'Prospects' => 'Targets'
        );
        $endOffset = (floor(($totalCount - 1) / $limit)) * $limit;
        $pageData['ordering'] = $order;
        $pageData['ordering']['sortOrder'] = $this->getReverseSortOrder($pageData['ordering']['sortOrder']);
        //get url parameters as an array
        $pageData['queries'] = $this->generateQueries($pageData['ordering']['sortOrder'], $offset, $prevOffset, $nextOffset, $endOffset, $totalCounted);
        //join url parameters from array to a string
        $pageData['urls'] = $this->generateURLS($pageData['queries']);
        $pageData['offsets'] = array('current' => $offset, 'next' => $nextOffset, 'prev' => $prevOffset, 'end' => $endOffset, 'total' => $totalCount, 'totalCounted' => $totalCounted);
        $pageData['bean'] = array('objectName' => $seed->object_name, 'moduleDir' => $seed->module_dir, 'moduleName' => strtr($seed->module_dir, $module_names));
        $pageData['stamp'] = $this->stamp;
        $pageData['access'] = array('view' => $this->seed->ACLAccess('DetailView'), 'edit' => $this->seed->ACLAccess('EditView'));
        $pageData['idIndex'] = $idIndex;
        if (!$this->seed->ACLAccess('ListView')) {
            $pageData['error'] = 'ACL restricted access';
        }

        $queryString = '';

        if (isset($_REQUEST["searchFormTab"]) && $_REQUEST["searchFormTab"] == "advanced_search" ||
            isset($_REQUEST["type_basic"]) && (count($_REQUEST["type_basic"] > 1) || $_REQUEST["type_basic"][0] != "") ||
            isset($_REQUEST["module"]) && $_REQUEST["module"] == "MergeRecords"
        ) {
            $queryString = "-advanced_search";
        } else if (isset($_REQUEST["searchFormTab"]) && $_REQUEST["searchFormTab"] == "basic_search") {
            if ($seed->module_dir == "Reports") $searchMetaData = SearchFormReports::retrieveReportsSearchDefs();
            else $searchMetaData = SearchForm::retrieveSearchDefs($seed->module_dir);

            $basicSearchFields = array();

            if (isset($searchMetaData['searchdefs']) && isset($searchMetaData['searchdefs'][$seed->module_dir]['layout']['basic_search']))
                $basicSearchFields = $searchMetaData['searchdefs'][$seed->module_dir]['layout']['basic_search'];

            foreach ($basicSearchFields as $basicSearchField) {
                $field_name = (is_array($basicSearchField) && isset($basicSearchField['name'])) ? $basicSearchField['name'] : $basicSearchField;
                $field_name .= "_basic";
                if (isset($_REQUEST[$field_name]) && (!is_array($basicSearchField) || !isset($basicSearchField['type']) || $basicSearchField['type'] == 'text' || $basicSearchField['type'] == 'name')) {
                    // Ensure the encoding is UTF-8
                    $queryString = htmlentities($_REQUEST[$field_name], null, 'UTF-8');
                    break;
                }
            }
        }

        return array('data' => $data, 'data_raw' => $rows_raw, 'pageData' => $pageData, 'query' => $queryString);
    }

}