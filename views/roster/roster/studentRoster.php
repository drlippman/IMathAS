<?php
use app\components\AppUtility;

$this->title = 'Roster';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid=' . $_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
?>
    <link rel="stylesheet" type="text/css"
          href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8"
            src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<div><h2>Student Roster </h2></div>
<div class="cpmid">
            <span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid=' . $course->id) ?>">View
                    Login Grid</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'assign-sections-and-codes?cid=' . $course->id); ?>">Assign
                    Sections and/or Codes</a><br>
            </span><span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-late-passes?cid=' . $course->id); ?>">Manage
                    LatePasses</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-tutors?cid=' . $course->id); ?>">Manage
                    Tutors</a><br/>
            </span><span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-enrollment?cid=' . $course->id . '&enroll=student'); ?>">Enroll
                    Student with known username</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'enroll-from-other-course?cid=' . $course->id); ?>">Enroll
                    students from another course</a><br/>
            </span><span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'import-student?cid=' . $course->id); ?>">Import
                    Students from File</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'create-and-enroll-new-student?cid=' . $course->id); ?>">Create
                    and Enroll new student</a><br/>
            </span><br class="clear"/>
</div>
<p>check: <a class="uncheck-all" href="#">None</a> /
    <a class="check-all" href="#">All</a>
    With Selected:
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >    <input type=submit name=submit value="E-mail" title="Send e-mail to the selected students"></span>
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type=submit name=submit value="Message" title="Send a message to the selected students"></span>
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type=submit name=submit value="Unenroll" title="Unenroll the selected students"></span>
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type=submit name=submit value="Lock" title="Lock selected students out of the course">
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type=submit name=submit value="Make Exception" title="Make due date exceptions for selected students"></span>
    <span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type=submit name=submit value="Copy Emails" title="Get copyable list of email addresses for selected students"></span><span><a HREF="<?php echo AppUtility::getURLFromHome('site', 'work-in-progress') ?>" >   <input type="button" value="Pictures" onclick="rotatepics()" title="View/hide student pictures, if available"/></span></p>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
    <table class="student-data-table" id="student-information-table">
    <thead>
    <tr>
        <th></th>
        <?php if ($isSection == true) {
            ?>
            <th>Section</th>
        <?php }
        if ($isCode == true) {
            ?>
            <th>Code</th>
        <?php } ?>
        <th>Last</th>
        <th>First</th>
        <th>Email</th>
        <th>UserName</th>
        <th>Last Access</th>
        <th>Grades</th>
        <th>Due Dates</th>
        <th>Chg Info</th>
        <th>Lock Out</th>
    </tr>
    </thead>
</table>
