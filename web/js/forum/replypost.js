$(document).ready(function ()
{    initEditor();
    $("#reply-btn").click(function()
    {
        document.forms["add-thread"].submit();
    });
    var i=1;
    $('.add-more').click(function(e){
        e.preventDefault();
        $(this).before('<input name="file-'+i+'" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-'+i+'" placeholder="Description"><br>');
        i++;
    });
});

function checkNumber(event){
    var code = event.which;
    if((code == 8) || (code > 47 && code < 58)) { //Backspace key press
        return true;
    }
    return false;
}


