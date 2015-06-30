<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Add Assessment';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
?>

<?php
//if (isset($_GET['id'])) {
//    echo '<div class="cp"><a href="addquestions.php?aid='.$_GET['id'].'&amp;cid='.$cid.'" onclick="return confirm(\''._('This will discard any changes you have made on this page').'\');">'._('Add/Remove Questions').'</a></div>';
//}
?>
<?php echo $page_isTakenMsg ?>

<form method=post action="<?php echo $page_formActionTag ?>">
    <p></p>
<span class=form>Assessment Name:</span>
<span class=formright><input type=text size=30 name=name value="<?php echo str_replace('"','&quot;',$line['name']);?>"></span><BR class=form>

Summary:<BR>
<div >
    <?php echo "<div class='left col-md-11'><div class= 'editor'>
        <textarea cols=50 rows=15 id=summary name=summary style=width: 100%></textarea></div></div><br>"; ?>
</div><BR>
Intro/Instructions:<BR>
<div>
    <?php echo "<div class='left col-md-11'><div class= 'editor'>
    <textarea cols=50 rows=20 id=intro name=intro style=width: 100%></textarea></div></div><br>"; ?>
</div><BR>

<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php /*writeHtmlChecked($line['avail'],0)*/;?> onclick="document.getElementById('datediv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php /*writeHtmlChecked($line['avail'],1)*/;?> onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
		</span><br class="form"/>

<!--<div id="datediv" style="display:--><?php //echo ($line['avail']==1)?"block":"none"; ?><!--">-->

    <span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,"0",0); ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" class="pull-left" value="sdate" <?php writeHtmlChecked($startdate,"0",1); ?>/>

            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'EventDate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m/d/Y"),
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
                'name' => 'end_time',
                'value' => time(),
                'pluginOptions' => [
                'showSeconds' => false,
                'class' => 'time'
                ]
                ]);
            echo '</div>';?>

		</span><BR class=form>

    <span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,"2000000000",0); ?>/>
			 Always after start date<br/>
			<input type=radio name="edatetype" class="pull-left" value="edate"  <?php writeHtmlChecked($enddate,"2000000000",1); ?>/>
            <?php
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'EventDate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m/d/Y"),
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
                'name' => 'end_time',
                'value' => time(),
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>

		</span><BR class=form>

    <span class="form">Keep open as review:</span>
		<span class="formright">
			<input type=radio name="doreview" value="0" <?php writeHtmlChecked($line['reviewdate'],0,0); ?>> Never<br/>
			<input type=radio name="doreview" value="2000000000" <?php writeHtmlChecked($line['reviewdate'],2000000000,0); ?>> Always after due date<br/>
			<input type=radio name="doreview" class="pull-left " value="rdate" <?php if ($line['reviewdate']>0 && $line['reviewdate']<2000000000) { echo "checked=1";} ?>>
            <?php
            echo '<label class="end pull-left"> Until</label>';
            echo '<div class = "pull-left col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'EventDate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m/d/Y"),
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
                'name' => 'end_time',
                'value' => time(),
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>

		</span><BR class=form>
<!--</div>-->

<span class=form></span>
		<span class=formright>
<!--            button name should be dynamic-->
			<input type=submit value="<?php echo 'Create Assessment';?>"> now or continue below for Assessment Options
		</span><br class=form>

