$(document).ready(function ()
{
    var isData =  $('#isData').val();
    if(isData == 0){
        var msg = 'Does not contains any record';
        CommonPopUp(msg);
    }
    hidebody();
    $('#collapse').hide();
    $('#butn').click(function()
    {
        ExpandOne();
    });
    $("a[name=tabs]").on("click", function () {
        var threadid = $(this).attr("data-var");
        var html = '<div><p>Are you sure? This will remove your thread.</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'List Post By Name', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "confirm": function () {
                    $(this).dialog("close");
                    var threadId = threadid;
                    jQuerySubmit('mark-as-remove-ajax', {threadId:threadId}, 'markAsRemoveSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    });
});

function hidebody()
{
    var count = $('#count').val();

    for(var i=0; i< count; i++){

        var node = document.getElementById('item'+i);
        node.className = 'hidden';
    }

}
function collapseall()
{
    var count = $('#count').val();
    for(var i=0; i< count; i++)
    {
        var node = document.getElementById('item' + i);
        var buti = document.getElementById('butn' + i);
        node.className = 'blockitems';
        buti.value = '-';
    }
    document.getElementById("expand").value = 'Collapse All';
    document.getElementById("expand").onclick = expandall;
}
function expandall()
{
    var count = $('#count').val();
    for(var i=0; i< count; i++)
    {
        var node = document.getElementById('item' + i);
        var buti = document.getElementById('butn' + i);
        node.className = 'hidden';
        buti.value = '+';
    }
    document.getElementById("expand").value = 'Expand All';
    document.getElementById("expand").onclick = collapseall;
}
function toggleshow(inum)
{
    var node = document.getElementById('item' + inum);
    var buti = document.getElementById('butn' + inum);
    if (node.className == 'blockitems')
    {
        node.className = 'hidden';
        buti.value = '+';
    }
    else
    {
        node.className = 'blockitems';
        buti.value = '-';
    }
}
function showall()
{
    var count = $('#count').val();
    for(var i=0; i< count; i++){

        $('.blockitems').show(i);
    }
}
var  flag =0;
function changeProfileImage(element,id)
{
    if(flag == 0 )
    {
        element.style.width = "200px";
        element.style.height = "175px";
        flag =1;
    }else
    {
        element.style.width = "47px";
        element.style.height = "47px";
        flag=0;
    }

}

