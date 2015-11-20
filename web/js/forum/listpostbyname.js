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
        node.className = 'blockitems col-sm-12 col-md-12';
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

function toggleshow(bnum) {
    var node = document.getElementById('m'+bnum);
    var butn = document.getElementById('butn'+bnum);
    if (node.className == 'blockitems') {
        node.className = 'hidden';
        butn.value = '+';
    } else {
        node.className = 'blockitems col-sm-12 col-md-12';
        butn.value = '-';
    }
}
function toggleshowall() {
    for (var i=0; i<bcnt; i++) {
        var node = document.getElementById('m'+i);
        var butn = document.getElementById('butn'+i);
        node.className = 'blockitems col-sm-12 col-md-12';
        butn.value = '-';
    }
    document.getElementById("toggleall").value = 'Collapse All';
    document.getElementById("toggleall").onclick = togglecollapseall;
}
function onsubmittoggle() {
    for (var i=0; i<bcnt; i++) {
        var node = document.getElementById('m'+i);
        node.className = 'pseudohidden';
    }
}
function togglecollapseall() {
    for (var i=0; i<bcnt; i++) {
        var node = document.getElementById('m'+i);
        var butn = document.getElementById('butn'+i);
        node.className = 'hidden';
        butn.value = '+';
    }
    document.getElementById("toggleall").value = 'Expand All';
    document.getElementById("toggleall").onclick = toggleshowall;
}
function onarrow(e,field) {
    if (window.event) {
        var key = window.event.keyCode;
    } else if (e.which) {
        var key = e.which;
    }

    if (key==40 || key==38) {
        var i;
        for (i = 0; i < field.form.elements.length; i++)
            if (field == field.form.elements[i])
                break;

        if (key==38) {
            i = i-2;
            if (i<0) { i=0;}
        } else {
            i = (i + 2) % field.form.elements.length;
        }
        if (field.form.elements[i].type=='text') {
            field.form.elements[i].focus();
        }
        return false;
    } else {
        return true;
    }
}
function onenter(e,field) {
    if (window.event) {
        var key = window.event.keyCode;
    } else if (e.which) {
        var key = e.which;
    }
    if (key==13) {
        var i;
        for (i = 0; i < field.form.elements.length; i++)
            if (field == field.form.elements[i])
                break;
        i = (i + 2) % field.form.elements.length;
        field.form.elements[i].focus();
        return false;
    } else {
        return true;
    }
}


