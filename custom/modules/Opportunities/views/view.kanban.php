<?php
/**
 * Created by CaroDev.
 * User: Jacky
 */

require_once 'include/MVC/View/views/view.kanban.php';

class OpportunitiesViewKanban extends ViewKanban
{
    public function __construct()
    {
        parent::__construct();
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
            $key = $data_raw[$row['ID']][$field];
            $row['COLUMN_VALUE'] = $key;
            $row['STATUS'] = 'default';
            if ($data_raw[$row['ID']]['date_closed'] <= date('Y-m-d')) {
                $row['STATUS'] = 'danger';
            }
            if (empty($sources[$key . 'total_amount'])) {
                $sources[$key . 'total_amount'] = 0;
            }
            $sources[$key . 'total_amount'] += $data_raw[$row['ID']]['amount_usdollar'];
            $sources[$key][] = $row;
        }

        return $sources;
    }
}