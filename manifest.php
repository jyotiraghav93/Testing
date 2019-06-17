<?php

$manifest = array(
    array(
        'acceptable_sugar_versions' => array(),
    ),
    array(
        'acceptable_sugar_flavors' => array(
            'CE',
            'PRO',
            'ENT',
        ),
    ),
    'readme' => 'See ReadMe.txt',
    'key' => 'CRMExpertsNY',
    'author' => 'CRMExpertsNY.Com',
    'description' => 'Kanban View v2 for all modules',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Kanban View Ninja for SuiteCRM',
    'published_date' => '2018-03-30 00:00:00',
    'type' => 'module',
    'version' => '2.2',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'copy' => array(
        array(
            'from' => '<basepath>/custom',
            'to' => 'custom',
        ),
        array(
            'from' => '<basepath>/include',
            'to' => 'include',
        ),
        array (
            'from' => '<basepath>/license',
            'to' => 'modules/kanban',
        ),
    ),
    'action_view_map' =>
    array (
        array(
            'from'=> '<basepath>/license_admin/actionviewmap/kanbanLicenseAddon_actionviewmap.php',
            'to_module'=> 'kanban',
        ),
    ),
    'administration' =>
    array(
        array(
            'from'=>'<basepath>/license_admin/menu/kanbanLicenseAddon_admin.php',
            'to' => 'modules/Administration/kanbanLicenseAddon_admin.php',
        ),
    ),
);
