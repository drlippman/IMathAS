<?php
use app\components\AppUtility;
use \app\components\interpretUtility;
use app\models\Assessments;
use \app\components\CategoryScoresUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Grade Book Detail', false);

global $isTeacher, $isTutor, $temp,$lastanswers;
$isteacher = $defaultValuesArray['isteacher'];
$gbmode = $defaultValuesArray['gbmode'];
$istutor = $defaultValuesArray['istutor'];
$from = $defaultValuesArray['from'];
$totonleft = $defaultValuesArray['totonleft'];
$links = $defaultValuesArray['links'];
$hidenc = $defaultValuesArray['hidenc'];
$availshow = $defaultValuesArray['availshow'];
$asid = $defaultValuesArray['asid'];
$pers = $defaultValuesArray['pers'];
$stu = $params['stu'];

//PROCESS ANY TODOS
if (isset($params['clearattempt']) && isset($params['asid']) && $isTeacher) {

    if ($params['clearattempt'] == "true") {
        $isgroup = $defaultValuesArray['groupId'];
        if ($isgroup) {
            $pers = 'group';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        } else {
            $pers = 'student';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        }
        echo "<p>Are you sure you want to clear this $pers's assessment attempt?  This will make it appear the $pers never tried the assessment, and the $pers will receive a new version of the assessment.</p>"; ?>
        <p><input type=button
                  onclick='window.location.href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&clearattempt=confirmed&asid=' . $params['asid']) ?>"'
                  value="Really Clear">
            <input type=button value="Back" class="secondarybtn"
                   onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&uid=' . $params['uid']) ?>"'>
        </p>
        <?php exit;
    }
}

if (isset($params['breakfromgroup']) && isset($params['asid']) && $isTeacher) {
    if ($params['breakfromgroup'] == "confirmed") {
    } else {
        echo $defaultValuesArray['studentNameWithAssessmentName'];
        echo "<p>Are you sure you want to separate this student from their current group?</p>";?>
        <p><input type=button
                  onclick="window.location='<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessement-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&uid=' . $params['uid'] . '&breakfromgroup=confirmed'); ?> "
                  value="Really Separate">
            <input type=button value="Back" class="secondarybtn"
                   onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&uid=' . $params['uid']) ?>"'>
        </p>
        <?php
        exit;
    }
}

if (isset($params['clearscores']) && isset($params['asid']) && $isTeacher) {
    if ($_GET['clearscores'] == "true") {
        $isgroup = $defaultValuesArray['groupId'];
        if ($isgroup) {
            $pers = 'group';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        } else {
            $pers = 'student';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        }
        echo "<p>Are you sure you want to clear this $pers's scores for this assessment?</p>"; ?>
        <p><input type=button
                  onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&uid=' . $_GET['uid'] . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $_GET['asid'] . '&clearscores=confirmed') ?>"'
                  value="Really Clear"></n>
            <input type=button value="Back" class="secondarybtn"
                   onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $_GET['asid'] . '&uid=' . $_GET['uid']) ?>"'>
        </p>
        <?php
        exit;
    }
}
if (isset($params['clearq']) && isset($params['asid']) && $isteacher) {

    if (!$params['confirmed'] == "true") {
        $isgroup = $defaultValuesArray['groupId'];
        if ($isgroup) {
            $pers = 'group';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        } else {
            $pers = 'student';
            echo $defaultValuesArray['studentNameWithAssessmentName'];
        }
        echo "<p>Are you sure you want to clear this $pers's scores for this question?</p>"; ?>

        <p><input type=button
                  onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&uid=' . $params['uid'] . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&clearq=' . $params['clearq'] . '&confirmed=true') ?>"'
                  value="Really Clear"></n>
            <input type=button value="Back" class="secondarybtn"
                   onclick='window.location="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&gbmode=' . $gbmode . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&uid=' . $params['uid']) ?>"'>
        </p>
        <?php
        exit;
    }
}

if (isset($params['forcegraphimg'])) {
    $sessiondata['graphdisp'] = 2;
}

//OUTPUTS
if ($links == 0) { //View/Edit full assessment


$coursetheme = 'default.css';
$useeditor = 'review';
$sessiondata['coursetheme'] = $coursetheme;
$sessiondata['isteacher'] = $isTeacher;

if ($isTeacher || $isTutor) {
    $placeinhead = '<script type="text/javascript" src="' . AppUtility::getBasePath() . '/web/js/gradebook/rubric.js?v=070113"></script>';

}
echo "<style type=\"text/css\">p.tips {	display: none;}\n</style>\n";

//Temporary fixed exception on click of null score commented the following code.

//if (!$assessmentData) {
//    echo "uh oh.  Bad assessment id";
//    exit;
//}
$line = $assessmentData;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
</div>
<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page word-wrap-break-word"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($isTeacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']);
    } elseif($isTutor || $isStudent){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'gradebook', 'userId' => $currentUser , 'isTutor'=> $isTutor]);
    }?>
</div>
<?php
/*
 * start shadow-box
 */

echo '<div class="col-md-12 col-sm-12 padding-left-right-zero tab-content shadowBox padding-bottom-two-em">';

echo "<div class='col-md-12 col-sm-12 padding-bottom-fifteen'>";
echo "<h3 class='col-md-12 col-sm-12'>{$studentData[1]}, {$studentData[0]}</h3>\n";

//do time limit mult
$timelimitmult = $studntData[2];
$line['timelimit'] *= $timelimitmult;
$teacherreview = $params['uid'];

