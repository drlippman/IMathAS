<?php
use app\components\AppUtility;

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Roster';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid=' .$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<div><h2>Student Roster </h2></div>
<div class="cpmid">
            <span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'login-grid-view?cid=' . $course->id) ?>">View Login Grid</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'assign-sections-and-codes?cid=' . $course->id); ?>">Assign Sections and/or Codes</a><br>
            </span>
            <span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-late-passes?cid=' . $course->id); ?>">Manage LatePasses</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'manage-tutors?cid=' . $course->id); ?>">Manage Tutors</a><br/>
            </span>
            <span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-enrollment?cid=' . $course->id . '&enroll=student'); ?>">Enroll Student with known username</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'enroll-from-other-course?cid=' . $course->id); ?>">Enroll students from another course</a><br/>
            </span>
            <span class="column" style="width:auto;">
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'import-student?cid=' . $course->id); ?>">Import Students from File</a><br/>
                <a href="<?php echo AppUtility::getURLFromHome('roster/roster', 'create-and-enroll-new-student?cid=' . $course->id); ?>">Create and Enroll new student</a><br/>
            </span><br class="clear"/>
</div>
<div class="button-container">
    <form>
        <span>Check: <a class="check-all" href="#">All</a>/<a class="non-locked" href="#">Non-locked</a>/<a class="uncheck-all" href="#">None</a> With Selected:</span>
    </form>
    <form action="roster-email?cid=<?php echo $course->id ?>" method="post" id="roster-form">
        <input type="hidden" id="student-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-email" value="Email"></span>
    </form>
    <form action="roster-message?cid=<?php echo $course->id ?>" method="post" id="roster-form">
        <input type="hidden" id="message-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-message" value="Message"></span>
    </form>
        <span> <a class="btn btn-primary" id="unenroll-btn">Unenroll</a></span>
        <span> <a class="btn btn-primary" id="lock-btn">Lock</a></span>
    <form action="make-exception?cid=<?php echo $course->id ?>" name="teacherMakeException" id="make-student" method="post">
        <input type="hidden" id="exception-id" name="student-data" value=""/>
        <input type="hidden" id="section-name" name="section-data" value=""/>
        <span> <input type="submit" class="btn btn-primary" id="roster-makeExc" value="Make Exception"></span>
    </form>
    <form action="copy-student-email?cid=<?php echo $course->id ?>" method="post" id="roster-form">
        <input type="hidden" id="email-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-copy-emails" value="Copy Emails"></span>
    </form>
    <form>
        <input type="button"  id='imgtab' class="btn btn-primary" value="Pictures" onclick="rotatepics()" >
    </form>
</div>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<table class="student-data-table table table-bordered table-striped table-hover data-table" id="student-information" bPaginate="false" >
    <thead>
    <tr>
        <th></th>
        <?php if ($isImageColumnPresent == 1) {
            ?><th></th>
        <?php }
            if ($isSection == true) {
            ?>
            <th>Section</th>
        <?php
        }
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
        <th>Change Info</th>
        <th>Lock Out</th>
    </tr>
    </thead>
    <tbody id="student-information-table">
    </tbody>
</table>

