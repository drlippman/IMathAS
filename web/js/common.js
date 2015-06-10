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