if ($canedit) {
    if ($rubricsData) {
        $rubrics = array();
        $tempRubrics = array();
        foreach ($rubricsData as $rubric) {
            $tempRubrics[0] = $rubric['id'];
            $tempRubrics[1] = $rubric['rubrictype'];
            $tempRubrics[2] = $rubric['rubric'];
            array_push($rubrics, $tempRubrics);
        }
        echo printrubrics($rubrics);
    }
    unset($rubrics);
}

list($testtype, $showans) = explode('-', $line['deffeedback']);
if ($showans == 'N' && !$isTeacher && !$isTutor) {
    echo "You shouldn't be here";
    exit;
}
echo "<h4 class='col-md-12 col-sm-12 margin-top-zero'>{$line['name']}</h4>\n";
$aid = $line['assessmentid'];

if (($isTeacher || $isTutor) && !isset($params['lastver']) && !isset($params['reviewver'])) {
    if ($line['agroupid'] > 0) {

        echo "<p>Group members: <ul>";
        foreach ($groupMembers as $row) {
            echo "<li>{$row['LastName']}, {$row['FirstName']}</li>";
        }
        echo "</ul></p>";
    }
}

if ($line['starttime'] == 0) {
    echo '<div class="col-md-12 col-sm-12">Started: Not yet started</div>';
} else {
    echo "<div class='col-md-12 col-sm-12'>Started: " . AppUtility::tzdate("F j, Y, g:i a", $line['starttime']) . "</div>";
}
if ($line['endtime'] == 0) {
    echo "<div class='col-md-12 col-sm-12'>Not Submitted</div>";
} else {
    echo "<div class='col-md-12 col-sm-12 padding-top-five'>Last change: " . AppUtility::tzdate("F j, Y, g:i a", $line['endtime']) . "</div>";
    $timespent = round(($line['endtime'] - $line['starttime']) / 60);
    if ($timespent < 250) {
        echo "<div class='col-md-12 col-sm-12 padding-top-five'>Time spent: " . $timespent . " minutes<br/></div>\n";
    }
    $timeontask = array_sum(explode(',', str_replace('~', ',', $line['timeontask'])));
    if ($timeontask > 0) {
        echo "<div class='col-md-12 col-sm-12 padding-top-five'>Total time questions were on-screen: " . round($timeontask / 60, 1) . " minutes.</div>";
    }
}
$saenddate = $line['enddate'];
unset($exped);
if ($exceptionData['enddate']) {
    $exped = $exceptionData['enddate'];
    if ($exped > $saenddate) {
        $saenddate = $exped;
    }
}

if ($isteacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) {
    if (($exped) && $exped != $line['enddate']) {
        echo "<div>Has exception, with due date: " . AppUtility::tzdate("F j, Y, g:i a", $exped);
        echo "  <button type=\"button\" onclick=\"window.location.href='exception?cid=$course->id&aid={$line['assessmentid']}&uid={$params['uid']}&asid={$params['asid']}&from=$from&stu=$stu'\">Edit Exception</button>";
        echo "<br/>Original Due Date: " . AppUtility::tzdate("F j, Y, g:i a", $line['enddate']);
    } else {
        echo "<div class='col-md-12 col-sm-12 padding-top-ten'>Due Date: " . AppUtility::tzdate("F j, Y, g:i a", $line['enddate']);
        echo "  <button class='margin-left-fifteen' type=\"button\" onclick=\"window.location.href='exception?cid=$course->id&aid={$line['assessmentid']}&uid={$params['uid']}&asid={$params['asid']}&from=$from&stu=$stu'\">Make Exception</button>";
    }
    echo "</div>";
}
if ($isTeacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) {
    if ($line['agroupid'] > 0) {
        echo "<p>This assignment is linked to a group.  Changes will affect the group unless specified. "; ?>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&cid=' . $course->id . '&asid=' . $params['asid'] . '&from=' . $from . '&uid=' . $params['uid'] . '&breakfromgroup=true'); ?> ">Separate
            from Group</a></p>
    <?php
    }
}
?>
</div>
<form id="mainform" method=post
      action="gradebook-view-assessment-details?stu=<?php echo $stu ?>&cid=<?php echo $course->id ?>&from=<?php echo $from ?>&asid=<?php echo $asid ?>&update=true">

