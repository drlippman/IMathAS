<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AssessmentUtility;

$this->title = 'Add Forum';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
$hidetitle = false;
?>

<h3><b><?php echo $pageTitle; ?></b>
<!--    <img src="--><?php //echo AppUtility::getAssetURL() ?><!--img/help.gif" alt="Help"-->
<!--                                         onClick="window.open('--><?php //echo AppUtility::getHomeURL() ?><!--docs/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>-->
</h3>
<?php if (isset($modifyForumId)){ ?>
<form enctype="multipart/form-data" method=post
      action="add-forum?cid=<?php echo $course->id ?>&modifyFid=<?php echo $modifyForumId; ?>">
    <?php }else{ ?>
    <form enctype="multipart/form-data" method=post action="add-forum?cid=<?php echo $course->id ?>">
        <?php } ?>

        <span class=form>Name: </span>
	<span class=formright>
        <!-- Title-->
        <?php $title = 'Enter Forum Name here';
        if ($forumData) {
            $title = $forumData['name'];
        }
        ?>
        <input type=text size=0 name=title value="<?php echo $title; ?>"><br/>
	</span>
        <!--    Text Editor-->
        <BR class=form>
        &nbsp;&nbsp; Description:<BR>
        <?php $text = "<p>Enter forum description here</p>";
        if ($forumData) {
        $text = $forumData['description'];
        } ?>
        <div>
            <?php echo "<div class='left col-md-11'><div class= 'editor'>
            <textarea id='forum-description' name='forum-description' style='width: 100%;' rows='20' cols='200'>
            $text
            </textarea></div> "?>
        </div>

        <!--Show-->
        <div>
            <span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'],AppConstant::NUMERIC_ZERO);?>
                   onclick="document.getElementById('datediv').style.display='none'; "/>Hide<br/>
			<input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'],AppConstant::NUMERIC_ONE);?>
                   onclick="document.getElementById('datediv').style.display='block';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php AssessmentUtility::writeHtmlChecked($forumData['avail'], AppConstant::NUMERIC_TWO); ?>
                   onclick="document.getElementById('datediv').style.display='none'; "/>Show Always<br/>
		</span><br class="form"/>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">
<?php $startTime = $eTime; ?>
                <span class=form>Available After:</span>
		        <span class=formright>
			        <input type=radio name="available-after"
                           value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValue['startDate'], '0', AppConstant::NUMERIC_ZERO) ?>/>
			        Always until end date<br/>
			        <input type=radio name="available-after" class="pull-left"
                           value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValue['startDate'] , '1', AppConstant::NUMERIC_ONE) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m-d-y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $eTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		        </span><BR class=form>

                <span class=form>Available Until:</span>
		        <span class=formright>
			        <input type=radio name="available-until"
                           value="2000000000" <?php AssessmentUtility::writeHtmlChecked($defaultValue['endDate'], '2000000000', 0) ?>/>
                        Always after start date<br/>
                        <input type=radio name="available-until" class="pull-left"
                               value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValue['endDate'], '2000000000', 1) ?>/>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m-d-y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
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
                    echo '</div>'; ?>
		    </span><BR class=form>
            </div>
                <span class=form>Group forum?</span>
                    <span class=formright>
                         <?php
                        AssessmentUtility::writeHtmlSelect("groupsetid",$groupNameId,$groupNameLabel,$forumData['groupsetid'],"Not group forum",0);
                        if ($forumData['groupsetid'] > 0 && $defaultValue['hasGroupThreads']) {
                            echo '<br/>WARNING: <span style="font-size: 80%">Group threads exist.  Changing the group set will set all existing threads to be non-group-specific threads</span>';
                        }        ?>

		</span><br class=form>


                <span class=form>Allow anonymous posts:</span>
                    <span class=formright>
            <input type="checkbox" name="allow-anonymous-posts" value="1"<?php if ($defaultValue['allowanon']) { echo "checked=1";}?> >
	            </span><br class=form>

                <span class=form>Allow students to modify posts:</span>
                    <span class=formright>
            <input type="checkbox" name="allow-students-to-modify-posts" value="2"<?php if ($defaultValue['allowmod']) { echo "checked=1";}?>>
	            </span><br class=form>

                <span class=form>Allow students to delete own posts (if no replies):</span>
                    <span class=formright>
            <input type="checkbox" name="allow-students-to-delete-own-posts" value="4"<?php if ($defaultValue['allowdel']) { echo "checked=1";}?>>
	            </span><br class=form>

                <span class=form>Turn on "liking" posts:</span>
                    <span class=formright>
            <input type="checkbox" name="like-post" value="8"<?php if ($defaultValue['allowlikes']) { echo "checked=1";}?>>
	            </span><br class=form>

                <span class=form>Viewing before posting:</span>
                    <span class=formright>
            <input type="checkbox" name="viewing-before-posting" value="16"<?php if ($defaultValue['viewAfterPost']) { echo "checked=1";}?>>
                        Prevent students from viewing posts until they have created a thread.
