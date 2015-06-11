initstack = new Array();
window.onload = init;
$(document).ready(function(){

//    $('.dataTables_filter input').get(0).type = 'text';
    $('.dataTables_filter').prop('type', 'text');
});

function jQuerySubmit(url, data, successCallBack) {
    $.post(
        url,
        data,
        eval(successCallBack)
    );
}

function jQuerySubmitAjax(url, type, data, successCallBack, errorCallBack) {
    $.ajax({
        url: url,
        type:type,
        data: data,
        beforeSend: function() {
        },
        afterSend: function(){
        },
        success: successCallBack,
        error: errorCallBack
    });
}


function isElementExist(element)
{
    if ($(element).length){
        return true;
    }
    return false;
}

function capitalizeFirstLetter(str)
{
    return str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
        return letter.toUpperCase();
    });
}

function init() {
    for (var i=0; i<initstack.length; i++) {
    var foo = initstack[i]();
    }
}

function CommonPopUp(message)
{

    var html = '<div><p>'+message+'</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Okay": function () {
                $('#searchText').val(null);

                $(this).dialog('destroy').remove();
                return false;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }

    });

}