<?php if ($isteacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) { ?>

    <div class="col-md-12 col-sm-12 gradebook-view-assessment-link mobile-padding-bottom-one-pt-five-em">
        <div class="col-md-2 col-sm-3 padding-top-one-pt-five-em">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&cid=' . $course->id . '&asid=' . $params['asid'] . '&from=' . $from . '&uid=' . $params['uid'] . '&clearattempt=true'); ?>"
           onmouseover="tipshow(this,'Clear everything, resetting things like the student never started.  Student will get new versions of questions.')"
           onmouseout="tipout()">Clear Attempt</a>
        </div>
        <div class="col-md-2 col-sm-3 padding-top-one-pt-five-em">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&cid=' . $course->id . '&asid=' . $params['asid'] . '&from=' . $from . '&uid=' . $params['uid'] . '&clearscores=true') ?>"
               onmouseover="tipshow(this,'Clear scores and attempts, but keep same versions of questions')"
               onmouseout="tipout()">Clear Scores</a>
        </div>
        <div class="col-md-2 col-sm-3 padding-top-one-pt-five-em">
            <a href="#" onclick="markallfullscore();$('#uppersubmit').show();return false;"
               onmouseover="tipshow(this,'Change all scores to full credit')" onmouseout="tipout()">All Full Credit</a>
        </div>
        <div style="display:none;" id="uppersubmit" class="col-md-2 col-sm-3 padding-left-zero padding-top-one-pt-five-em">
            <input type="submit" value="Record Changed Grades">
        </div>
        <div class="col-md-2 col-sm-3 padding-right-zero padding-left-three-em mobile-padding-left-one-em padding-top-one-pt-five-em">
            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-test?cid=' . $course->id . '&id=' . $line['assessmentid'] . '&actas=' . $params['uid']); ?> "
               onmouseover="tipshow(this,'Take on role of this student, bypassing date restrictions, to submit answers')"
               onmouseout="tipout()">View as student
            </a>
        </div>
        <div class="col-md-2 col-sm-4 padding-top-one-pt-five-em">
            <a href="<?php echo AppUtility::getURLFromHome('assessment', 'assessment/show-print-test?cid=' . $course->id . '&asid=' . $params['asid']); ?>"
               target="_blank" onmouseover="tipshow(this,'Pull up a print version of this student\'s assessment')"
               onmouseout="tipout()">Print Version
            </a>
        </div>
    </div>
<?php
}
if (($line['timelimit'] > 0) && ($line['endtime'] - $line['starttime'] > $line['timelimit'])) {
    $over = $line['endtime'] - $line['starttime'] - $line['timelimit'];
    ?> <p> <?php echo " Time limit exceeded by ";
    if ($over > 60) {
        $overmin = floor($over / 60);
        echo "$overmin minutes, ";
        $over = $over - $overmin * 60;
    }
    echo "$over seconds.<BR>\n";
    $reset = $line['endtime'] - $line['timelimit'];
    if ($isTeacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) {
        ?>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&starttime=' . $reset . '&asid=' . $params['asid'] . '&from=' . $from . '&cid=' . $course->id . '&uid=' . $params['uid']) ?>">
            Clear overtime and accept grade </a> </p>
    <?php
    }
}
if (strpos($line['questions'], ';') === false) {
    $questions = explode(",", $line['questions']);
    $bestquestions = $questions;
} else {
    list($questions, $bestquestions) = explode(";", $line['questions']);
    $questions = explode(",", $questions);
    $bestquestions = explode(",", $bestquestions);
}

if ($line['timeontask'] == '') {
    $timesontask = array_fill(0, count($questions), '');
} else {
    $timesontask = explode(',', $line['timeontask']);
}
if ($params['lastver']){
    $seeds = explode(",", $line['seeds']);
    $sp = explode(";", $line['scores']);
    $scores = explode(",", $sp[0]);
    if (isset($sp[1])) {
        $rawscores = explode(",", $sp[1]);
    }
    $attempts = explode(",", $line['attempts']);
    $lastanswers = explode("~", $line['lastanswers']); ?>
<div class="gradebook-view-assessment-sub-link padding-top-twenty col-md-12 col-sm-12">
    <div class="col-md-3 col-sm-3">
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&from=' . $from . '&cid=' . $course->id . '&uid=' . $params['uid']) ?> ">Show
            Scored Attempts
        </a>
    </div>
    <div class="col-md-3 col-sm-3">
        <b>Showing Last Attempts</b>
    </div>
    <div class="col-md-3 col-sm-3">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&from=' . $from . '&cid=' . $course->id . '&uid=' . $params['uid'] . '&reviewver=1') ?> ">Show
                Review Attempts
            </a>
    </div>
</div>
<?php
} else if ($params['reviewver']) {
    $seeds = explode(",", $line['reviewseeds']);
    $sp = explode(";", $line['reviewscores']);
    $scores = explode(",", $sp[0]);
    if (isset($sp[1])) {
        $rawscores = explode(",", $sp[1]);
    }
    $attempts = explode(",", $line['reviewattempts']);
    $lastanswers = explode("~", $line['reviewlastanswers']); ?>
<div class="gradebook-view-assessment-sub-link padding-top-twenty col-md-12 col-sm-12">
    <div class="col-md-3 col-sm-3">
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&from=' . $from . '&cid=' . $course->id . '&uid=' . $params['uid']) ?> ">Show
        Scored Attempts
        </a>
    </div>
    <div class="col-md-3 col-sm-3">
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&from=' . $from . '&cid=' . $course->id . '&uid=' . $params['uid'] . '&lastver=1') ?> ">Show
        Last Graded Attempts
        </a>
    </div>
    <div class="col-md-3 col-sm-3">
        <b>Showing Review Attempts</b>
    </div>
</div>
<?php
} else {
    $seeds = explode(",", $line['bestseeds']);
    $sp = explode(";", $line['bestscores']);
    $scores = explode(",", $sp[0]);

    if (isset($sp[1])) {
        $rawscores = explode(",", $sp[1]);
    }
    $attempts = explode(",", $line['bestattempts']);
    $lastanswers = explode("~", $line['bestlastanswers']);
    $questions = $bestquestions; ?>
    <div class="gradebook-view-assessment-sub-link padding-top-twenty col-md-12 col-sm-12">
        <div class="col-md-3 col-sm-4">
            <b>Showing Scored Attempts</b>
        </div>
        <div class="col-md-3 col-sm-3">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&cid=' . $course->id . '&from=' . $from . '&uid=' . $params['uid'] . '&lastver=1'); ?>  ">
                Show Last Attempts
            </a>
        </div>
        <div class="col-md-3 col-sm-3">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&asid=' . $params['asid'] . '&cid=' . $course->id . '&from=' . $from . '&uid=' . $params['uid'] . '&reviewver=1'); ?> ">Show
                Review Attempts
            </a>
        </div>
    </div>
<?php
}
$totalpossible = 0;
$pts = array();
$withdrawn = array();
$rubric = array();
$extref = array();
$owners = array();
foreach ($questionsData as $r) {
    if ($r[1] == 9999) {
        $pts[$r[0]] = $line['defpoints']; //use defpoints
    } else {
        $pts[$r[0]] = $r[1]; //use points from question
    }
    //$totalpossible += $pts[$r[0]];  do later
    $withdrawn[$r[0]] = $r[2];
    $rubric[$r[0]] = $r[5];
    if ($r[3] == 'multipart') {

        $answeights[$r[0]] = getansweights($r[0], $r[4], $questions, $seeds);
        for ($i = 0; $i < count($answeights[$r[0]]) - 1; $i++) {
            $answeights[$r[0]][$i] = round($answeights[$r[0]][$i] * $pts[$r[0]], 2);
        }
        //adjust for rounding
        $diff = $pts[$r[0]] - array_sum($answeights[$r[0]]);
        $answeights[$r[0]][count($answeights[$r[0]]) - 1] += $diff;

    }
    if (($line['showhints'] == 1 && $r[6] != 1) || $r[6] == 2) {
        if ($r[7] != '') {
            $extref[$r[0]] = explode('~~', $r[7]);
        }
    }
    $owners[$r[0]] = $r[8];
}

