<?php
use app\components\AppUtility;
$this->title = 'Roster';
$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html>
<html>
<head>

    <link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
</head>
<body>

        <div ><h2>Student Roster </h2></div>

        <div class="cpmid">
            <span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid='.$course->id) ?>">View Login Grid</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'assign-sections-and-codes?cid='.$course->id); ?>">Assign Sections and/or Codes</a><br>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-late-passes?cid='.$course->id); ?>">Manage LatePasses</a><br/>
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


            <table class="student-data-table" id="student-information-table">
                <thead>
                <tr><th></th>
                    <?php if($isSection == true)
                    {?>
                    <th>Section</th>
                   <?php }
                    if($isCode == true)
                    {?>
                    <th>Code</th>
                   <?php } ?>
                    <th>Last</th><th>First</th><th>Email</th><th>UserName</th><th>Last Access</th><th>Grades</th><th>Due Dates</th><th>Chg Info</th><th>Lock Out</th>
                </tr>
                </thead>
            </table>

            <script type="text/javascript">

                $(document).ready(function () {
                //    createRosterTableHeader();
                    var course_id =  $( "#course-id" ).val();
                    selectCheckBox();
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

//                function createRosterTableHeader(){
//                var html = "<table class='student-data-table' id='student-information-table'>";
//                    html += "<thead><tr><th></th><th>Last</th><th>First</th><th>Email</th><th>Username</th><th>Last Access</th>";
//                    html += "<th>Grades</th><th>Due Dates</th><th>Chg Info</th><th>Lock Out</th></tr></thead><tbody></tbody></table>";
//                    $('roster-div').append(html);
//                }

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
            </script>
        </form>



</body>
</html>
