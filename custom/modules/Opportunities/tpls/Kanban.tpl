{foreach item=COLUMN key=CLABEL from=$COLUMNS}
    <div class="kanban-col-container">
        <div class="panel panel-default kanban-col ui-droppable" id="PANEL{$COLUMN|strtoupper|replace:' ':''|replace:'/':''}" data-column='{$COLUMN}'>
            <div class="{$COLUMN | strtolower}-header panel-heading kanban-col-header">
                <div class="title" style="top: 13px">
                    <h4>
                        {$CLABEL}&nbsp;
                        <p>
                            <small style="color: #FFFFFF; font-size: 11px">
                                {assign var=TOTAL_AMOUNT value=$COLUMN|cat:'total_amount'}
                                {sugar_currency_format var=$DATA[$TOTAL_AMOUNT] currency_symbol=true}
                                &nbsp;|&nbsp;
                            </small>
                            <small id="count-items-{$COLUMN|replace:' ':''|replace:'/':''}" style="color: #FFFFFF; font-size: 11px">{$DATA[$COLUMN]|@count}</small>
                        </p>
                    </h4>
                </div>
            </div>
            <div class="panel-body">
                <div class="kanban-centered u-clearfix">
                    {foreach from=$DATA[$COLUMN] item=ITEM}
                        <article class="kanban-entry grab" id="item{$ITEM.ID}" data-id="{$ITEM.ID}" draggable="true">
                            <div class="kanban-entry-inner">
                                <div class="kanban-label">
                                    <div class="row" style="margin: 0">
                                        <div class="col-lg-8">
                                            <h2>
                                                <a href="index.php?module={$MODULE}&action=DetailView&record={$ITEM.ID}" style="font-size: 13px">
                                                    {$ITEM.NAME}
                                                </a>
                                            </h2>
                                        </div>
                                        <div class="col-lg-4 text-right">
                                            <a href="javascript:void(0);"><img id = "info_{$ITEM.ID}" src="include/javascript/jqwidgets450/jqwidgets/styles/images/kanbaan_info.png" onclick="ShowToolTip('{$ITEM.ID}')" height="20px" title=""></a>
                                        </div>
                                    </div>
                                    <div class="row" style="margin: 0">
                                        <div class="col-lg-8">
                                            <a href="index.php?module=Accounts&action=DetailView&record={$ITEM.ACCOUNT_ID}">
                                                {sugar_field parentFieldArray=$ITEM vardef=$LIST_FIELDS.ACCOUNT_NAME displayType=ListView field="ACCOUNT_NAME"}
                                            </a>
                                        </div>
                                        <div class="col-lg-4">
                                            <span class="label label-{$ITEM.STATUS}" style="color: #FFFFFF">
                                                {sugar_field parentFieldArray=$ITEM vardef=$LIST_FIELDS.DATE_CLOSED displayType=ListView field="DATE_CLOSED"}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row" style="margin: 0; padding-top: 10px">
                                        <div class="col-lg-8">
                                            <img src="{$ITEM.USER.image}" height="32px" title="{sugar_field parentFieldArray=$ITEM vardef=$LIST_FIELDS.ASSIGNED_USER_NAME displayType=ListView field="ASSIGNED_USER_NAME"}">
                                            {sugar_field parentFieldArray=$ITEM vardef=$LIST_FIELDS.ASSIGNED_USER_NAME displayType=ListView field="ASSIGNED_USER_NAME"}
                                        </div>
                                        <div class="col-lg-4">
                                            {sugar_field parentFieldArray=$ITEM vardef=$LIST_FIELDS.AMOUNT_USDOLLAR displayType=ListView field="AMOUNT_USDOLLAR"}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
{/foreach}


{literal}               
    <script type="text/javascript">                             
        function ShowToolTip(id) {
             var infoID = "#info_"+id;

            var url = 'index.php?to_pdf=1&module=Home&action=AdditionalDetailsRetrieve&bean=Opportunities&id=' + id;
            var title = '<div class="qtip-title-text">Additional Information</div>' +
                '<div class="qtip-title-buttons">' +
                '</div>';
            var body = SUGAR.language.translate('app_strings', 'LBL_LOADING_PAGE');
            $(infoID).qtip({
                content: {
                    title: {
                        text: title,
                        button: true,
                    },
                    text: body,
                },
                events: {
                    render: function(event, api) {
                        $.ajax(url).done(function(data) {
                            SUGAR.util.globalEval(data);
                            var divCaption = "#qtip-" + api.id + "-title";
                            var divBody = "#qtip-" + api.id + "-content";
                            if (data.caption != "") {
                                $(divCaption).html(result.caption);
                            }
                            api.set('content.text', result.body);
                        }).fail(function() {
                            $(divBody).html(SUGAR.language.translate('app_strings', 'LBL_EMAIL_ERROR_GENERAL_TITLE'));
                        }).always(function() {});
                    }
                },
                position: {
                  my: 'top left',
                  at: 'bottom right'
                },
                show: {solo: true, ready: true, event: false},
                hide: {event: false},
                style: {
                    width: 224,
                    padding: 5,
                    color: 'black',
                    textAlign: 'left',
                    border: {
                        width: 1,
                        radius: 3
                    },
                    tip: 'topLeft',
                    classes: {
                        tooltip: 'ui-widget',
                        tip: 'ui-widget',
                        title: 'ui-widget-header',
                        content: 'ui-widget-content'
                    }
                }
            });
            $(infoID).qtip("show");
        }
    </script>
{/literal}