<fieldset><legend>Assessment Options</legend>
<?php
if (count($page_copyFromSelect['val'])>0) {
    ?>
    <span class=form>Copy Options from:</span>
    <span class=formright>

<?php
writeHtmlSelect ("copyfrom",$page_copyFromSelect['val'],$page_copyFromSelect['label'],0,"None - use settings below",0," onChange=\"chgcopyfrom()\"");
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
    <span class=formright><input type="password" name="assmpassword" id="assmpassword" value="<?php echo $line['password'];?>" autocomplete="off"> <a href="#" onclick="apwshowhide(this);return false;">Show</a></span><br class=form />
    <span class=form>Time Limit (minutes, 0 for no time limit): </span>
			<span class=formright><input type=text size=4 name=timelimit value="<?php echo abs($timelimit);?>">
				<input type="checkbox" name="timelimitkickout" <?php if ($timelimit<0) echo 'checked="checked"';?> /> Kick student out at timelimit</span><BR class=form>
    <span class=form>Display method: </span>
			<span class=formright>
				<select name="displaymethod">
                    <option value="AllAtOnce" <?php writeHtmlSelected($line['displaymethod'],"AllAtOnce",0) ?>>Full test at once</option>
                    <option value="OneByOne" <?php writeHtmlSelected($line['displaymethod'],"OneByOne",0) ?>>One question at a time</option>
                    <option value="Seq" <?php writeHtmlSelected($line['displaymethod'],"Seq",0) ?>>Full test, submit one at time</option>
                    <option value="SkipAround" <?php writeHtmlSelected($line['displaymethod'],"SkipAround",0) ?>>Skip Around</option>
                    <option value="Embed" <?php writeHtmlSelected($line['displaymethod'],"Embed",0) ?>>Embedded</option>
                    <option value="VideoCue" <?php writeHtmlSelected($line['displaymethod'],"VideoCue",0) ?>>Video Cued</option>
                </select>
			</span><BR class=form>

    <span class=form>Default points per problem: </span>
    <span class=formright><input type=text size=4 name=defpoints value="<?php echo $line['defpoints'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>></span><BR class=form>

    <span class=form>Default attempts per problem (0 for unlimited): </span>
			<span class=formright>
				<input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>" >
				<span id="showreattdiffver" class="<?php if ($testtype!="Practice" && $testtype!="Homework") {echo "show";} else {echo "hidden";} ?>">
	 			<input type=checkbox name="reattemptsdiffver" <?php writeHtmlChecked($line['shuffle']&8,8); ?>/> Reattempts different versions</span>
	 		</span><BR class=form>

    <span class=form>Default penalty:</span>
			<span class=formright>
				<input type=text size=4 name=defpenalty value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>%
			   	<select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';}?>>
                    <option value="0" <?php if ($skippenalty==0) {echo "selected=1";} ?>>per missed attempt</option>
                    <option value="1" <?php if ($skippenalty==1) {echo "selected=1";} ?>>per missed attempt, after 1</option>
                    <option value="2" <?php if ($skippenalty==2) {echo "selected=1";} ?>>per missed attempt, after 2</option>
                    <option value="3" <?php if ($skippenalty==3) {echo "selected=1";} ?>>per missed attempt, after 3</option>
                    <option value="4" <?php if ($skippenalty==4) {echo "selected=1";} ?>>per missed attempt, after 4</option>
                    <option value="5" <?php if ($skippenalty==5) {echo "selected=1";} ?>>per missed attempt, after 5</option>
                    <option value="6" <?php if ($skippenalty==6) {echo "selected=1";} ?>>per missed attempt, after 6</option>
                    <option value="10" <?php if ($skippenalty==10) {echo "selected=1";} ?>>on last possible attempt only</option>
                </select>
			</span><BR class=form>

    <span class=form>Feedback method: </span>
			<span class=formright>
				<select id="deffeedback" name="deffeedback" onChange="chgfb()" >
                    <option value="NoScores" <?php if ($testtype=="NoScores") {echo "SELECTED";} ?>>No scores shown (last attempt is scored)</option>
                    <option value="EndScore" <?php if ($testtype=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
                    <option value="EachAtEnd" <?php if ($testtype=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
                    <option value="EndReview" <?php if ($testtype=="EndReview") {echo "SELECTED";} ?>>Reshow question with score at the end of the test </option>

                    <option value="AsGo" <?php if ($testtype=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
                    <option value="Practice" <?php if ($testtype=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
                    <option value="Homework" <?php if ($testtype=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
                </select>
			</span><BR class=form>

    <span class=form>Show Answers: </span>
			<span class=formright>
				<span id="showanspracspan" class="<?php if ($testtype=="Practice" || $testtype=="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showansprac">
                    <option value="V" <?php if ($showans=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
                    <option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
                    <option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
                    <option value="J" <?php if ($showans=="J") {echo "SELECTED";} ?>>After last attempt or Jump to Ans button (Skip Around only)</option>
                    <option value="0" <?php if ($showans=="0") {echo "SELECTED";} ?>>Always</option>
                    <option value="1" <?php if ($showans=="1") {echo "SELECTED";} ?>>After 1 attempt</option>
                    <option value="2" <?php if ($showans=="2") {echo "SELECTED";} ?>>After 2 attempts</option>
                    <option value="3" <?php if ($showans=="3") {echo "SELECTED";} ?>>After 3 attempts</option>
                    <option value="4" <?php if ($showans=="4") {echo "SELECTED";} ?>>After 4 attempts</option>
                    <option value="5" <?php if ($showans=="5") {echo "SELECTED";} ?>>After 5 attempts</option>
                </select>
				</span>
				<span id="showansspan" class="<?php if ($testtype!="Practice" && $testtype!="Homework") {echo "show";} else {echo "hidden";} ?>">
				<select name="showans">
                    <option value="V" <?php if ($showans=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
                    <option value="N" <?php if ($showans=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
                    <option value="I" <?php if ($showans=="I") {echo "SELECTED";} ?>>Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
                    <option value="F" <?php if ($showans=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
                    <option value="A" <?php if ($showans=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
                </select>
				</span>
			</span><br class=form>
    <span class="form">Use equation helper?</span>
			<span class="formright">
				<select name="eqnhelper">
                    <option value="0" <?php writeHtmlSelected($line['eqnhelper'],0) ?>>No</option>
                    <?php
                    //start phasing these out; don't show as option if not used.
                    if ($line['eqnhelper']==1 || $line['eqnhelper']==2) {
                        ?>
                        <option value="1" <?php writeHtmlSelected($line['eqnhelper'],1) ?>>Yes, simple form (no logs or trig)</option>
                        <option value="2" <?php writeHtmlSelected($line['eqnhelper'],2) ?>>Yes, advanced form</option>
                    <?php
                    }
                    ?>
                    <option value="3" <?php writeHtmlSelected($line['eqnhelper'],3) ?>>MathQuill, simple form</option>
                    <option value="4" <?php writeHtmlSelected($line['eqnhelper'],4) ?>>MathQuill, advanced form</option>
                </select>
			</span><br class="form" />
    <span class=form>Show hints and video/text buttons when available?</span>
			<span class=formright>
				<input type="checkbox" name="showhints" <?php writeHtmlChecked($line['showhints'],1); ?>>
			</span><br class=form>

    <span class=form>Show "ask question" links?</span>
			<span class=formright>
				<input type="checkbox" name="msgtoinstr" <?php writeHtmlChecked($line['msgtoinstr'],1); ?>/> Show "Message instructor about this question" links<br/>
				<input type="checkbox" name="doposttoforum" <?php writeHtmlChecked($line['posttoforum'],0,true); ?>/> Show "Post this question to forum" links, to forum <?php writeHtmlSelect("posttoforum",$page_forumSelect['val'],$page_forumSelect['label'],$line['posttoforum']); ?>
			</span><br class=form>

    <span class=form>Show answer entry tips?</span>
			<span class=formright>
				<select name="showtips">
                    <option value="0" <?php writeHtmlSelected($line['showtips'],0) ?>>No</option>
                    <option value="1" <?php writeHtmlSelected($line['showtips'],1) ?>>Yes, after question</option>
                    <option value="2" <?php writeHtmlSelected($line['showtips'],2) ?>>Yes, under answerbox</option>
                </select>
			</span><br class=form>

    <span class=form>Allow use of LatePasses?: </span>
			<span class=formright>
				<?php
                writeHtmlSelect("allowlate",$page_allowlateSelect['val'],$page_allowlateSelect['label'],$line['allowlate']%10);
                ?>
                <label><input type="checkbox" name="latepassafterdue" <?php writeHtmlChecked($line['allowlate']>10,true); ?>> Allow LatePasses after due date, within 1 LatePass period</label>
			</span><BR class=form>

    <span class=form>Make hard to print?</span>
			<span class=formright>
				<input type="radio" value="0" name="noprint" <?php writeHtmlChecked($line['noprint'],0); ?>/> No <input type="radio" value="1" name="noprint" <?php writeHtmlChecked($line['noprint'],1); ?>/> Yes
			</span><br class=form>


    <span class=form>Shuffle item order: </span>
			<span class=formright><input type="checkbox" name="shuffle" <?php writeHtmlChecked($line['shuffle']&1,1); ?>>
			</span><BR class=form>
    <span class=form>Gradebook Category:</span>
			<span class=formright>

<?php
writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
?>
			</span><br class=form>
    <span class=form>Count: </span>
			<span <?php if ($testtype=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
				<input type=radio name="cntingb" value="1" <?php writeHtmlChecked($cntingb,1,0); ?> /> Count in Gradebook<br/>
				<input type=radio name="cntingb" value="0" <?php writeHtmlChecked($cntingb,0,0); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="cntingb" value="3" <?php writeHtmlChecked($cntingb,3,0); ?> /> Don't count in grade total<br/>
				<input type=radio name="cntingb" value="2" <?php writeHtmlChecked($cntingb,2,0); ?> /> Count as Extra Credit
			</span>
			<span <?php if ($testtype!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
				<input type=radio name="pcntingb" value="0" <?php writeHtmlChecked($pcntingb,0,0); ?> /> Don't count in grade total and hide from students<br/>
				<input type=radio name="pcntingb" value="3" <?php writeHtmlChecked($pcntingb,3,0); ?> /> Don't count in grade total<br/>
			</span><br class=form />
    <?php
    if (!isset($CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) {
//        ?>
        <span class="form">Tutor Access:</span>
        <span class="formright">
<?php
writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$line['tutoredit']);
?>
			</span><br class="form" />
    <?php
    }
    ?>
    <span class="form">Calendar icon:</span>
			<span class="formright">
				Active: <input name="caltagact" type=text size=4 value="<?php echo $line['caltag'];?>"/>,
				Review: <input name="caltagrev" type=text size=4 value="<?php echo $line['calrtag'];?>"/>
			</span><br class="form" />

</fieldset>

<fieldset><legend>Advanced Options</legend>
    <span class=form>Minimum score to receive credit: </span>
			<span class=formright>
				<input type=text size=4 name=minscore value="<?php echo $line['minscore'];?>">
				<input type="radio" name="minscoretype" value="0" <?php writeHtmlChecked($minscoretype,0);?>> Points
				<input type="radio" name="minscoretype" value="1" <?php writeHtmlChecked($minscoretype,1);?>> Percent
			</span><BR class=form>

    <span class=form>Show based on another assessment: </span>
			<span class=formright>Show only after a score of
				<input type=text size=4 name=reqscore value="<?php echo $line['reqscore'];?>">
		   		points is obtained on
                <?php
                writeHtmlSelect ("reqscoreaid",$page_copyFromSelect['val'],$page_copyFromSelect['label'],$line['reqscoreaid'],"Dont Use",0,null);
                ?>
			</span><br class=form>
    <span class="form">Default Feedback Text:</span>
			<span class="formright">
				Use? <input type="checkbox" name="usedeffb" <?php writeHtmlChecked($usedeffb,true); ?>><br/>
				Text: <input type="text" size="60" name="deffb" value="<?php echo str_replace('"','&quot;',$deffb);?>" />
			</span><br class="form" />
    <span class=form>All items same random seed: </span>
			<span class=formright>
				<input type="checkbox" name="sameseed" <?php writeHtmlChecked($line['shuffle']&2,2); ?>>
			</span><BR class=form>
    <span class=form>All students same version of questions: </span>
			<span class=formright>
				<input type="checkbox" name="samever" <?php writeHtmlChecked($line['shuffle']&4,4); ?>>
			</span><BR class=form>

    <span class=form>Penalty for questions done while in exception/LatePass: </span>
			<span class=formright>
				<input type=text size=4 name="exceptionpenalty" value="<?php echo $line['exceptionpenalty'];?>">%
			</span><BR class=form>

    <span class=form>Group assessment: </span>
			<span class=formright>
				<input type="radio" name="isgroup" value="0" <?php writeHtmlChecked($line['isgroup'],0); ?> />Not a group assessment<br/>
				<input type="radio" name="isgroup" value="1" <?php writeHtmlChecked($line['isgroup'],1); ?> />Students can add members with login passwords<br/>
				<input type="radio" name="isgroup" value="2" <?php writeHtmlChecked($line['isgroup'],2); ?> />Students can add members without passwords<br/>
				<input type="radio" name="isgroup" value="3" <?php writeHtmlChecked($line['isgroup'],3); ?> />Students cannot add members, and can't start the assessment until you add them to a group
			</span><br class="form" />
    <span class=form>Max group members (if group assessment): </span>
			<span class=formright>
				<input type="text" name="groupmax" value="<?php echo $line['groupmax'];?>" />
			</span><br class="form" />
			<span class="form">Use group set:<?php
                if ($taken) {
                    if ($line['isgroup']==0) {
                        echo '<br/>Only empty group sets can be used after the assessment has started';
                    } else {
                        echo '<br/>Cannot change group set after the assessment has started';
                    }
                }?></span>
			<span class="formright">
				<?php writeHtmlSelect('groupsetid',$page_groupsets['val'],$page_groupsets['label'],$line['groupsetid'],null,null,($taken && $line['isgroup']>0)?'disabled="disabled"':''); ?>
			</span><br class="form" />
    <span class="form">Default Outcome:</span>
			<span class="formright"><select name="defoutcome">
                    <?php
                    $ingrp = false;
                    $issel = false;
//                    foreach ($page_outcomeslist as $oc) {
//                        if ($oc[1]==1) {//is group
//                            if ($ingrp) { echo '</optgroup>';}
//                            echo '<optgroup label="'.htmlentities($oc[0]).'">';
//                            $ingrp = true;
//                        } else {
//                            echo '<option value="'.$oc[0].'" ';
//                            if ($line['defoutcome'] == $oc[0]) { echo 'selected="selected"'; $issel = true;}
//                            echo '>'.$page_outcomes[$oc[0]].'</option>';
//                        }
//                    }
                    if ($ingrp) { echo '</optgroup>';}
                    ?>
                </select>
			</span><br class="form" />
    <span class=form>Show question categories:</span>
			<span class=formright>
				<input name="showqcat" type="radio" value="0" >No <br />
				<input name="showqcat" type="radio" value="1" >In Points Possible bar <br />
				<input name="showqcat" type="radio" value="2" >In navigation bar (Skip-Around only)
			</span><br class="form" />

    <span class=form>Display for tutorial-style questions: </span>
			<span class=formright>
				<input type="checkbox" name="istutorial" >
			</span><BR class=form>

</fieldset>
</div>
</fieldset>
<div class=submit><input class=""  type=submit name="" value="Create Assessment"></div>
<?php

function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
    echo "<select name=\"$name\" id=\"$name\" ";
    echo (isset($actions)) ? $actions : "" ;
    echo ">\n";
    if (isset($defaultLabel) && isset($defaultVal)) {
        echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
    }
    for ($i=0;$i<count($valList);$i++) {
        if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
            echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
        } else {
            echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
        }
    }
    echo "</select>\n";
}

function writeHtmlMultiSelect($name,$valList,$labelList,$selectedVals=array(),$defaultLabel=null) {
    echo "<div class=\"multisel\"><select name=\"{$name}[]\" id=\"$name\">";
    if (isset($defaultLabel)) {
        echo " <option value=\"null\" selected=\"selected\">$defaultLabel</option>\n";
    }
    if (is_array($valList[0])) {//has a group structure
        $ingrp = false;
        foreach ($valList as $oc) {
            if ($oc[1]==1) {//is group
                if ($ingrp) { echo '</optgroup>';}
                echo '<optgroup label="'.htmlentities($oc[0]).'">';
                $ingrp = true;
            } else {
                echo '<option value="'.$oc[0].'">'.$labelList[$oc[0]].'</option>';
            }
        }
        if ($ingrp) { echo '</optgroup>';}
    } else {
        $val = array();
        for ($i=0;$i<count($valList);$i++) {
            $val[$valList[$i]] = $labelList[$i];
            echo "	<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
        }
    }
    echo '</select><input type="button" value="Add Another" onclick="addmultiselect(this,\''.$name.'\')"/>';
    if (count($selectedVals)>0) {
        foreach ($selectedVals as $v) {
            echo '<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span>';
            echo '<input type="hidden" name="'.$name.'[]" value="'.$v.'"/>'.(is_array($valList[0])?$labelList[$v]:$val[$v]);
            echo '</div>';
        }
    }
    echo '</div>';
}

//writeHtmlChecked is used for checking the appropriate radio box on html forms
function writeHtmlChecked ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            echo "checked ";
        }
    } else {
        if ($var==$test) {
            echo "checked ";
        }
    }
}

//writeHtmlChecked is used for checking the appropriate radio box on html forms
function getHtmlChecked ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            return "checked ";
        }
    } else {
        if ($var==$test) {
            return "checked ";
        }
    }
}

//writeHtmlSelected is used for selecting the appropriate entry in a select item
function writeHtmlSelected ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            echo 'selected="selected"';
        }
    } else {
        if ($var==$test) {
            echo 'selected="selected"';
        }
    }
}

//writeHtmlSelected is used for selecting the appropriate entry in a select item
function getHtmlSelected ($var,$test,$notEqual=null) {
    if ((isset($notEqual)) && ($notEqual==1)) {
        if ($var!=$test) {
            return 'selected="selected"';
        }
    } else {
        if ($var==$test) {
            return 'selected="selected"';
        }
    }
}
?>