echo '<script type="text/javascript">
			function hidecorrect() {
				var butn = $("#hctoggle");
				if (!butn.hasClass("hchidden")) {
					butn.html("' . _('Show Correct Questions') . '");
					butn.addClass("hchidden");
					$(".iscorrect").hide();
				} else {
					butn.html("' . _('Hide Correct Questions') . '");
					butn.removeClass("hchidden");
					$(".iscorrect").show();
				}
			}
			function hideperfect() {
				var butn = $("#hptoggle");
				if (!butn.hasClass("hphidden")) {
					butn.html("' . _('Show Perfect Questions') . '");
					butn.addClass("hphidden");
					$(".isperfect").hide();
				} else {
					butn.html("' . _('Hide Perfect Questions') . '");
					butn.removeClass("hphidden");
					$(".isperfect").show();
				}
			}
			function hideNA() {
				var butn = $("#hnatoggle");
				if (!butn.hasClass("hnahidden")) {
					butn.html("' . _('Show Unanswered Questions') . '");
					butn.addClass("hnahidden");
				} else {
					butn.html("' . _('Hide Unanswered Questions') . '");
					butn.removeClass("hnahidden");
				}
				$(".notanswered").toggle();
			}
			function showallans() {
				$("span[id^=\'ans\']").removeClass("hidden");
				$(".sabtn").replaceWith("<span>Answer: </span>");
			}
			function previewall() {
				$(\'input[value="Preview"]\').trigger(\'click\').remove();
			}
			var focuscolorlock = false;
			$(function() {
				$(".review input[name*=\'-\']").each(function(i, el) {
					var partname = $(el).attr("name");
					var idparts = partname.split("-");
					var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
					$(el).on("mouseover", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
					}).on("mouseout", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
					}).on("focus", function () {
						focuscolorlock = true;
						$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
					}).on("blur", function () {
						focuscolorlock = false;
						$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
					});
				});
				$("input[id^=\'showansbtn\']").each(function(i, el) {
					var partname = $(el).attr("id").substring(10);
					var idparts = partname.split("-");
					var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
					$(el).on("mouseover", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
					}).on("mouseout", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
					});
				});
				$("input[id^=\'qn\'], input[id^=\'tc\'], select[id^=\'qn\'], div[id^=\'qnwrap\'], span[id^=\'qnwrap\']").each(function(i,el) {
					var qn = $(el).attr("id");
					if (qn.length>6 && qn.substring(0,6)=="qnwrap") {
						qn = qn.substring(6)*1;
					} else {
						qn = qn.substring(2)*1;
					}
					if (qn>999) {
						var partname = (Math.floor(qn/1000)-1)+"-"+(qn%1000);
						$(el).on("mouseover", function () {
							if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
						}).on("mouseout", function () {
							if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
						}).on("focus", function () {
							focuscolorlock = true;
							$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
						}).on("blur", function () {
							focuscolorlock = false;
							$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
						});
					}
				});
			});
			</script>';