You will likely also want to disable modifying posts
	            </span><br class=form>

                <span class=form>Get email notify of new posts:</span>
                    <span class=formright>
            <input type="checkbox" name="Get-email-notify-of-new-posts" value="1"<?php if ($defaultValue['hasSubScrip']) { echo "checked=1";}?>>
	            </span><br class=form>

                <span class=form>Default display:</span>
                    <span class=formright>

            <select name="default-display" class="form-control">
                <option value="0" <?php if ($defaultValue['defDisplay']==0 || $defaultValue['defDisplay']==1) {echo "selected=1";}?>>Expanded</option>
                <option value="2" <?php if ($defaultValue['defDisplay']==2) {echo "selected=1";}?>>Condensed</option>
            </select>
		</span><br class=form>


                <span class=form>Sort threads by:</span>
		<span class=formright>
			<input type=radio name="sort-thread" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValue['sortBy'],0);?>>Thread start date<br/>
 			<input type=radio name="sort-thread" value="1"<?php AssessmentUtility::writeHtmlChecked($defaultValue['sortBy'],1);?>/> Most recent reply date<br/>
		</span><br class="form"/>

                <span class=form>Students can create new threads:</span>
		        <span class="formright">

			<input type=radio name="new-thread"
                   value="0" <?php if ($defaultValue['postBy']==2000000000) { echo "checked=1";}?>>Alway <br/>
			<input type=radio name="new-thread"
                   value="2000000000" <?php if ($defaultValue['postBy']==0) { echo "checked=1";}?>>Never<br/>
			<input type=radio name="new-thread" class="pull-left "
                   value="1" <?php if ($defaultValue['postBy']<2000000000 && $defaultValue['postBy']>0) { echo "checked=1";}?> >
                    <?php
                    echo '<label class="end pull-left">Before:</label>';
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'newThreadDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left"> at </label>';
                    echo '<div class=" col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'newThreadTime',
                        'value' =>  $eTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>

		</span><BR class=form>

                <span class=form>Students can reply to posts:</span>
		        <span class="formright">

                    <input type=radio name="reply-to-posts"
                   value="0" <?php if ($defaultValue['replyBy']==2000000000) { echo "checked=1";}?>>Alway <br/>
			<input type=radio name="reply-to-posts"
                   value="2000000000" <?php if ($defaultValue['replyBy']==0) { echo "checked=1";}?>>Never<br/>
			<input type=radio name="reply-to-posts" class="pull-left "
                   value="1" <?php if ($defaultValue['replyBy']<2000000000 && $defaultValue['replyBy']>0) { echo "checked=1";}?> >
                    <?php
                    echo '<label class="end pull-left">Before:</label>';
                    echo '<div class = "pull-left col-lg-4 time-input">';
                    echo DatePicker::widget([
                        'name' => 'replayPostDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left"> at </label>';
                    echo '<div class=" col-lg-6">';
                    echo TimePicker::widget([
                        'name' => 'replayPostTime',
                        'value' => $eTime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>

		</span><BR class=form>


                <span class=form>Calendar icon:</span>
                    <span class=formright>
            New Threads: <input type="text" name="calendar-icon-text1" value="<?php echo $defaultValue['postTag'];?>" size="2"> ,
            Replies: <input type="text" name="calendar-icon-text2" value="<?php echo $defaultValue['replyTag'];?>" size="2">
	            </span><br class=form>


                <span class=form>Count in gradebook?</span>
		<span class=formright>
            <input type=radio name="count-in-gradebook" value="0" <?php if ($defaultValue['cntInGb']==0) { echo 'checked=1';}?> onclick="toggleGBdetail(false)"/>No<br/>
			<input type=radio name="count-in-gradebook" value="1" <?php if ($defaultValue['cntInGb']==1) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes<br/>
			<input type=radio name="count-in-gradebook" value="4" <?php if ($defaultValue['cntInGb']==4 && $defaultValue['points'] > 0) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes, but hide from students for now<br/>
			<input type=radio name="count-in-gradebook" value="2" <?php if ($defaultValue['cntInGb']==2) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes, as extra credit<br/>

		</span><br class="form"/>
                <div id="gbdetail" <?php if ($defaultValue['cntingb']==0 && $defaultValue['points']==0) { echo 'style="display:none;"';}?>>

                    <span class=form>Points:</span>
                    <span class=formright>
            <input type="text" name="points" value="<?php echo $defaultValue['points'];?>" size="3"> points
	            </span><br class=form>

                    <span class=form>Gradebook Category:</span>
                    <span class=formright>
 <?php AssessmentUtility::writeHtmlSelect("gradebook-category",$gbcatsId,$gbcatsLabel,$defaultValue['gbCat'],"Default",0); ?>
            </span><br class=form>
                     <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
                    $page_tutorSelect['val'] = array(2,0,1); ?>
                    <span class=form>Tutor Access:</span>
                    <span class=formright>
                <?php AssessmentUtility::writeHtmlSelect("tutor-edit",$page_tutorSelect['val'],$page_tutorSelect['label'],$forumData['tutoredit']); ?>
</span><br class=form>
                    <span class="form">Use Scoring Rubric</span><span class=formright>
                   <?php AssessmentUtility::writeHtmlSelect('rubric',$rubricsId,$rubricsLabel,$forumData['rubric']); ?>
                            <a href="<?php echo AppUtility::getURLFromHome('site','work-in-progress') ?>">Add new
                            rubric</a> | <a
                            href="<?php echo AppUtility::getURLFromHome('site','work-in-progress') ?>">Edit
                            rubrics</a>

                      </span>
<br class=form>
                    <?php if (count($pageOutcomesList) > 0) { ?>
    <span class="form">Associate Outcomes:</span></span class="formright">
    <?php
                        $gradeoutcomes = array();
                        AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
    <br class="form"/>
    <?php } ?>
<!--                 --><?php //  $outcomes = explode(',',$forumData['outcomes']); ?>
<!--                    <span class="form">Associate Outcomes:</span>-->
<!--			<span class="formright">-->

<!--                --><?php //AssessmentUtility::writeHtmlMultiSelect('outcomes',$pageOutcomesList,$pageOutcomes,$outcomes,'Select an outcome...'); ?>
<!--                <select name="associate-outcomes" class="form-control">-->
<!--                    --><?php
//                    $inGroup = false;
//                    $isSelected = false;
//                    foreach ($pageOutcomesList as $outcome) {
//                        if ($outcome[1]==AppConstant::NUMERIC_ONE) {//is, group
//                            if ($inGroup) { echo '</optgroup>';}
//                            echo '<optgroup label="'.htmlentities($outcome[0]).'">';
//                            $inGroup = true;
//                        } else {
//                            echo '<option value="'.$outcome[0].'" ';
//                            if ($assessmentData['defoutcome'] == $outcome[0]) { echo 'selected="selected"'; $isSelected = true;}
//                            echo '>'.$pageOutcomes[$outcome[0]].'</option>';
//                        }
//                    }
//                    if ($inGroup) { echo '</optgroup>';}
//                    ?>
<!--                </select>-->
<!--			</span>-->
<!--                        <input type="button" value="Add Another">-->

<br class=form>
</div>

                        <span class=form>Forum Type:</span>
		<span class=formright>
            	<input type=radio name="forum-type" value="0" <?php if ($forumData['forumtype']==0) { echo 'checked=1';}?>/>Regular forum<br/>
			<input type=radio name="forum-type" value="1" <?php if ($forumData['forumtype']==1) { echo 'checked=1';}?>/>File sharing forum
		</span><br class="form"/>
                        <span class=form>Categorize posts?</span>
                    <span class=formright>
                <input type=checkbox name="categorize-posts" value="1" <?php if ($forumData['taglist'] != '') {
                    echo "checked=1";
                } ?>
                       onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';"/>
			  <span id="tagholder" style="display:<?php echo ($forumData['taglist'] == '') ? "none" : "inline"; ?>">
			  Enter in format CategoryDescription:category,category,category<br/>
			  <input type="text" size="50" height="20" name="taglist"><?php echo $forumData['taglist']; ?>
			  </span><br class=form><br class=form>
            <div>
                <button type=submit name="submitbtn" class="btn btn-primary"
                        value="Create Forum"><?php echo $saveTitle; ?></button>
            </div>
        </div>
        </div>
    </form>