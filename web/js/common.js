initstack = new Array();
window.onload = init;
jQuery(document).ready(function()
{
    jQuery('.dataTables_filter').prop('type', 'text');
    jQuery("#flash-message").animate({opacity:0.5}, 5000).fadeOut();

});

function jQuerySubmit(url, data, successCallBack) {
    jQuery.post(
        url,
        data,
        eval(successCallBack)
    );
}

function jQuerySubmitAjax(url, type, data, successCallBack, errorCallBack) {
    jQuery.ajax({
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
    if (jQuery(element).length){
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
    jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: '', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,draggable:false,
        closeText: "hide",
        buttons: {
            "Ok": function () {
                jQuery('#searchText').val(null);
                showBodyScroll();
                jQuery(this).dialog('destroy').remove();
                return false;
            }
        },
        open: function(){
            hideBodyScroll();
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        },
        close: function (event, ui) {
            showBodyScroll();
            jQuery(this).remove();
        }


    });

}

function createDataTable(classNameHandler){
    bPaginate = jQuery('.'+classNameHandler).attr('bPaginate');
    if(bPaginate.length > 0){
        bPaginate = jQuery.parseJSON(bPaginate);
    }else{
        bPaginate = true;
    }
    jQuery('.'+classNameHandler).DataTable({"bPaginate": bPaginate});

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
        jQuery.each(array,function(key,element){
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
    var cancelUrl = jQuery(this).attr('href');
    e.preventDefault();
    jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                jQuery(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                jQuery(this).dialog("close");
                return true;
            }
        },
        close: function (event, ui) {
            jQuery(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}

//
//$(document).ready(function ()
//{
//    $("input").keypress(function(e){
//        var subject = $(".subject").val();
//        $('#flash-message').hide();
//        if(subject.length > 60)
//        {
//            $('#flash-message').show();
//            $('#flash-message').html("<div class='alert alert-danger'>The Subject field cannot contain more than 60 characters.");
//            return false;
//        }else{
//            $('#flash-message').hide();
//        }
//    });
//    $("input").keyup(function(e){
//        if(e.keyCode == 8 || e.keyCode == 46)
//        {
//            $('#flash-message').hide();
//        }
//    });
//});
//
//function submitForm()
//{
//    var subject = $(".subject").val();
//    if(subject.length < 1)
//    {
//        var html = '<div><p>No subject.Send anyway?</p></div>';
//        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
//            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
//            width: 'auto', resizable: false,
//            closeText: "hide",
//            buttons: {
//                "Cancel": function () {
//                    $(this).dialog('destroy').remove();
//                    return false;
//                },
//                "confirm": function () {
//                    $(this).dialog("close");
//                    $('#flash-message').hide();
//                    $('#form-id').submit();
//                    return true;
//                }
//            },
//            close: function (event, ui) {
//                $(this).remove();
//            }
//        });
//    }else if(subject.length > 60)
//    {
//        $('#flash-message').show();
//        $('#flash-message').html("<div class='alert alert-danger'>The Subject field cannot contain more than 60 characters.");
//        return false;
//    }else{
//        $('#flash-message').hide();
//        $('#form-id').submit();
//    }
//}