echo '<div class="col-md-12 col-sm-12"><div class="col-md-12 col-sm-12 padding-top-bottom-two-em"><button class="margin-top-five" type="button" id="hctoggle" onclick="hidecorrect()">' . _('Hide Correct Questions') . '</button>';
echo ' <button class="margin-top-five margin-left-ten" type="button" id="hptoggle" onclick="hideperfect()">' . _('Hide Perfect Questions') . '</button>';
echo ' <button class="margin-top-five margin-left-ten" type="button" id="hnatoggle" onclick="hideNA()">' . _('Hide Unanswered Questions') . '</button>';
echo ' <button class="margin-top-five margin-left-ten margin-right-ten" type="button" id="showanstoggle" onclick="showallans()">' . _('Show All Answers') . '</button>';
echo ' <button class="margin-top-five" type="button" id="prevtoggle" onclick="previewall()">' . _('Preview All') . '</button></div>';
$total = 0;
for ($i = 0; $i < count($questions); $i++) {
    $temp = " ";
    echo "<div ";
    if($canedit && (getpts($scores[$i]) == $pts[$questions[$i]])){
        echo 'class="col-md-12 col-sm-12 iscorrect isperfect"';
        print_r($i);
    }elseif($canedit && (($rawscores) && isperfect($rawscores[$i])) || getpts($scores[$i]) == $pts[$questions[$i]]) {
        echo 'class="iscorrect col-md-12 col-sm-12"';
    }elseif($scores[$i] == -1) {
        echo 'class="notanswered col-md-12 col-sm-12"';
    }else{
        echo 'class="iswrong col-md-12 col-sm-12"';
    }
    $totalpossible += $pts[$questions[$i]];

    echo '>';

    foreach ($librariesName as $libraryName) {
        if ($libraryName['questionId'] == $questions[$i]) {
            if ($libraryName[2] == null) {
                list($qsetid, $cat) = array($libraryName[0], $libraryName[1]);
            } else {
                list($qsetid, $cat) = array($libraryName[0], $libraryName[2]);
            }

        }
    }

    if ($isTeacher || $isTutor || ($testtype == "Practice" && $showans != "V") || ($testtype != "Practice" && (($showans == "I" && !in_array(-1, $scores)) || ($showans != "V" && time() > $saenddate)))) {
        $showa = true;
    } else {
        $showa = false;
    }

    if (isset($answeights[$questions[$i]])) {
        $GLOBALS['questionscoreref'] = array("scorebox$i", $answeights[$questions[$i]]);
    } else {
        $GLOBALS['questionscoreref'] = array("scorebox$i", $pts[$questions[$i]]);
    }

    if (isset($rawscores[$i])) {

        if (strpos($rawscores[$i], '~') !== false) {
            $colors = explode('~', $rawscores[$i]);
        } else {
            $colors = array($rawscores[$i]);
        }
    } else {
        $colors = array();
    }
    $capturechoices = true;
    $choicesdata = array();
    $qtypes = displayq($i, $qsetid, $seeds[$i], $showa, false, $attempts[$i], false, false, false, $colors);
    echo $temp;
    echo '</div>';

    if ($scores[$i] == -1) {
        $scores[$i] = "N/A";
    } else {
        $total += getpts($scores[$i]);
    }
    echo "<div class='col-md-12 col-sm-12'><div class='review question-form-control col-md-12 col-sm-12 padding-top-bottom-fifteen padding-left-right-thirty'>Question " . ($i + 1) . ": ";
    if ($withdrawn[$questions[$i]] == 1) {
        echo "<span class=\"red\">Question Withdrawn</span> ";
    }
    list($pt, $parts) = printscore($scores[$i]);
    if ($canedit && $parts == '') {
        echo "<input type=text size=4 id=\"scorebox$i\" name=\"$i\" value=\"$pt\">";
        if ($rubric[$questions[$i]] != 0) {
            echo printrubriclink($rubric[$questions[$i]], $pts[$questions[$i]], "scorebox$i", "feedback", ($i + 1));
        }
    } else {
        echo $pt;
    }

    if ($parts != '') {
        if ($canedit) {
            echo " (parts: ";
            $prts = explode(', ', $parts);
            for ($j = 0; $j < count($prts); $j++) {
                echo "<input type=text size=2 id=\"scorebox$i-$j\" name=\"$i-$j\" value=\"{$prts[$j]}\">";
                if ($rubric[$questions[$i]] != 0) {
                    echo printrubriclink($rubric[$questions[$i]], $answeights[$questions[$i]][$j], "scorebox$i-$j", "feedback", ($i + 1) . ' pt ' . ($j + 1));
                }
                echo ' ';
            }
            echo ")";
        } else {
            echo " (parts: $parts)";
        }
    }
    echo " out of {$pts[$questions[$i]]} ";
    if ($parts != '') {
        echo '(parts: ';
        for ($j = 0; $j < count($answeights[$questions[$i]]); $j++) {
            if ($j > 0) {
                echo ', ';
            }
            echo "<span id=\"ptpos$i-$j\">" . $answeights[$questions[$i]][$j] . '</span>';
        }
        echo ')';
    }
    echo "in {$attempts[$i]} attempt(s)\n";
    if ($isTeacher || $isTutor) {
        if ($canedit && getpts($scores[$i]) == $pts[$questions[$i]]) {
            echo '<div class="iscorrect isperfect">';
        } else if ($canedit && ((isset($rawscores) && isperfect($rawscores[$i])) || getpts($scores[$i]) == $pts[$questions[$i]])) {
            echo '<div class="iscorrect">';
        } else if ($scores[$i] === 'N/A') {
            echo '<div class="notanswered">';
        } else {
            echo '<div>';
        }

        if ($canedit && $parts != '') {
            $togr = array();
            foreach ($qtypes as $k => $t) {
                if ($t == 'essay' || $t == 'file') {
                    $togr[] = $k;
                }
            }

            echo 'Quick grade: <a href="#" class="quickgrade" onclick="quickgrade(' . $i . ',0,\'scorebox\',' . count($prts) . ',[' . implode(',', $answeights[$questions[$i]]) . ']);return false;">Full credit all parts</a>';
            if (count($togr) > 0) {
                $togr = implode(',', $togr);
                echo ' | <a href="#" onclick="quickgrade(' . $i . ',1,\'scorebox\',[' . $togr . '],[' . implode(',', $answeights[$questions[$i]]) . ']);return false;">Full credit all manually-graded parts</a>';
            }
        } else if ($canedit) {
            echo 'Quick grade: <a class="quickgrade" href="#" onclick="quicksetscore(\'scorebox' . $i . '\',' . $pts[$questions[$i]] . ');return false;">Full credit</a>';
        }
        $laarr = explode('##', $lastanswers[$i]);

        if ($attempts[$i] != count($laarr)) {
            //echo " (clicked \"Jump to answer\")";
        }
        if (count($laarr) > 1) {
            echo "<br/>Previous Attempts:";
            $cnt = 1;
            for ($k = 0; $k < count($laarr) - 1; $k++) {
                if ($laarr[$k] == "ReGen") {
                    echo ' ReGen ';
                } else {
                    echo "  <b>$cnt:</b> ";
                    if (preg_match('/@FILE:(.+?)@/', $laarr[$k], $match)) {
                        $url = getasidfileurl($match[1]);
                        echo "<a href=\"$url\" target=\"_new\">" . basename($match[1]) . "</a>";
                    } else {
                        if (strpos($laarr[$k], '$f$')) {
                            if (strpos($laarr[$k], '&')) { //is multipart q
                                $laparr = explode('&', $laarr[$k]);
                                foreach ($laparr as $lk => $v) {
                                    if (strpos($v, '$f$')) {
                                        $tmp = explode('$f$', $v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $laarr[$k] = implode('&', $laparr);
                            } else {
                                $tmp = explode('$f$', $laarr[$k]);
                                $laarr[$k] = $tmp[0];
                            }
                        }
                        if (strpos($laarr[$k], '$!$')) {
                            if (strpos($laarr[$k], '&')) { //is multipart q
                                $laparr = explode('&', $laarr[$k]);
                                foreach ($laparr as $lk => $v) {
                                    if (strpos($v, '$!$')) {
                                        $qn = ($i + 1) * 1000 + $lk;
                                        $tmp = explode('$!$', $v);
                                        //$laparr[$lk] = $tmp[0];
                                        $laparr[$lk] = prepchoicedisp($choicesdata[$qn][0] == 'matching' ? $tmp[0] : $tmp[1], $choicesdata[$qn]);
                                    }
                                }
                                $laarr[$k] = implode('&', $laparr);
                            } else {
                                $tmp = explode('$!$', $laarr[$k]);
                                //$laarr[$k] = $tmp[0];
                                $laarr[$k] = prepchoicedisp($choicesdata[$i][0] == 'matching' ? $tmp[0] : $tmp[1], $choicesdata[$i]);
                            }
                        } else {
                            $laarr[$k] = strip_tags($laarr[$k]);
                        }


                        if (strpos($laarr[$k], '$#$')) {
                            if (strpos($laarr[$k], '&')) { //is multipart q
                                $laparr = explode('&', $laarr[$k]);
                                foreach ($laparr as $lk => $v) {
                                    if (strpos($v, '$#$')) {
                                        $tmp = explode('$#$', $v);
                                        $laparr[$lk] = $tmp[0];
                                    }
                                }
                                $laarr[$k] = implode('&', $laparr);
                            } else {
                                $tmp = explode('$#$', $laarr[$k]);
                                $laarr[$k] = $tmp[0];
                            }
                        }

                        echo str_replace(array('&', '%nbsp;', '%%'), array('; ', '&nbsp;', '&'), $laarr[$k]);
                    }
                    $cnt++;
                }
            }
        }
        if ($timesontask[$i] != '' && !isset($params['reviewver'])) {
            echo '<br/>Average time per submission: ';
            $timesarr = explode('~', $timesontask[$i]);
            $avgtime = array_sum($timesarr) / count($timesarr);
            if ($avgtime < 60) {
                echo round($avgtime, 1) . ' seconds ';
            } else {
                echo round($avgtime / 60, 1) . ' minutes ';
            }
            echo '<br/>';
        }

        if ($isTeacher || ($isTutor && $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT)) {
            ?>

            <br><a target="_blank"
                   href="<?php echo AppUtility::getURLFromHome('message', 'message/index?cid=' . $course->id . '&add=new&quoteq=' . $i . '-' . $qsetid . '-' . $seeds[$i] . '-' . $line['assessmentid'] . '&to=' . $params['uid']); ?> ">Use
                in Msg</a>
            | <a
                href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook-view-assessment-details?stu=' . $stu . '&cid=' . $course->id . '&from=' . $from . '&asid=' . $params['asid'] . '&uid=' . $params['uid'] . '&clearq=' . $i); ?> ">Clear
                Score</a>
            (Question ID: <a
                href="<?php echo AppUtility::getURLFromHome('question', 'question/mod-data-set?id=' . $qsetid . '&cid=' . $cid . '&qid=' . $questions[$i] . '&aid=' . $aid); ?>  "><?php echo $qsetid ?></a>
            <?php if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) { ?>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/index?add=new&cid=' . $CFG['GEN']['sendquestionproblemsthroughcourse'] . '&to=' . $owners[$questions[$i]] . '&title=Problem%20with%20question%20id%20' . $qsetid); ?> "
                   target="_blank">Message owner</a> to report problems.
            <?php
            }
            echo ')';
            if (isset($extref[$questions[$i]])) {
                echo "&nbsp; Had help available: ";
                foreach ($extref[$questions[$i]] as $v) {
                    $extrefpt = explode('!!', $v);
                    echo '<a href="' . $extrefpt[1] . '" target="_blank">' . $extrefpt[0] . '</a> ';
                }
            }
        }
        echo '</div>';
        echo '</div>';

    }
    echo "</div>\n";

}

echo "<p></p><div class='col-md-12 col-sm-12'><div class='review question-form-control'>Total: $total/$totalpossible</div></div>";
if ($canedit && !($params['lastver']) && !($params['reviewver'])) {
    echo "<div class='col-md-12 col-sm-12 padding-left-zero'>
    <span class='col-md-12 col-sm-12'>Feedback to student</span>
    <div class='col-md-12 col-sm-12'>
        <textarea class='max-width-hundred-per' cols=60 rows=4 id=\"feedback\" name=\"feedback\">{$line['feedback']}</textarea>
    </div>
    </div>";
    if ($line['agroupid'] > 0) {
        echo "<p>Update grade for all group members? <input type=checkbox name=\"updategroup\" checked=\"checked\" /></p>";
    }
    echo "
    <div class='col-md-12 col-sm-12 padding-top-one-pt-five-em'>
    <input type=submit value=\"Record Changed Grades\"> ";
    if (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype'] != 0) {
        ?>
        <span class="padding-left-one-em"><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid=' . $course->id); ?>">
            Return to GradeBook without saving
            </a>
        </span>
    <?php
    }
    echo"</div>";
    /*
    if ($line['agroupid']>0) {
        $q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
        $q2 .= "i_u.id=i_a_s.userid AND i_a_s.agroupid='{$line['agroupid']}'";
        $result = mysql_query($q2) or die("Query failed : " . mysql_error());
        echo "Group members: <ul>";
        while ($row = mysql_fetch_row($result)) {
            echo "<li>{$row[0]}, {$row[1]}</li>";
        }
        echo "</ul>";
    }
    */

} else if (trim($line['feedback']) != '') {
    echo "<p>Instructor Feedback:<div class=\"intro\">{$line['feedback']}</div></p>";
    if (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype'] != 0) {
        ?>
        <p>
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $stu . '&cid=' . $course->id); ?> ">Return
                to GradeBook</a></p>
    <?php
    }
}
echo "</form>";
echo '</div>';

if (count($countOfQuestion) > 0) {
    CategoryScoresUtility::catscores($questions, $scores, $line['defpoints'], $line['defoutcome'], $cid);
}
} else if ($links == 1) { //show grade detail question/category breakdown
    echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
    echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
    if ($stu > 0) {
        echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Student Detail</a> ";
    }

    echo "&gt; Detail</div>";
    echo "<h2>Grade Book Detail</h2>\n";

    echo "<h3>{$currentData['FirstName']}, {$currentData['LastName']}</h3>\n";
    $line = $assessmentAndAssessmentSessionData;
    echo "<h4>{$line['name']}</h4>\n";
    echo "<p>Started: " . AppUtility::tzdate("F j, Y, g:i a", $line['starttime']) . "<BR>\n";
    if ($line['endtime'] == 0) {
        echo "Not Submitted</p>\n";
    } else {
        echo "Last change: " . AppUtility::tzdate("F j, Y, g:i a", $line['endtime']) . "</p>\n";
    }

    if (count($questionIds) > 0) {
        $sp = explode(';', $line['bestscores']);

        CategoryScoresUtility::catscores(explode(',', $line['questions']), explode(',', $sp[0]), $line['defpoints'], $line['defoutcome'], $cid);
    }
    $scores = array();
    $qs = explode(',', $line['questions']);
    $sp = explode(';', $line['bestscores']);
    foreach (explode(',', $sp[0]) as $k => $score) {
        $scores[$qs[$k]] = getpts($score);
    }
    echo "<h4>Question Breakdown</h4>\n";
    echo "<table cellpadding=5 class=gb><thead><tr><th>Question</th><th>Points / Possible</th></tr></thead><tbody>\n";
    $i = 1;
    $totpt = 0;
    $totposs = 0;
    foreach ($questionsInformation as $row) {
        if ($i % 2 != 0) {
            echo "<tr class=even>";
        } else {
            echo "<tr class=odd>";
        }
        echo '<td>';
        if ($row['withdrawn'] == 1) {
            echo '<span class="red">Withdrawn</span> ';
        }
        echo $row['description'];
        echo "</td><td>{$scores[$row['id']]} / ";
        if ($row['points'] == 9999) {
            $poss = $line['defpoints'];
        } else {
            $poss = $row['points'];
        }
        echo $poss;

        echo "</td></tr>\n";
        $i++;
        $totpt += $scores[$row['id']];
        $totposs += $poss;
    }
    echo "</table>\n";

    $pc = round(100 * $totpt / $totposs, 1);
    echo "<p>Total:  $totpt / $totposs  ($pc %)</p>\n";
    ?>
    <p>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $stu . '&cid=' . $course->id) ?>">Return
            to GradeBook</a></p>
<?php
}
echo '</div>';
/*
 * end shadow-box
 */

