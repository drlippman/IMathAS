initstack = new Array();
window.onload = init;
$(document).ready(function(){

//    $('.dataTables_filter input').get(0).type = 'text';
    $('.dataTables_filter').prop('type', 'text');

    $("#flash-message").animate({opacity:0.5}, 5000).fadeOut();
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

function createDataTable(classNameHandler){
    bPaginate = $('.'+classNameHandler).attr('bPaginate');
    if(bPaginate.length > 0){
        bPaginate = $.parseJSON(bPaginate);
    }else{
        bPaginate = true;
    }
    $('.'+classNameHandler).DataTable({"bPaginate": bPaginate});

}

function initEditor()
{
    tinyMCE.init({
        selector: "textarea",
        width: "100%",
        theme : "advanced",
        theme_advanced_buttons1 : "fontselect,fontsizeselect,formatselect,bold,italic,underline,strikethrough,separator,sub,sup,separator,cut,copy,paste,pasteword,undo,redo,justifyleft,justifycenter,justifyright,justifyfull,separator,numlist",
        theme_advanced_buttons2 : "bullist, outdent,indent,separator,forecolor,backcolor,separator,hr,anchor,link,unlink,charmap,image,advlist,table,tablecontrols,separator,code,separator,asciimath,asciimathcharmap,asciisvg",
        theme_advanced_buttons3 : "",
        theme_advanced_fonts : "Arial=arial,helvetica,sans-serif,Courier New=courier new,courier,monospace,Georgia=georgia,times new roman,times,serif,Tahoma=tahoma,arial,helvetica,sans-serif,Times=times new roman,times,serif,Verdana=verdana,arial,helvetica,sans-serif",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_source_editor_height: "500",
        plugins : 'asciimath,asciisvg,dataimage,table,inlinepopups,paste,media,advlist',
        gecko_spellcheck : true,
        extended_valid_elements : 'iframe[src|width|height|name|align],param[name|value],@[sscr]',
        theme_advanced_resizing : true,
        table_styles: "Gridded=gridded;Gridded Centered=gridded centered",
        cleanup_callback : "imascleanup",
        convert_urls: false,
        AScgiloc : '../../../filter/graph/svgimg.php',
        ASdloc : '/js/d.svg'

    });
}
//function to find unique element in array
Array.prototype.unique = function()
{
    var tmp = {}, out = [];
    for(var i = 0, n = this.length; i < n; ++i)
    {
        if(!tmp[this[i]]) { tmp[this[i]] = true; out.push(this[i]); }
    }
    return out;
}
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}
function isKeyPresent(array,index){
var count = 0;
    if(array != undefined){
        $.each(array,function(key,element){
           if(key == index){
               count++;
           }
        });
        if(count != 0){
            return true;
        }else{
            return false;
        }
    }

}
function alertPopUp(message, e){
    var html = '<div><p>'+message+'</p></div>';
    var cancelUrl = $(this).attr('href');
    e.preventDefault();
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                $(this).dialog("close");
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}
