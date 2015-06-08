
$(document).ready(function () {
    $('.student-data').DataTable();
});
function addText()
{
    var text_id =  document.getElementById("txt_add").value;
    $( ".latepass-text-id" ).each(function() {
        var oldlatepass = $(this).val();
        $(this).val(parseInt(oldlatepass) + parseInt(text_id));
    });
}
function replaceText()
{
    var text_id =  document.getElementById("txt_add").value;
    $( ".latepass-text-id" ).each(function() {
        $(this).val(parseInt(text_id));
    });
}