function getpts($sc)
{
    if (strpos($sc, '~') === false) {
        if ($sc > 0) {
            return $sc;
        } else {
            return 0;
        }
    } else {
        $sc = explode('~', $sc);
        $tot = 0;
        foreach ($sc as $s) {
            if ($s > 0) {
                $tot += $s;
            }
        }
        return round($tot, 1);
    }
}

function isperfect($sc)
{
    if (strpos($sc, '~') === false) {
        if ($sc == 1) {
            return true;
        }
    } else if (strpos($sc, '.') === false && strpos($sc, '0') === false) {
        return true;
    }
    return false;
}

function printscore($sc)
{
    if (strpos($sc, '~') === false) {

        return array($sc, '');
    } else {
        $pts = getpts($sc);
        $sc = str_replace('-1', 'N/A', $sc);
        $sc = str_replace('~', ', ', $sc);
        return array($pts, $sc);
    }
}

//evals a portion of the control section to extract the $answeights
//which might be randomizer determined, hence the seed
function getansweights($qi, $code, $questions, $seeds)
{

    if (preg_match('/scoremethod\s*=\s*"(singlescore|acct|allornothing)"/', $code)) {
        return array(1);
    }
    $i = array_search($qi, $questions);

    return sandboxgetweights($code, $seeds[$i]);
}

