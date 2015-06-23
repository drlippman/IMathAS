<?php
use app\components\AppUtility;
$this->title = 'Gradebook';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid=' .$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>
<h2>Gradebook</h2>
<input type="hidden" class="course-info" name="course-info" value="<?php echo $course->id; ?>"/>
<div class="cpmid">
    Offline Grades: <a href="#">Add</a>, <a href="#">Manage</a> | <select id="exportsel" onchange="chgexport()"><option value="0">Export to...</option></select> |
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
    <form action="roster-email?cid=<?php echo $course->id ?>" method="post" id="roster-form">
<!--        <input type="hidden" id="student-id" name="student-data" value=""/>-->
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-email" value="E-mail"></span>
    </form>
    <form action="copy-student-email?cid=<?php echo $course->id ?>" method="post" id="roster-form">
<!--        <input type="hidden" id="email-id" name="student-data" value=""/>-->
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-copy-emails" value="Copy E-mails"></span>
    </form>
    <form action="roster-message?cid=<?php echo $course->id ?>" method="post" id="roster-form">
<!--        <input type="hidden" id="message-id" name="student-data" value=""/>-->
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-message" value="Message"></span>
    </form>
    <span> <a class="btn btn-primary" id="unenroll-btn">Unenroll</a></span>
    <span> <a class="btn btn-primary" id="lock-btn">Lock</a></span>
    <form action="make-exception?cid=<?php echo $course->id ?>" name="teacherMakeException" id="make-student" method="post">
<!--        <input type="hidden" id="exception-id" name="student-data" value=""/>-->
<!--        <input type="hidden" id="section-name" name="section-data" value=""/>-->
        <span> <input type="submit" class="btn btn-primary" id="roster-makeExc" value="Make Exception"></span>
    </form>
</div>


<div id="tbl-container">
    <div id="bigcontmyTable" style="margin: 0px; padding: 0px;">
        <div id="tblcontmyTable" style="margin: 0px; padding: 0px;">
            <table class="gb" id="myTable" tableindex="1" style="position: static;">
                <thead>
                <tr>
                    <th><div style="width: 192px; height: 77px;">Name<br><span class="small">N=4</span><br><select id="toggle5" onchange="chgtoggle()"><option value="0" selected="selected">Show Locked</option><option value="2">Hide Locked</option></select></div></th>
                    <th style="cursor: default;"><div style="width: 14px; height: 77px;">&nbsp;</div></th>
                    <th class="nocolorize"><div style="width: 63px;">Section<br><select id="secfiltersel" onchange="chgsecfilter()">
                                <option value="-1" selected="1">All</option>
                            <?php foreach ($sections as $section){
                                echo "<option value='{$section->section}'>{$section->section}</option>";
                            }?>
                            </select></div></th>
                    <th class="nocolorize"><div style="width: 46px;">Code</div></th>
                    <th class="cat0"><div style="width: 56px;">test<br>0&nbsp;pts<br><a class="small" href="addassessment.php?id=1&amp;cid=5&amp;from=gb">[Settings]</a><br><a class="small" href="isolateassessgrade.php?cid=5&amp;aid=1">[Isolate]</a></div></th>
                    <th class="cat0"><div style="width: 56px;">new<br>10&nbsp;pts<br><a class="small" href="addassessment.php?id=4&amp;cid=5&amp;from=gb">[Settings]</a><br><a class="small" href="isolateassessgrade.php?cid=5&amp;aid=4">[Isolate]</a></div></th>
                    <th class="cat0"><div style="width: 56px;">rr<br>10&nbsp;pts<br><a class="small" href="addassessment.php?id=3&amp;cid=5&amp;from=gb">[Settings]</a><br><a class="small" href="isolateassessgrade.php?cid=5&amp;aid=3">[Isolate]</a></div></th>
                    <th class="cat"><div style="width: 67px;"><span class="cattothdr">Default<br>20&nbsp;pts<br><a class="small" href="gradebook.php?cid=5&amp;cat=0&amp;catcollapse=2">[Collapse]</a></span></div></th>
                    <th><div style="width: 52px;"><span class="cattothdr">Total<br>20&nbsp;pts</span></div></th>
                    <th><div style="width: 60px;">%</div></th>
                </tr>
                </thead>
                <tbody class="gradebook-table-body">
