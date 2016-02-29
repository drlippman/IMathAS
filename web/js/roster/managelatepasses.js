
$(document).ready(function () {
    createDataTable('student-data');
    $('.student-data').DataTable();
});
function addReplaceMultiplyTextValue(value)
{
    var text_id =  document.getElementById("txt_add").value;
    if(text_id.length == 0){
        text_id = 0;
        var message = " 'Text' field should not be empty.";
        CommonPopUp(message);

    }else if(text_id.match(/^[a-zA-Z]+$/)){
    text_id = 0;
    var message = " Enter only integer value.";
    CommonPopUp(message);
}else if(value == 1){
        $( ".latepass-text-id" ).each(function() {
            var oldlatepass = $(this).val();
            if(oldlatepass.length == 0){
                oldlatepass = 0;
            }
            $(this).val(parseInt(oldlatepass) + parseInt(text_id));
        });

    }else if(value == 2){
        $( ".latepass-text-id" ).each(function() {
            $(this).val(parseInt(text_id));
        });
    }else if(value == 3){
        $(".latepass-text-id").each(function () {
            var oldlatepass = $(this).val();
            if(oldlatepass.length == 0){
                $(this).val(oldlatepass);
            }else{
                $(this).val(parseInt(oldlatepass) * parseInt(text_id));
            }
        });
    }
}