function sandboxgetweights($code, $seed)
{
    srand($seed);
    $code = interpretUtility::interpret('control', 'multipart', $code);
    if (($p = strrpos($code, 'answeights')) !== false) {
        $np = strpos($code, "\n", $p);
        if ($np !== false) {
            $code = substr($code, 0, $np) . ';if(isset($answeights)){return;};' . substr($code, $np);
        } else {
            $code .= ';if(isset($answeights)){return;};';
        }
        //$code = str_replace("\n",';if(isset($answeights)){return;};'."\n",$code);
    } else {
        $p = strrpos($code, 'answeights');
        $np = strpos($code, "\n", $p);
        if ($np !== false) {
            $code = substr($code, 0, $np) . ';if(isset($anstypes)){return;};' . substr($code, $np);
        } else {
            $code .= ';if(isset($answeights)){return;};';
        }
        //$code = str_replace("\n",';if(isset($anstypes)){return;};'."\n",$code);
    }

    eval($code);
    if (!isset($answeights)) {
        if (!is_array($anstypes)) {
            $anstypes = explode(",", $anstypes);
        }
        $n = count($anstypes);
        if ($n > 1) {
            $answeights = array_fill(0, $n - 1, round(1 / $n, 3));
            $answeights[] = 1 - array_sum($answeights);
        } else {
            $answeights = array(1);
        }
    } else if (!is_array($answeights)) {
        $answeights = explode(',', $answeights);
    }
    $sum = array_sum($answeights);
    if ($sum == 0) {
        $sum = 1;
    }
    foreach ($answeights as $k => $v) {
        $answeights[$k] = $v / $sum;
    }
    return $answeights;
}

