$(document).ready(function () {
    var course_id = $("#course-id").val();
    selectCheckBox();
    studentLock();
    studentUnenroll();
    jQuerySubmit('student-roster-ajax', { course_id: course_id }, 'studentRosterSuccess');
});

var studentData;
function studentRosterSuccess(response) {
    response = JSON.parse(response);
    var isCode = response.data.isCode;
    var isSection = response.data.isSection;
    var isImageColumnPresent = response.data.isImageColumnPresent;
    if (response.status == 0) {
        var students = response.data.query;
        showStudentInformation(students, isCode, isSection, isImageColumnPresent);
        studentData = students;
    }
}

function showStudentInformation(students,isCode,isSection,isImageColumnPresent)
{
    var courseId =  $( "#course-id" ).val();
    var html = "";
    $.each(students, function (index, student) {
        html += "<tr> <td><div class='checkbox'><label><input type='checkbox' name='student-information-check' value='" + student.id + "'><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></td>";
        if (isImageColumnPresent == 1) {
            imageURL = 'dummy_profile.jpg';
            if (student.hasuserimg != 0) {
                imageURL = student.id + ".jpg";
            }
            html += "<td><img  class='images circular-image' src='../../Uploads/" + imageURL + "' ></td>";
        }
        html += "<td class = 'LastName ";
        if (student.locked != 0) {
            html += " locked-student";
        }
        html += " '>"+ capitalizeFirstLetter(student.lastname) + "</td>";
        if (student.locked == 0) {
            html += "<td class = 'FirstName'>";
        } else {
            html += "<td  class='FirstName locked-student '>";
        }
        html += capitalizeFirstLetter(student.firstname) + "</td>";
        html += "<td>" + student.email + "</td>";
        html += "<td class = 'Username'>" + student.username + "</td>";
        displayText ="never";
        if (student.locked == 0) {
            if (student.lastaccess != 0) {
                displayText= datecal(student.lastaccess);
            }
            html += "<td><a href='login-log?cid=" + courseId + "&uid=" + student.id + "'>" + displayText +"</a></td>";
        }
        else {
            html += "<td><a>Is locked out</a></a></td>"
        }
        html += "<td><ul class='nav nav-tabs roster-settings'>" +
        "<li class='dropdown'> <a class='dropdown-toggle' data-toggle='dropdown' href='#'><i class='fa fa-cog icon-nav'></i>Settings<span class='caret caret-settings'></span></a>" +
        "<ul class='dropdown-menu settings-menu'><li>" +
        "<a href='#'><img class='small-icon' src='../../img/gradebook.png'>&nbsp;Grades</a>" +
        "<a class ='roster-make-exception' href='make-exception?cid="+courseId+"&student-data="+ student.id +"&section-data="+ student.section +"'><i class='fa fa-plus-square fa-fw'></i>&nbsp;Exception</a>" +
        "<a href='change-student-information?cid=" + courseId + "&uid=" + student.id + "'><i class='fa fa-cogs'></i>&nbsp;Change Information</a>";
        if (student.locked == 0) {
            html += "<a  href='#' onclick='lockUnlockStudent(false," + student.id + ")'><i class='fa fa-lock fa-fw'></i>&nbsp;Lock</a>";
        } else {
            html += "<a href='#' onclick='lockUnlockStudent(true," + student.id + ")'><i class='fa fa-unlock'></i>&nbsp;Unlock</a>";
        }
        html += "</li></ul></li></ul></td>";
    });
    $('#student-information-table').append(html);
    createDataTable('student-data-table');
    bindEvent();
    $(".images").hide();
}
function selectCheckBox() {
    $('.student-data-table input[name = "header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#student-information-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#student-information-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
    $('.non-locked').click(function () {
        $('#student-information-table input:checkbox').each(function () {
            var selectedEntry = $(this).val();
            var lockedStudent = 0;
            $.each(studentData, function (index, student) {
                if(selectedEntry == student.id){
                    if(student.locked != 0){
                        lockedStudent = 1;
                    }
                }
            });
            if (lockedStudent == 0) {
                $(this).prop('checked', true);
            } else {
                $(this).prop('checked', false);
            }
        })
    });
}
function datecal(a) {
    var date = new Date(a * 1000);
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    var year = date.getUTCFullYear();
    var hh = date.getHours(),
        h = hh,
        min = ('0' + date.getMinutes()).slice(-2),
        ampm = 'AM',
        time;
    if (hh > 12) {
        h = hh - 12;
        ampm = 'PM';
    } else if (hh === 12) {
        h = 12;
        ampm = 'PM';
    } else if (hh == 0) {
        h = 12;
    }
    time = month + '/' + day + '/' + year + '  ' + h + ':' + min + ' ' + ampm;
    return time;
}
function studentLock() {
    $('#lock-btn').click(function (e) {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function () {
            markArray.push($(this).val());
            var selectedEntry = $(this).val();
            $.each(studentData, function (index, student) {
                if(selectedEntry == student.id){
                    dataArray.push((capitalizeFirstLetter(student.lastname) + ', ' + capitalizeFirstLetter(student.firstname) + ' (' +student.username + ')').trim());
                }
            });
        });
        dataArray.sort();
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
                        $('#student-information-table input[name="student-information-check"]:checked, input[name = "header-checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray, courseid: course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('#student-information-table input[name="student-information-check"]:checked, input[name = "header-checked"]:checked').each(function () {
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
            var msg = "Select at least one student to assign lock.";
            CommonPopUp(msg);
        }
    });
}
function markLockSuccess(response) {
    location.reload();
}
function studentUnenroll() {
    $('#un-enroll-link').click(function (e) {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function () {
            markArray.push($(this).val());
            var selectedEntry = $(this).val();
            $.each(studentData, function (index, student) {
                if(selectedEntry == student.id){
                    dataArray.push((capitalizeFirstLetter(student.lastname) + ', ' + capitalizeFirstLetter(student.firstname) + ' (' +student.username + ')').trim());
                }
            });
        });
        dataArray.sort();
        if (markArray.length != 0) {
            var html = '<div><p><b style = "color: red">Warning!</b>:&nbsp;This will delete ALL course data about these students. This action cannot be undone. ' +
                'If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.</p><p>Are you SURE' +
                ' you want to unenroll the selected students?</p></p></div>';
            $.each(dataArray, function (index, studentData) {
                html += studentData + '<br>';
            });
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: '730', resizable: false,
                closeText: "hide",
                buttons: {
                    "Unenroll": function () {
                        $('#student-information-table input[name="student-information-check"]:checked, input[name = "header-checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray, courseid: course_id};
                        jQuerySubmit('mark-unenroll-ajax', data, 'markUnenrollSuccess');
                        return true;
                    },
                    "Lock Students Out Instead": function () {
                        $('#student-information-table input[name="student-information-check"]:checked, input[name = "header-checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray, courseid: course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('#student-information-table input[name="student-information-check"]:checked, input[name = "header-checked"]:checked').each(function () {
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
            e.preventDefault();
            var msg = "Select at least one student to unenroll.";
            CommonPopUp(msg);
        }
    });
}
function markUnenrollSuccess(response) {
    location.reload();
}
function studentEmail() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("student-id").value = markArray;
        document.forms["roster-email-form"].submit();
    } else {
        var msg = "Select at least one student to send Email.";
        CommonPopUp(msg);
    }
}
function studentMessage() {
    var markArray = createStudentList();
    if (markArray.length != 0) {
        document.getElementById("message-id").value = markArray;
        document.forms["roster-message-form"].submit();
    } else {
        var msg = "Select at least one student to send Message.";
        CommonPopUp(msg);
    }
}
function copyStudentsEmail() {
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
    var markArray = [];
    var sectionName;
    $('.student-data-table input[name = "student-information-check"]:checked').each(function () {
        markArray.push($(this).val());
        var selectedId = $(this).val();
        $.each(studentData, function (index, student) {
            if(selectedId == student.id){
                sectionName = student.section;
            }
        });
    });
    if (markArray.length != 0) {
        document.getElementById("exception-id").value = markArray;
        document.getElementById("section-name").value = sectionName;
        document.forms["make-exception-form"].submit();
    } else {
        var msg = "Select at least one student to make an exception.";
        CommonPopUp(msg);
    }
}
var picsize = 0;
function rotatepics() {
    picsize = (picsize + 1) % 3;
    picshow(picsize);
}
function picshow(size) {
    var course_id = $("#course-id").val();
    if (size == 0) {
        els = document.getElementById("student-information").getElementsByTagName("img");
        for (var i = 0; i < els.length; i++) {
            els[i].style.display = "none";
        }
    } else {
        els = document.getElementById("student-information").getElementsByTagName("img");
        for (var i = 0; i < els.length; i++) {
            els[i].style.display = "inline";
            if (size == 2) {
                els[i].style.width = "100px";
                els[i].style.height = "100px"
            }
            if (size == 1) {
                els[i].style.width = "50px";
                els[i].style.height = "50px";
            }
        }
    }
}
function lockUnlockStudent(lockOrUnlock, studentId) {
    var courseId = $("#course-id").val();
    if (lockOrUnlock == true) {
        lockOrUnlock = 1;
        var data = {lockOrUnlock: lockOrUnlock, studentId: studentId, courseId: courseId};
        jQuerySubmit('lock-unlock-ajax', data, 'lockUnlockSuccess');
    } else {
        lockOrUnlock = 0;
        var html = '<div><p>Are you sure? You want to lock out student from course</p></div><p>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "confirm": function () {
                    $('#searchText').val(null);
                    $(this).dialog('destroy').remove();
                    var data = {lockOrUnlock: lockOrUnlock, studentId: studentId, courseId: courseId};
                    jQuerySubmit('lock-unlock-ajax', data, 'lockUnlockSuccess');
                    return true;
                },
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    }
}


function lockUnlockSuccess(response)
{
    location.reload();
}

function bindEvent(){
    $('.roster-make-exception').click(function(e) {
        e.preventDefault();
        var cancelUrl = $(this).attr('href');
        var f = document.createElement('form');
        f.style.display = 'none';
        this.parentNode.appendChild(f);
        f.method = 'post';
        f.action = cancelUrl;
        f.submit();
    });
}

function createStudentList(){
    var markArray = [];
    $('.student-data-table input[name = "student-information-check"]:checked').each(function () {
        markArray.push($(this).val());
    });
    return markArray;
}
