$(document).ready(function () {
    var count = 0;
    $('.create-exception').click(function() {
        $('.assessment-list input:checkbox').each(function () {
            if($(this).prop("checked") == true){
                count ++;
            }
        });
        if(count == 0){
            CommonPopUp("Select atleast one assessment from list to create new exception.");
            return false;
        }
        var date1 = document.getElementById('w0').value;
        var time1 = document.getElementById('w1').value;
        var date2 = document.getElementById('w2').value;
        var time2 = document.getElementById('w3').value;
        if(date1 == "" || date1 == null || date2 == "" || date2 == null || time1 == "" || time1 == null || time2 == "" || time2 == null){
            CommonPopUp("Date and Time cannot be empty.");
            return false;
        }
    });
    $('.clear-exception').click(function() {
        $('.exception-list input:checkbox').each(function () {
            if($(this).prop("checked") == true){
                count ++;
            }
        });
        if(count == 0){
            CommonPopUp("Select atleast one entry from list to clear exception.");
            return false;
        }
    });
});