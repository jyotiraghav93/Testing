<?php

require_once 'include/MVC/View/views/view.list.php';
require_once 'include/ListView/KanbanViewSmarty.php';

/**
 * Class ViewKanban
 * @property SugarBean $bean
 */
class ViewKanban extends ViewList
{
    public function __construct()
    {
        parent::__construct();
    }

    public function preDisplay()
    {
        $this->lv = new KanbanViewSmarty();
    }

    public function display()
    {
        //////////////////
        
        require_once('modules/kanban/license/OutfittersLicense.php');
        $validate_license = OutfittersLicense::isValid('kanban');
        if($validate_license !== true) {
            echo '<p class="error">Kanban Ninja is no longer active due to the following reason: '.$validate_license.' Users will have limited to no access until the issue has been addressed.</p>';
            return;
            //functionality may be altered here in response to the key failing to validate
        }       
        
        //////////////////
        parent::display();
    }

    public function process()
    {
        $this->changeColumn();
        parent::process();
    }

    public function listViewProcess()
    {
        if (!$this->headers) {
            return;
        }

        global $app_list_strings, $app_strings;

        $_REQUEST['action'] = 'Kanban';

        $this->processSearchForm();
        $this->lv->searchColumns = $this->searchForm->searchColumns;

        if (empty($_REQUEST['search_form_only']) || $_REQUEST['search_form_only'] == false) {
            $no_config = true;

            // check config
            $config = array();

            $module = $this->module;
            $config[$module] = array();

            $file_path = 'custom/modules/Administration/kanban.conf.php';
            if (is_file($file_path)) {
                $config = include $file_path;
            }

            if ($module == 'Opportunities' && empty($config[$module])) {
                $config[$module] = array (
                    'field' => 'sales_stage',
                    'limit' => '10000',
                    'views' => array(
                        'Prospecting',
                        'Needs Analysis',
                        'Value Proposition',
                        'Proposal/Price Quote',
                        'Negotiation/Review',
                    ),
                );
            }

            if (!empty($config[$module]) && !empty($config[$module]['views'])) {
                $no_config = false;

                $field = $config[$module]['field'];
                $field_columns = $config[$module]['views'];
                $field_options = $app_list_strings[$this->bean->field_defs[$field]['options']];

                $columns = array();

                foreach ($field_options as $v => $l) {
                    if (in_array($v, $field_columns)) {
                        $columns[$l] = $v;
                    }
                }

                $this->lv->ss->assign('KBWIDTH', (count($columns) * 320));
                $this->lv->ss->assign('COLUMNS', $columns);

                // setup view
                // where
                $where = $this->bean->table_name . '.' . $field . " IN ('" . implode("','", $field_columns) . "')";
                if ($this->where) {
                    $this->where .= ' AND ' . $where;
                } else {
                    $this->where = $where;
                }

                $this->params['custom_select'] = ' , ' . $this->bean->table_name . '.' . $field;

                // search
                $this->lv->ss->assign("SEARCH", true);
                $this->lv->ss->assign('savedSearchData', $this->searchForm->getSavedSearchData());
                // file content
                $file_content = '';
                if (file_exists("custom/modules/{$this->module}/tpls/Kanban.tpl")) {
                    $file_content = "custom/modules/{$this->module}/tpls/Kanban.tpl";
                }
                $this->lv->ss->assign('FILE_CONTENT', $file_content);
                // limit
                $limit = !empty($config[$module]['limit']) ? $config[$module]['limit'] : 10000;
                $file = 'include/ListView/ListViewKanban.tpl';
                if (file_exists("custom/modules/{$this->module}/tpls/ListViewKanban.tpl")) {
                    $file = "custom/modules/{$this->module}/tpls/ListViewKanban.tpl";
                }

                $this->lv->setup($this->seed, $file, $this->where, $this->params, 0, $limit);

                // get data kanban
                $data = $this->lv->data['data'];
                $data_raw = $this->lv->data['data_raw'];

                $sources = $this->getKanbanData($data, $data_raw, $field);
                $this->lv->ss->assign('DATA', $sources);
            }

            $this->lv->ss->assign('MODULE', $this->module);
            $this->lv->ss->assign('APP', $app_strings);
            $this->lv->ss->assign('NO_CONFIG', $no_config);
            $this->lv->ss->assign('LIST_FIELDS', $this->lv->displayColumns);
            $this->lv->ss->assign('actionDisabledLink', '');

            $savedSearchName = empty($_REQUEST['saved_search_select_name']) ? '' : (' - ' . $_REQUEST['saved_search_select_name']);
            echo $this->lv->display();
        }
    }

    /**
     * @param $data array data display from list view
     * @param $data_raw array raw data from list view
     * @param $field string setting field for kanban
     * @return array
     */
    public function getKanbanData($data, $data_raw, $field)
    {
        $sources = array();

        foreach ($data as $row) {
            $row['USER'] = $this->getResources($row['ASSIGNED_USER_ID']);
            $row['COLUMN_VALUE'] = $data_raw[$row['ID']][$field];
            $sources[$data_raw[$row['ID']][$field]][] = $row;
        }

        return $sources;
    }

    /**
     * changeColumn
     */
    public function changeColumn()
    {
        if (!empty($_POST['item_id']) && !empty($_POST['new_value'])) {
            $file_path = 'custom/modules/Administration/kanban.conf.php';
            if (is_file($file_path)) {
                $config = include $file_path;
            }

            $module = $_POST['module'];
            $record = $_POST['item_id'];
            $field = $config[$module]['field'];
            $value = $_POST['new_value'];

            if (!empty($config[$module]) && !empty($config[$module]['views'])) {
                $focus = BeanFactory::getBean($module, $record);
                $focus->$field = $value;
                $focus->save();
            }

            sugar_cleanup(true);
        }
    }

    /**
     * get user info
     *
     * @param $user_id
     * @return array
     */
    public function getResources($user_id)
    {
        $result = $this->bean->db->query("SELECT * FROM users WHERE id = '$user_id' AND is_group = 0 AND status = 'Active'");

        $row = $this->bean->db->fetchRow($result);
        $user = array(
            'id' => $row['id'],
            'name' => $row['user_name'],
            'image' => !empty($row['photo']) ? 'index.php?entryPoint=download&id='. $row['id'] .'_photo&type=Users' : 'include/javascript/jqwidgets450/jqwidgets/styles/images/common.png',
        );

        return $user;
    }
}