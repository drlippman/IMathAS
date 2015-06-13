$(document).ready(function () {
    var course_id =  $( "#course-id" ).val();
    selectCheckBox();
    studentLock();
    studentUnenroll();
    studentEmail();
    studentMessage();
    teacherMakeException();
    copyStudentEmail();
    jQuerySubmit('student-roster-ajax',{ course_id: course_id }, 'studentRosterSuccess');
});
var studentData;
function studentRosterSuccess(response)
{
    response= JSON.parse(response);
    var isCode = response.data.isCode;
    var isSection = response.data.isSection;
    var isImageColumnPresent = response.data.isImageColumnPresent;
    if (response.status == 0) {
        var students = response.data.query;
        showStudentInformation(students,isCode,isSection,isImageColumnPresent);
        studentData = students;
    }
}
function showStudentInformation(students,isCode,isSection,isImageColumnPresent)
{var courseId =  $( "#course-id" ).val();
    var html = "";
    var courseId =  $( "#course-id" ).val();
    $.each(students, function(index, student){

        html += "<tr> <td><input type='checkbox' name='student-information-check' value='"+student.id+"'></td>";
        if(isImageColumnPresent == 1) {
        if(student.hasuserimg == 0 ){
            html += "<td><img  class='images circular-image'  src='../../Uploads/dummy_profile.jpg' ></td>";
        }else{
            html += "<td><img  class='images circular-image' src='../../Uploads/" + student.id+".jpg' ></td>";
        }
        }
        if(isSection == true)
        {
            if(student.section == null){
                html += "<td class='section-class'></td>";
            }else{
            html += "<td class='section-class'>"+student.section+"</td>";
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
        if(student.locked ==0){ html += "<td class = 'LastName'>"+ capitalizeFirstLetter(student.lastname)+"</td>"; }else{html += "<td  class='LastName locked-student '>"+ capitalizeFirstLetter(student.lastname)+"</td>";}
        if(student.locked ==0){ html += "<td class = 'FirstName'>"+ capitalizeFirstLetter(student.firstname)+"</td>"; }else{html += "<td  class='FirstName locked-student '>"+ capitalizeFirstLetter(student.firstname)+"</td>";}
        html += "<td><a>"+student.email+"</a></td>";
        html += "<td class = 'Username'>"+student.username+"</td>";
        if(student.locked ==0)
        {
            if(student.lastaccess != 0){ html += "<td><a href='login-log?cid=" + courseId + "&uid="+ student.id +"'>"+datecal(student.lastaccess)+"</a></td>"; }
            else{ html += "<td><a href='login-log?cid=" + courseId + "&uid="+ student.id +"'>never</a></td>"; }
        }
        else{ html += "<td><a>Is locked out</a></a></td>" }
        html += "<td><a>Grades</a></td>";
        html += "<td><a>Exception</a></td>";
        html += "<td><a href='change-student-information?cid=" + courseId + "&uid="+ student.id +"'>Change</a></td>";
        if(student.locked == 0) {
            html += "<td class = 'lock-class'><a  href='#' onclick='lockUnlockStudent(false,"+student.id+")'>Lock</a></td>"; }
        else{ html += "<td class = 'lock-class'><a href='#' onclick='lockUnlockStudent(true,"+student.id+")'>Unlock</a></td>"; }
    });
    $('#student-information-table').append(html);
    $('.student-data-table').DataTable();
    $(".images").hide();
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
            if(($(this).parent().siblings('.lock-class').text())== "Lock"){
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
            dataArray.push( $(this).parent().siblings('.LastName').text()+' '+$(this).parent().siblings('.FirstName').text()+' ('+$(this).parent().siblings('.Username').text()+')');
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
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
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
            dataArray.push( $(this).parent().siblings('.LastName').text()+' '+$(this).parent().siblings('.FirstName').text()+
            ' ('+$(this).parent().siblings('.Username').text()+')');
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
            e.preventDefault();
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
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
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
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
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
            e.preventDefault();
        }
    });
}
function copyStudentEmail(){
    $('#roster-copy-emails').click(function(e){
        var markArray = [];
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
        });
        if(markArray.length!=0){
            document.getElementById("email-id").value = markArray;
        }else
        {
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
            e.preventDefault();
        }
    });
}
function teacherMakeException(){
    $('#roster-makeExc').click(function(e){
        var markArray = [];
        var sectionName;
        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
            markArray.push($(this).val());
            sectionName = ($(this).parent().siblings('.section-class').text());
        });
        if(markArray.length!=0){
            document.getElementById("exception-id").value = markArray;
            document.getElementById("section-name").value = sectionName;
        }else
        {
            var msg ="Select atleast one student.";
            CommonPopUp(msg);
            e.preventDefault();
        }
    });
}
var picsize = 0;
function rotatepics() {
    picsize = (picsize+1)%3;
    picshow(picsize);
}
function picshow(size) {
    var course_id =  $( "#course-id" ).val();
    if (size==0) {
        els = document.getElementById("student-information").getElementsByTagName("img");
        for (var i=0; i<els.length; i++) {
            els[i].style.display = "none";
        }
    } else {
        els = document.getElementById("student-information").getElementsByTagName("img");
        for (var i=0; i<els.length; i++) {
            els[i].style.display = "inline";
            if (size==2) {
                els[i].style.width = "100px";
                els[i].style.height = "100px"
            }
            if (size==1) {
                els[i].style.width = "50px";
                els[i].style.height = "50px";
            }
        }
    }
}
function lockUnlockStudent(lockOrUnlock,studentId)
{
    var courseId =  $( "#course-id" ).val();
    if(lockOrUnlock == true){
        lockOrUnlock = 1;
        var data = {lockOrUnlock: lockOrUnlock,studentId:studentId,courseId:courseId};
        jQuerySubmit('lock-unlock-ajax', data, 'lockUnlockSuccess');
    }else{
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
                    var data = {lockOrUnlock: lockOrUnlock,studentId:studentId,courseId:courseId};
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
console.log(response);
    location.reload();
}
