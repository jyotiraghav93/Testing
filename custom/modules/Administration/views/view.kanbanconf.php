<?php

class ViewKanbanConf extends SugarView
{
    public function process()
    {
        parent::process();

        if (!empty($_POST['kbmodule']) && !empty($_POST['kbfield']) && !empty($_POST['kboptions'])) {
            $kbmodule = $_POST['kbmodule'];
            $kblimit = $_POST['kblimit'];
            $kbfield = $_POST['kbfield'];
            $kboptions = $_POST['kboptions'];

            $file_path = 'custom/modules/Administration/kanban.conf.php';
            $kanban_config = [];
            if (is_file($file_path)) {
                $kanban_config = include $file_path;

                $kanban_config[$kbmodule] = [
                    'field' => $kbfield,
                    'limit' => $kblimit,
                    'views' => $kboptions
                ];
            } else {
                $kanban_config[$kbmodule] = [
                    'field' => $kbfield,
                    'limit' => $kblimit,
                    'views' => $kboptions
                ];
            }

            $file = fopen($file_path, 'w');
            fwrite($file, "<?php\n\n return " . var_export($kanban_config, true) . ';');
            fclose($file);

            // write menu
            $QRR_Required = false;
            foreach ($kanban_config as $m => $o) {
                $file_menu_path = "custom/Extension/modules/$m/Ext/Menus/Kanban.menu.php";

                mkdir_recursive("custom/Extension/modules/$m/Ext/Menus");

                if (!file_exists($file_menu_path)) {
                    $file_menu = fopen($file_menu_path, 'w');
                    fwrite($file_menu, "<?php\n\n\$module_menu[] = Array('index.php?module=$m&action=Kanban', 'Kanban View', 'List', '$m');");
                    fclose($file_menu);
                    $QRR_Required = true;
                }


                $file_menu_path = "custom/modules/$m/action_view_map.php";
                if (!file_exists($file_menu_path)) {
                    $file_menu = fopen($file_menu_path, 'w');
                    fwrite($file_menu, "<?php\n\n\$action_view_map['kanban'] = 'kanban';");
                    fclose($file_menu);
                    $QRR_Required = true;
                }
            }

            if($QRR_Required == true){
                $this->taskRunFullQRR();
            }
        }
    }

    public function display()
    {
        global $mod_strings, $app_strings, $moduleList, $beanList, $beanFiles;

        //////////////////
        
        require_once('modules/kanban/license/OutfittersLicense.php');
        $validate_license = OutfittersLicense::isValid('kanban');
        if($validate_license !== true) {
            echo '<p class="error">Kanban Ninja is no longer active due to the following reason: '.$validate_license.' Users will have limited to no access until the issue has been addressed.</p>';
            return;
            //functionality may be altered here in response to the key failing to validate
        }       
        
        //////////////////
        // config
        $config = array();
        $file_path = 'custom/modules/Administration/kanban.conf.php';
        if (file_exists($file_path)) {
            $config = include $file_path;
        }

        $modules = [];
        $moduleList[] = 'ProjectTask';
        foreach ($moduleList as $module) {
            $modules[$module] = $module;
        }

        $this->ss->assign('APP', $app_strings);
        $this->ss->assign('MOD', $mod_strings);
        $this->ss->assign('MODULES', $modules);
        $this->ss->assign('CONFIG', $config);

        $m = '';
        $l = '';

        if (!empty($_GET['load'])) {
            $m = $_GET['load'];
        }

        if (!empty($_GET['list'])) {
            $l = $_GET['list'];
        }

        if (!$m && !$l) {
            $this->ss->display('custom/modules/Administration/tpls/KanbanConf.tpl');
        } else if (!$l) {
            if ($beanList[$m]) {
                require_once $beanFiles[$beanList[$m]];
                /* @var $focus Basic */
                $focus = new $beanList[$m];

                $fields = $focus->field_defs;

                $enum_fields = '<option></option>';
                foreach ($fields as $field => $options) {
                    if ($options['type'] == 'enum' || $options['type'] == 'multienum') {
                        $selected = '';
                        if (!empty($config[$m]) && $config[$m]['field'] == $field) {
                            $selected = 'selected';
                        }

                        $enum_fields .= '<option '. $selected .' value="' . $field . '" list="' . $options['options'] . '">' . $field . '</option>';
                    }
                }

                header('Content-Type: application/json');
                echo json_encode(array(
                    'limit' => !empty($config[$m]['limit']) ? $config[$m]['limit'] : 10000,
                    'options' => $enum_fields
                ));
                sugar_cleanup(true);
            }
        } else {
            global $app_list_strings;

            echo $l;

            if (!empty($app_list_strings[$l])) {
                $mconf = $_GET['mconf'];
                $options = $app_list_strings[$l];
                $str = '';
                foreach ($options as $value => $label) {
                    $selected = '';
                    if (!empty($config[$mconf]) && in_array($value, $config[$mconf]['views'])) {
                        $selected = 'selected';
                    }

                    $str .= '<option '. $selected .' value="' . $value . '">' . $label . '</option>';
                }

                echo $str;
            }
        }
    }


    function taskRunFullQRR() {
        global $mod_strings;
        global $current_user;
        
        // setup LBL_ALL_MODULES for full QRR
        $backupModStrings = isset($mod_strings) ? $mod_strings : null;
        require 'modules/Administration/language/en_us.lang.php';
        // perform full QRR - execute query mode
        require_once 'modules/Administration/QuickRepairAndRebuild.php';
        $repairer = new RepairAndClear();
        $repairer->repairAndClearAll(array('clearAll'), array($mod_strings['LBL_ALL_MODULES']), true, false);
        // reset altered flags to it's original state
        $GLOBALS['installing'] = true;
        $mod_strings = $backupModStrings;
    }
}