<?php
use app\components\AppUtility;
$this->title = 'Gradebook';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid=' .$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<h2>Gradebook</h2>
<input type="hidden" class="course-info" id="course-id" name="course-info" value="<?php echo $course->id; ?>"/>
<input type="hidden" class="user-info" name="user-info" value="<?php echo $user->id; ?>"/>
<div class="cpmid">
    Offline Grades: <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id); ?>">Add</a>, <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/manage-offline-grades?cid='.$course->id); ?>">Manage</a> | <select id="exportsel" onchange="chgexport()"><option value="0">Export to...</option></select> |
    <a href="#">GB Settings</a> | <a href="#">Averages</a> | <a href="#">Comments</a> | <input type="button" id="lockbtn" class="btn-primary"onclick="lockcol()" value="Lock headers"> |
    Color:      <select id="colorsel" onchange="updateColors(this)"><option value="0">None</option></select> | <a href="#" onclick="chgnewflag(); return false;">NewFlag</a><br><br>
    Category:   <select id="filtersel" onchange="chgfilter()"><option value="-1">All</option><option value="0">Default</option><option value="-2" selected="1">Category Totals</option></select> |
    Not Counted:<select id="toggle2" onchange="chgtoggle()"><option value="0">Show all</option><option value="1">Show stu view</option><option value="2" selected="selected">Hide all</option></select> |
    Show:       <select id="toggle3" onchange="chgtoggle()"><option value="0">Past due</option><option value="3">Past &amp; Attempted</option><option value="4">Available Only</option><option value="1">Past &amp; Available</option><option value="2" selected="selected">All</option></select> |
    Links:      <select id="toggle1" onchange="chgtoggle()"><option value="0">View/Edit</option><option value="1" selected="selected">Scores</option></select> |
    Pics:       <select id="toggle4" onchange="chgtoggle()"><option value="0" selected="selected">None</option><option value="1">Small</option><option value="2">Big</option></select>
</div>

<div class="button-container">
    <form>
        <span>Check: <a class="check-all" href="#">All</a>/<a class="uncheck-all" href="#">None</a> With Selected:</span>
    </form>
    <form>
        <span> <a class="btn btn-primary" id="unenroll-btn">Print Report</a></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-email?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="student-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-email" value="E-mail"></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/copy-student-email?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="email-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-copy-emails" value="Copy E-mails"></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-message?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="message-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-message" value="Message"></span>
    </form>
    <span> <a class="btn btn-primary" id="unenroll-btn" onclick="studentUnenroll()">Unenroll</a></span>
    <span> <a class="btn btn-primary" id="lock-btn">Lock</a></span>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/make-exception?cid='.$course->id.'&gradebook=1' ) ?>" name="teacherMakeException" id="make-student" method="post">
        <input type="hidden" id="exception-id" name="student-data" value=""/>
        <input type="hidden" id="section-name" name="section-data" value=""/>
        <span> <input type="submit" class="btn btn-primary" id="gradebook-makeExc" value="Make Exception"></span>
    </form>
</div><br/>

<div class="gradebook-div"></div>

<p>Meanings:  IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit
<br>
<sup>*</sup>Has feedback,<sub> d</sub>Dropped score,<sup> e</sup>Has exception,<sup> LP</sup>Used latepass</p>
