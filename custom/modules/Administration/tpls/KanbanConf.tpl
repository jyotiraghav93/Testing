{literal}
    <style>
        .kanbanconf tr td {
            padding: 5px 0;
        }
    </style>
{/literal}

<h3>Current Config</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-bottom: 1px solid #666666">
    <tr style="border-bottom: 1px solid #dddddd">
        <td><strong>Module</strong></td>
        <td><strong>Field</strong></td>
        <td><strong>Limit</strong></td>
        <td><strong>Columns</strong></td>
    </tr>

    {foreach from=$CONFIG item=C key=M}
        <tr>
            <td>{$M}</td>
            <td>{$C.field}</td>
            <td>{$C.limit}</td>
            <td>
                {foreach from=$C.views item=column}
                    <strong>{$column}</strong> |
                {/foreach}
            </td>
        </tr>
    {/foreach}
</table>

<form action="" method="post" >
    <input type="hidden" name="module" value="Administration">
    <input type="hidden" name="action" value="KanbanConf">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="kanbanconf">
        <tr><td colspan='100'><h2>Kanban Config</h2></td></tr>
        <tr><td colspan='100'>Config Kanban View</td></tr>
        <tr><td><br></td></tr>

        <tr>
            <td colspan='100'>
                <table border="0" cellspacing="1" cellpadding="1" class="actionsContainer">
                    <tr>
                        <td>
                            <input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="button primary" type="button" value="Save" name="save" onclick="saveSettings()" value="{$APP.LBL_SAVE_BUTTON_LABEL}" >
                            <input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="button" onclick="this.form.action.value='index'; this.form.module.value='Administration';" type="submit" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
                        </td>
                    </tr>
                </table>

                <div class='add_table' style='margin-bottom:5px'>
                    <table id="KanbanConf" class="themeSettings edit view" style='margin-bottom:0px;' border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="20%" scope="row">Choose Module: <span class="required">*</span></td>
                            <td width="30%">
                                <select name="kbmodule" id="kbmodule" onchange="loadFields(this)">
                                    <option></option>
                                    {html_options options=$MODULES}
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" scope="row">Items per page: </td>
                            <td width="30%"><input type="text" name="kblimit" id="kblimit" value=""></td>
                        </tr>
                        <tr>
                            <td width="20%" scope="row">Choose Field to View: <span class="required">*</span></td>
                            <td width="30%">
                                <select name="kbfield" id="chooseFieldKB" onchange="loadOptions(this)"></select>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" scope="row">Choose Options to View: <span class="required">*</span></td>
                            <td width="30%">
                                <select name="kboptions[]" id="chooseOptionsKB" multiple="multiple"></select>
                            </td>
                        </tr>
                    </table>
                </div>

                <table border="0" cellspacing="1" cellpadding="1" class="actionsContainer">
                    <tr>
                        <td>
                            <input title="{$APP.LBL_SAVE_BUTTON_TITLE}" class="button primary" onclick="saveSettings()" type="button" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" >
                            <input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" class="button" onclick="this.form.action.value='index'; this.form.module.value='Administration';" type="submit" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>

{literal}
<script>
    function saveSettings() {
        SUGAR.ajaxUI.showLoadingPanel();
        var kbmodule = $('select[name=kbmodule]').val();
        var chooseFieldKB  = $('#chooseFieldKB :selected').text();
        var kblimit  = $('input[name=kblimit]').val();
        var chooseOptionsKB = [];
        $("#chooseOptionsKB ").each(function(i, sel){
            chooseOptionsKB = $(sel).val(); 
        });

        var data = {
            module: 'Administration',
            action: 'KanbanConf',
            kbmodule: kbmodule,
            kblimit: kblimit,
            kbfield: chooseFieldKB,
            kboptions: chooseOptionsKB,
            save: 'saving'
        }
            
        request = $.ajax({
            url: 'index.php?to_pdf=1&module=Administration&action=KanbanConf',
            type: "post",
            data: data
        });


        request.complete(function (response, textStatus, jqXHR){
            setTimeout(function(){ location.reload(); SUGAR.ajaxUI.hideLoadingPanel();}, 4000);
        });

        // Callback handler that will be called on failure
        request.fail(function (jqXHR, textStatus, errorThrown){
            // Log the error to the console
            console.error(
                "The following error occurred: "+
                textStatus, errorThrown
            );
        });
    }
    function loadFields(obj) {
        var module = $(obj).val();
        $.get('index.php?to_pdf=1&module=Administration&action=KanbanConf&load=' + module, function (data) {
            $('#chooseFieldKB').html(data.options);
            $('input[name=kblimit]').val(data.limit);
            $('#chooseFieldKB').change();
        });
    }

    function loadOptions(obj) {
        var list = $('#chooseFieldKB option:selected').attr('list');
        var m = $('select[name=kbmodule]').val();
        $('#chooseOptionsKB').load('index.php?to_pdf=1&module=Administration&action=KanbanConf&list=' + list + '&mconf=' + m);
    }
</script>
{/literal}