$(document).ready(function () {
    var courseId = $(".course-info").val();
    var userId = $(".user-info").val();
    selectCheckBox();
    studentLock();
        var table = $('.gradebook-table').DataTable( {
            scrollY: "300px",
            scrollX: true,
            scrollCollapse: true,
            "paginate": false,
            "ordering":false,
            paging: false
        });
    new $.fn.dataTable.FixedColumns( table );
    var data = {courseId: courseId, userId: userId};
    jQuerySubmit('fetch-gradebook-data-ajax', data, 'fetchDataSuccess');
});
var data;
var showPics = 0;
var GradebookData;
function fetchDataSuccess(response){
    var result = JSON.parse(response);
    GradebookData = result.data.gradebook;
}
function chgtoggle(){
    showPics = $('#toggle4').val();
    $('.gradebook-table').remove();
    displayGradebook();
}
function selectCheckBox() {
    $('.check-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', true);
        })
    });

    $('.uncheck-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', false);
        })
    });
}

function highlightrow(el) {
    el.setAttribute("lastclass",el.className);
    el.className = "highlight";
}
function unhighlightrow(el) {
    el.className = el.getAttribute("lastclass");
}

function studentLock() {
    $('#lock-btn').click(function (e) {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            for(var i=1;i < GradebookData.length-1;i++){
                if(GradebookData[i][4][0] == $(this).val())
                {
                    dataArray.push(GradebookData[i][0][0]);
                }
            }
        });

        if (markArray.length != 0) {
            var html = '<div><p>Are you SURE you want to lock the selected students out of the course?</p></div><p>';
            $.each(dataArray, function (index, studentData) {
                html += studentData + '<br>';
            });
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Yes, Lock Out Student": function () {
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedStudents: markArray, courseId: course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        return false;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        }
        else {
            var msg = "Select atleast one student.";
            CommonPopUp(msg);
        }
    });
}
function markLockSuccess(response){
    location.reload();
}

function studentUnEnroll() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("checked-student").value = markArray;
        document.forms["un-enroll-form"].submit();
    } else {
        var msg = "Select at least one student to unenroll.";
        CommonPopUp(msg);
    }
}

function createStudentList(appendId, e){
    var markArray = [];
    $('.gradebook-table input[name = "checked"]:checked').each(function () {
        markArray.push($(this).val());
    });
    if (markArray.length != 0) {
        appendId.value = markArray;
    } else {
        var msg = "Select atleast one student.";
        CommonPopUp(msg);
        e.preventDefault();
    }
}

function studentMessage() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("message-id").value = markArray;
        document.forms["gradebook-message-form"].submit();
    } else {
        var msg = "Select at least one student to send Message.";
        CommonPopUp(msg);
    }
}

function studentEmail() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("student-id").value = markArray;
        document.forms["gradebook-email-form"].submit();
    } else {
        var msg = "Select at least one student to send Email.";
        CommonPopUp(msg);
    }
}

function studentCopyEmail() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("email-id").value = markArray;
        document.forms["copy-emails-form"].submit();
    } else {
        var msg = "Select at least one student.";
        CommonPopUp(msg);
    }
}

function teacherMakeException() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("exception-id").value = markArray;
        document.forms["make-exception-form"].submit();
    } else {
        var msg = "Select at least one student to make an exception.";
        CommonPopUp(msg);
    }
}
function chgfilter() {
    var ffilter = document.getElementById("ffilter").value;
    window.location = tagfilterurl+'&ffilter='+ffilter;
}
function createStudentList(){
    var markArray = [];
    $('.gradebook-table input[name = "checked"]:checked').each(function () {
        markArray.push($(this).val());
    });
    return markArray;
}

