//$(document).ready(function(){
//    createDataTable('display-user-table');
//});
//function saveStudentData() {
//    var studentInformation = <?php echo json_encode($studentData ); ?>;
//    var existingData = studentInformation['existingUsers'];
//    var NewStudentData =  <?php echo json_encode($uniqueStudents ); ?>;
//    if(existingData){
//        var html = '<div><p>Existing students detail : </p></div><p>';
//        html += '* Already existing in system' + '<br>';
//        $.each(existingData, function (index, thread) {
//            html += thread.userName + '<br>';
//        });
//        html += '<br>' + '* Already enrolled in course[Skip them]' + '<br>';
//        $.each(existingData, function (index, thread) {
//            html += thread.userName + '<br>';
//        });
//
//        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
//            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
//            width: 'auto', resizable: false,
//            closeText: "hide",
//            buttons: {
//                "confirm": function () {
//                    $('#searchText').val(null);
//                    $(this).dialog('destroy').remove();
//                    jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData}, 'saveCsvFileSuccess');
//                    return true;
//                },
//                "Cancel": function () {
//                    $(this).dialog('destroy').remove();
//                    return false;
//                }
//            },
//            close: function (event, ui) {
//                $(this).remove();
//            }
//        });
//    }else {
//        jQuerySubmit('save-csv-file-ajax', {studentData: NewStudentData}, 'saveCsvFileSuccess');
//    }
//
//}
//function saveCsvFileSuccess(response)
//{
//    var courseId = $("#course-id").val();
//
//    if(status == 0)
//    {
//        window.location = "student-roster?cid="+courseId;
//    }
//}
