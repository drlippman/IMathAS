<?php
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/course?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<?php echo $page_isTakenMsg ?>
    <form method=post action="<?php echo $page_formActionTag ?>">
<p></p>
<div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading col-sm-6 padding-left-zero">
            <div class="vertical-align title-page"><?php echo $this->title ?><img class="help-img" src="<?php echo AppUtility::getAssetURL()?>img/helpIcon.png" alt="Help" onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <div style="background: #f8f8f8; height: 75px;">
        <?php
        if (count($pageCopyFromSelect['val'])>AppConstant::NUMERIC_ZERO) {
            ?>
            <div class="padding-top-twenty">
                <div class='col-sm-2 select-text-margin margin-left-fifteen'><?php AppUtility::t('Copy Options From')?></div>
                <div class='col-sm-5 padding-left-ten'>
                    <?php
                    AssessmentUtility::writeHtmlSelect ("copyfrom",$pageCopyFromSelect['val'],$pageCopyFromSelect['label'],AppConstant::NUMERIC_ZERO,"None - use settings below",AppConstant::NUMERIC_ZERO," onChange=\"chgcopyfrom()\"");
                    ?>
                            </div><br class=form>
            </div>
        <?php
        }
        ?>
        <div id="copyfromoptions" class="hidden" style="background-color:#f8f8f8;margin-top: 15px;">
            <div class='col-sm-2 padding-left-thirty'><?php AppUtility::t('Also copy')?></div>
            <div class='col-sm-10 padding-left-zero padding-left-ten'>
                <div class="col-sm-8">
                    <input type=checkbox name="copysummary"/>
                    <span class="padding-left"><?php AppUtility::t('Summary')?></span>
                </div>
                <div class="col-sm-8 margin-top-five">
                    <input type=checkbox name="copyinstr" />
                    <span class="padding-left"><?php AppUtility::t('Instructions')?></span>
                </div>
                <div class="col-sm-8 margin-top-five">
                    <input type=checkbox name="copydates" />
                    <span class="padding-left"><?php AppUtility::t('Dates')?></span>
                </div>
                <div class="col-sm-8 margin-top-five">
                    <input type=checkbox class="padding-left" name="copyendmsg" />
                    <span class="padding-left"><?php AppUtility::t('End of Assessment Messages')?></span>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-fifteen margin-bottom-twenty-five">
            <div class='col-sm-2 padding-left-thirty'>Remove any existing per-question settings?</div>
            <div class='col-sm-10 padding-left-twenty-five'>
                <div class="checkbox override-hidden">
                    <label class="margin-left-two margin-top-five inline-checkbox label-visible">
                        <input type=checkbox name="removeperq" />
                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                    </label>
                </div>
            </div>
            </div>
            <br class=form />
        </div>
    </div><div class="clear-both"></div>
    <div class="add-assessment-margin">

    <div class="name-of-item">
        <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Name of Assessment')?></div>
        <div class='col-sm-10'>
            <input class="form-control input-item-title" type=text size=0 name=name value="<?php echo str_replace('"','&quot;',$assessmentData['name']);?>">
        </div>
    </div> <BR class=form>

    <div class="editor-summary">
       <div class="col-sm-2"><?php AppUtility::t('Summary')?></div>
        <div class="col-sm-10">
            <div class='editor summary-html-lord add-assessment-summary-textarea'>
                <textarea cols=5 rows=12 id=summary name=summary style='width: 100%;'><?php echo $assessmentData["summary"];?></textarea>
            </div><br>
        </div>
    </div><BR class=form>

    <div>
        <div class="col-sm-2"><?php AppUtility::t('Intro/Instructions')?></div>
        <div class="col-sm-10">
            <div class='editor instructions-html-lord add-assessment-summary-textarea'>
            <textarea cols=5 rows=12 id='intro' name='intro' style='width: 100%'><?php echo $assessmentData["intro"];?></textarea></div><br>
        </div>
    </div>

    <div class="col-sm-12 padding-left-zero">
        <div class=col-sm-2><?php AppUtility::t('Visibility')?></div>
        <div class=col-sm-10>
            <input class="margin-left-two" type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ONE);?> onclick="document.getElementById('datediv').style.display='block';"/><span class="padding-left"><?php AppUtility::t('Show By Dates')?></span>
            <label class="non-bold" style="padding-left: 79px"><input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['avail'],AppConstant::NUMERIC_ZERO);?> onclick="document.getElementById('datediv').style.display='none';"/><span class="padding-left"><?php AppUtility::t('Hide')?></span></label><br>
        </div><br class="form"/>
    </div>
        <div id="datediv" style="padding-top:20px; display:<?php echo ($assessmentData['avail']==AppConstant::NUMERIC_ONE)?'block':'none'; ?>">
    <div class="col-sm-12 padding-left-zero margin-top-twenty">
            <div class=col-sm-2 ><?php AppUtility::t('Available After')?></div>
                <div class=col-sm-10>
                    <label class="pull-left non-bold"><input type=radio name="sdatetype" class="margin-left-two pull-left margin-top-two" value="0" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ZERO); ?>/><span class="padding-left"><?php AppUtility::t('Always until end date')?></span></label>
                    <label class="pull-left non-bold padding-right-fifteen padding-left-fourty"><input class="margin-top-five" type=radio name="sdatetype"  value="1" <?php AssessmentUtility::writeHtmlChecked($startDate,"0",AppConstant::NUMERIC_ONE); ?>/></label>
                    <?php
                    echo '<div class = "margin-top-minus-five time-input pull-left width-twenty-four-per">';
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
                    echo '<label class="end pull-left non-bold margin-top-two padding-left-right-twenty"> at </label>';
                     echo '<div class="margin-top-minus-five pull-left width-twenty-two-per available-after-time-picker">';
                        echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $sTime,
                        'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                        ]
                        ]);
                    echo '</div>';?>

                </div>
            <BR class=form>
    </div>
    <div class="col-sm-12 padding-left-zero margin-top-twenty-five">
            <div class=col-sm-2><?php AppUtility::t('Available Until')?></div>
                <div class=col-sm-10>
                    <label class='pull-left non-bold'><input type=radio name="edatetype" class="margin-left-two pull-left margin-top-two" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",0); ?>/><span class="padding-left"></span><?php AppUtility::t('Always after start date')?></span></label>
                    <label class='pull-left non-bold padding-right-fifteen padding-left-thirty-three'><input class="margin-top-five" type=radio name="edatetype"  value="1"  <?php AssessmentUtility::writeHtmlChecked($endDate,"2000000000",1); ?>/></label>
                    <?php
                    echo '<div class = "margin-top-minus-five pull-left time-input width-twenty-four-per">';
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
                    echo '<label class="end pull-left non-bold margin-top-two padding-left-right-twenty"> at </label>';
                    echo '<div class="margin-top-minus-five pull-left width-twenty-two-per available-until-time-picker">';

                    echo TimePicker::widget([
                        'name' => 'etime',
                        'value' => $eTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                </div>
            <BR class=form>
    </div>
    <div class="col-sm-12 padding-left-zero margin-top-fifteen">
            <div class="col-sm-2"><?php AppUtility::t('Keep open as review')?></div>
            <div class="col-sm-10">
                <div class="col-sm-12 padding-left-zero">
                    <label class=''><input class="margin-left-two" type=radio name="doreview" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?>></label><span class="padding-left"><?php AppUtility::t('Never')?></span>
                    <label style="padding-left: 137px"><input type=radio name="doreview" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($assessmentData['reviewdate'],2000000000,AppConstant::NUMERIC_ZERO); ?>></label><span class="padding-left"><?php AppUtility::t('Always after due date')?></span>
                </div>
                <div class="col-sm-12 padding-left-zero margin-top-fifteen">
                    <label class='pull-left select-text-margin'><input type=radio name="doreview" class="margin-left-two pull-left " value="1" <?php if ($assessmentData['reviewdate']>AppConstant::NUMERIC_ZERO && $assessmentData['reviewdate']<2000000000) { echo "checked=1";} ?>></label>
                    <?php
                    echo '<label class="select-text-margin end pull-left non-bold padding-left padding-right-twenty"> Until</label>';
                    echo '<div class = "pull-left time-input width-twenty-five-per">';
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
                    echo '<label class="end pull-left non-bold select-text-margin padding-left-right-twenty"> at </label>';
                    echo '<div class="width-twenty-two-per available-until-time-picker floatleft">';
                    echo TimePicker::widget([
                        'name' => 'rtime',
                        'value' => $reviewTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                </div>
            </div><BR class=form>
    </div>
        </div>

        <span class=form></span>
    <br class=form>

    <div class="padding-left-fifteen clickme row add-item1 assement-options-margin" onclick="coreOptionToggle()">
        <div class="col-sm-1 plus-icon padding-left-ten">
            <img class="assessment-add-item-icon" id="img"  src="<?php echo AppUtility::getAssetURL()?>img/assessAddIcon.png">
        </div>
        <div class="col-sm-2 add-item-text padding-left-zero select-text-margin">
            <p><?php AppUtility::t('Core Options');?></p>
        </div>
    </div>
        <div id="core-options" class="col-sm-12 core-options assement-options-margin">
            <div class="col-sm-12 margin-top-fifteen padding-left-zero">
                <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Require Password')?></div>
                <div class=col-sm-10>
                    <input type="password" class="form-control width-fifty-per display-inline-block" name="assmpassword" id="assmpassword" value="<?php echo $assessmentData['password'];?>" autocomplete="off">
                    <a class="margin-left-twenty" href="#" onclick="apwshowhide(this);return false;"><?php AppUtility::t('Show')?></a>
                </div>
                <br class=form />
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
                <div class='col-sm-2'><?php AppUtility::t('Time Limit (In minutes, 0 for no time limit)')?> </div>
                <div class='col-sm-10'>
                    <input class="form-control width-ten-per display-inline-block" type=text size=4 name=timelimit value="<?php echo abs($timeLimit);?>">
                    <span class="margin-left-twenty">
                        <input type="checkbox" name="timelimitkickout" <?php if ($timeLimit<0) echo 'checked="checked"';?> />
                        <span class="margin-left-five"> <?php AppUtility::t('Kick student out at timelimit') ?>
                        </span>
                    </span>
                </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-fifteen padding-left-zero">
            <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Display method')?></div>
            <div class='col-sm-10'>
                <select name="displaymethod" class="width-fifty-per form-control">
                    <option value="AllAtOnce" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"AllAtOnce",AppConstant::NUMERIC_ZERO) ?>>Full test at once</option>
                    <option value="OneByOne" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"OneByOne",AppConstant::NUMERIC_ZERO) ?>>One question at a time</option>
                    <option value="Seq" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"Seq",AppConstant::NUMERIC_ZERO) ?>>Full test, submit one at time</option>
                    <option value="SkipAround" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"SkipAround",AppConstant::NUMERIC_ZERO) ?>>Skip Around</option>
                    <option value="Embed" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"Embed",AppConstant::NUMERIC_ZERO) ?>>Embedded</option>
                    <option value="VideoCue" <?php AssessmentUtility::writeHtmlSelected($assessmentData['displaymethod'],"VideoCue",AppConstant::NUMERIC_ZERO) ?>>Video Cued</option>
                </select>
            </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2'><?php AppUtility::t('Default points per problem')?></div>
            <div class='col-sm-10'>
                <input class="form-control width-ten-per display-inline-block" type="text" size=4 name="defpoints" value="<?php echo $assessmentData['defpoints'];?>" <?php if ($taken) {echo 'disabled=disabled';}?>>
            </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2'><?php AppUtility::t('Default attempts per problem (0 for unlimited)')?></div>
                    <div class='col-sm-10'>
                        <input class="floatleft form-control width-ten-per display-inline-block" type=text size=4 name=defattempts value="<?php echo $assessmentData['defattempts'];?>" >
                        <span id="showreattdiffver" class="<?php if ($testType!="Practice" && $testType!="Homework") {echo "show";} else {echo "hidden";} ?> floatleft select-text-margin margin-left-twenty">
                            <input type=checkbox class="padding-left" name="reattemptsdiffver" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_EIGHT,AppConstant::NUMERIC_EIGHT); ?>/>
                                <span class="margin-left-five"> Reattempts different versions</span>
                        </span>
                    </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-fifteen padding-left-zero">
            <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Default penalty')?></div>
                    <div class='col-sm-10'>
                        <div class="col-sm-2 floatleft padding-left-zero">
                            <input class="form-control width-sixty-six-per display-inline-block" type=text size=4 name=defpenalty value="<?php echo $assessmentData['defpenalty'];?>" <?php if ($assessmentSessionData) {echo 'disabled=disabled';}?>>
                            <span class="margin-left-five"> %</span>
                        </div>
                        <div class="col-sm-10 floatleft padding-left-ten padding-right-zero margin-left-minus-twenty-five">
                            <select name="skippenalty" <?php if ($taken) {echo 'disabled=disabled';} ?> class="form-control width-sixty-per">
                                <option value="0" <?php if ($skipPenalty==AppConstant::NUMERIC_ZERO) {echo "selected=1";} ?>>per missed attempt</option>
                                <option value="1" <?php if ($skipPenalty==AppConstant::NUMERIC_ONE) {echo "selected=1";} ?>>per missed attempt, after 1</option>
                                <option value="2" <?php if ($skipPenalty==AppConstant::NUMERIC_TWO) {echo "selected=1";} ?>>per missed attempt, after 2</option>
                                <option value="3" <?php if ($skipPenalty==AppConstant::NUMERIC_THREE) {echo "selected=1";} ?>>per missed attempt, after 3</option>
                                <option value="4" <?php if ($skipPenalty==AppConstant::NUMERIC_FOUR) {echo "selected=1";} ?>>per missed attempt, after 4</option>
                                <option value="5" <?php if ($skipPenalty==AppConstant::NUMERIC_FIVE) {echo "selected=1";} ?>>per missed attempt, after 5</option>
                                <option value="6" <?php if ($skipPenalty==AppConstant::NUMERIC_SIX) {echo "selected=1";} ?>>per missed attempt, after 6</option>
                                <option value="10" <?php if ($skipPenalty==AppConstant::NUMERIC_TEN) {echo "selected=1";} ?>>on last possible attempt only</option>
                            </select>
                        </div>
                    </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Feedback method')?></div>
                    <div class='col-md-10 col-sm-10'>
                        <select id="deffeedback" name="deffeedback" onChange="chgfb()" class="form-control">
                            <option value="NoScores" <?php if ($testType=="NoScores") {echo "SELECTED";} ?>>No scores shown (last attempt is scored)</option>
                            <option value="EndScore" <?php if ($testType=="EndScore") {echo "SELECTED";} ?>>Just show final score (total points & average) - only whole test can be reattemped</option>
                            <option value="EachAtEnd" <?php if ($testType=="EachAtEnd") {echo "SELECTED";} ?>>Show score on each question at the end of the test </option>
                            <option value="EndReview" <?php if ($testType=="EndReview") {echo "SELECTED";} ?>>Reshow question with score at the end of the test </option>

                            <option value="AsGo" <?php if ($testType=="AsGo") {echo "SELECTED";} ?>>Show score on each question as it's submitted (does not apply to Full test at once display)</option>
                            <option value="Practice" <?php if ($testType=="Practice") {echo "SELECTED";} ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not saved</option>
                            <option value="Homework" <?php if ($testType=="Homework") {echo "SELECTED";} ?>>Homework: Show score on each question as it's submitted & allow similar question to replace missed question</option>
                        </select>
                    </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Show Answers')?></div>
                    <div class='col-sm-8 col-sm-8'>
                        <span id="showanspracspan" class="<?php if ($testType=="Practice" || $testType=="Homework") {echo "show";} else {echo "hidden";} ?>">
                        <select name="showansprac" class="form-control width-fifty-per">
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
                        <select name="showans" class="form-control width-fifty-per">
                            <option value="V" <?php if ($showAnswer=="V") {echo "SELECTED";} ?>>Never, but allow students to review their own answers</option>
                            <option value="N" <?php if ($showAnswer=="N") {echo "SELECTED";} ?>>Never, and don't allow students to review their own answers</option>
                            <option value="I" <?php if ($showAnswer=="I") {echo "SELECTED";} ?>>Immediately (in gradebook) - don't use if allowing multiple attempts per problem</option>
                            <option value="F" <?php if ($showAnswer=="F") {echo "SELECTED";} ?>>After last attempt (Skip Around only)</option>
                            <option value="A" <?php if ($showAnswer=="A") {echo "SELECTED";} ?>>After due date (in gradebook)</option>
                        </select>
                        </span>
                    </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class="col-sm-2 select-text-margin"><?php AppUtility::t('Use equation helper?')?></div>
                    <div class="col-sm-10">
                        <select name="eqnhelper" class="form-control width-fifty-per">
                            <option value="0" <?php AssessmentUtility::writeHtmlSelected($assessmentData['eqnhelper'],AppConstant::NUMERIC_ZERO) ?>>No</option>
                            <?php
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
                    </div><br class="form" />
            </div>

            <div class="col-sm-12 margin-top-fifteen padding-left-zero">
            <div class=col-sm-2><?php AppUtility::t('Show hints and video/text buttons when available?')?></div>
                    <div class=col-sm-10>
                        <label class=""><input type="checkbox" name="showhints" <?php AssessmentUtility::writeHtmlChecked($assessmentData['showhints'],AppConstant::NUMERIC_ONE); ?>></label>
                    </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-fifteen padding-left-zero">
                <div class=col-sm-2><?php AppUtility::t('Show "ask question" links?')?></div>
                <div class=col-sm-10>
                    <span class="col-sm-12 padding-left-zero">
                        <input type="checkbox" name="msgtoinstr" <?php AssessmentUtility::writeHtmlChecked($assessmentData['msgtoinstr'],AppConstant::NUMERIC_ONE); ?>/>
                        <span class="margin-left-ten non-bold"><?php AppUtility::t('Show "Message instructor about this question" links')?></span>
                    </span>

                    <span class="col-sm-12 padding-left-zero margin-top-fifteen">
                        <input type="checkbox" name="doposttoforum" <?php AssessmentUtility::writeHtmlChecked($assessmentData['posttoforum'],AppConstant::NUMERIC_ZERO,true); ?>/>
                        <span class="margin-left-ten non-bold">Show "Post this question to forum" links, to forum</span>
                    </span>

                    <div class="col-sm-12 padding-left-zero padding-right-zero margin-top-fifteen select-ask-ques-link">
                    <?php AssessmentUtility::writeHtmlSelect("posttoforum",$pageForumSelect['val'],$pageForumSelect['label'],$assessmentData['posttoforum']); ?></span>
                    </div>
                </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2'><?php AppUtility::t('Show answer entry tips?')?></div>
                    <div class=col-sm-10>
                        <select name="showtips" class="form-control width-fifty-per">
                            <option value="0" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_ZERO) ?>>No</option>
                            <option value="1" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_ONE) ?>>Yes, after question</option>
                            <option value="2" <?php AssessmentUtility::writeHtmlSelected($assessmentData['showtips'],AppConstant::NUMERIC_TWO) ?>>Yes, under answerbox</option>
                        </select>
                    </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
                <div class='col-sm-2 '><?php AppUtility::t('Allow use of LatePasses?')?></div>
                <div class='col-sm-10 select-allow-useof-latepasses'>
                    <div class="col-sm-6 padding-left-zero padding-right-zero">
                        <?php AssessmentUtility::writeHtmlSelect("allowlate",$pageAllowLateSelect['val'],$pageAllowLateSelect['label'],$assessmentData['allowlate']%AppConstant::NUMERIC_TEN); ?>
                    </div>
                    <label class="col-sm-6 select-text-margin non-bold padding-left-thirty">
                        <input class="floatleft" type="checkbox" name="latepassafterdue" <?php AssessmentUtility::writeHtmlChecked($line['allowlate']>AppConstant::NUMERIC_TEN,true); ?>>
                        <span class="col-sm-10 margin-top-minus-five">Allow LatePasses after due date, within 1 LatePass period</span>
                    </label>
                </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-ten padding-left-zero">
            <div class=col-sm-2><?php AppUtility::t('Make hard to print?')?></div>
                    <div class=col-sm-10>
                        <input type="radio" value="0" name="noprint" <?php AssessmentUtility::writeHtmlChecked($assessmentData['noprint'],AppConstant::NUMERIC_ZERO); ?>/><span class="padding-left">No</span>
                        <label class="non-bold margin-left-thirty"><input type="radio" value="1" name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'],AppConstant::NUMERIC_ONE); ?>/><span class="padding-left">Yes</span></label>
                    </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-ten padding-left-zero">
            <div class=col-sm-2><?php AppUtility::t('Shuffle item order')?></div>
                    <div class=col-sm-10><input type="checkbox" name="shuffle" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ONE); ?>>
                    </div><BR class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
            <div class='col-sm-2 select-text-margin'><?php AppUtility::t('Gradebook Category')?></div>
                    <div class='col-sm-10 select-gradebook-category'>
                    <?php
                    AssessmentUtility::writeHtmlSelect("gbcat",$pageGradebookCategorySelect['val'],$pageGradebookCategorySelect['label'],$gradebookCategory,"Default",AppConstant::NUMERIC_ZERO);
                    ?>
                    </div><br class=form>
            </div>

            <div class="col-sm-12 margin-top-twenty padding-left-zero">
               <div class=col-sm-2><?php AppUtility::t('Count')?></div>
               <div class="col-sm-10">
                   <span <?php if ($testType=="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="stdcntingb">
                        <div class="col-sm-12 padding-left-zero">
                            <input type=radio name="cntingb" value="1" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_ONE,AppConstant::NUMERIC_ZERO); ?> />
                            <span class="padding-left">Count in Gradebook</span>
                        </div>

                       <div class="col-sm-12 padding-left-zero margin-top-five">
                            <input type=radio name="cntingb" value="0" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?> />
                            <span class="padding-left">Don't count in grade total and hide from students</span>
                       </div>

                        <div class="col-sm-12 padding-left-zero margin-top-five">
                            <input type=radio name="cntingb" value="3" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_THREE,AppConstant::NUMERIC_ZERO); ?> />
                            <span class="padding-left">Don't count in grade total</span><br/>
                        </div>

                        <div class="col-sm-12 padding-left-zero margin-top-five">
                            <input type=radio name="cntingb" value="2" <?php AssessmentUtility::writeHtmlChecked($countInGradebook,AppConstant::NUMERIC_TWO,AppConstant::NUMERIC_ZERO); ?> />
                            <span class="padding-left">Count as Extra Credit</span>
                        </div>
                    </span>
                <span <?php if ($testType!="Practice") {echo "class=hidden";} else {echo "class=formright";} ?> id="praccntingb">
                    <input type=radio name="pcntingb" value="0" <?php AssessmentUtility::writeHtmlChecked($pointCountInGradebook,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_ZERO); ?> /><span class="padding-left">Don't count in grade total and hide from students</span><br/>
                    <input type=radio name="pcntingb" value="3" <?php AssessmentUtility::writeHtmlChecked($pointCountInGradebook,AppConstant::NUMERIC_THREE,AppConstant::NUMERIC_ZERO); ?> /><span class="padding-left">Don't count in grade total</span><br/>
                </div></span>
            </div>

            <?php if (!isset(   $CFG['GEN']['allowinstraddtutors']) || $CFG['GEN']['allowinstraddtutors']==true) { ?>
                <div class="col-sm-12 margin-top-ten padding-left-zero">
                    <div class="col-sm-2 select-text-margin">Tutor Access</div>
                    <div class="col-sm-10 select-tutor-access">
                    <?php AssessmentUtility::writeHtmlSelect("tutoredit",$pageTutorSelect['val'],$pageTutorSelect['label'],$assessmentData['tutoredit']); ?>
                    </div><br class="form" />
                </div>
            <?php } ?>

            <div class="col-sm-12 margin-top-twenty margin-bottom-twenty padding-left-zero">
                <div class="col-sm-2 select-text-margin"><?php AppUtility::t('Calendar icon')?></div>
                <div class="col-sm-10">
                    <span>
                        <span>Active</span>
                        <input class="margin-left-five form-control width-ten-per display-inline-block" name="caltagact" type=text size=4 value="<?php echo $assessmentData['caltag'];?>"/>
                    </span>
                    <span class="margin-left-twenty">
                        <span>Review</span>
                        <input class="margin-left-five form-control width-ten-per display-inline-block" name="caltagrev" type=text size=4 value="<?php echo $assessmentData['calrtag'];?>"/>
                    </span>
                </div><br class="form" />
            </div>

        </div>

    <div class="padding-left-fifteen col-sm-12 clickmegreen row add-item1 assement-options-margin" style="margin-top: 10px" onclick="advanceOptionToggle()">
        <div class="col-sm-1 plus-icon padding-left-ten">
            <img class="assessment-add-item-icon" id="img1"  src="<?php echo AppUtility::getAssetURL()?>img/assessAddIcon.png">
        </div>
        <div class="col-sm-3 add-item-text padding-left-zero">
            <p><?php AppUtility::t('Advance Options');?></p>
        </div>
    </div>
        <div id="advance-options" class="advance-options col-sm-12  assement-options-margin" style="background-color: #fafafa">

            <div class="col-sm-12 padding-left-zero">
                <div class='col-sm-2'><?php AppUtility::t('Minimum score to receive credit')?></div>
                <div class='col-sm-10'>
                    <input class="form-control display-inline-block width-ten-per" type=text size=4 name=minscore value="<?php echo $assessmentData['minscore'];?>">
                    <span class="margin-left-twenty">
                        <input type="radio" name="minscoretype" value="0" <?php AssessmentUtility::writeHtmlChecked($minScoreType,AppConstant::NUMERIC_ZERO);?>>
                        <span class="margin-left-five">Points</span>
                    </span>
                    <label class="non-bold margin-left-twenty"><input type="radio" name="minscoretype" value="1" <?php AssessmentUtility::writeHtmlChecked($minScoreType,AppConstant::NUMERIC_ONE);?>>
                        <span class="margin-left-five">Percent</span></label>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class='col-sm-2'><?php AppUtility::t('Show based on another assessment')?></div>
                <div class='col-md-10 col-sm-10'>
                       <div class="floatleft">
                        <span><?php AppUtility::t('Show only after a score of')?></span>
                        <input type=text class="margin-left-fifteen form-control width-fifty display-inline-block" size=4 name=reqscore value="<?php echo $assessmentData['reqscore'];?>">
                        </div>
                        <div class="padding-left-thirty floatleft col-md-8 col-sm-8 assessment-pt-obtain-on">
                          <span class="margin-left-fifteen select-text-margin">
                            <?php AppUtility::t('points is obtained on')?>
                          </span>
                            <?php AssessmentUtility::writeHtmlSelect ("reqscoreaid",$pageCopyFromSelect['val'],$pageCopyFromSelect['label'],$assessmentData['reqscoreaid'],"Dont Use",AppConstant::NUMERIC_ZERO,null); ?>
                        </div>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class="col-sm-2 select-text-margin"><?php AppUtility::t('Default Feedback Text')?></div>
                <div class="col-sm-10">
                   <span class="">
                       <span> Use?</span>
                       <input class="margin-left-ten" type="checkbox" name="usedeffb" <?php AssessmentUtility::writeHtmlChecked($useDefFeedback,true); ?>>
                   </span>
                   <span class="margin-left-twenty">
                       <span>Text</span>
                       <input class="form-control width-fifty-per margin-left-ten display-inline-block width-fifty-per" type="text" size="60" name="deffb" value="<?php echo str_replace('"','&quot;',$defFeedback);?>" />
                   </span>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty margin-top-twenty">
                <div class='col-sm-2'>All items same random seed</div>
                <div class='col-sm-10'>
                    <input type="checkbox" name="sameseed" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_TWO,AppConstant::NUMERIC_TWO); ?>>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class='col-sm-2'>All students same version of questions</div>
                <div class='col-sm-10'>
                    <input type="checkbox" name="samever" <?php AssessmentUtility::writeHtmlChecked($assessmentData['shuffle']&AppConstant::NUMERIC_FOUR,AppConstant::NUMERIC_FOUR); ?>>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class='col-sm-2'>Penalty for questions done while in exception/LatePass</div>
                <div class='col-sm-10'>
                    <input class="form-control width-ten-per display-inline-block" type=text size=4 name="exceptionpenalty" value="<?php echo $assessmentData['exceptionpenalty'];?>"><span class="margin-left-five">%</span>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class='col-sm-2'>Group assessment</div>
                <div class='col-sm-10'>
                   <span class="col-sm-12 padding-left-zero">
                       <input type="radio" name="isgroup" value="0" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_ZERO); ?> />
                       <span class="padding-left">Not a group assessment</span>
                   </span>
                    <span class="col-sm-12 padding-left-zero margin-top-ten">
                        <input type="radio" name="isgroup" value="1" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_ONE); ?> />
                        <span class="padding-left">Students can add members with login passwords</span>
                    </span>
                    <span class="col-sm-12 padding-left-zero margin-top-ten">
                        <input type="radio" name="isgroup" value="2" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_TWO); ?> />
                        <span class="padding-left">Students can add members without passwords</span>
                    </span>

                    <span class="col-sm-12 padding-left-zero margin-top-ten">
                        <input class="floatleft" type="radio" name="isgroup" value="3" <?php AssessmentUtility::writeHtmlChecked($assessmentData['isgroup'],AppConstant::NUMERIC_THREE); ?> />
                        <span class="col-sm-10 padding-left-twenty">Students cannot add members, and can't start the assessment until you add them to a group</span>
                    </span>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class='col-sm-2'>Max group members (if group assessment)</div>
                <div class='col-sm-10'>
                    <input class="form-control display-inline-block width-ten-per" type="text" name="groupmax" value="<?php echo $assessmentData['groupmax'];?>" />
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class="col-sm-2 select-text-margin">Use group set:<?php
                    if ($taken) {
                        if ($assessmentData['isgroup']== AppConstant::NUMERIC_ZERO) {
                            echo '<br/>Only empty group sets can be used after the assessment has started';
                        } else {
                            echo '<br/>Cannot change group set after the assessment has started';
                        }
                    }?>
                </div>
                <div class="col-sm-10 select-use-group-set">
                    <?php AssessmentUtility::writeHtmlSelect("groupsetid",$pageGroupSets['val'],$pageGroupSets['label'],$assessmentData['groupsetid'],"Not group forum",0); ?>
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
                <div class="col-sm-2 select-text-margin">Default Outcome</div>
                <div class="col-sm-10"><select name="defoutcome" class="form-control width-fifty-per">
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
                </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty">
            <div class='col-sm-2'>Show question categories:</div>
            <div class='col-sm-10'>
                <span class="col-sm-12 padding-left-zero">
                    <input name="showqcat" type="radio" value="0" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"0"); ?>>
                    <span class="padding-left">No</span>
                </span>
                <span class="col-sm-12 padding-left-zero margin-top-ten">
                    <input name="showqcat" type="radio" value="1" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"1"); ?>>
                    <span class="padding-left">In Points Possible bar</span>
                </span>
                <span class="col-sm-12 padding-left-zero margin-top-ten">
                    <input name="showqcat" type="radio" value="2" <?php AssessmentUtility::writeHtmlChecked($showQuestionCategory,"2"); ?>>
                    <span class="padding-left">In navigation bar (Skip-Around only)</span>
                </span>
            </div>
            </div>

            <div class="col-sm-12 padding-left-zero margin-top-twenty margin-bottom-twenty-five">
                <div class='col-sm-2'>Display for tutorial-style questions</div>
                <div class='col-sm-10'>
                    <input type="checkbox" name="istutorial" <?php AssessmentUtility::writeHtmlChecked($assessmentData['istutorial'],AppConstant::NUMERIC_ONE); ?>>
                </div>
            </div>
            </div>
        </div>
    <div class="col-sm-8 padding-left-thirty padding-top-thirty padding-bottom-thirty">
            <?php if (isset($assessmentData['id'])) {
                echo '<div class="floatleft padding-right-twenty">
                <a class="btn btn-primary" href="'.AppUtility::getURLFromHome('question','question/add-questions?aid='.$assessmentData['id'].'&amp;cid='.$course->id).'" onclick="return confirm(\''.AppUtility::t('This will discard any changes you have made on this page',false).'\');">
                    <i class="fa fa-share header-right-btn"></i>'
                    .AppUtility::t('Add/Remove Questions',false).
                    '</a></div>';
            } ?>

            <div class="header-btn floatleft">
                <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $saveTitle ?></button>
            </div>
    </div>
</div>
    </form>