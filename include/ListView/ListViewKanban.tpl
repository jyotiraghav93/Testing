<link rel="stylesheet" href="include/javascript/jqwidgets450/jqwidgets/styles/kanban.css" type="text/css"/>

{include file='include/ListView/ListViewColumnsFilterDialog.tpl'}
<script type='text/javascript' src='{sugar_getjspath file='include/javascript/popup_helper.js'}'></script>

{include file='include/ListView/ListViewKanbanPagination.tpl'}
<div id="KanbanContainer" style="width: {$KBWIDTH}px">
    <div id="KanbanContainerCore">
        <div class='row' style="padding: 0; margin: 0">
            {if $FILE_CONTENT}
                {include file=$FILE_CONTENT}
            {else}
                {foreach item=COLUMN key=CLABEL from=$COLUMNS}
                    <div class="kanban-col-container">
                        <div class="panel panel-default kanban-col ui-droppable" id="PANEL{$COLUMN|strtoupper|replace:' ':''|replace:'/':''}" data-column='{$COLUMN}'>
                            <div class="{$COLUMN | strtolower}-header panel-heading kanban-col-header">
                                <div class="title">
                                    <h4>
                                        {$CLABEL}&nbsp;
                                        <small style="color: #FFFFFF">(</small>
                                        <small id="count-items-{$COLUMN|replace:' ':''|replace:'/':''}" style="color: #FFFFFF">{$DATA[$COLUMN]|@count}</small>
                                        <small style="color: #FFFFFF">)</small>
                                    </h4>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="kanban-centered">
                                    {foreach from=$DATA[$COLUMN] item=ITEM}
                                        <article class="kanban-entry grab" id="item{$ITEM.ID}" data-id="{$ITEM.ID}" draggable="true">
                                            <div class="kanban-entry-inner">
                                                <div class="kanban-label">
                                                    <div class="row" style="margin: 0">
                                                        <div class="col-lg-8">
                                                            <h2>
                                                                <a href="index.php?module={$MODULE}&action=DetailView&record={$ITEM.ID}">
                                                                    {$ITEM.NAME}
                                                                </a>                                        
                                                            </h2>                                                        
                                                        </div>
                                                        <div class="text-right">
                                                            <a href="javascript:void(0);"><img id = "info_{$ITEM.ID}" src="include/javascript/jqwidgets450/jqwidgets/styles/images/kanbaan_info.png" onclick="ShowToolTip('{$ITEM.ID}')" height="20px" title=""></a>
                                                        </div>
                                                    </div>
                                                    {foreach from=$LIST_FIELDS item=OPTION key=FIELD}
                                                        <p>
                                                            <strong>{sugar_translate module=$MODULE label=$OPTION.label}: </strong>
                                                            <span>{sugar_field parentFieldArray=$ITEM vardef=$OPTION displayType=ListView field=$FIELD}</span>
                                                        </p>
                                                    {/foreach}
                                                </div>
                                            </div>
                                        </article>
                                    {/foreach}
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/if}
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<!-- Static Modal -->
<div class="modal modal-static fade" id="processing-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <i class="fa fa-refresh fa-5x fa-spin"></i>
                    <h4>Processing...</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var current_module = '{$MODULE}';
</script>
{literal}
<script>
    $(function () {
        var kanbanCol = $('#KanbanContainer');
//        kanbanCol.css({
//            'height': (window.innerHeight - 160) + 'px'
//        });

        draggableUIInit();

        // $('.panel-heading').click(function () {
        //     var $panelBody = $(this).parent().children('.panel-body');
        //     $panelBody.slideToggle();
        // });
    });

    function draggableUIInit() {
        var sourceId;
        var sourceColumn = '';
        var recordId = '';

        $('#KanbanContainer .panel-body .kanban-centered').sortable({
            connectWith: "#KanbanContainer .panel-body .kanban-centered",
            scroll: false,
            revert: 100,
            tolerance: 'pointer',
            cursor: 'move',
            distance: 10,
            helper: 'clone',
            appendTo: 'body',
            zIndex: 10000,
            start: function (event, ui) {
                sourceId = $(this).parents('.kanban-col').attr('id');
                sourceColumn = $(this).parents('.kanban-col').attr('data-column');
                recordId = ui.item.attr('data-id');
            },
            sort: function (event, ui) {

            },
            over: function (event, ui) {
                $('#KanbanContainer .panel-body').removeClass('kanban-target');
                $(this).parents('.panel-body').addClass('kanban-target');
            },
            stop: function (event, ui) {
                $('#KanbanContainer .panel-body').removeClass('kanban-target');
                var targetColumn = ui.item.parents('.kanban-col').attr('data-column');
                // Post data
                if (sourceColumn != targetColumn) {
                    $('#processing-modal').modal('toggle'); // before post
                    $.post('index.php', {
                        module: current_module,
                        action: 'Kanban',
                        to_pdf: 1,
                        item_id: recordId,
                        new_value: targetColumn
                    }, function (response) {
                        var count_source = $('#count-items-' + sourceColumn.replace(/ /g, '').replace(/\//g, ''));
                        var count_target = $('#count-items-' + targetColumn.replace(/ /g, '').replace(/\//g, ''));
                        var ncsource = parseInt(count_source.html());
                        var nctarget = parseInt(count_target.html());
                        count_source.html(ncsource - 1);
                        count_target.html(nctarget + 1);
                        $('#processing-modal').modal('toggle'); // after post
                    });
                }
            }
        }).disableSelection();
    }

    function ShowToolTip(id, module) {
         var infoID = "#info_"+id;
         console.log('mod : ',current_module);
                var url = 'index.php?to_pdf=1&module=Home&action=AdditionalDetailsRetrieve&bean=' + current_module + '&id=' + id;
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