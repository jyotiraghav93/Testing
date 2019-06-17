<?php
if (! defined('sugarEntry') || ! sugarEntry) die('Not A Valid Entry Point');


function post_install() {

    
    global $sugar_version;
    if(preg_match( "/^6.*/", $sugar_version)) {
        echo "
            <script>
            document.location = 'index.php?module=kanban&action=license';
            </script>"
        ;
    } else {
        echo "
            <script>
            var app = window.parent.SUGAR.App;
            window.parent.SUGAR.App.sync({callback: function(){
                app.router.navigate('#bwc/index.php?module=kanban&action=license', {trigger:true});
            }});
            </script>"
        ;
    }
}

