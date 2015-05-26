<?php

use app\components\AppUtility;
use yii\helpers\Html;
?>
<!DOCTYPE html>
<html>
<head>

    <div class="breadcrumb" id="title_bar">
        <a HREF="<?php echo AppUtility::getURLFromHome('site', 'index') ?>">Home</a><br/>
    </div>
    <link rel="stylesheet" type="text/css"
          href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
    <h3>Assign Section/Code Numbers</h3>
</head>
<body>
<form method="post" action="listusers.php?cid=2&amp;assigncode=1">
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
		<table class="student-data" id="student-data-table">
			<thead>
			<tr><th></th><th>Name</th><th>Section</th><th>Code</th>
			</tr>
			</thead>
        </table>
        <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('roster', 'roster/student-roster?cid='.$course->id)?>" id="aaa">Submit</a>
             <script type="text/javascript">


                $(document).ready(function () {
                    var course_id =  $( "#course-id" ).val();

                    jQuerySubmit('assign-sections-and-codes-ajax',{ course_id: course_id }, 'assignSectionsAndCodesSuccess');

                });
                function assignSectionsAndCodesSuccess(response) {


                    var students = JSON.parse(response);
                    students = students.studentinformation;
                    var html = "";
//                   var null=;
                    $.each(students, function (index, student) {
                        if(student.section == 'NULL' )
                        {
                            student.section=" ";
                        }
                        html += "<tr> <td><input type='checkbox' name='student-information-check' value='" + student.id + "'></td>";
                        html += "<td>" + student.Name + "</td>";
                        html += "<td><input type='text' value='"+student.section+"'></td>";
                        html += "<td><input type='text' value='"+student.code+"'></td>";
                    });
                    $('#student-data-table').append(html);
                    $('.student-data').DataTable();
                }

                $(document).ready(function(){

                    $('#aaa').click(function(){
                        alert("hi");

                    });

//                    $("#mytable tr).each(function(index){
//                    $(this).append("<td>" + col_array[index] + "</td>");
//                });
                });

            </script>


	</form>
</body>
</html>