function scorestocolors($sc, $pts, $answ, $noraw)
{
    if (!$noraw) {
        $pts = 1;
    }
    if (trim($sc) == '') {
        return '';
    }
    if (strpos($sc, '~') === false) {
        if ($pts == 0) {
            $color = 'ansgrn';
        } else if ($sc < 0) {
            $color = '';
        } else if ($sc == 0) {
            $color = 'ansred';
        } else if ($pts - $sc < .011) {
            $color = 'ansgrn';
        } else {
            $color = 'ansyel';
        }
        return array($color);
    } else {
        $scarr = explode('~', $sc);
        if ($noraw) {
            for ($i = 0; $i < count($answ) - 1; $i++) {
                $answ[$i] = round($answ[$i] * $pts, 2);
            }
            //adjust for rounding
            $diff = $pts - array_sum($answ);
            $answ[count($answ) - 1] += $diff;
        } else {
            $answ = array_fill(0, count($scarr), 1);
        }

        $out = array();
        foreach ($scarr as $k => $v) {
            if ($answ[$k] == 0) {
                $color = 'ansgrn';
            } else if ($v < 0) {
                $color = '';
            } else if ($v == 0) {
                $color = 'ansred';
            } else if ($answ[$k] - $v < .011) {
                $color = 'ansgrn';
            } else {
                $color = 'ansyel';
            }
            $out[$k] = $color;
        }
        return $out;
    }
}

function prepchoicedisp($v, $choicesdata)
{
    if ($v == '') {
        return '';
    }
    foreach ($choicesdata[1] as $k => $c) {
        $c = str_replace('&', '%%', $c);
        $sh = strip_tags($c);
        if (trim($sh) == '' || strpos($c, '<table') !== false) {
            $sh = "[view]";
        } else if (strlen($sh) > 15) {
            $sh = substr($sh, 0, 15) . '...';
        }
        if ($sh != $c) {
            $choicesdata[1][$k] = '<span onmouseover="tipshow(this,\'' . trim(str_replace('&', '%%', htmlentities($c, ENT_QUOTES | ENT_HTML401))) . '\')" onmouseout="tipout()">' . $sh . '</span>';
        }
    }
    if ($choicesdata[0] == 'choices') {
        return ($choicesdata[1][$v]);
    } else if ($choicesdata[0] == 'multans') {
        $p = explode('|', $v);
        $out = array();
        foreach ($p as $pv) {
            $out[] = $choicesdata[1][$pv];
        }
        return 'Selected: ' . implode(', ', $out);
    } else if ($choicesdata[0] == 'matching') {
        return $v;
    }

}

function printrubrics($rubricarray)
{
    $out = '<script type="text/javascript">';
    $out .= 'var imasrubrics = new Array();';
    foreach ($rubricarray as $info) {
        $out .= "imasrubrics[{$info[0]}] = {'type':{$info[1]},'data':[";
        $data = unserialize($info[2]);
        foreach ($data as $i => $rubline) {
            if ($i != 0) {
                $out .= ',';
            }
            $out .= '["' . str_replace('"', '\\"', $rubline[0]) . '",';
            $out .= '"' . str_replace('"', '\\"', $rubline[1]) . '"';
            $out .= ',' . $rubline[2];
            $out .= ']';
        }
        $out .= ']};';
    }
    $out .= '</script>';
    return $out;
}

function printrubriclink($rubricid, $points, $scorebox, $feedbackbox, $qn = 'null', $width = 600)
{
    $out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
    $out .= "<img border=0 src='../../img/assess.png' alt=\"rubric\"></a>";
    return $out;
}

?>
<script>
    function quickgrade(qn,type,prefix,todo,vals) {
        if (type==0) { //all
            for (var i=0;i<todo;i++) {
                document.getElementById(prefix+qn+"-"+i).value = vals[i];
            }
        } else {  //select
            for (var i=0;i<todo.length;i++) {
                document.getElementById(prefix+qn+"-"+todo[i]).value = vals[todo[i]];
            }
        }
    }
    </script>