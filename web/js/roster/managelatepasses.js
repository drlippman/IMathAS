
$(document).ready(function () {
    $('.student-data').DataTable();
});
function addText()
{
    var text_id =  document.getElementById("txt_add").value;
    if(text_id.length == 0){
        var message = " 'To all students' field should not be empty.";
        CommonPopUp(message);
    }if(text_id.match(/^[a-zA-Z]+$/)){
    var message = " Enter only integer value.";
    CommonPopUp(message);
}
    else
    {

        $( ".latepass-text-id" ).each(function() {
            var oldlatepass = $(this).val();
            $(this).val(parseInt(oldlatepass) + parseInt(text_id));
        });
    }

}
function replaceText()
{
    var text_id =  document.getElementById("txt_add").value;
    $( ".latepass-text-id" ).each(function() {
        $(this).val(parseInt(text_id));
    });
}


