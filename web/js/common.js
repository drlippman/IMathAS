/**
 * Created by tudip on 4/5/15.
 */

//Show pop dialog for delete the course.
$(".delete-confirmation").on("click", function (e) {
    var html = "<div>Are you sure?</div>";
    e.preventDefault();
    $('<div></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Confirm": function () {
                $(this).dialog("close");
                return true;
            },
            "Cancel": function () {
                $(this).dialog("close");
                return false;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
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