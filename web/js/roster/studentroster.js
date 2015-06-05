$(document).ready(function () {
    var course_id =  $( "#course-id" ).val();
    selectCheckBox();
    studentLock();
    studentUnenroll();
    studentEmail();
    studentMessage();
    jQuerySubmit('student-roster-ajax',{ course_id: course_id }, 'studentRosterSuccess');
});
var studentData;
function studentRosterSuccess(response)
{
    var students = JSON.parse(response);
    var isCode = students.isCode;
    var isSection = students.isSection;
    if (students.status == 0) {
        var students = students.query;
        showStudentInformation(students,isCode,isSection);
        studentData = students;
    }
}
function showStudentInformation(students,isCode,isSection)
{
    var html = "";
    $.each(students, function(index, student){
        html += "<tr> <td><input type='checkbox' name='student-information-check' value='"+student.id+"'></td>";
        if(isSection == true)
        {
            if(student.section == null){
                html += "<td></td>";
            }else{
            html += "<td>"+student.section+"</td>";
            }
        }
        if(isCode == true)
        {
            if(student.code == null){
                html += "<td></td>";
            }else{
                html += "<td>"+student.code+"</td>";
            }

        }
        if(student.locked ==0){ html += "<td>"+capitalizeFirstLetter(student.lastname)+"</td>"; }else{html += "<td  class='locked-student'>"+capitalizeFirstLetter(student.lastname)+"</td>";}
        if(student.locked ==0){ html += "<td>"+capitalizeFirstLetter(student.firstname)+"</td>"; }else{html += "<td  class='locked-student'>"+capitalizeFirstLetter(student.firstname)+"</td>";}
        html += "<td><a>"+student.email+"</a></td>";
        html += "<td>"+student.username+"</td>";
        if(student.locked ==0)
        {
            if(student.lastaccess != 0){ html += "<td><a>"+datecal(student.lastaccess)+"</a></td>"; }
            else{ html += "<td><a>never</a></td>"; }
        }
        else{ html += "<td><a>Is locked out</a></a></td>" }
        html += "<td><a>Grades</a></td>";
        html += "<td><a>Exception</a></td>";
        html += "<td><a>Chg</a></td>";
        if(student.locked == 0) { html += "<td><a>Lock</a></td>"; }
        else{ html += "<td><a>Unlock</a></td>"; }
    });
    $('#student-information-table').append(html);
    $('.student-data-table').DataTable();
}
function selectCheckBox(){
    $('.check-all').click(function(){
        $('#student-information-table input:checkbox').each(function(){
            $(this).prop('checked',true);
        })
    });
    $('.uncheck-all').click(function(){
        $('#student-information-table input:checkbox').each(function(){
            $(this).prop('checked',false);
        })
    });
    $('.non-locked').click(function(){
        $('#student-information-table input:checkbox').each(function(){
            if($(this).parent().next().next().next().next().next().next().next().next().next().next().next().text() == "Lock"){
            $(this).prop('checked',true);
            }else{
                $(this).prop('checked',false);
            }
        })
    });
}
function datecal(a)
{
    var date = new Date(a*1000);
    var  month = ('0' + (date.getMonth() + 1)).slice(-2);
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

function studentLock(){
    $('#lock-btn').click(function(e){
        var course_id =  $( "#course-id" ).val();
        var markArray = [];
        var dataArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
            dataArray.push( $(this).parent().next().next().next().text()+' '+$(this).parent().next().next().next().next().text()+' ('+$(this).parent().next().next().next().next().next().next().text()+')');
        });

        if(markArray.length!=0) {
            var html = '<div><p>Are you SURE you want to lock the selected students out of the course?</p></div><p>';
            $.each(dataArray, function (index, studentData) {
               html += studentData+'<br>';
            });
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Yes, Lock Out Student": function () {
                        $('#student-information-table input[name="student-information-check"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray,courseid:course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('#student-information-table input[name="student-information-check"]:checked').each(function () {
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
        else
        {
            alert("No users selected.");
        }
    });
}

function markLockSuccess(response){
    location.reload();
}

function studentUnenroll(){
    $('#unenroll-btn').click(function(e){
        var course_id =  $( "#course-id" ).val();
        var markArray = [];
        var dataArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
            dataArray.push( $(this).parent().next().next().next().text()+' '+$(this).parent().next().next().next().next().text()+
            ' ('+$(this).parent().next().next().next().next().next().next().text()+')');
        });

        if(markArray.length!=0) {
            var html = '<div><p><b style = "color: red">Warning!</b>:&nbsp;This will delete ALL course data about these students. This action cannot be undone. ' +
                'If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.</p><p>Are you SURE' +
                ' you want to unenroll the selected students?</p></p></div>';
            $.each(dataArray, function (index, studentData) {
                html += studentData+'<br>';
            });
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: '730', resizable: false,
                closeText: "hide",
                buttons: {
                    "Unenroll": function () {
                        $('#student-information-table input[name="student-information-check"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray,courseid:course_id};
                        jQuerySubmit('mark-unenroll-ajax', data, 'markUnenrollSuccess');
                        return true;
                    },
                    "Lock Students Out Instead": function () {
                        $('#student-information-table input[name="student-information-check"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedstudents: markArray,courseid:course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('#student-information-table input[name="student-information-check"]:checked').each(function () {
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
        else
        {
            alert("Please select the checkbox to Unenroll the students.");
        }
    });
}

function markUnenrollSuccess(response){
    location.reload();
}

function studentEmail(){
    $('#roster-email').click(function(e){
        var markArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
        });
        if(markArray.length!=0){
            document.getElementById("student-id").value = markArray;
        }else
        {
            e.preventDefault();
            alert("No users selected.");
        }
    });
}
function studentMessage(){
    $('#roster-message').click(function(e){
        var markArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
        });
        if(markArray.length!=0){
            document.getElementById("message-id").value = markArray;
        }else
        {
            alert("No users selected.");
            e.preventDefault();
        }
    });
}