<!--                <tr class="even"  lastclass="even"><td class="locked" scope="row"><div class="trld" style="width: 192px; height: 27px;"><input type="checkbox" name="checked[]" value="23">&nbsp;<a href="gradebook.php?cid=5&amp;stu=23">Pawar,&nbsp;Akash</a></div></td><td><div style="width: 14px; height: 27px;"><div class="trld">&nbsp;</div></div></td><td class="c"><div style="width: 63px;"></div></td><td class="c"><div style="width: 46px;"></div></td><td class="c"><div style="width: 56px;"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=1&amp;uid=23">-</a></div></td><td class="c"><div style="width: 56px;"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=16&amp;uid=23">0</a></div></td><td class="c isact"><div style="width: 56px;"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=4&amp;uid=23">10</a><sup>e</sup></div></td><td class="c"><div style="width: 67px;">10</div></td><td class="c"><div style="width: 52px;">10</div></td><td class="c"><div style="width: 60px;">50.0%</div></td></tr><tr class="odd" onmouseover="highlightrow(this)" onmouseout="unhighlightrow(this)" lastclass="odd"><td class="locked" scope="row"><div class="trld" style="height: 27px;"><input type="checkbox" name="checked[]" value="28">&nbsp;<a href="gradebook.php?cid=5&amp;stu=28">patil,&nbsp;digvijay</a></div></td><td><div class="trld" style="height: 27px;">&nbsp;</div></td><td class="c">2</td><td class="c">d</td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=1&amp;uid=28">-</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=4&amp;uid=28">-</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=3&amp;uid=28">-</a><sup>e</sup></td><td class="c">0</td><td class="c">0</td><td class="c">0.0%</td></tr><tr class="even" onmouseover="highlightrow(this)" onmouseout="unhighlightrow(this)" lastclass="even"><td class="locked" scope="row"><div class="trld" style="height: 27px;"><input type="checkbox" name="checked[]" value="15">&nbsp;<a href="gradebook.php?cid=5&amp;stu=15">sgsfds,&nbsp;fgdfsg</a></div></td><td><div class="trld" style="height: 27px;">&nbsp;</div></td><td class="c">22</td><td class="c">44</td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=1&amp;uid=15">-</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=15&amp;uid=15">0 (IP)</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=13&amp;uid=15">10</a><sup>e</sup></td><td class="c">10</td><td class="c">10</td><td class="c">50.0%</td></tr><tr class="odd" onmouseover="highlightrow(this)" onmouseout="unhighlightrow(this)" lastclass="odd"><td class="locked" scope="row"><div class="trld" style="height: 27px;"><input type="checkbox" name="checked[]" value="24">&nbsp;<a href="gradebook.php?cid=5&amp;stu=24">chaudhari,&nbsp;priyanka</a></div></td><td><div class="trld" style="height: 27px;">&nbsp;</div></td><td class="c">3</td><td class="c">ds</td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=1&amp;uid=24">-</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=12&amp;uid=24">10</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=8&amp;uid=24">10</a><sup>e</sup></td><td class="c">20</td><td class="c">20</td><td class="c">100.0%</td></tr><tr class="even" onmouseover="highlightrow(this)" onmouseout="unhighlightrow(this)" lastclass="even"><td class="locked" scope="row"><div class="trld" style="height: 27px;"><input type="checkbox" name="checked[]" value="11">&nbsp;<a href="gradebook.php?cid=5&amp;stu=11">d,&nbsp;tushar</a></div></td><td><div class="trld" style="height: 27px;">&nbsp;</div></td><td class="c">jhjkn</td><td class="c"></td><td class="c"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=1&amp;uid=11">-</a></td><td class="c"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=14&amp;uid=11">0</a><sup>e</sup></td><td class="c isact"><a href="gb-viewasid.php?stu=0&amp;cid=5&amp;asid=new&amp;aid=3&amp;uid=11">-</a><sup>e</sup></td><td class="c">0</td><td class="c">0</td><td class="c">0.0%</td></tr><tr class="odd" onmouseover="highlightrow(this)" onmouseout="unhighlightrow(this)"><td class="locked" scope="row"><div class="trld" style="height: 27px;"><a href="gradebook.php?cid=5&amp;stu=-1">Averages</a></div></td><td><div class="trld" style="height: 27px;">&nbsp;</div></td><td class="c"></td><td class="c"></td><td class="c">-</td><td class="c"><a href="gb-itemanalysis.php?stu=0&amp;cid=5&amp;asid=average&amp;aid=4" onmouseover="tipshow(this,'5-number summary: n = 3<br/>0,&nbsp;0,&nbsp;0,&nbsp;10,&nbsp;10<br/>0%,&nbsp;0%,&nbsp;0%,&nbsp;100%,&nbsp;100%')" onmouseout="tipout()">3.3</a></td><td class="c"><a href="gb-itemanalysis.php?stu=0&amp;cid=5&amp;asid=average&amp;aid=3" onmouseover="tipshow(this,'5-number summary: n = 3<br/>10,&nbsp;10,&nbsp;10,&nbsp;10,&nbsp;10<br/>100%,&nbsp;100%,&nbsp;100%,&nbsp;100%,&nbsp;100%')" onmouseout="tipout()">10</a></td><td class="c"><span onmouseover="tipshow(this,'5-number summary: 0,&nbsp;0,&nbsp;10,&nbsp;10,&nbsp;20<br/>0%,&nbsp;0%,&nbsp;50%,&nbsp;50%,&nbsp;100%')" onmouseout="tipout()">8</span></td><td class="c">8</td><td class="c">40%</td></tr>-->
                </tbody>
            </table>
        </div>
    </div>
</div>

<p>Meanings:  IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit
<br>
<sup>*</sup>Has feedback,<sub> d</sub>Dropped score,<sup> e</sup>Has exception,<sup> LP</sup>Used latepass</p>
