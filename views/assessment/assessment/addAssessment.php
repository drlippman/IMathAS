<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>

<h2><?php echo $pageTitle ?></h2>
<?php echo $page_isTakenMsg ?>
<form method=post action="add-assessment?cid=<?php echo $course->id ?>&id=<?php echo $assessmentData['id'];?>">
    <p></p>
<span class=form>Assessment Name:</span>
<span class=formright><input type=text size=30 name=name value="<?php echo str_replace('"','&quot;',$assessmentData['name']);?>"></span><BR class=form>
Summary:<BR>
<div >
    <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea cols=50 rows=15 id=summary name=summary style='width: 100%'>$assessmentData->summary</textarea></div></div><br>"; ?>
</div><BR>
Intro/Instructions:<BR>
<div>
    <?php echo "<div class='left col-md-11'><div>
    <textarea cols=50 rows=20 id='intro' name='intro' style='width: 100%'>$assessmentData->intro</textarea></div></div><br>"; ?>
</div><BR>

<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ZERO);?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ONE);?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
		</span><br class="form"/>

<div id="datediv" style="display:<?php echo ($assessmentData['avail']==AppConstant::NUMERIC_ONE)?"block":"none"; ?>">

    <span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ZERO); ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ONE); ?>/>

            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'sdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $sDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
            <?php
            echo '<label class="end pull-left col-lg-1"> at </label>';
		     echo '<div class="pull-left col-lg-6">';

                echo TimePicker::widget([
                'name' => 'stime',
                'value' => $sTime,
                'pluginOptions' => [
                'showSeconds' => false,
                'class' => 'time'
                ]
                ]);
            echo '</div>';?>

		</span><BR class=form>

    <span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",0); ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" class="pull-left" value="edate"  <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",1); ?>/>
            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'edate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $eDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
            <?php
            echo '<label class="end pull-left col-lg-1"> at </label>';
            echo '<div class="pull-left col-lg-6">';

            echo TimePicker::widget([
                'name' => 'etime',
                'value' => $eTime,
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>

		</span><BR class=form>

    <span class="form">Keep open as review:</span>
		<span class="formright">
			<input type=radio name="doreview" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?>> Never<br/>
			<input type=radio name="doreview" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],2000000000,AppConstant::NUMERIC_ZERO); ?>> Always after due date<br/>
			<input type=radio name="doreview" class="pull-left " value="rdate" <?php if ($assessmentData['reviewdate']>AppConstant::NUMERIC_ZERO && $assessmentData['reviewdate']<2000000000) { echo "checked=1";} ?>>
            <?php
            echo '<label class="end pull-left"> Until</label>';
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'rdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $reviewDate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
            <?php
            echo '<label class="end pull-left"> at </label>';
            echo '<div class=" col-lg-6">';
            echo TimePicker::widget([
                'name' => 'rtime',
                'value' => $reviewTime,
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>

		</span><BR class=form>
</div>

<span class=form></span>
		<span class=formright>
<!--            button name should be dynamic-->
			<input type=submit value="<?php echo $saveTitle;?>"> now or continue below for Assessment Options
		</span><br class=form>

<fieldset><legend>Assessment Options</legend>
<?php
if (count($pageCopyFromSelect['val'])>AppConstant::NUMERIC_ZERO) {
    ?>
    <span class=form>Copy Options from:</span>
    <span class=formright>

<?php
AssessmentUtility::writeHtmlSelect ("copyfrom",$pageCopyFromSelect['val'],$pageCopyFromSelect['label'],AppConstant::NUMERIC_ZERO,"None - use settings below",AppConstant::NUMERIC_ZERO," onChange=\"chgcopyfrom()\"");
?>
		</span><br class=form>
<?php
}
?>

<div id="copyfromoptions" class="hidden">
    <span class=form>Also copy:</span>
		<span class=formright>
			<input type=checkbox name="copysummary" /> Summary<br/>
			<input type=checkbox name="copyinstr" /> Instructions<br/>
			<input type=checkbox name="copydates" /> Dates <br/>
			<input type=checkbox name="copyendmsg" /> End of Assessment Messages
		</span><br class=form />
    <span class=form>Remove any existing per-question settings?</span>
		<span class=formright>
			<input type=checkbox name="removeperq" />
		</span><br class=form />

</div>
<div id="customoptions" class="show">
<fieldset><legend>Core Options</legend>
    <span class=form>Require Password (blank for none):</span>
    <span class=formright><input type="password" name="assmpassword" id="assmpassword" value="<?php echo $assessmentData['password'];?>" autocomplete="off"> <a href="#" onclick="apwshowhide(this);return false;">Show</a></span><br class=form />
    <span class=form>Time Limit (minutes, 0 for no time limit): </span>
			<span class=formright><input type=text size=4 name=timelimit value="<?php echo abs($timeLimit);?>">
				<input type="checkbox" name="timelimitkickout" <?php if ($timeLimit<0) echo 'checked="checked"';?> /> Kick student out at timelimit</span><BR class=form>
    <span class=form>Display method: </span>
			<span class=formright>
				<select name="displaymethod">
                    <option value="AllAtOnce" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"AllAtOnce",AppConstant::NUMERIC_ZERO) ?>>Full test at once</option>
                    <option value="OneByOne" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"OneByOne",AppConstant::NUMERIC_ZERO) ?>>One question at a time</option>
                    <option value="Seq" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"Seq",AppConstant::NUMERIC_ZERO) ?>>Full test, submit one at time</option>
                    <option value="SkipAround" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"SkipAround",AppConstant::NUMERIC_ZERO) ?>>Skip Around</option>
                    <option value="Embed" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"Embed",AppConstant::NUMERIC_ZERO) ?>>Embedded</option>
                    <option value="VideoCue" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"VideoCue",AppConstant::NUMERIC_ZERO) ?>>Video Cued</option>
                </select>
			</span><BR class=form>

    <span class=form>Default points per problem: </span>
    <span class=formright><input type=text size=4 name=defpoints value="<?php echo $assessmentData['defpoints'];?>" <?php if ($assessmentSessionData) {echo 'disabled=disabled';}?>></span><BR class=form>

    <span class=form>Default attempts per problem (0 for unlimited): </span>
			<span class=formright>
				<input type=text size=4 name=defattempts value="<?php echo $assessmentData['defattempts'];?>" >
				<span id="showreattdiffver" class="<?php if ($testType!="Practice" && $testType!="Homework") {echo "show";} else {echo "hidden";} ?>">
	 			<input type=checkbox name="reattemptsdiffver" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_EIGHT,AppConstant::NUMERIC_EIGHT); ?>/> Reattempts different versions</span>
	 		</span><BR class=form>

    <span class=form>Default penalty:</span>
			<span class=formright>
				<input type=text size=4 name=defpenalty value="<?php echo $assessmentData['defpenalty'];?>" <?php if ($assessmentSessionData) {echo 'disabled=disabled';}?>>%
			   	<select name="skippenalty" <?php if ($assessmentSessionData) {echo 'disabled=disabled';}?>>
                    <option value="0" <?php if ($skipPenalty==AppConstant::NUMERIC_ZERO) {echo "selected=1";} ?>>per missed attempt</option>
                    <option value="1" <?php if ($skipPenalty==AppConstant::NUMERIC_ONE) {echo "selected=1";} ?>>per missed attempt, after 1</option>
                    <option value="2" <?php if ($skipPenalty==AppConstant::NUMERIC_TWO) {echo "selected=1";} ?>>per missed attempt, after 2</option>
                    <option value="3" <?php if ($skipPenalty==AppConstant::NUMERIC_THREE) {echo "selected=1";} ?>>per missed attempt, after 3</option>
                    <option value="4" <?php if ($skipPenalty==AppConstant::NUMERIC_FOUR) {echo "selected=1";} ?>>per missed attempt, after 4</option>
                    <option value="5" <?php if ($skipPenalty==AppConstant::NUMERIC_FIVE) {echo "selected=1";} ?>>per missed attempt, after 5</option>
                    <option value="6" <?php if ($skipPenalty==AppConstant::NUMERIC_SIX) {echo "selected=1";} ?>>per missed attempt, after 6</option>
                    <option value="10" <?php if ($skipPenalty==AppConstant::NUMERIC_TEN) {echo "selected=1";} ?>>on last possible attempt only</option>
                </select>
			</span><BR class=form>

    <span class=form>Feedback method: </span>
			<span class=formright>
				<select id="deffeedback" name="deffeedback" onChange="chgfb()" >
                    <option value="NoScores" <?php if ($testType=="NoScores") {echo "SELECTED";} ?>>No scores shown (last attempt is scored)</option>
                    <option value="EndScore" <?php if ($testType=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
                    <option value="EachAtEnd" <?php if ($testType=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
                    <option value="EndReview" <?php if ($testType=="EndReview") {echo "SELECTED";} ?>>Reshow question with score at the end of the test </option>

                    <option value="AsGo" <?php if ($testType=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
                    <option value="Practice" <?php if ($testType=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
                    <option value="Homework" <?php if ($testType=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
                </select>
			</span><BR class=form>

    <span class=form>Show Answers: </span>
			<span class=formright>
				<span id="showanspracspan" class="<?php if ($testType=="Practice" || $testType=="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showansprac">
                    <option value="V" <?php if ($showAnswer=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
                    <option value="N" <?php if ($showAnswer=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
                    <option value="F" <?php if ($showAnswer=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
                    <option value="J" <?php if ($showAnswer=="J") {echo "SELECTED";} ?>>After last attempt or Jump to Ans button (Skip Around only)</option>
                    <option value="0" <?php if ($showAnswer=="0") {echo "SELECTED";} ?>>Always</option>
                    <option value="1" <?php if ($showAnswer=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
                    <option value="2" <?php if ($showAnswer=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
                    <option value="3" <?php if ($showAnswer=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
                    <option value="4" <?php if ($showAnswer=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
                    <option value="5" <?php if ($showAnswer=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
                </select>
				</span>
				<span id="showansspan" class="<?php if ($testType!="Practice" && $testType!="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showans">
                    <option value="V" <?php if ($showAnswer=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
                    <option value="N" <?php if ($showAnswer=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
                    <option value="I" <?php if ($showAnswer=="I") {echo "SELECTED";} ?>>Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
                    <option value="F" <?php if ($showAnswer=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
                    <option value="A" <?php if ($showAnswer=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
                </select>
				</span>
			</span><br class=form>
    <span class="form">Use equation helper?</span>
			<span class="formright">
				<select name="eqnhelper">
                    <option value="0" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_ZERO) ?>>No</option>
                    <?php
                    //start phasing these out; don't show as option if not used.
                    if ($assessmentData['eqnhelper']==AppConstant::NUMERIC_ONE || $assessmentData['eqnhelper']==AppConstant::NUMERIC_TWO) {
                        ?>
                        <option value="1" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_ONE) ?>>Yes, simple form (no logs or trig)</option>
                        <option value="2" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_TWO) ?>>Yes, advanced form</option>
                    <?php
                    }
                    ?>
                    <option value="3" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_THREE) ?>>MathQuill, simple form</option>
                    <option value="4" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_FOUR) ?>>MathQuill, advanced form</option>
                </select>
			</span><br class="form" />
    <span class=form>Show hints and video/text buttons when available?</span>
			<span class=formright>
				<input type="checkbox" name="showhints" <?php AssessmentUtility::writeHtmlChecked($assessmentData['showhints'],AppConstant::NUMERIC_ONE); ?>>
			</span><br class=form>

    <span class=form>Show "ask question" links?</span>
			<span class=formright>
				<input type="checkbox" name="msgtoinstr" <?php AssessmentUtility::writeHtmlChecked($assessmentData['msgtoinstr'],AppConstant::NUMERIC_ONE); ?>/> Show "Message instructor about this question" links<br/>
				<input type="checkbox" name="doposttoforum" <?php AssessmentUtility::writeHtmlChecked($assessmentData['posttoforum'],AppConstant::NUMERIC_ZERO,true); ?>/> Show "Post this question to forum" links, to forum <?php AssessmentUtility::writeHtmlSelect("posttoforum",$pageForumSelect['val'],$pageForumSelect['label'],$assessmentData['posttoforum']); ?>
			</span><br class=form>

    <span class=form>Show answer entry tips?</span>
			<span class=formright>
				<select name="showtips">
                    <option value="0" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_ZERO) ?>>No</option>
                    <option value="1" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_ONE) ?>>Yes, after question</option>
                    <option value="2" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_TWO) ?>>Yes, under answerbox</option>
                </select>
			</span><br class=form>

    <span class=form>Allow use of LatePasses?: </span>
			<span class=formright>
				<?php
                AssessmentUtility::writeHtmlSelect("allowlate",$pageAllowLateSelect['val'],$pageAllowLateSelect['label'],$assessmentData['allowlate']%AppConstant::NUMERIC_TEN);
                ?>
                <label><input type="checkbox" name="latepassafterdue" <?php AssessmentUtility::writeHtmlChecked($line['allowlate']>AppConstant::NUMERIC_TEN,true); ?>> Allow LatePasses after due date, within 1 LatePass period</label>
			</span><BR class=form>

    <span class=form>Make hard to print?</span>
			<span class=formright>
				<input type="radio" value="0" name="noprint" <?php AssessmentUtility::writeHtmlChecked($assessmentData['noprint'],AppConstant::NUMERIC_ZERO); ?>/> No <input type="radio" value="1" name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'],AppConstant::NUMERIC_ONE); ?>/> Yes
			</span><br class=form>


    <span class=form>Shuffle item order: </span>
			<span class=formright><input type="checkbox" name="shuffle" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ONE); ?>>
			</span><BR class=form>
    <span class=form>Gradebook Category:</span>
			<span class=formright>

<?php
AssessmentUtility::writeHtmlSelect("gbcat",$pageGradebookCategorySelect['val'],$pageGradebookCategorySelect['label'],$gradebookCategory,"Default",AppConstant::NUMERIC_ZERO);
?>
			</span><br class=form>
    <span class=form>Count: </span>
			<span <?php if ($testType=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
				<input type=radio name="cntingb" value="1" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO); ?> /> Count in Gradebook<br/>
				<input type=radio name="cntingb" value="0" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb" value="3" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_THREE,AppConstant::NUMERIC_ZERO); ?> /> Don't count in grade total<br/>
				<input type=radio name="cntingb" value="2" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_TWO,AppConstant::NUMERIC_ZERO); ?> /> Count as Extra Credit
			</span>
			<span <?php if ($testType!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
				<input type=radio name="pcntingb" value="0" <?php AssessmentUtility::writeHtmlChecked($pointCountInGradebook,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="pcntingb" value="3" <?php AssessmentUtility::writeHtmlChecked($pointCountInGradebook,AppConstant::NUMERIC_THREE,AppConstant::NUMERIC_ZERO); ?> /> Don't count in grade total<br/>
			</span><br class=form />
    <?php
    if (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) {
//        ?>
        <span class="form">Tutor Access:</span>
        <span class="formright">
<?php
AssessmentUtility::writeHtmlSelect("tutoredit",$pageTutorSelect['val'],$pageTutorSelect['label'],$assessmentData['tutoredit']);
?>
			</span><br class="form" />
    <?php
    }
    ?>
    <span class="form">Calendar icon:</span>
			<span class="formright">
				Active: <input name="caltagact" type=text size=4 value="<?php echo $assessmentData['caltag'];?>"/>,
				Review: <input name="caltagrev" type=text size=4 value="<?php echo $assessmentData['calrtag'];?>"/>
			</span><br class="form" />

</fieldset>

<fieldset><legend>Advanced Options</legend>
    <span class=form>Minimum score to receive credit: </span>
			<span class=formright>
				<input type=text size=4 name=minscore value="<?php echo $assessmentData['minscore'];?>">
				<input type="radio" name="minscoretype" value="0" <?php AssessmentUtility::writeHtmlChecked($minScoreType,AppConstant::NUMERIC_ZERO);?>> Points
				<input type="radio" name="minscoretype" value="1" <?php AssessmentUtility::writeHtmlChecked($minScoreType,AppConstant::NUMERIC_ONE);?>> Percent
			</span><BR class=form>

    <span class=form>Show based on another assessment: </span>
			<span class=formright>Show only after a score of
				<input type=text size=4 name=reqscore value="<?php echo $assessmentData['reqscore'];?>">
		   		points is obtained on
                <?php
                AssessmentUtility::writeHtmlSelect ("reqscoreaid",$pageCopyFromSelect['val'],$pageCopyFromSelect['label'],$assessmentData['reqscoreaid'],"Dont Use",AppConstant::NUMERIC_ZERO,null);
                ?>
			</span><br class=form>
    <span class="form">Default Feedback Text:</span>
			<span class="formright">
				Use? <input type="checkbox" name="usedeffb" <?php AssessmentUtility::writeHtmlChecked($useDefFeedback,true); ?>><br/>
				Text: <input type="text" size="60" name="deffb" value="<?php echo str_replace('"','&quot;',$defFeedback);?>" />
			</span><br class="form" />
    <span class=form>All items same random seed: </span>
			<span class=formright>
				<input type="checkbox" name="sameseed" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_TWO,AppConstant::NUMERIC_TWO); ?>>
			</span><BR class=form>
    <span class=form>All students same version of questions: </span>
			<span class=formright>
				<input type="checkbox" name="samever" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_FOUR,AppConstant::NUMERIC_FOUR); ?>>
			</span><BR class=form>

    <span class=form>Penalty for questions done while in exception/LatePass: </span>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" value="<?php echo $assessmentData['exceptionpenalty'];?>">%
			</span><BR class=form>

    <span class=form>Group assessment: </span>
			<span class=formright>
				<input type="radio" name="isgroup" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_ZERO); ?> />Not a group assessment<br/>
				<input type="radio" name="isgroup" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_ONE); ?> />Students can add members with login passwords<br/>
				<input type="radio" name="isgroup" value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_TWO); ?> />Students can add members without passwords<br/>
				<input type="radio" name="isgroup" value="3" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_THREE); ?> />Students cannot add members, and can't start the assessment until you add them to a group
			</span><br class="form" />
    <span class=form>Max group members (if group assessment): </span>
			<span class=formright>
				<input type="text" name="groupmax" value="<?php echo $assessmentData['groupmax'];?>" />
			</span><br class="form" />
			<span class="form">Use group set:<?php
                if ($assessmentSessionData) {
                    if ($assessmentData['isgroup']== AppConstant::NUMERIC_ZERO) {
                        echo '<br/>Only empty group sets can be used after the assessment has started';
                    } else {
                        echo '<br/>Cannot change group set after the assessment has started';
                    }
                }?></span>
			<span class="formright">
                <?php AssessmentUtility::writeHtmlSelect("groupsetid",$pageGroupSets['val'],$pageGroupSets['label'],$assessmentData['groupsetid'],"Not group forum",0); ?>
<!--				--><?php //AssessmentUtility::writeHtmlSelect('groupsetid',$pageGroupSets['val'],$pageGroupSets['label'],$assessmentData['groupsetid'],null,null,($assessmentSessionData && $assessmentData['isgroup']>AppConstant::NUMERIC_ZERO)?'disabled="disabled"':''); ?>
			</span><br class="form" />
    <span class="form">Default Outcome:</span>
			<span class="formright"><select name="defoutcome">
                    <?php
                    $inGroup = false;
                    $isSelected = false;
                    foreach ($pageOutcomesList as $outcome) {
                        if ($outcome[1]==AppConstant::NUMERIC_ONE) {//is group
                            if ($inGroup) { echo '</optgroup>';}
                            echo '<optgroup label="'.htmlentities($outcome[0]).'">';
                            $inGroup = true;
                        } else {
                            echo '<option value="'.$outcome[0].'" ';
                            if ($assessmentData['defoutcome'] == $outcome[0]) { echo 'selected="selected"'; $isSelected = true;}
                            echo '>'.$pageOutcomes[$outcome[0]].'</option>';
                        }
                    }
                    if ($inGroup) { echo '</optgroup>';}
                    ?>
                </select>
			</span><br class="form" />
    <span class=form>Show question categories:</span>
			<span class=formright>
				<input name="showqcat" type="radio" value="0" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"0"); ?>>No <br />
				<input name="showqcat" type="radio" value="1" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"1"); ?>>In Points Possible bar <br />
				<input name="showqcat" type="radio" value="2" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"2"); ?>>In navigation bar (Skip-Around only)
			</span><br class="form" />

    <span class=form>Display for tutorial-style questions: </span>
			<span class=formright>
				<input type="checkbox" name="istutorial" <?php AssessmentUtility::writeHtmlChecked($assessmentData['istutorial'],AppConstant::NUMERIC_ONE); ?>>
			</span><BR class=form>

</fieldset>
</div>
</fieldset>
<div class=submit><input class=""  type=submit name="" value="<?php echo $saveTitle;?>"></div>