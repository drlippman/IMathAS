<?php
use app\components\AppUtility;
$this->title = 'Student Roster';
?>
<!DOCTYPE html>
<html>
<head>

    <div class="breadcrumb" id="title_bar">
        <a HREF="<?php echo AppUtility::getURLFromHome('site', 'index') ?>">Home</a><br/>
    </div>
</head>
<body>

        <div ><h2>Student Roster </h2></div>

        <div class="cpmid">
            <span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid='.$course->id) ?>">View Login Grid</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Assign Sections and/or Codes</a><br>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Manage LatePasses</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Manage Tutors</a><br/>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Enroll Student with known username</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Enroll students from another course</a><br/>
            </span><span class="column" style="width:auto;">
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Import Students from File</a><br/>
                <a HREF="<?php echo AppUtility::getURLFromHome('admin/admin', 'index') ?>">Create and Enroll new student</a><br/>

            </span><br class="clear"/>
        </div>
        <form id="qform" method=post action="listusers.php?cid=2">

            <p>Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',true,'locked')">Non-locked</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
                With Selected:
                <input type=submit name=submit value="E-mail" title="Send e-mail to the selected students">
                <input type=submit name=submit value="Message" title="Send a message to the selected students">
                <input type=submit name=submit value="Unenroll" title="Unenroll the selected students">		<input type=submit name=submit value="Lock" title="Lock selected students out of the course">
                <input type=submit name=submit value="Make Exception" title="Make due date exceptions for selected students">
                <input type=submit name=submit value="Copy Emails" title="Get copyable list of email addresses for selected students">
                <input type="button" value="Pictures" onclick="rotatepics()" title="View/hide student pictures, if available"/></p>

            <table class=gb id=myTable>
                <thead>
                <tr>
                    <th></th>
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
            Number of students: 0<br/>		<script type="text/javascript">
                initSortTable('myTable',Array(false,false,'S','S','S','S','D',false,false,false),true);
            </script>
        </form>



</body>
</html>
