<?php
use app\components\AppUtility;
$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>
</head>
<body>

        <div ><h2>Student Roster </h2></div>

        <div class="cpmid">
            <span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid='.$course->id) ?>">View Login Grid</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Assign Sections and/or Codes</a><br>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Manage LatePasses</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Manage Tutors</a><br/>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-enrollment?cid='.$course->id.'&enroll=student'); ?>">Enroll Student with known username</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'enroll-from-other-course?cid='.$course->id); ?>">Enroll students from another course</a><br/>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Import Students from File</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress'); ?>">Create and Enroll new student</a><br/>

            </span><br class="clear"/>
        </div>
        <form id="qform" method=post action="listusers.php?cid=2">

            <p>check: <a  class="uncheck-all" href="#">None</a> /
                <a   class="check-all" href="#">All</a>
                With Selected:
                <input type=submit name=submit value="E-mail" title="Send e-mail to the selected students">
                <input type=submit name=submit value="Message" title="Send a message to the selected students">
                <input type=submit name=submit value="Unenroll" title="Unenroll the selected students">		<input type=submit name=submit value="Lock" title="Lock selected students out of the course">
                <input type=submit name=submit value="Make Exception" title="Make due date exceptions for selected students">
                <input type=submit name=submit value="Copy Emails" title="Get copyable list of email addresses for selected students">
                <input type="button" value="Pictures" onclick="rotatepics()" title="View/hide student pictures, if available"/></p>
            <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
            <table class=gb id=student-information-table>
                <thead>
                <tr>
                    <th></th>
                    <th>Last</th>
                    <th>First</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Last Access</th>
                    <th>Grades</th>
                    <th>Due Dates</th>
                    <th>Chg Info</th>
                    <th>Lock Out</th>
                </tr>
                </thead>
                <tbody>


                </tbody>
            </table>
            Number of Student : <input id="count">

            <script type="text/javascript">
                //initSortTable('myTable',Array(false,false,'S','S','S','S','D',false,false,false),true);

                $(document).ready(function () {
                    var course_id =  $( "#course-id" ).val();
                    selectCheckBox();
                    jQuerySubmit('student-roster-ajax',{ course_id: course_id }, 'studentRosterSuccess');

                });

                function studentRosterSuccess(response)
                {
                   console.log(response);
                    var students = JSON.parse(response);


                    if (students.status == 0) {
                        var students = students.query;


                        showStudentInformation(students);
                }
                }
                function showStudentInformation(students)
                {
                    var count = 0;
                    var html = "";

                   $.each(students, function(index, student){
                       html += "<tr> <td><input type='checkbox' name='student-information-check' value='"+student.id+"'></td>";
                       html += "<td>"+capitalizeFirstLetter(student.LastName)+"</td>";
                       html += "<td>"+capitalizeFirstLetter(student.FirstName)+"</td>";
                       html += "<td>"+student.email+"</td>";
                       html += "<td>"+student.SID+"</td>";
                       html += "<td>"+datecal(student.lastaccess)+"</td>";
                       html += "<th><a>Grades</a></th>";
                       html += "<th><a>Exception</a></th>";
                       html += "<th><a>Chg</a></th>";
                       html += "<th><a>Lock</a></th>";

                       count++;
                        });
                    $('#count').val(count);
                    $('#student-information-table').append(html);
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
            </script>
        </form>



</body>
</html>
