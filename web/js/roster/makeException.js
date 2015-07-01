$(document).ready(function () {
    $('.record-submit').click(function() {
        var date1 = document.getElementById('w0').value;
        var time1 = document.getElementById('w1').value;
        var date2 = document.getElementById('w2').value;
        var time2 = document.getElementById('w3').value;
        if(date1 == "" || date1 == null || date2 == "" || date2 == null || time1 == "" || time1 == null || time2 == "" || time2 == null){
            CommonPopUp("Date and Time cannot be empty.");
            return false;
        }
    });
});