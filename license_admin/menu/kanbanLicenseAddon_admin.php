<?php

global $sugar_version;

$admin_option_defs=array();

if(preg_match( "/^6.*/", $sugar_version) ) {
    $admin_option_defs['Administration']['kanbanlicenseaddon_info']= array('helpInline','LBL_KANBANLICENSEADDON_LICENSE_TITLE','LBL_KANBANLICENSEADDON_LICENSE','./index.php?module=kanban&action=license');
} else {
    $admin_option_defs['Administration']['kanbanlicenseaddon_info']= array('helpInline','LBL_KANBANLICENSEADDON_LICENSE_TITLE','LBL_KANBANLICENSEADDON_LICENSE','javascript:parent.SUGAR.App.router.navigate("#bwc/index.php?module=kanban&action=license", {trigger: true});');
}

$admin_group_header[]= array('LBL_KANBANLICENSEADDON','',false,$admin_option_defs, '');
