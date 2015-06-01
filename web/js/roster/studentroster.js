$(document).ready(function () {
    var course_id =  $( "#course-id" ).val();
    selectCheckBox();
    //studentLock();
    jQuerySubmit('student-roster-ajax',{ course_id: course_id }, 'studentRosterSuccess');
});
function studentRosterSuccess(response)
{
    var students = JSON.parse(response);
    var isCode = students.isCode;
    var isSection = students.isSection;
    if (students.status == 0) {
        var students = students.query;
        showStudentInformation(students,isCode,isSection);
    }
}
function showStudentInformation(students,isCode,isSection)
{
    var html = "";
    $.each(students, function(index, student){
        html += "<tr> <td><input type='checkbox' name='student-information-check' value='"+student.id+"'></td>";
        if(isSection == true)
        {
            html += "<td>"+student.section+"</td>";
        }
        if(isCode == true)
        {
            html += "<td>"+student.code+"</td>";
        }
        html += "<td>"+capitalizeFirstLetter(student.lastname)+"</td>";
        html += "<td>"+capitalizeFirstLetter(student.firstname)+"</td>";
        html += "<td>"+student.email+"</td>";
        html += "<td>"+student.username+"</td>";
        html += "<td>"+datecal(student.lastaccess)+"</td>";
        html += "<th><a>Grades</a></th>";
        html += "<th><a>Exception</a></th>";
        html += "<th><a>Chg</a></th>";
        html += "<th><a>Lock</a></th>";
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

//function studentLock(){
//    $('#msg-btn').click(function(){
//        var markArray = [];
//        $('.student-data-table input[name = "student-information-check"]:checked').each(function() {
//            markArray.push($(this).val());
//            $(this).prop('checked',false);
//
//        });
//        var data =  {studentsArray: markArray};
//        //jQuerySubmit('mark-lock-ajax', data, 'marklockSuccess');
//        window.location = "student-lock?data="+markArray;
//    });
//}
//
//function marklockSuccess(response){console.log(response);
//
//}