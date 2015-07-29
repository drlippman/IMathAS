$(document).ready(function () {
    var courseId = $(".course-info").val();
    var userId = $(".user-info").val();
    studentMessage();
    studentEmail();
    selectCheckBox();
    studentLock();
    studentCopyEmail();
    teacherMakeException();
    $('.gradebook-table').dataTable( {
        "scrollX": true,
        "paginate": false

    } );
});
var data;
var showPics = 0;
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
            dataArray.push($(this).parent().text());
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

function studentUnenroll() {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            dataArray.push($(this).parent().text());
        });
        if (markArray.length != 0) {

            var html = '<div><p><b style = "color: red">Warning!</b>:&nbsp;This will delete ALL course data about these students. This action cannot be undone. ' +
                'If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.</p><p>Are you SURE' +
                ' you want to unenroll the selected students?</p></p></div>';
            $.each(dataArray, function (index, studentData) {
                html += studentData + '<br>';
            });
            var cancelUrl = $(this).attr('href');
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: '730', resizable: false,
                closeText: "hide",
                buttons: {
                    "Unenroll": function () {
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedStudents: markArray, courseId: course_id};
                        jQuerySubmit('mark-unenroll-ajax', data, 'markUnenrollSuccess');
                        return true;
                    },
                    "Lock Students Out Instead": function () {
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
}
function markUnenrollSuccess(response) {
    location.reload();
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
    $('#roster-message').click(function (e) {
        var appendId =  document.getElementById("message-id");
        createStudentList(appendId, e);
    });
}

function studentEmail() {
    $('#roster-email').click(function (e) {
        var appendId =  document.getElementById("student-id");
        createStudentList(appendId, e);
    });
}

function studentCopyEmail() {
    $('#roster-copy-emails').click(function (e) {
        var appendId =  document.getElementById("email-id");
        createStudentList(appendId, e);
    });
}

function teacherMakeException() {
    $('#gradebook-makeExc').click(function (e) {
        var markArray = [];
        var sectionName;
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            sectionName = document.getElementById($(this).val()).textContent;
        });
        if (markArray.length != 0) {
            document.getElementById("exception-id").value = markArray;
            document.getElementById("section-name").value = sectionName;
        } else {
            var msg = "Select atleast one student.";
            CommonPopUp(msg);
            e.preventDefault();
        }
    });
}


