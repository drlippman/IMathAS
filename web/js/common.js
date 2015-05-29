/**
 * Created by tudip on 4/5/15.
 */
$(document).ready(function(){

//    $('.dataTables_filter input').get(0).type = 'text';
    $('.dataTables_filter').prop('type', 'text');
});

function jQuerySubmit(url, data, successCallBack) {
alert(data);
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