<?php
/**
 * Created by CaroDev.
 * User: Jacky
 */

require_once 'include/ListView/ListViewSmarty.php';
require_once 'include/ListView/KanbanViewData.php';

class KanbanViewSmarty extends ListViewSmarty
{
    public $select = false;

    public function __construct()
    {
        parent::__construct();
        $this->lvd = new KanbanViewData();
    }

    /**
     * Setup the class
     * @param seed SugarBean Seed SugarBean to use
     * @param file File Template file to use
     * @param string $where
     * @param offset :0 int offset to start at
     * @param int :-1 $limit
     * @param string []:array() $filter_fields
     * @param array :array() $params
     *    Potential $params are
     * $params['distinct'] = use distinct key word
     * $params['include_custom_fields'] = (on by default)
     * $params['massupdate'] = true by default;
     * $params['handleMassupdate'] = true by default, have massupdate.php handle massupdates?
     * @param string :'id' $id_field
     * @return bool
     */
    function setup($seed, $file, $where, $params = array(), $offset = 0, $limit = -1, $filter_fields = array(), $id_field = 'id', $id = null)
    {
        $this->should_process = true;
        if (isset($seed->module_dir) && !$this->shouldProcess($seed->module_dir)) {
            return false;
        }
        if (isset($params['export'])) {
            $this->export = $params['export'];
        }
        if (!empty($params['multiSelectPopup'])) {
            $this->multi_select_popup = $params['multiSelectPopup'];
        }
        if (!empty($params['massupdate']) && $params['massupdate'] != false) {
            $this->show_mass_update_form = true;
            $this->mass = $this->getMassUpdate();
            $this->mass->setSugarBean($seed);
            if (!empty($params['handleMassupdate']) || !isset($params['handleMassupdate'])) {
                $this->mass->handleMassUpdate();
            }
        }
        $this->seed = $seed;

        $filter_fields = $this->setupFilterFields($filter_fields);

        $data = $this->lvd->getListViewData($seed, $where, $offset, $limit, $filter_fields, $params, $id_field, true, $id);

        $this->fillDisplayColumnsWithVardefs();

        $this->process($file, $data, $seed->object_name);

        return true;
    }
}